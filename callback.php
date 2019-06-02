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

date_default_timezone_set('Asia/Tokyo');




function  AddFileLink( $response, $event, string $filepath, string $kind ){

    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');


        
    $service = new Google_Service_Sheets($client);

    
    $date    = date('Y/m/d h:i:s');
    
    $user = "kayama";
   
    
    $url = $filepath;
    $comment = $event->originalContentUrl;
    
     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    var_dump($resp);
    
}




function AddText( $response, $event ){
   $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');

   //$title = $event->getTitle();
   //$address = $event->getAddress();
  // $latitude = strval (  $event->getLatitude());
  // $longitude = strval ( $event->getLongitude());
        
    $service = new Google_Service_Sheets($client);

    
    $date    = date('Y/m/d h:i:s');
    
    $user = "kayama";
    $kind = "text";
    
  
    
    $url = "";
    $comment = $event->getText();
    
     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    var_dump($resp);
    

}




function AddLocationLink( $response, $event ){
   $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');

   $title = $event->getTitle();
   $address = $event->getAddress();
   $latitude = strval (  $event->getLatitude());
   $longitude = strval ( $event->getLongitude());
        
    $service = new Google_Service_Sheets($client);

    
    $date    = date('Y/m/d h:i:s');
    
    $user = "kayama";
    $kind = "location";
    
    $url = "";
    $comment = "";
    
     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment, $latitude, $longitude ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    var_dump($resp);
    

}


function  AddImageLink( $response, $event, string $filepath ){

    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');


        
    $service = new Google_Service_Sheets($client);

    
    $date    = date('Y/m/d h:i:s');
    
    $user = "kayama";
    $kind = "image";
    
    $url = $filepath;
    $comment = $event->originalContentUrl;
    
     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    var_dump($resp);
    
}

define('APPLICATION_NAME', 'Disaster report');
define('GSCOPES', implode(' ', array(
        Google_Service_Drive::DRIVE)
));


function upload_contents_gdr( $kind , $ext, $mime_type, $folder_id, $response ) {  // ファイルのGoogle Driveアップロード

          $filename = make_filename( $kind, $ext );

// Get the API client and construct the service object.
         $client = getClient();
         $client->setApplicationName(APPLICATION_NAME);        
         
         $client->setScopes(GSCOPES);

         $service = new Google_Service_Drive($client);


   global $log;
   $log->addWarning("file name ${filename}\n");
   
$fileMetadata = new Google_Service_Drive_DriveFile(array(
    'name' => $filename,
    'parents' => array($folder_id),
));

 //   'mimeType' => 'image/jpeg',

$content = $response->getRawBody();

$file = $service->files->create($fileMetadata, array(
    'data' => $content,
    'mimeType' => $mime_type,
    'uploadType' => 'multipart',
    'fields' => 'id'));
    
    return $file->alternateLink;

}

function make_filename( $kind, $ext ){  //  make unique file name


           $tempFilePath = tempnam('.', "${kind}-");
           unlink($tempFilePath);
           $filePath = $tempFilePath . ".${ext}";
           $filename = basename($filePath);
           
           return $filename;
}


//  $kind   'image'  'video'  'voice'
//  $ext    'jpg'    'mp4'    'mp4'
//  $content_type  application/octet-stream


function upload_contents( $kind , $ext, $content_type, $response ) {  // ファイルのDropBoxアップロード
          global $log;
          
          
          $log->addWarning("upload contents in\n");
          
 //          file upload           
 
           
           $filename = make_filename( $kind, $ext );
  
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


 //   $client->setApplicationName(getenv('APPLICATION_NAME'));
 //   $client->setScopes(SCOPES);
    
    
   
   $auth_str = getenv('authstr');
   
   $json = json_decode($auth_str, true);
   
   
  
    
    
   
    
     $client = new Google_Client();
     
    $client->setAuthConfig( $json );
    
   // $client->setAssertionCredentials($credentials);
    
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);



    $client->setApplicationName('AddSheet');
    
  //  if ($client->getAuth()->isAccessTokenExpired()) {
   //     $client->getAuth()->refreshTokenWithAssertion();
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
        
        AddLocationLink( $response, $event );
         continue;
 
   
      }
   
          
          
      
      if ($event instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage) {  //  イメージメッセージの場合
            
            $message_id = $event->getMessageId();
            
            $response = $bot->getMessageContent($message_id );
            
            if ($response->isSucceeded()) {
            
            
            
          	   $image_folder_id = getenv('IMAGE_FOLDER_ID');
               // $filepath =  upload_contents( 'image' , 'jpg', 'application/octet-stream', $response );
                $filepath =  upload_contents_gdr( 'image' , 'jpg', 'image/jpeg', $image_folder_id , $response );
          
                $bot->replyText($event->getReplyToken(), "画像共有リンク   ${filepath} ");
                
               //AddImageLink( $response, $event, $filepath );
                
                AddFileLink( $response, $event, $filepath, "image"  );
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
                
                AddFileLink( $response, $event, $filepath, "voice"  );
                
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
                
                   AddFileLink( $response, $event, $filepath, "video"  );
                continue;

        
				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}

     
     
     
     
            $bot->replyText($event->getReplyToken(), "ビデオイベント   共有エラー");
     
     
          continue;
          
              
          
        }
            
    if ($event instanceof \LINE\LINEBot\Event\MessageEvent\FileMessage) {  //  ファイルメッセージの場合
            
            
          
     
            $bot->replyText($event->getReplyToken(), "ファイルイベント   line://nv/location ");
     
                   
           AddFileLink( $response, $event, $filepath, "file"  );
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
            
          
           AddText( $response, $event  );
           
                
     
            $bot->replyText($event->getReplyToken(), "テキストメッセージ   line://nv/location  ${tgText}");
     
     
          continue;
          
        }
        

         
     
   
        $bot->replyText($event->getReplyToken(), "その他メッセージ　  line://nv/location ");
        
   }

