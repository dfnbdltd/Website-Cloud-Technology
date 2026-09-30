<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['session_id', 'user_id', 'sender', 'message', 'seen_at'];

    public function markSeen()
    {
        if (!$this->seen_at) {
            $this->forceFill(['seen_at' => $this->freshTimestamp()])->save();
        }
    }
}
