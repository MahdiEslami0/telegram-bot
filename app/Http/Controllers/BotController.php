<?php

namespace App\Http\Controllers;

use Telegram\Bot\Laravel\Facades\Telegram;

class BotController extends Controller
{


    public function setWebhook()
    {
        $response = Telegram::getUpdates();
        return  $response;
    }
}
