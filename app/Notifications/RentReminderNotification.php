<?php

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lease $lease,
        public int $daysUntilDue = 5
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

        return (new MailMessage)
            ->subject('Rappel de loyer - ' . $unit?->name)
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line('Ceci est un rappel amical que votre loyer est dû dans ' . $this->daysUntilDue . ' jours.')
            ->line('**Propriété:** ' . $property?->name)
            ->line('**Unité:** ' . $unit?->name)
            ->line('**Montant:** ' . number_format($this->lease->rent_amount, 0, ',', ' ') . ' XOF')
            ->action('Voir les détails', url('/'))
            ->line('Merci de votre ponctualité !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rent_reminder',
            'client_id' => $this->lease->id,
            'unit_name' => $this->lease->unit?->name,
            'amount' => $this->lease->rent_amount,
            'days_until_due' => $this->daysUntilDue,
            'message' => 'Rappel: loyer dû dans ' . $this->daysUntilDue . ' jours',
        ];
    }
}
