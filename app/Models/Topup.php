<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topup extends Model
{
    protected $fillable = ['user_id', 'reference', 'amount', 'method', 'sender_number', 'transaction_id', 'status', 'admin_note', 'approved_by', 'approved_at'];

    protected $casts = [
        'amount' => 'float',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
