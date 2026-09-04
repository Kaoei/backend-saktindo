<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\RekeningBank;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public $rekenings;
    public ?string $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, ?string $customMessage = null)
    {
        $this->invoice = $invoice;
        $this->customMessage = $customMessage;
        $this->invoice->loadMissing(['salesOrder.customer', 'salesOrders.customer', 'payments']);
        
        // Fetch bank accounts according to invoice tax type
        $toko = str_starts_with($invoice->tax_type ?? '', 'sjb') ? 'sjb' : 'js';
        $this->rekenings = RekeningBank::where('toko', $toko)->orWhereNull('toko')->get();
        if ($this->rekenings->isEmpty()) {
            $this->rekenings = RekeningBank::all();
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan Tagihan Invoice: ' . $this->invoice->invoice_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-reminder',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
