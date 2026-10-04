<?php

namespace App\Services;

use PHPMailer\PHPMailer\SMTP;

class DiagnosticSmtp extends SMTP
{
    private $authenticationFailure;

    public function authenticate($username, $password, $authtype = null, $OAuth = null)
    {
        $authenticated = parent::authenticate($username, $password, $authtype, $OAuth);

        if (!$authenticated) {
            $this->recordAuthenticationFailure($this->getLastReply(), $this->getError());
        }

        return $authenticated;
    }

    public function getAuthenticationFailure(): ?array
    {
        return $this->authenticationFailure;
    }

    protected function recordAuthenticationFailure(string $reply, array $error): void
    {
        $this->authenticationFailure = [
            'reply' => trim($reply),
            'error' => $error,
        ];
    }
}
