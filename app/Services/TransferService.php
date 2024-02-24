<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TransferService
{
    public static function check()
    {
        $response = Http::get('https://run.mocky.io/v3/5794d450-d2e2-4412-8131-73d0293ac1cc');

        return $response->ok() ? true : false;
    }
}
