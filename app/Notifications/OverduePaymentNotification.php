<?php

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverduePaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lease $lease,
        public int $daysOverdue,
        public string $level = 'reminder', // reminder, warning, urgent
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->lease->rent_amount, 0, ',', ' ');
        $unit = $this->lease->unit?->name ?? 'N/A';
        $property = $this->lease->unit?->property?->name ?? 'N/A';

        $mail = (new MailMessage);

        return match ($this->level) {
            'urgent' => $mail
                ->subject("⚠️ MISE EN DEMEURE — Loyer impayé depuis {$this->daysOverdue} jours")
                ->greeting("Madame, Monsieur {$notifiable->last_name},")
                ->line("Malgré nos précédentes relances, nous constatons que votre loyer reste **impayé depuis {$this->daysOverdue} jours**.")
                ->line("**Montant dû:** {$amount} XOF")
                ->line("**Unité:** {$unit} — {$property}")
                ->line('Nous vous prions de régulariser votre situation **dans les plus brefs délais** afin d\'éviter toute procédure.')
                ->line('En cas de difficulté, veuillez nous contacter immédiatement.')
                ->salutation('Cordialement, La Direction'),

            'warning' => $mail
                ->subject("🔔 Rappel important — Loyer en retard de {$this->daysOverdue} jours")
                ->greeting("Bonjour {$notifiable->first_name},")
                ->line("Nous vous rappelons que votre loyer est **en retard de {$this->daysOverdue} jours**.")
                ->line("**Montant dû:** {$amount} XOF")
                ->line("**Unité:** {$unit} — {$property}")
                ->line('Merci de procéder au règlement le plus rapidement possible.')
                ->line('Si vous avez déjà effectué le paiement, veuillez ignorer ce message.')
                ->salutation('Cordialement'),

            default => $mail
                ->subject("📋 Rappel de loyer — {$this->daysOverdue} jours de retard")
                ->greeting("Bonjour {$notifiable->first_name},")
                ->line("Nous vous informons que votre loyer du mois en cours n'a **pas encore été reçu**.")
                ->line("**Montant:** {$amount} XOF")
                ->line("**Unité:** {$unit} — {$property}")
                ->line('Merci de régulariser votre paiement dans les meilleurs délais.')
                ->salutation('Cordialement'),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'overdue_payment',
            'level' => $this->level,
            'lease_id' => $this->lease->id,
            'days_overdue' => $this->daysOverdue,
            'amount' => $this->lease->rent_amount,
            'message' => match ($this->level) {
                'urgent' => "⚠️ Mise en demeure : loyer impayé depuis {$this->daysOverdue} jours",
                'warning' => "🔔 Rappel : loyer en retard de {$this->daysOverdue} jours",
                default => "📋 Rappel : loyer en attente depuis {$this->daysOverdue} jours",
            },
        ];
    }
}
