<?php

namespace Tests\Feature\Staff;

use App\Models\ChecklistDetail;
use App\Models\ChecklistSession;
use App\Models\Laptop;
use App\Models\Module;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Module::syncFromConfig();
    }

    public function test_staff_can_view_checklist_form(): void
    {
        $staff = $this->makeStaffUser();
        Laptop::factory()->count(3)->create();

        $response = $this->actingAs($staff)->get(route('staff.checklist.create'));

        $response->assertOk()
            ->assertSee('Laptop Rack Checklist')
            ->assertSee('Simpan Checklist');
    }

    public function test_staff_can_store_checklist_and_mark_missing(): void
    {
        $staff = $this->makeStaffUser();
        $owner = User::factory()->student()->create();

        $foundLaptop = Laptop::factory()->create();
        $borrowedLaptop = Laptop::factory()->create([
            'status' => 'borrowed',
        ]);
        $missingLaptop = Laptop::factory()->create([
            'owner_id' => $owner->id,
        ]);

        $response = $this->actingAs($staff)->post(route('staff.checklist.store'), [
            'found_laptops' => [
                $foundLaptop->id,
                $borrowedLaptop->id,
            ],
            'note' => 'Checklist malam',
        ]);

        $response->assertRedirect();

        $session = ChecklistSession::first();
        $this->assertNotNull($session);
        $this->assertEquals(3, $session->total_laptops);
        $this->assertEquals(1, $session->found_count);
        $this->assertEquals(1, $session->missing_count);
        $this->assertEquals(1, $session->borrowed_count);
        $this->assertTrue($session->note === 'Checklist malam');

        $this->assertEquals(3, ChecklistDetail::count());
        $this->assertTrue(Laptop::find($missingLaptop->id)->is_missing);
        $this->assertEquals(1, Violation::count());
        $this->assertEquals($owner->id, Violation::first()->user_id);
    }

    private function makeStaffUser(): User
    {
        $staff = User::factory()->staff()->create();
        $module = Module::where('key', 'staff.checklist')->first();
        if ($module) {
            $staff->modules()->syncWithoutDetaching([$module->id]);
        }

        return $staff;
    }
}
