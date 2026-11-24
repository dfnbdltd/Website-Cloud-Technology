<?php

namespace App\Mail;

use App\Models\HostingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/** "Your domain is now hosted" - sent to the owner's account email when the admin marks the order hosted. */
class HostingHostedMail extends Mailable
{
    use Queueable;

    public $order;

    public function __construct(HostingOrder $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('Your domain ' . $this->order->domain . ' is now hosted')
            ->view('emails.hosting-hosted');
    }
}
