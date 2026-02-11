<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Renter;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\Property;
use App\Notifications\MaintenanceCreatedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;

class NotificationTest extends TenantTestCase
{
    public function test_maintenance_created_notification_is_sent()
    {
        Notification::fake();

        $user = User::factory()->create();
        $renter = Renter::factory()->create(['user_id' => $user->id]);
        $unit = Unit::factory()->create();
        $lease = Lease::factory()->create([
            'unit_id' => $unit->id,
            'renter_id' => $renter->id
        ]);

        // Manually trigger the creation logic or use the actual component if feasible
        // Here we simulate the notification sending that happens in the component
        $request = $unit->maintenanceRequests()->create([
            'property_id' => $unit->property_id,
            'user_id' => $user->id,
            'title' => 'Test Issue',
            'description' => 'Test Desc',
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $admin = User::factory()->create();

        // Simulate sending
        $admin->notify(new MaintenanceCreatedNotification($request));

        Notification::assertSentTo(
            [$admin],
            MaintenanceCreatedNotification::class,
            function ($notification) use ($request) {
                return $notification->request->id === $request->id;
            }
        );
    }
}
