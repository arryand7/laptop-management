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

    private const HEADER = "code,name,brand,model,serial_number,status,owner_student_number,notes,spec_cpu,spec_ram,spec_storage,spec_os,qr_code\n";

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

        return Laptop::create(array_merge([
            'code' => "LP-T{$n}",
            'name' => "Laptop {$n}",
            'status' => 'available',
            'qr_code' => "QR-T{$n}",
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
        $this->makeLaptop(['code' => 'LP-1', 'name' => 'Lama']);

        $this->upload($admin, "LP-1,Lama Baru,,,,,,,,,,,\nLP-2,Laptop Baru,Dell,,,,,,,,,,\n")
            ->assertRedirect(route('admin.laptops.import.preview'));

        $this->actingAs($admin)->get(route('admin.laptops.import.preview'))
            ->assertOk()
            ->assertSee('Laptop Baru')
            ->assertSee('Lama Baru');

        $this->assertDatabaseMissing('laptops', ['code' => 'LP-2']);
        $this->assertDatabaseHas('laptops', ['code' => 'LP-1', 'name' => 'Lama']);
    }

    public function test_commit_creates_and_updates_laptops_without_wiping_blank_cells(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['student_number' => '1001']);
        $existing = $this->makeLaptop([
            'code' => 'LP-1',
            'name' => 'Lama',
            'brand' => 'Acer',
            'serial_number' => 'SN-1',
            'specifications' => ['cpu' => 'i3', 'ram' => '4GB'],
        ]);

        // Baris 1 dicocokkan lewat serial (kode kosong), hanya mengubah RAM, pemilik, dan status.
        $this->upload($admin, ",,,,SN-1,maintenance,1001,,,8GB,,,\nLP-2,Laptop Baru,Dell,X1,SN-2,,1001,Catatan,i7,16GB,512GB,Win11,\n");

        $this->actingAs($admin)->post(route('admin.laptops.import.commit'))
            ->assertRedirect(route('admin.laptops.index'))
            ->assertSessionHas('status');

        $existing->refresh();
        $this->assertSame('Lama', $existing->name);
        $this->assertSame('Acer', $existing->brand);
        $this->assertSame('maintenance', $existing->status);
        $this->assertSame($student->id, $existing->owner_id);
        $this->assertSame(['cpu' => 'i3', 'ram' => '8GB'], $existing->specifications);

        $new = Laptop::where('code', 'LP-2')->first();
        $this->assertNotNull($new);
        $this->assertSame($student->id, $new->owner_id);
        $this->assertSame('available', $new->status);
        $this->assertSame('i7', $new->specifications['cpu']);
        $this->assertSame('1001', $new->qr_code);
    }

    public function test_new_laptop_without_code_gets_generated_code(): void
    {
        $admin = User::factory()->admin()->create();

        $this->upload($admin, ",Tanpa Kode,,,,,,,,,,,\n");
        $this->actingAs($admin)->post(route('admin.laptops.import.commit'));

        $laptop = Laptop::where('name', 'Tanpa Kode')->first();
        $this->assertNotNull($laptop);
        $this->assertStringStartsWith('LP-', $laptop->code);
        $this->assertNotEmpty($laptop->qr_code);
    }

    public function test_errors_are_reported(): void
    {
        $admin = User::factory()->admin()->create();
        $a = $this->makeLaptop(['code' => 'LP-A', 'serial_number' => 'SN-A']);
        $b = $this->makeLaptop(['code' => 'LP-B', 'serial_number' => 'SN-B']);
        $borrowed = $this->makeLaptop(['code' => 'LP-C', 'status' => 'borrowed']);

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
            'LP-A,,,,SN-B,,,,,,,,',          // konflik kode vs serial
            'LP-X,Baru,,,,wrong,,,,,,,',      // status tidak valid
            'LP-Y,Baru,,,,,9999,,,,,,',       // NIS pemilik tidak ada
            'LP-Z,,,,,,,,,,,,',               // laptop baru tanpa nama
            'LP-C,,,,,available,,,,,,,',      // sedang dipinjam
            'LP-W,Baru W,,,,,,,,,,,',         // valid
            'LP-W,Baru W2,,,,,,,,,,,',        // duplikat kode di file
        ]) . "\n";

        $this->upload($admin, $rows);

        $plan = $this->actingAs($admin)->get(route('admin.laptops.import.preview'))->assertOk()->viewData('plan');

        $this->assertSame(7, $plan['summary']['total']);
        $this->assertSame(6, $plan['summary']['error']);
        $this->assertSame(1, $plan['summary']['new']);
    }

    public function test_commit_can_abort_when_there_are_errors(): void
    {
        $admin = User::factory()->admin()->create();

        $this->upload($admin, "LP-OK,Baik,,,,,,,,,,,\nLP-BAD,Buruk,,,,wrong,,,,,,,\n");

        $this->actingAs($admin)->post(route('admin.laptops.import.commit'), ['abort_on_error' => '1'])
            ->assertRedirect(route('admin.laptops.import.preview'));

        $this->assertDatabaseMissing('laptops', ['code' => 'LP-OK']);
    }

    public function test_cancel_discards_pending_import(): void
    {
        $admin = User::factory()->admin()->create();

        $this->upload($admin, "LP-OK,Baik,,,,,,,,,,,\n");
        $this->assertNotEmpty(Storage::disk('local')->files('import-tmp'));

        $this->actingAs($admin)->post(route('admin.laptops.import.cancel'))
            ->assertRedirect(route('admin.laptops.index'));

        $this->assertEmpty(Storage::disk('local')->files('import-tmp'));
        $this->assertDatabaseMissing('laptops', ['code' => 'LP-OK']);
    }

    public function test_template_and_export_download(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeLaptop(['code' => 'LP-E', 'name' => 'Export Me']);

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
