<?php

namespace App\Services;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notifier
{
    /**
     * Send a mail without ever breaking the request that triggered it:
     * a mail server problem is logged, the customer's action still succeeds.
     */
    public static function send($to, Mailable $mail)
    {
        try {
            Mail::to($to)->send($mail);

            return true;
        } catch (\Throwable $e) {
            Log::error('Mail delivery failed', ['to' => $to, 'mail' => get_class($mail), 'error' => $e->getMessage()]);

            return false;
        }
    }
}
