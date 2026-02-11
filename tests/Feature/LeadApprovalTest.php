<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Livewire\Livewire;

// RefreshDatabase is applied globally via Pest.php

beforeEach(function () {
    //
});

afterEach(function () {
    try {
        tenancy()->end();
    } catch (\Throwable $e) {
    }
});

test('admin can see leads index', function () {
    $user = User::factory()->create();

    // Create admin role and assign
    \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    $user->assignRole('admin');

    // Create a lead
    Lead::create([
        'name' => 'Lead One',
        'email' => 'lead@one.com',
        'company' => 'One Corp',
        'status' => LeadStatus::PENDING,
    ]);

    $this->actingAs($user)
        ->get(route('central.tenants.leads'))
        ->assertOk()
        ->assertSee('One Corp');
});

test('create-tenant-modal pre-fills from lead', function () {
    $lead = Lead::create([
        'name' => 'Modal Prefill',
        'email' => 'modal@prefill.com',
        'company' => 'Modal Corp',
        'status' => LeadStatus::PENDING,
    ]);

    Livewire::test('pages::central.tenants.modals.create')
        ->call('open', $lead->id)
        ->assertSet('company', 'Modal Corp')
        ->assertSet('email', 'modal@prefill.com')
        ->assertSet('subdomain', 'modal-corp');
});

test('registering tenant via modal marks lead as approved', function () {
    $lead = Lead::create([
        'name' => 'Modal Approve',
        'email' => 'modal@approve.com',
        'company' => 'Modal Approve Inc',
        'status' => LeadStatus::PENDING,
    ]);

    Illuminate\Support\Facades\Bus::fake();

    Livewire::test('pages::central.tenants.modals.create')
        ->call('open', $lead->id)
        ->set('subdomain', 'modal-' . str()->random(5))
        ->call('register')
        ->assertHasNoErrors();

    Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\CreateTenantJob::class, function ($job) use ($lead) {
        return $job->lead_id === $lead->id;
    });
});
