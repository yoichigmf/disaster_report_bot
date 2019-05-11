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


$dropboxToken = getenv('DROPBOXACCESSTOKEN');

$page = 1;
$action ="";

$score = -1;
//require "menus.php"; //menus.phpのプログラムを使うよ



foreach ($events as $event) {



   if ($event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage) {  // Location event
   
    
        $title = $event->getTitle();
        $address = $event->getAddress();
        $latitude = $event->getLatitude();
        $longitude = $event->getLongitude();
        
        
         $bot->replyText($event->getReplyToken(), "ロケーションイベント ${title} ${address} ${latitude} ${longitude}");
        
        
 
   
   }
   
          
      if ($event instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage) {  //  イメージメッセージの場合
            
            $message_id = $event->getMessageId();
            
            $response = $bot->getMessageContent($message_id );
            
            if ($response->isSucceeded()) {
            
            
             $url = "https://content.dropboxapi.com/2/files/upload";
                   
            
           $tempFilePath = tempnam('.', 'image-');
           unlink($tempFilePath);
           $filePath = $tempFilePath . '.jpg';
           $filename = basename($filePath);
        
             $tgfilename = "/disasterinfo/${filename}";
             
             $filearg = "Dropbox-API-Arg: {\"path\":\"${tgfilename}\"}";
        
              $auth = "Authorization: Bearer ${dropboxToken}"
                  $headers = array(
                       $auth , //(2)
                          $filearg,//(3)
                           'Content-Type: application/octet-stream'
                    );
        
        
        

        
                 $options = array(
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_URL => $url,
                           CURLOPT_HTTPHEADER => $headers,
                           CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => $response->getRawBody()     
                       );

                   $ch = curl_init();

                  curl_setopt_array($ch, $options);

                 $result = curl_exec($ch);
        
        
        
  			//		  $tempfile = tmpfile();
  			//		  fwrite($tempfile, $response->getRawBody());
				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}

     
            $bot->replyText($event->getReplyToken(), "イメージイベント   ${message_id} ");
     
     
          continue;
          
        }
        
      
      
       if ($event instanceof \LINE\LINEBot\Event\MessageEvent\AudioMessage) {  //  オーディオメッセージの場合  debug
            
            
          
     
            $bot->replyText($event->getReplyToken(), "オーディオイベント   line://nv/location ");
     
     
          continue;
          
        }
        
      
       if ($event instanceof \LINE\LINEBot\Event\MessageEvent\VideoMessage) {  //  ビデオメッセージの場合
            
            
          
     
            $bot->replyText($event->getReplyToken(), "ビデオイベント   line://nv/location ");
     
     
          continue;
          
        }
            
        if ($event instanceof \LINE\LINEBot\Event\MessageEvent\FileMessage) {  //  ファイルメッセージの場合
            
            
          
     
            $bot->replyText($event->getReplyToken(), "ファイルイベント   line://nv/location ");
     
     
          continue;
          
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
