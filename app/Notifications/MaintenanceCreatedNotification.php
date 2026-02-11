<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MaintenanceRequest $request
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $priorityEmoji = match ($this->request->priority?->value ?? 'low') {
            'urgent' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            default => '🟢',
        };

        return (new MailMessage)
            ->subject($priorityEmoji . ' Nouveau ticket maintenance - ' . $this->request->title)
            ->greeting('Bonjour,')
            ->line('Un nouveau ticket de maintenance a été créé.')
            ->line('**Titre:** ' . $this->request->title)
            ->line('**Priorité:** ' . ($this->request->priority?->label() ?? 'Basse'))
            ->line('**Propriété:** ' . $this->request->property?->name)
            ->line('**Unité:** ' . ($this->request->unit?->name ?? 'N/A'))
            ->when($this->request->description, function ($message) {
                return $message->line('**Description:** ' . $this->request->description);
            })
            ->action('Voir le ticket', url('/maintenance'))
            ->line('Merci de traiter ce ticket dans les meilleurs délais.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'maintenance_created',
            'request_id' => $this->request->id,
            'title' => $this->request->title,
            'priority' => $this->request->priority?->value,
            'property_name' => $this->request->property?->name,
            'message' => 'Nouveau ticket: ' . $this->request->title,
        ];
    }
}
