<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

// use Telegram\Bot\Actions;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected array $aliases = ['subscribe'];
    public function handle()
    {
        $name = $this->getUpdate()->getMessage()->from->first_name;

        // \Log::info($this->getUpdate()->getMessage()->from);
        $keyboard = [
            ['7', '8', '9'],
            ['4', '5', '6'],
            ['1', '2', '3'],
            ['0']
        ];

        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $this->replyWithMessage([
            'text' => "Hello {$name}! Welcome to our bot, Here are our available commands:",
            'reply_markup' => $reply_markup
        ]);
    }
}
