<?php

namespace App\Mail;

use App\Models\DomainOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class InvoiceMail extends Mailable
{
    use Queueable;

    public $order;

    public function __construct(DomainOrder $order)
    {
        $this->order = $order->loadMissing('items');
    }

    public function build()
    {
        return $this->subject('Invoice ' . $this->order->reference . ' — payment received')->view('emails.invoice');
    }
}
