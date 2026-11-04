<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Notifies the sales inbox. Replying goes straight to the customer. */
class EnquiryReceived extends Mailable
{
    public function __construct(
        public readonly Enquiry $enquiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
            subject: "New enquiry: {$this->enquiry->product_title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.enquiry-received');
    }
}
