<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/vendor/autoload.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;

define('SCOPES', implode(' ', array(
        Google_Service_Drive::DRIVE)
));

$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));




$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LineMessageAPIChannelAccessToken'));

$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);


function getClient() {
    $client = new Google_Client();
    $client->setApplicationName(getenv('APPLICATION_NAME'));
    $client->setScopes(SCOPES);
    
    
   
   $auth_str = getenv('authstr');
   
   $auth_config = json_decode($auth_string, true);
   
   $client->setAuthConfig($auth_config);
   $client->setAccessType('offline');
    

   $token_str = getenv('tokenstr');
  
        $accessToken = json_decode($token_str, true);
        $client->setAccessToken($accessToken);
   
 

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken( getenv('refreshtoken'));

    }
    return $client;
}





$sign = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

$events = $bot->parseEventRequest(file_get_contents('php://input'), $sign);

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
            
            // Get the API client and construct the service object.
             $client = getClient();
             $service = new Google_Service_Drive($client);

            
            
             $folder_id = getenv('FolderID');
             
             
             $fileMetadata = new Google_Service_Drive_DriveFile(array(
                 'name' => 'photo.jpg',
                  'parents' => array(FOLDER_ID),
              ));

                $content = $response->getRawBody();
                
  			
  			$file = $service->files->create($fileMetadata, array(
                    'data' => $content,
                   'mimeType' => 'image/jpeg',
                  'uploadType' => 'multipart',
                  'fields' => 'id'));
                  
                  $fid = $file->id;
                  
                     $bot->replyText($event->getReplyToken(), "イメージイベント file id  ${fid} ");
    
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
