<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotController;
use Telegram\Bot\Laravel\Facades\Telegram;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/test', [BotController::class, 'test']);
Route::get('/deleteWebhook', [BotController::class, 'deleteWebhook']);
Route::get('/setWebhook', [BotController::class, 'setWebhook']);

Route::post('/InputHandel', [BotController::class, 'handleRequest']);
