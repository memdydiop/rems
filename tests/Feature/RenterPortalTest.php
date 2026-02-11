<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TenantTestCase;

class RenterPortalTest extends TenantTestCase
{
    protected $renterUser;
    protected $renterProfile;
    protected $unit;
    protected $lease;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user who is a renter
        $this->renterUser = User::factory()->create();

        $this->renterProfile = Renter::factory()->create([
            'user_id' => $this->renterUser->id,
            'email' => $this->renterUser->email,
        ]);

        $property = Property::factory()->create();
        $this->unit = Unit::factory()->create(['property_id' => $property->id]);

        $this->lease = Lease::factory()->create([
            'unit_id' => $this->unit->id,
            'renter_id' => $this->renterProfile->id,
            'status' => 'active',
        ]);
    }

    public function test_non_renter_cannot_access_portal()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('renter.dashboard'))
            ->assertForbidden();
    }

    public function test_renter_can_access_dashboard()
    {
        $this->actingAs($this->renterUser)
            ->get(route('renter.dashboard'))
            ->assertOk()
            ->assertSee($this->lease->unit->name);
    }

    public function test_renter_can_see_payments_history()
    {
        $this->actingAs($this->renterUser)
            ->get(route('renter.payments'))
            ->assertOk()
            ->assertSee('Historique des Paiements');
    }

    public function test_renter_can_create_maintenance_request()
    {
        $this->actingAs($this->renterUser);

        Livewire::test('pages::renter.modals.create-maintenance')
            ->set('title', 'Fuite eau')
            ->set('description', 'Grosse fuite dans la cuisine')
            ->set('priority', 'high')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('maintenance_requests', [
            'title' => 'Fuite eau',
            'unit_id' => $this->unit->id,
            'status' => 'pending',
            'priority' => 'high',
        ]);
    }
}
