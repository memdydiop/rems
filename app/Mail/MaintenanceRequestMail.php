<?php

namespace App\Mail;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public MaintenanceRequest $request,
        public ?string $requestUrl = null
    ) {
    }

    public function envelope(): Envelope
    {
        $priority = match ($this->request->priority) {
            'urgent' => '🔴 URGENT',
            'high' => '🟠 Haute priorité',
            default => ''
        };

        return new Envelope(
            subject: $priority . ' Nouvelle demande de maintenance',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance-request',
        );
    }
}
