<?php

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lease $lease,
        public int $daysUntilExpiry
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unit = $this->lease->unit;
        $property = $unit?->property;

        $subject = $this->daysUntilExpiry <= 7
            ? '⚠️ Bail expire bientôt - ' . $unit?->name
            : 'Bail expirant - ' . $unit?->name;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour,')
            ->line('Le bail suivant expire dans ' . $this->daysUntilExpiry . ' jours.')
            ->line('**Propriété:** ' . $property?->name)
            ->line('**Unité:** ' . $unit?->name)
            ->line('**Locataire:** ' . $this->lease->renter?->first_name . ' ' . $this->lease->renter?->last_name)
            ->line('**Date d\'expiration:** ' . $this->lease->end_date->format('d/m/Y'))
            ->action('Gérer le bail', url('/leases'))
            ->line('Pensez à renouveler ou à préparer la fin du bail.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lease_expiring',
            'lease_id' => $this->lease->id,
            'unit_name' => $this->lease->unit?->name,
            'renter_name' => $this->lease->renter?->first_name . ' ' . $this->lease->renter?->last_name,
            'expiry_date' => $this->lease->end_date->format('Y-m-d'),
            'days_until_expiry' => $this->daysUntilExpiry,
            'message' => 'Bail expire dans ' . $this->daysUntilExpiry . ' jours',
        ];
    }
}
