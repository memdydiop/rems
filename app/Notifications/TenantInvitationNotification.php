<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\TenantInvitation;

class TenantInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TenantInvitation $invitation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Initialize tenancy if running in queue effectively or just use the domain from the tenant
        // Since we are inside a tenant context (Notification sent by Admin), tenancy()->tenant should be set.
        // However, to be safe, we explicitly pass the domain if possible.

        $domain = tenancy()->tenant->domains->first()?->domain ?? '127.0.0.1';
        $protocol = app()->isProduction() ? 'https' : 'http';

        // Handle port for dev environment
        $appUrl = config('app.url');
        $port = parse_url($appUrl, PHP_URL_PORT);
        $portSuffix = $port ? ":{$port}" : '';

        // Manually build URL to ensures correct host even if context is lost
        $joinUrl = "{$protocol}://{$domain}{$portSuffix}/join/{$this->invitation->token}";

        return (new MailMessage)
            ->subject('Invitation to join ' . tenancy()->tenant->company)
            ->line('You have been invited to join the workspace: ' . tenancy()->tenant->company)
            ->line('Role: ' . ucfirst($this->invitation->role))
            ->action('Accept Invitation', $joinUrl)
            ->line('This invitation expires in 48 hours.')
            ->line('If you did not expect this invitation, no further action is required.');
    }
}
