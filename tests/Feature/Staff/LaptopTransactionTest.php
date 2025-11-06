<?php

namespace Tests\Feature\Staff;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\BorrowTransaction;
use App\Models\Laptop;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class LaptopTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Module::syncFromConfig();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_staff_can_load_transaction_page(): void
    {
        $staff = $this->makeStaffUser();

        $response = $this->actingAs($staff)->get(route('staff.transactions.index'));

        $response->assertOk()
            ->assertSee('Laptop Borrow/Return System');
    }

    public function test_preview_returns_borrow_payload_for_available_laptop(): void
    {
        $staff = $this->makeStaffUser();
        $student = User::factory()->student()->create([
            'qr_code' => 'STUDENT-001',
            'student_number' => 'NIS001',
            'classroom' => 'X-1',
        ]);
        $laptop = Laptop::factory()->create([
            'qr_code' => 'LAPTOP-001',
            'code' => 'LP-001',
            'status' => 'available',
        ]);

        $response = $this->actingAs($staff)->postJson(route('staff.transactions.preview'), [
            'student_qr' => 'STUDENT-001',
            'laptop_qr' => 'LAPTOP-001',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'mode' => 'borrow',
                'student' => [
                    'name' => $student->name,
                ],
                'laptop' => [
                    'code' => $laptop->code,
                ],
            ])
            ->assertJsonStructure([
                'due_at',
                'due_at_display',
            ]);
    }

    public function test_preview_returns_return_payload_for_active_borrow(): void
    {
        $staff = $this->makeStaffUser();
        $student = User::factory()->student()->create([
            'qr_code' => 'STUDENT-RET',
            'student_number' => 'NISRET',
            'classroom' => 'XI-2',
        ]);
        $laptop = Laptop::factory()->create([
            'qr_code' => 'LAPTOP-RET',
            'code' => 'LP-RET',
            'status' => 'borrowed',
        ]);

        BorrowTransaction::factory()->create([
            'transaction_code' => 'TRX-1',
            'student_id' => $student->id,
            'laptop_id' => $laptop->id,
            'staff_id' => $staff->id,
            'usage_purpose' => 'Testing',
            'status' => 'borrowed',
            'was_late' => false,
            'borrowed_at' => now()->subHour(),
            'due_at' => now()->addHours(5),
        ]);

        $response = $this->actingAs($staff)->postJson(route('staff.transactions.preview'), [
            'student_qr' => 'STUDENT-RET',
            'laptop_qr' => 'LAPTOP-RET',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'mode' => 'return',
            ])
            ->assertJsonStructure([
                'borrow_transaction' => [
                    'transaction_code',
                    'borrowed_at',
                    'due_at',
                ],
            ]);
    }

    public function test_confirm_borrow_creates_transaction_and_marks_laptop(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:00:00'));

        $staff = $this->makeStaffUser();
        $student = User::factory()->student()->create([
            'qr_code' => 'STUDENT-BOR',
            'student_number' => 'NISBOR',
        ]);
        $laptop = Laptop::factory()->create([
            'qr_code' => 'LAPTOP-BOR',
            'code' => 'LP-BOR',
            'status' => 'available',
        ]);

        $response = $this->actingAs($staff)->postJson(route('staff.transactions.confirm'), [
            'student_qr' => 'STUDENT-BOR',
            'laptop_qr' => 'LAPTOP-BOR',
            'usage_purpose' => 'Simulasi Ujian',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'mode' => 'borrow',
            ]);

        $this->assertDatabaseHas('borrow_transactions', [
            'student_id' => $student->id,
            'laptop_id' => $laptop->id,
            'status' => 'borrowed',
            'usage_purpose' => 'Simulasi Ujian',
        ]);

        $transaction = BorrowTransaction::where('laptop_id', $laptop->id)->first();
        $this->assertNotNull($transaction);
        $this->assertSame('borrowed', $transaction->status);
        $this->assertEquals(Carbon::parse('2025-01-02 08:00:00'), $transaction->due_at);

        $this->assertDatabaseHas('laptops', [
            'id' => $laptop->id,
            'status' => 'borrowed',
        ]);

        Carbon::setTestNow();
    }

    public function test_confirm_return_marks_laptop_available_and_closes_transaction(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $staff = $this->makeStaffUser();
        $student = User::factory()->student()->create([
            'qr_code' => 'STUDENT-RET-2',
            'student_number' => 'NISRET2',
        ]);
        $laptop = Laptop::factory()->create([
            'qr_code' => 'LAPTOP-RET-2',
            'code' => 'LP-RET-2',
            'status' => 'borrowed',
        ]);

        $transaction = BorrowTransaction::create([
            'transaction_code' => 'TRX-RET-' . Str::upper(Str::random(4)),
            'student_id' => $student->id,
            'laptop_id' => $laptop->id,
            'staff_id' => $staff->id,
            'usage_purpose' => 'Peminjaman Test',
            'status' => 'borrowed',
            'was_late' => false,
            'borrowed_at' => now()->subHours(5),
            'due_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($staff)->postJson(route('staff.transactions.confirm'), [
            'student_qr' => 'STUDENT-RET-2',
            'laptop_qr' => 'LAPTOP-RET-2',
            'staff_notes' => 'Kondisi baik',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'mode' => 'return',
                'was_late' => false,
            ]);

        $transaction->refresh();
        $this->assertSame('returned', $transaction->status);
        $this->assertNotNull($transaction->returned_at);
        $this->assertSame('Kondisi baik', $transaction->staff_notes);

        $this->assertDatabaseHas('laptops', [
            'id' => $laptop->id,
            'status' => 'available',
        ]);

        Carbon::setTestNow();
    }

    private function makeStaffUser(): User
    {
        $staff = User::factory()->staff()->create([
            'qr_code' => 'STAFF-QR',
        ]);

        $module = Module::where('key', 'staff.transactions')->first();
        if ($module) {
            $staff->modules()->syncWithoutDetaching([$module->id]);
        }

        return $staff;
    }
}
