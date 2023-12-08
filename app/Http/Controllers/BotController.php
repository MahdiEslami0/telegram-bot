<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class BotController extends Controller
{
    protected $telegram;
    protected $chat_id;
    protected $username;
    protected $chat_text;
    protected $chat_type;
    protected $message_id;
    protected $reply_to_message;

    public function handleRequest(Request $request)
    {
        Log::info($request);
        if (isset($request['inline_query'])) {
            $this->inline_query($request);
        } else {
            if (!isset($request['chosen_inline_result'])) {
                $this->chat_id = $request['message']['chat']['id'];
                $this->username = $request['message']['from']['username'];
                $this->chat_type = $request['message']['chat']['type'];
                $this->message_id = $request['message']['message_id'];
                Log::info($this->chat_type);
                if (isset($request['message']['reply_to_message'])) {
                    $this->reply_to_message = $request['message']['reply_to_message'];
                }
                if (isset($request['message']['text']) &&  $this->chat_type == "group" || $this->chat_type == "supergroup") {
                    $this->chat_text = $request['message']['text'];
                    $this->group();
                }
                if (isset($request['message']['text']) &&  $this->chat_type == "private") {
                    $this->chat_text = $request['message']['text'];
                    $this->private();
                }
            }
        }
        return 'ok';
    }


    public function inline_query($request)
    {
        $inlineQueryId = $request['inline_query']['id'];
        $search = $request['inline_query']['query'];
        if (substr($search, -1) === '+') {
            Telegram::answerInlineQuery([
                'inline_query_id' => $inlineQueryId,
                'results' => json_encode($this->get_google_image($search)),
                'cache_time' => '10000'
            ]);
        } else {
            Telegram::answerInlineQuery([
                'inline_query_id' => $inlineQueryId,
                'results' => null,
                'cache_time' => '10000'
            ]);
        }
    }

    public function test()
    {

        return env('search_api_key');
    }


    public function get_google_image($search)
    {
        if ($search == null) {
            $search = "not found";
        }
        $client = new Client();

        $apiKey = env('search_api_key');
        $url = 'https://serpapi.com/search.json?q=' . $search . '&location=Austin,+Texas,+United+States&google_domain=google.com&hl=en&api_key=' . $apiKey . '&engine=google_images';
        $response = $client->request('GET', $url);
        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            $body = $response->getBody();
            $data = json_decode($body, true);
            $imageResults = $data['images_results'] ?? [];
            $responses = [];
            $resultCount = 0; // Counter to limit the number of results

            foreach ($imageResults as $imageResult) {
                if ($resultCount >= 10) {
                    break; // Break the loop if 10 results are reached
                }

                $thumbnailUrl = $imageResult['thumbnail'] ?? '';
                $originalImageUrl = $imageResult['original'] ?? '';
                // Check if the image URL ends with '.jpg' (case insensitive)
                if ($thumbnailUrl !== '' && $originalImageUrl !== '' && preg_match('/\.jpg$/i', $originalImageUrl)) {
                    $responses[] = [
                        'type' => 'photo',
                        'id' => uniqid(),
                        'title' => $imageResult['title'] ?? '',
                        'photo_url' => $originalImageUrl,
                        'thumbnail_url' => $originalImageUrl
                    ];
                    $resultCount++; // Increment the counter
                }
            }
            return $responses;
        } else {
            return null;
        }
    }




    public function private()
    {
        switch ($this->chat_text) {
            case '/start':
                Telegram::sendMessage([
                    'chat_id' => $this->chat_id,
                    'text' => 'سلام دوست عزیز , به ربات خوش آمدی',
                    // 'reply_to_message_id' => $this->reply_to_message['message_id']
                ]);
                break;
            default:
                return 'ok';
        }
    }


    public function group()
    {
        switch ($this->chat_text) {
            case 'حذف':
                $this->delete_message_group();
                break;
            case 'بن':
                $this->ban_user_group();
                break;
            default:
                return 'ok';
        }
    }


    public function delete_message_group()
    {
        // \Log::info('sads');
        try {
            Telegram::deleteMessage([
                'chat_id' => $this->chat_id,
                'message_id' => $this->reply_to_message['message_id']
            ]);
            Telegram::deleteMessage([
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id
            ]);
        } catch (\Exception $e) {
            Telegram::sendMessage([
                'chat_id' => $this->chat_id,
                'text' =>  'این پیام قابل حذف نیست',
                'reply_to_message_id' => $this->message_id
            ]);
            \Log::info($e);
        }
    }

    public function ban_user_group()
    {
        try {
            Telegram::banChatMember([
                'chat_id' => $this->chat_id,
                'user_id' =>  $this->reply_to_message['from']['id']
            ]);
            $text = ' کابری : ' . $this->reply_to_message['from']['username'] . ' به دلیل به تخم گرفتن قوانین بن شد ! ';
            Telegram::sendMessage([
                'chat_id' => $this->chat_id,
                'text' =>  $text,
                'reply_to_message_id' => $this->reply_to_message['message_id']
            ]);
        } catch (\Exception $e) {
            Telegram::sendMessage([
                'chat_id' => $this->chat_id,
                'text' =>  'کاربر یافت نشد 😐',
                'reply_to_message_id' => $this->reply_to_message['message_id']
            ]);
        }
    }


    public function setWebhook()
    {
        $bot =  config('telegram.bot');
        $response = Telegram::setWebhook(['url' =>  $bot['webhook_url']]);
        return $response;
    }
    public function deleteWebhook()
    {
        $response = Telegram::deleteWebhook();
        return $response;
    }
}
