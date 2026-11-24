<?php

namespace App\Mail;

use App\Models\HostingOrder;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/** Payment-received receipt for a hosting order, sent to the OWNER's account email. */
class HostingInvoiceMail extends Mailable
{
    use Queueable;

    public $order;
    public $invoice;

    public function __construct(HostingOrder $order, Invoice $invoice = null)
    {
        $this->order = $order;
        $this->invoice = $invoice;
    }

    public function build()
    {
        return $this->subject('Payment received — ' . $this->order->product . ' (' . $this->order->reference . ')')
            ->view('emails.hosting-invoice');
    }
}
