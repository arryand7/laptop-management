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

    public function test_staff_can_edit_and_delete_checklist(): void
    {
        $staff = $this->makeStaffUser();
        $owner = User::factory()->student()->create();

        $foundLaptop = Laptop::factory()->create(['last_checked_at' => now()->subDay()]);
        $missingLaptop = Laptop::factory()->create([
            'owner_id' => $owner->id,
            'is_missing' => false,
        ]);

        $this->actingAs($staff)->post(route('staff.checklist.store'), [
            'found_laptops' => [$foundLaptop->id],
            'note' => 'Checklist awal',
        ]);

        $session = ChecklistSession::first();
        $this->assertNotNull($session);

        $response = $this->actingAs($staff)->put(route('staff.checklist.update', $session), [
            'found_laptops' => [$foundLaptop->id, $missingLaptop->id],
            'note' => 'Revisi checklist',
        ]);

        $response->assertRedirect();

        $session->refresh();
        $this->assertEquals(2, $session->found_count);
        $this->assertEquals(0, $session->missing_count);
        $this->assertSame('Revisi checklist', $session->note);
        $this->assertFalse(Laptop::find($missingLaptop->id)->is_missing);

        $this->actingAs($staff)->delete(route('staff.checklist.destroy', $session))
            ->assertRedirect(route('staff.checklist.history'));

        $this->assertDatabaseMissing('checklist_sessions', ['id' => $session->id]);
        $this->assertEquals(0, ChecklistDetail::count());
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
