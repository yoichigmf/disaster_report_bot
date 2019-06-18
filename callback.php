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


function GetUserName( $event ) {
  $uid = $event->getUserId();

   global $log;
   global $httpClient;

   $bot2 = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);

   $response = $bot2->getProfile($uid);

   $profile = $response->getJSONDecodedBody();

   $username = $profile['displayName'];

   $log->addWarning("user name ${username}\n");

   return $username;

}



function  AddAudioFileLink( $response, $event, string $filepath, string $kind, string $trtext ){

    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');



    $service = new Google_Service_Sheets($client);


    $date    = date('Y/m/d h:i:s');

   //var_dump($event);


     //  ユーザ名の取得
    $user = GetUserName($event);

    $comment = $trtext;
    $url = $filepath;
    //$comment = $event->originalContentUrl;

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    var_dump($resp);

}


function  AddFileLink( $response, $event, string $filepath, string $kind ){

    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');



    $service = new Google_Service_Sheets($client);


    $date    = date('Y/m/d h:i:s');

   //var_dump($event);


     //  ユーザ名の取得
    $user = GetUserName($event);

    $comment = "";
    $url = $filepath;
    //$comment = $event->originalContentUrl;

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    var_dump($resp);

}




function AddText( $event ){
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

    //  ユーザ名の取得
   $user = GetUserName($event);
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

    //  ユーザ名の取得
   $user = GetUserName($event);
    $kind = "location";

    $url = "";
    $comment = "${title} ${address}";

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment, $latitude, $longitude ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    var_dump($resp);


}






function upload_contents_gdr( $kind , $ext, $mime_type, $folder_id, $response ) {  // ファイルのGoogle Driveアップロード

          $filename = make_filename( $kind, $ext );

// Get the API client and construct the service object.
         $client = getClient_drive();
         //$client->setApplicationName(APPLICATION_NAME);




   global $log;
   $log->addWarning("file name ${filename}\n");

$fileMetadata = new Google_Service_Drive_DriveFile(array(
    'name' => $filename,
    'parents' => array($folder_id),
));

 //   'mimeType' => 'image/jpeg',

$content = $response->getRawBody();

   $log->addWarning("get Raw\n");
var_dump($fileMetadata);

$service = new Google_Service_Drive($client);

   $log->addWarning("make service \n");
   $file = $service->files->create($fileMetadata, array(
    'data' => $content,
    'mimeType' => 'image/jpeg',
    'uploadType' => 'multipart',
    'fields' => 'id'));

    $file_id = $file->getId();

    $tfileurl = "https://drive.google.com/uc?id=${file_id}";
    //$tfilename = $file->alternateLink;
    $log->addWarning("make file ${tfileurl}\n");



    return $tfileurl;

}

function make_filename( $kind, $ext ){  //  make unique file name


           $tempFilePath = tempnam('.', "${kind}-");
           unlink($tempFilePath);
           $filePath = $tempFilePath . ".${ext}";
           $filename = basename($filePath);

           return $filename;
}


function make_filename_path( $kind, $ext ){  //  make unique file name full path


           $tempFilePath = tempnam('.', "${kind}-");
           unlink($tempFilePath);
           $filePath = $tempFilePath . ".${ext}";
          // $filename = basename($filePath);

           return $filePath;
}


//  $kind   'image'  'video'  'voice'
//  $ext    'jpg'    'mp4'    'mp4'
//  $content_type  application/octet-stream

//  content upload to dropbox
function upload_contents( $kind , $ext, $content_type, $response ) {
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

// create shared link of dropbox content
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


//  Google Spread Sheet 用クライアント作成
function getClient() {


   $auth_str = getenv('authstr');

   $json = json_decode($auth_str, true);


     $client = new Google_Client();

    $client->setAuthConfig( $json );


    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);



    $client->setApplicationName('AddSheet');

    return $client;


}


