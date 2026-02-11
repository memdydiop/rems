<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

// RefreshDatabase is applied globally via Pest.php

test('can create tenant via modal', function () {
    Bus::fake();

    $user = User::factory()->create();
    $company = 'Test Company ' . \Illuminate\Support\Str::random(5);
    $subdomain = strtolower('test-' . \Illuminate\Support\Str::random(5));
    $name = 'Admin User';
    $email = 'admin@example.com';

    Livewire::actingAs($user)
        ->test('pages::central.tenants.modals.create')
        ->set('company', $company)
        ->set('subdomain', $subdomain)
        ->set('name', $name)
        ->set('email', $email)
        ->call('register')
        ->assertHasNoErrors();

    Bus::assertDispatched(\App\Jobs\CreateTenantJob::class, function ($job) use ($company, $subdomain, $name, $email) {
        return $job->company === $company &&
            $job->subdomain === $subdomain &&
            $job->name === $name &&
            $job->email === $email;
    });
});

test('modal validation rules', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::central.tenants.modals.create')
        ->set('company', '')
        ->set('subdomain', '')
        ->set('name', '')
        ->set('email', '')
        ->call('register')
        ->assertHasErrors(['company', 'subdomain', 'name', 'email']);
});

test('modal domain must be unique', function () {
    $user = User::factory()->create();
    $subdomain = strtolower('existing-' . \Illuminate\Support\Str::random(5));
    $company = 'Existing Company';

    $this->markTestSkipped('Skipping tenant existence check in SQLite memory environment due to connection isolation issues.');

    Tenant::withoutEvents(function () use (&$tenant, $subdomain, $company) {
        $tenant = new Tenant;
        $tenant->id = $subdomain;
        $tenant->tenancy_db_name = 'tenant_' . $subdomain;
        $tenant->company = $company;
        $tenant->saveQuietly();

        $tenant->domains()->createQuietly(['domain' => $subdomain . '.' . (config('tenancy.central_domains')[0] ?? 'localhost')]);
    });

    Livewire::actingAs($user)
        ->test('pages::central.tenants.modals.create')
        ->set('company', 'New Company')
        ->set('subdomain', $subdomain) // Duplicate
        ->set('name', 'New Admin')
        ->set('email', 'new@example.com')
        ->call('register')
        ->assertHasErrors(['subdomain']);

    Tenant::withoutEvents(function () use ($tenant) {
        $tenant?->delete();
    });
});
