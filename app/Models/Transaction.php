<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_sender_id',
        'user_receiver_id',
        'vendor_id',
        'value',
        'give_up',
    ];
}
