<?php

namespace Tests\Feature\Admin;

use App\Models\AppSetting;
use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaptopImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "name,brand,model,serial_number,status,owner_student_number,notes,spec_cpu,spec_ram,spec_storage,spec_os\n";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        AppSetting::query()->create(['site_name' => 'Test Site']);
    }

    private function makeLaptop(array $attributes = []): Laptop
    {
        static $n = 0;
        $n++;

        $sn = "SN-T{$n}";

        return Laptop::create(array_merge([
            'serial_number' => $sn,
            'name' => "Laptop {$n}",
            'status' => 'available',
            'qr_code' => $sn,
        ], $attributes));
    }

    private function upload(User $admin, string $rows)
    {
        $file = UploadedFile::fake()->createWithContent('laptop.csv', self::HEADER . $rows);

        return $this->actingAs($admin)->post(route('admin.laptops.import'), ['file' => $file]);
    }

    public function test_dry_run_does_not_change_database(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeLaptop(['serial_number' => 'SN-1', 'name' => 'Lama']);

        $this->upload($admin, "Lama Baru,,,SN-1,,,,,,,\nLaptop Baru,Dell,,SN-2,,,,,,,\n")
            ->assertRedirect(route('admin.laptops.import.preview'));

        $this->actingAs($admin)->get(route('admin.laptops.import.preview'))
            ->assertOk()
            ->assertSee('Laptop Baru')
            ->assertSee('Lama Baru');

        $this->assertDatabaseMissing('laptops', ['serial_number' => 'SN-2']);
        $this->assertDatabaseHas('laptops', ['serial_number' => 'SN-1', 'name' => 'Lama']);
    }

    public function test_commit_creates_and_updates_laptops_without_wiping_blank_cells(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['student_number' => '1001']);
        $existing = $this->makeLaptop([
            'serial_number' => 'SN-1',
            'name' => 'Lama',
            'brand' => 'Acer',
            'specifications' => ['cpu' => 'i3', 'ram' => '4GB'],
        ]);

        // Baris 1 dicocokkan lewat serial_number, hanya mengubah RAM, pemilik, dan status.
        $this->upload($admin, ",,,SN-1,maintenance,1001,,,8GB,,\nLaptop Baru,Dell,X1,SN-2,,1001,Catatan,i7,16GB,512GB,Win11\n");

        $this->actingAs($admin)->post(route('admin.laptops.import.commit'))
            ->assertRedirect(route('admin.laptops.index'))
            ->assertSessionHas('status');

        $existing->refresh();
        $this->assertSame('Lama', $existing->name);
        $this->assertSame('Acer', $existing->brand);
        $this->assertSame('maintenance', $existing->status);
        $this->assertSame($student->id, $existing->owner_id);
        $this->assertSame(['cpu' => 'i3', 'ram' => '8GB'], $existing->specifications);

        $new = Laptop::where('serial_number', 'SN-2')->first();
        $this->assertNotNull($new);
        $this->assertSame($student->id, $new->owner_id);
        $this->assertSame('available', $new->status);
        $this->assertSame('i7', $new->specifications['cpu']);
        $this->assertSame('SN-2', $new->qr_code);
    }

    public function test_new_laptop_without_serial_number_gets_generated_serial_number(): void
    {
        $admin = User::factory()->admin()->create();

        $this->upload($admin, "Tanpa Serial,,,,,,,,,,\n");
        $this->actingAs($admin)->post(route('admin.laptops.import.commit'));

        $laptop = Laptop::where('name', 'Tanpa Serial')->first();
        $this->assertNotNull($laptop);
        $this->assertNotEmpty($laptop->serial_number);
        $this->assertSame($laptop->serial_number, $laptop->qr_code);
    }

    public function test_errors_are_reported(): void
    {
        $admin = User::factory()->admin()->create();
        $a = $this->makeLaptop(['serial_number' => 'SN-A']);
        $b = $this->makeLaptop(['serial_number' => 'SN-B']);
        $borrowed = $this->makeLaptop(['serial_number' => 'SN-C', 'status' => 'borrowed']);

        $student = User::factory()->student()->create();
        BorrowTransaction::unguarded(fn () => BorrowTransaction::create([
            'transaction_code' => 'TRX-1',
            'laptop_id' => $borrowed->id,
            'student_id' => $student->id,
            'staff_id' => $admin->id,
            'usage_purpose' => 'Belajar',
            'status' => 'borrowed',
            'borrowed_at' => now(),
            'due_at' => now()->addDay(),
        ]));

        $rows = implode("\n", [
            'Baru,,,SN-X,wrong,,,,,,',      // status tidak valid
            'Baru,,,SN-Y,,9999,,,,,',       // NIS pemilik tidak ada
            ',,,SN-Z,,,,,,,',               // laptop baru tanpa nama
            ',,,SN-C,available,,,,,,',      // sedang dipinjam
            'Baru W,,,SN-W,,,,,,,',         // valid
            'Baru W2,,,SN-W,,,,,,,',        // duplikat serial di file
        ]) . "\n";

        $this->upload($admin, $rows);

        $plan = $this->actingAs($admin)->get(route('admin.laptops.import.preview'))->assertOk()->viewData('plan');

        $this->assertSame(6, $plan['summary']['total']);
        $this->assertSame(5, $plan['summary']['error']);
        $this->assertSame(1, $plan['summary']['new']);
    }

    public function test_commit_can_abort_when_there_are_errors(): void
    {
        $admin = User::factory()->admin()->create();

        $this->upload($admin, "Baik,,,SN-OK,,,,,,,\nBuruk,,,SN-BAD,wrong,,,,,,");

        $this->actingAs($admin)->post(route('admin.laptops.import.commit'), ['abort_on_error' => '1'])
            ->assertRedirect(route('admin.laptops.import.preview'));

        $this->assertDatabaseMissing('laptops', ['serial_number' => 'SN-OK']);
    }

    public function test_cancel_discards_pending_import(): void
    {
        $admin = User::factory()->admin()->create();

        $this->upload($admin, "Baik,,,SN-OK,,,,,,,\n");
        $this->assertNotEmpty(Storage::disk('local')->files('import-tmp'));

        $this->actingAs($admin)->post(route('admin.laptops.import.cancel'))
            ->assertRedirect(route('admin.laptops.index'));

        $this->assertEmpty(Storage::disk('local')->files('import-tmp'));
        $this->assertDatabaseMissing('laptops', ['serial_number' => 'SN-OK']);
    }

    public function test_template_and_export_download(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeLaptop(['serial_number' => 'SN-E', 'name' => 'Export Me']);

        $this->actingAs($admin)->get(route('admin.laptops.template'))->assertOk();

        $response = $this->actingAs($admin)->get(route('admin.laptops.export'));
        $response->assertOk();
        $this->assertStringContainsString('Export Me', $response->streamedContent());
    }

    public function test_non_admin_cannot_import(): void
    {
        $student = User::factory()->student()->create();
        $file = UploadedFile::fake()->createWithContent('laptop.csv', self::HEADER);

        $this->actingAs($student)->post(route('admin.laptops.import'), ['file' => $file])->assertForbidden();
    }
}
