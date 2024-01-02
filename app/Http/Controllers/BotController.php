<?php

namespace App\Http\Controllers;

use GoogleSearchResults;
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
        // Log::info($request);
        if (isset($request['inline_query'])) {
            $this->inline_query($request);
        } else {
            if (!isset($request['chosen_inline_result'])) {
                $this->chat_id = $request['message']['chat']['id'];
                $this->username = $request['message']['from']['username'];
                $this->chat_type = $request['message']['chat']['type'];
                $this->message_id = $request['message']['message_id'];
                // Log::info($this->chat_type);
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

    // public function scrapeGoogleImages($query)
    // {
    //     $query = urlencode($query);
    //     $url = "https://www.google.com/search?q={$query}&tbm=isch&safe=off"; // Add "&safe=off" to the URL

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36");
    //     $html = curl_exec($ch);
    //     curl_close($ch);

    //     $imageUrls = [];
    //     if ($html) {
    //         preg_match_all('/<img[^>]+src="([^">]+)"[^>]*>/', $html, $matches);

    //         if (isset($matches[1])) {
    //             $imageUrls = $matches[1];
    //         }
    //     }

    //     return $imageUrls;
    // }

    public function test()
    {
        $searchQuery = "iran";
        $imageUrls = $this->get_google_image($searchQuery);

        return $imageUrls;
    }


    public function get_google_image($search)
    {

        $apiKey = "AIzaSyDOUXFlrIwo9TYVYkQTkRGcBVRh3xbA6vg";
        $cx = '7350f12854f224354';
        $query = urlencode($search);
        $url = "https://www.googleapis.com/customsearch/v1?key={$apiKey}&cx={$cx}&searchType=image&q={$query}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        $responses = [];
        if (isset($result['items'])) {
            foreach ($result['items'] as $item) {
                $imageUrl = $item['link'];
                if (strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION)) === 'jpg') {
                    $responses[] = [
                        'type' => 'photo',
                        'id' => uniqid(),
                        'title' => $item['title'] ?? '',
                        'photo_url' => $imageUrl,
                        'thumbnail_url' => $imageUrl
                    ];
                }
            }
        }
        return $responses;

        // if ($search == null) {
        //     $search = "not found";
        // }
        // $url = "https://www.google.com/search?q=" . urlencode($search) . "&tbm=isch&safe=off";
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36");
        // $html = curl_exec($ch);
        // curl_close($ch);
        // $imageUrls = [];
        // if ($html) {
        //     preg_match_all('/<img[^>]+src="([^">]+)"[^>]*>/', $html, $matches);
        //     if (isset($matches[1])) {
        //         $imageUrls = $matches[1];
        //     }
        // }
        // $responses = [];
        // $resultCount = 0;
        // foreach ($imageUrls as $imageUrl) {
        //     if ($imageUrl !== '' && preg_match('/\.jpg$/i', $imageUrl)) {
        //         $responses[] = [
        //             'type' => 'photo',
        //             'id' => uniqid(),
        //             'title' => '',
        //             'photo_url' => $imageUrl,
        //             'thumbnail_url' => $imageUrl
        //         ];
        //         $resultCount++;
        //     }
        // }
        // return $responses;
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
            // \Log::info($e);
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
