<?php

namespace App\Http\Controllers;

use Telegram\Bot\Laravel\Facades\Telegram;

class BotController extends Controller
{


    public function setWebhook()
    {
        $response = Telegram::setWebhook(['url' => 'https://example.com/<token>/webhook']);
        return  $response;
    }
}
