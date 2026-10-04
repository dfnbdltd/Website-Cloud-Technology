<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Delivery record for every mail attempt (guide section 3: email_logs).
 */
class EmailLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id', 'user_id', 'recipient', 'template_key',
        'subject', 'status', 'error_message', 'sent_at',
    ];
}
