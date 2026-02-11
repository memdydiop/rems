<?php

namespace App\Notifications;

use App\Models\RentPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RentPayment $payment
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lease = $this->payment->lease;
        $unit = $lease?->unit;

        return (new MailMessage)
            ->subject('✅ Paiement reçu - ' . number_format($this->payment->amount, 0, ',', ' ') . ' XOF')
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line('Nous confirmons la réception de votre paiement.')
            ->line('**Montant:** ' . number_format($this->payment->amount, 0, ',', ' ') . ' XOF')
            ->line('**Unité:** ' . $unit?->name)
            ->line('**Date:** ' . $this->payment->paid_at?->format('d/m/Y'))
            ->line('**Méthode:** ' . ucfirst($this->payment->method ?? 'Espèces'))
            ->action('Voir mon historique', url('/'))
            ->line('Merci pour votre paiement !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'paid_at' => $this->payment->paid_at?->format('Y-m-d'),
            'message' => 'Paiement de ' . number_format($this->payment->amount, 0, ',', ' ') . ' XOF reçu',
        ];
    }
}
