<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Vendor extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'cpf',
        'cnpj',
        'password',
        'wallet',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];
}
