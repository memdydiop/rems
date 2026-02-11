<?php

namespace App\Mail;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaseExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $daysRemaining;

    public function __construct(
        public Lease $lease,
        public ?string $leaseUrl = null
    ) {
        $this->daysRemaining = now()->diffInDays($lease->end_date);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bail arrivant à expiration - ' . $this->daysRemaining . ' jours restants',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lease-expiring',
        );
    }
}
