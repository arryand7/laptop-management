<?php

namespace Tests\Feature\Admin;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "name,email,student_number,nisn,classroom,gender,phone,is_active,card_code,password\n";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        AppSetting::query()->create(['site_name' => 'Test Site']);
    }

    private function upload(User $admin, string $rows)
    {
        $file = UploadedFile::fake()->createWithContent('siswa.csv', self::HEADER . $rows);

        return $this->actingAs($admin)->post(route('admin.students.import'), ['file' => $file]);
    }

    public function test_dry_run_does_not_change_database(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->student()->create(['student_number' => '1001', 'email' => 'lama@example.com', 'name' => 'Lama']);

        $this->upload($admin, "Lama Baru,lama@example.com,1001,0011,X A,L,,,,\nSiswa Baru,baru@example.com,2002,0022,X B,P,,,,\n")
            ->assertRedirect(route('admin.students.import.preview'));

        $this->actingAs($admin)->get(route('admin.students.import.preview'))
            ->assertOk()
            ->assertSee('Siswa Baru')
            ->assertSee('Lama Baru');

        $this->assertDatabaseMissing('users', ['email' => 'baru@example.com']);
        $this->assertDatabaseHas('users', ['student_number' => '1001', 'name' => 'Lama']);
    }

    public function test_commit_creates_new_and_updates_existing_students(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->student()->create([
            'student_number' => '1001',
            'email' => 'lama@example.com',
            'name' => 'Lama',
            'classroom' => 'X A',
            'phone' => '0800',
        ]);

        $this->upload($admin, "Lama Update,lama@example.com,1001,0011,,,,,,\nSiswa Baru,baru@example.com,2002,0022,X B,P,0811,true,,\n");

        $this->actingAs($admin)->post(route('admin.students.import.commit'))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHas('status');

        $existing->refresh();
        $this->assertSame('Lama Update', $existing->name);
        $this->assertSame('0011', $existing->nisn);
        // Sel kosong tidak menghapus data lama.
        $this->assertSame('X A', $existing->classroom);
        $this->assertSame('0800', $existing->phone);

        $new = User::where('student_number', '2002')->first();
        $this->assertNotNull($new);
        $this->assertSame('student', $new->role);
        $this->assertSame('female', $new->gender);
        $this->assertSame('0022', $new->nisn);
        $this->assertNotNull($new->card_code);
        $this->assertSame($new->card_code, $new->qr_code);
    }

    public function test_conflicting_and_duplicate_rows_are_reported_as_errors(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->student()->create(['student_number' => '1001', 'email' => 'a@example.com']);
        User::factory()->student()->create(['student_number' => '1002', 'email' => 'b@example.com']);
        User::factory()->staff()->create(['email' => 'guru@example.com']);

        // Konflik NIS vs email, email milik staff, duplikat NIS di file
        $this->upload($admin, "X,a@example.com,1002,,,,,,,\nY,guru@example.com,3003,,,,,,,\nZ,z@example.com,4004,,,,,,,\nZ2,z2@example.com,4004,,,,,,,\n");

        $response = $this->actingAs($admin)->get(route('admin.students.import.preview'))->assertOk();
        $plan = $response->viewData('plan');

        $this->assertSame(4, $plan['summary']['total']);
        $this->assertSame(3, $plan['summary']['error']);
        $this->assertSame(1, $plan['summary']['new']);
    }

    public function test_commit_can_abort_when_there_are_errors(): void
    {
        $admin = User::factory()->admin()->create();

        $this->upload($admin, "Baik,baik@example.com,5005,,,,,,,\nBuruk,bukan-email,6006,,,,,,,\n");

        $this->actingAs($admin)->post(route('admin.students.import.commit'), ['abort_on_error' => '1'])
            ->assertRedirect(route('admin.students.import.preview'));

        $this->assertDatabaseMissing('users', ['student_number' => '5005']);
    }

    public function test_cancel_discards_pending_import(): void
    {
        $admin = User::factory()->admin()->create();

        $this->upload($admin, "Baik,baik@example.com,5005,,,,,,,\n");
        $this->assertNotEmpty(Storage::disk('local')->files('import-tmp'));

        $this->actingAs($admin)->post(route('admin.students.import.cancel'))
            ->assertRedirect(route('admin.students.index'));

        $this->assertEmpty(Storage::disk('local')->files('import-tmp'));
        $this->assertDatabaseMissing('users', ['student_number' => '5005']);
    }

    public function test_non_admin_cannot_import(): void
    {
        $student = User::factory()->student()->create();

        $file = UploadedFile::fake()->createWithContent('siswa.csv', self::HEADER);

        $this->actingAs($student)->post(route('admin.students.import'), ['file' => $file])->assertForbidden();
    }
}