define('GSCOPES', implode(' ', array(
        Google_Service_Drive::DRIVE)
));

//   Google Drive 用クライアントの作成
function getClient_drive() {

    $client = new Google_Client();

    $client->setApplicationName('upload contents');
    $client->setScopes(GSCOPES);
   $auth_str = getenv('authstr_drv');

   $json = json_decode($auth_str, true);



    $client->setAuthConfig( $json );

    $token_str = getenv('token_drv');

    $accessToken = json_decode($token_str, true);

    $client->setAccessToken($accessToken);

 // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {

        $refresh_token= getenv('token_refresh');
        $client->fetchAccessTokenWithRefreshToken( $refresh_token );

    }



    return $client;


}

//  flac オーディオファイルからテキストを取得する
function getTextFromAudio( $tflc ){


$jsonArray = array();
$jsonArray["config"]["encoding"] = "FLAC";
$jsonArray["config"]["sampleRateHertz"] = 16000;
$jsonArray["config"]["languageCode"] = "ja-JP";
$jsonArray["config"]["enableWordTimeOffsets"] = false;

$apikey = ggetenv("SPEECHAPIKEY");


$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://speech.googleapis.com/v1/speech:recognize?key=${apikey}");
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, true);

    $jsonArray["audio"]["content"] = base64_encode(file_get_contents($tflc));
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($jsonArray));
    $response = curl_exec($curl);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
   $result = json_decode($body, true); 

   // $transtext = $result["results"][0]["alternatives"][0]["transcript"];
    rerurn "sample";
}



$page = 1;
$action ="";

$score = -1;



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

            //    $filepath =  upload_contents_gdr( 'image' , 'jpg', 'image/jpeg', $image_folder_id , $response );


                $filepath =  upload_contents( 'image' , 'jpg', 'application/octet-stream', $response );

                $bot->replyText($event->getReplyToken(), "画像共有リンク   ${filepath} ");


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
                // $audio_folder_id = getenv('AUDIO_FOLDER_ID');
                // $filepath =  upload_contents_gdr( 'voice' , 'mp4', 'audio/mp4', $audio_folder_id , $response );
                
                //  mp4 ファイルの保存
                $tmp4 = make_filename_path( "voice", "mp4" );

                
                file_put_contents ( $tmp4, $response->getRawBody() ) ；
                
                
                $tflc = make_filename_path( "voice", "flac" );
                
                
                //  mp4  -> flac への変換
                shell_exec("ffmpeg -i ${tmp4} -vn -ar 16000 -ac 1 -acodec flac -f flac ${tflc}");
                
                //  mp4 ファイルの削除
                
                unlink( $tmp4 );
                
                //  flac ファイルのテキスト変換
                
                $returnText = getTextFromAudio( $tflc );


                unlink( $tflc );
                
                $bot->replyText($event->getReplyToken(), "音声共有リンク   ${filepath} ${returntext}");

                AddAudioFileLink( $response, $event, $filepath, "voice" ,${returntext} );

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
                // $video_folder_id = getenv('VIDEO_FOLDER_ID');
                // $filepath =  upload_contents_gdr( 'video' , 'mp4', 'video/mp4', $audio_folder_id , $response );

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



           $filepath =  upload_contents( 'file' , 'bin', 'application/octet-stream', $response );

            $bot->replyText($event->getReplyToken(), "ファイルイベント   line://nv/location ");


           AddFileLink( $response, $event, $filepath, "file"  );
          continue;

        }


   if ($event instanceof \LINE\LINEBot\Event\JoinEvent) {  // Join event add


    $log->addWarning("join event!\n");
    $bot->replyText($event->getReplyToken(), "ありがとうございます");
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


           AddText(  $event  );



            $bot->replyText($event->getReplyToken(), "テキストメッセージ    ${tgText}");


          continue;

        }





        $bot->replyText($event->getReplyToken(), "その他メッセージ　   ");

   }
