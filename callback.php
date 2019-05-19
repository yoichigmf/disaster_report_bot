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




function  AddImageLink( $response, $event, $filepath ){

    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');


        
    $service = new Google_Service_Sheets($client);

    
    $date    = date('Y/m/d h:i:s');
    
    $user = "kayama";
    $kind = "image";
    
    $url = $filepathe;
    $comment = $event->getTitle();
    
     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url, $comment ] ]);
     $resp = service.spreadsheets_values.append($this->spreadsheetId, 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    var_dump($resp);
    
}


//  $kind   'image'  'video'  'voice'
//  $ext    'jpg'    'mp4'    'mp4'
//  $content_type  application/octet-stream


function upload_contents( $kind , $ext, $content_type, $response ) {  // ファイルのDropBoxアップロード
          global $log;
          
          
          $log->addWarning("upload contents in\n");
          
 //          file upload           
           $tempFilePath = tempnam('.', "${kind}-");
           unlink($tempFilePath);
           $filePath = $tempFilePath . ".${ext}";
           $filename = basename($filePath);
  
            $dropboxToken = getenv('DROPBOXACCESSTOKEN');
            
  
             $url = "https://content.dropboxapi.com/2/files/upload";      
             $tgfilename = "/disasterinfo/${kind}/${filename}";
             
             $filearg = "Dropbox-API-Arg: {\"path\":\"${tgfilename}\"}";
        
              $auth = "Authorization: Bearer ${dropboxToken}";
                  $headers = array(
                       $auth , //(2)
                          $filearg,//(3)
                           "Content-Type: ${content_type}"
                    );
        
        
        
            $log->addWarning("file name ${tgfilename}\n");
            
        
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
                 
                 $log->addWarning("result ${result}\n");
                 
                 
                  curl_close($ch);
                 
                  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                 
                 
                 
                 $path = createSharedLink( $tgfilename );
                 return $path;

}

 function createSharedLink($path)
    {
        $url = "https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings";

        $ch = curl_init();

         $dropboxToken = getenv('DROPBOXACCESSTOKEN');
           
           
        $headers = array(
            'Authorization: Bearer ' . $dropboxToken,
            'Content-Type: application/json',
        );

        $post = array(
            "path" => "{$path}", //ファイルパス
            "settings" => array(
                "requested_visibility" => array(
                    ".tag" => "public" //公開
                ),
            ),
        );

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($post),
            CURLOPT_RETURNTRANSFER => true,
        );

        curl_setopt_array($ch, $options);

        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $link = "";
        if (!curl_errno($ch) && $http_code == "200") {
            $res = (array)json_decode($res);
            if ($res["url"]) {
                $link = $res["url"];
            } elseif ($res["error"]) {
                //既に設定済みなど
                $error = (array)$res["error"];
                print_r("WARNING: Failed to create shared link [{$path}] - {$error['.tag']}" . PHP_EOL);
            }
        } else {
            print_r("ERROR: Failed to access DropBox via API" . PHP_EOL);
            print_r(curl_error($ch) . PHP_EOL);
        }

        curl_close($ch);

        return $link;
    }
    
    

function getClient() {
    $client = new Google_Client();
    
    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');

 //   $client->setApplicationName(getenv('APPLICATION_NAME'));
 //   $client->setScopes(SCOPES);
    
    
   
   $auth_str = getenv('authstr');
   
   $auth_config = json_decode($auth_string, true);
   
   $client->setAuthConfig($auth_config);
   $client->setAccessType('offline');
    

  // $token_str = getenv('tokenstr');
  
  //      $accessToken = json_decode($token_str, true);
   //     $client->setAccessToken($accessToken);
   
 

    // Refresh the token if it's expired.
 //   if ($client->isAccessTokenExpired()) {
 //       $client->fetchAccessTokenWithRefreshToken( getenv('refreshtoken'));

  //  }
    return $client;
}



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
        
         continue;
 
   
      }
   
          
          
    
      if ($event instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage) {  //  イメージメッセージの場合
            
            $message_id = $event->getMessageId();
            
            $response = $bot->getMessageContent($message_id );
            
            if ($response->isSucceeded()) {
            
            

                 $filepath =  upload_contents( 'image' , 'jpg', 'application/octet-stream', $response );
                 
          
                $bot->replyText($event->getReplyToken(), "画像共有リンク   ${filepath} ");
                
                AddImageLink( $response, $event, $filepath );
                
                
                continue;

        
				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}

     
     
            $bot->replyText($event->getReplyToken(), "イメージイベント   ${message_id} 共有失敗");
     
     
          continue;
          
        }
        
      
      
       if ($event instanceof \LINE\LINEBot\Event\MessageEvent\AudioMessage) {  //  オーディオメッセージの場合  debug
            
             $message_id = $event->getMessageId();
            
            $response = $bot->getMessageContent($message_id );
            
            if ($response->isSucceeded()) {
          
                 $filepath =  upload_contents( 'voice' , 'mp4', 'application/octet-stream', $response );
                 
          
                $bot->replyText($event->getReplyToken(), "音声共有リンク   ${filepath} ");
                
                continue;

        
				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}

     
     
     
     
            $bot->replyText($event->getReplyToken(), "音声イベント   共有エラー");
     
     
          continue;
          
        }
        
      
       if ($event instanceof \LINE\LINEBot\Event\MessageEvent\VideoMessage) {  //  ビデオメッセージの場合
            
              
             $message_id = $event->getMessageId();
            
            $response = $bot->getMessageContent($message_id );
            
            if ($response->isSucceeded()) {
          
                 $filepath =  upload_contents( 'video' , 'mp4', 'application/octet-stream', $response );
                 
          
                $bot->replyText($event->getReplyToken(), "ビデオ共有リンク   ${filepath} ");
                
                continue;

        
				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}

     
     
     
     
            $bot->replyText($event->getReplyToken(), "ビデオイベント   共有エラー");
     
     
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
         continue;
        }
     
      }
    
 
 
     if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {  //  テキストメッセージの場合
            $tgText=$event->getText();
            
          
     
            $bot->replyText($event->getReplyToken(), "テキストメッセージ   line://nv/location  ${tgText}");
     
     
          continue;
          
        }
        

         
     
   
        $bot->replyText($event->getReplyToken(), "その他メッセージ　  line://nv/location ");s
        
   }

