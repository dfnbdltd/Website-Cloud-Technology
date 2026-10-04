<?php

namespace App\Jobs;

use App\Services\MailDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Guide section 7: sending goes through the queue (QUEUE_CONNECTION=sync
 * runs it inline; switch to redis/database for real background sending).
 */
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $companyId;
    public $templateKey;
    public $to;
    public $data;

    public function __construct($companyId, $templateKey, $to, $data)
    {
        $this->companyId = $companyId;
        $this->templateKey = $templateKey;
        $this->to = $to;
        $this->data = $data;
    }

    public function handle()
    {
        app(MailDispatcher::class)->sendByCompany(
            $this->companyId,
            $this->templateKey,
            $this->to,
            $this->data
        );
    }
}
