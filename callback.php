<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/vendor/autoload.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));


$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LineMessageAPIChannelAccessToken'));

$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);

$sign = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

$events = $bot->parseEventRequest(file_get_contents('php://input'), $sign);

$page = 1;
$action ="";

$score = -1;
//require "menus.php"; //menus.phpのプログラムを使うよ



foreach ($events as $event) {



   if ($event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage) {  // Location event
   
    
        $log->addWarning("location event event!\n");

        $address = $event->getAddress();
        
        $lat = $event->getLatitude();
        $lon = $event->getLongitude();
        
        $query = "";
        
        $status = SearchToiletData( $bot, $event, $lat, $lon, $query  );
        
        
 
   
   }
   
   

   if ($event instanceof \LINE\LINEBot\Event\JoinEvent) {  // Join event add
   
    
    $log->addWarning("join event!\n");
    $bot->replyText($event->getReplyToken(), "ありがとう");
     //  firstmessage( $bot, $event,0);
       continue;
   
   }
   
   
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent) ||
      !($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
      
      if (!($event instanceof \LINE\LINEBot\Event\PostbackEvent) ) {
         $bot->replyText($event->getReplyToken(), " event");
         
             continue;
      }
     else  {
     
       $bot->replyText($event->getReplyToken(), "post back event");
        }
     
      }
    
 
 
     if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {  //  テキストメッセージの場合
            $tgText=$event->getText();
            
          
     
            $bot->replyText($event->getReplyToken(), "テキストメッセージ   line://nv/location  ${tgText}");
     
     
          continue;
          
        }
     
   
        $bot->replyText($event->getReplyToken(), "その他メッセージ　  line://nv/location ");
        
   }
