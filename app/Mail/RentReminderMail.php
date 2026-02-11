<?php

namespace App\Mail;

use App\Models\Renter;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Renter $renter,
        public float $amount,
        public Carbon $dueDate,
        public string $property,
        public string $unit,
        public ?string $paymentUrl = null,
        public ?string $landlordName = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rappel de paiement de loyer',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rent-reminder',
        );
    }
}
