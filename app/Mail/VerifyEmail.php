<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Mailable
{
    use Queueable;

    public $user;
    public $url;

    public function __construct(User $user)
    {
        $this->user = $user;
        // Sign the path only, so the link works on both www. and the bare domain.
        $this->url = url(URL::temporarySignedRoute('verification.verify', now()->addHours(48), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ], false));
    }

    public function build()
    {
        return $this->subject('Verify your email address')->view('emails.verify');
    }
}
