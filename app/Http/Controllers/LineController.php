<?php

namespace App\Http\Controllers;
use App\Services\Gurunavi;
use Illuminate\Http\Request;
use Log;
use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use Line\LINEBot\HTTPClient\CurlHTTPClined;
use Line\LINEBot\Event\MessageEvent\TextMessage;

class LineController extends Controller
{

    public function restaurants(Request $request)
    {

        $httpClient = new CurlHTTPClient(env('LINE_ACCESS_TOKEN'));
        $linebot = new LINEBot($httpClient,['channelSecret' =>env('LINE_CHANNEL_SECRET')]);

        $signature = $request->header('x-line-signature');
        if (!$linebot->validateSignature($request->getContent(),$signature)) {
            abort(400, 'Invalid signature');
        }
        // イベントを取得
        $events = $linebot->parseEventRequest($request->getContent(), $signature);

        foreach($events as $event){
            // テキスト以外は処理をしない
            if(!($event instanceof TextMessage)) {
                Log::debug('No text message');
                continue;
            }

        // ぐるなび
        $gurunavi = new Gurunavi();
        $gurunaviResponce = $gurunavi->searchRestaurants($event->getText());

        if (array_key_exists('error' , $gurunaviResponce))
        {
            $replyText = $gurunaviResponce['error'][0]['message'];
            $replyToken = $event->getReplyToken();
            $linebot->replyText($replyToken,$replyText);
        }

        $replyText = '';
        foreach ($gurunaviResponce['rest'] as $restaurant)
        {
            $replyText .= 
                $restaurant['name'] . "\n" .
                $restaurant['url'] . "\n" .
                "\n";
        }

            $replyToken = $event->getReplyToken();
            // Log::debug($replyText);
            $linebot->replyText($replyToken,$replyText);
        }
    }
}
