<?php
header('Content-Type: text/html; charset=UTF-8');
//require_once __DIR__ . '../vendor/autoload.php';



use Monolog\Logger;
use Monolog\Handler\StreamHandler;



function GetUserNameUsingID( $user_id ) {
//  $uid = $event->getUserId();

   global $log;
   global $httpClient;

   $bot2 = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);

   $response = $bot2->getProfile($user_id);

   $profile = $response->getJSONDecodedBody();

   $username = $profile['displayName'];

   //$log->addWarning("user name ${username}\n");

   $emp =  empty( $username );

   //$log->addWarning("empty  ${emp}\n");

   if ( $emp == 1 ) {
        $username = null;

   }

   return $username;

}


function GetUserName( $event ) {
  $uid = $event->getUserId();

   global $log;
   global $httpClient;

   $bot2 = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);

   $response = $bot2->getProfile($uid);

   $profile = $response->getJSONDecodedBody();

   $username = $profile['displayName'];

   $log->addWarning("user name ${username}\n");

   $emp =  empty( $username );

   $log->addWarning("empty  ${emp}\n");

   if ( $emp == 1 ) {
        $username = "不明";

   }

   return $username;

}


//    Slack へのPost
function  PostSlack($date, $user, $kind, $url ,$comment, $lat, $lon ) {

global $slack_hook_url;
global  $log;


if (! empty($slack_hook_url)){


  $sttext = "$date $user $kind $url $comment $lat $lon";
  $webhook_url = $slack_hook_url;


  $slack = new Slack($slack_hook_url);

  $message = new SlackMessage($slack);

  $message->setText($sttext);




  $log->addWarning("url ${webhook_url}\n");




  if ( $kind == "image" ) {    //  画像の場合画像本体のURLもポストする

    $nurl = str_replace( "www.dropbox.com", "dl.dropboxusercontent.com", $url );

    $nnurl = str_replace( "?dl=0", "", $nurl );

    $log->addWarning("new url ${nurl} nn ${nnurl}\n");

    $attachment = new SlackAttachment("画像");
    $attachment->setImage($nnurl);
    $message->addAttachment($attachment);
  }

   $message->send();


  return TRUE;

}

else {
    return TRUE;
   }
}


function  AddAudioFileLink( $response, $event, string $filepath, string $kind, string $trtext ){

    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');



    $service = new Google_Service_Sheets($client);


    $date    = date('Y/m/d H:i:s');

   //var_dump($event);


     //  ユーザ名の取得  debug
    $user = GetUserName($event);

    $comment = $trtext;
    $url = $filepath;
    //$comment = $event->originalContentUrl;

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );
     PostSlack($date, $user, $kind, $url ,$comment, "","");
    var_dump($resp);

     if ( $user === "不明" ){
        return FALSE;
        }
    else {
        return TRUE;
        }



}

function  GetTopSheetName( $spreadsheetID, $client ) {


  $sheets = Getsheets($spreadsheetID, $client);


  $top_sheet = $sheets[0];


}


function Getsheets($spreadsheetID, $client) {
    $sheets = array();
    // Load Google API library and set up client
    // You need to know $spreadsheetID (can be seen in the URL)


    $sheetService = new Google_Service_Sheets($client);
    $spreadSheet = $sheetService->spreadsheets->get($spreadsheetID);
    $sheets = $spreadSheet->getSheets();
    foreach($sheets as $sheet) {
        $sheets[] = $sheet->properties->sheetId;
    }
    return $sheets;
}

//  書き込み対象シート名を取得しておく　名前が　config でないもので先頭のシート
function GetTargetSheetName( $spreadsheetId){
  $client = getClient();


  $client->addScope(Google_Service_Sheets::SPREADSHEETS);
  $client->setApplicationName('AddSheet');

  $sheetnames = Getsheets($spreadsheetI, $client);

  if ( $sheetname[0] === 'config'){
    return $sheetname[1];
    }

   return $sheetname[0];

}

function  AddFileLink( $response, $event, string $filepath, string $kind ){

          global $log;




    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');



    $service = new Google_Service_Sheets($client);


    $date    = date('Y/m/d H:i:s');

   //var_dump($event);

    $orgfilename = "";

    $comment = "";   // Google Sheets 書き出し用コメント
    $ncomment = "";  //  slack 書き出し用コメント


              $log->addWarning("kind ${kind}\n");


    if ( $kind == "image") {

              $log->addWarning("image ${kind}\n");
      $imgurl = str_replace( "?dl=0", "?dl=1", $filepath );
      $orgfilename = "=image(\"${imgurl}\")";

      $comment =  "=image(\"${imgurl}\")";
    }
    else
    {

                $log->addWarning("not image ${kind}\n");
  //  $orgfilename = $event->getFileName();   //  元ファイル名

   //     $comment = $orgfilename;
     //   $ncomment = $comment;


    }



     //  ユーザ名の取得
    $user = GetUserName($event);


    $url = $filepath;


              $log->addWarning("comment ${comment}\n");



     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

     PostSlack($date, $user, $kind, $url ,$ncomment, "","");


    var_dump($resp);

   if ( $user === "不明" ){
        return FALSE;
        }
    else {
        return TRUE;
        }



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


    $date    = date('Y/m/d H:i:s');

    //  ユーザ名の取得
   $user = GetUserName($event);
    $kind = "text";



    $url = "";
    $comment = $event->getText();

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

   // Slack へのPost
    PostSlack($date, $user, $kind, $url ,$comment, "", "" );


    var_dump($resp);

   if ( $user === "不明" ){
        return FALSE;
        }
    else {
        return TRUE;
        }


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


    $date    = date('Y/m/d H:i:s');

    //  ユーザ名の取得
   $user = GetUserName($event);
    $kind = "location";

    $url = "";
    $comment = "${title} ${address}";

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment, $latitude, $longitude ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    PostSlack($date, $user, $kind, $url ,$comment, $latitude,$longitude);
    var_dump($resp);

   if ( $user === "不明" ){
        return FALSE;
        }
    else {
        return TRUE;
        }



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
function upload_contents( $kind , $ext, $content_type, $response ,$appname ) {
          global $log;


          $log->addWarning("upload contents in\n");

 //          file upload


           $filename = make_filename( $kind, $ext );

            $dropboxToken = getenv('DROPBOXACCESSTOKEN');


             $url = "https://content.dropboxapi.com/2/files/upload";
             $tgfilename = "/disasterinfo/${appname}/${kind}/${filename}";

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



                 $path = createSharedLink( $tgfilename );  //
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


//  flac オーディオファイルからテキストを取得する   debug
function getTextFromAudio( $tflc , $apikey){

       global $log;



$jsonArray = array();
$jsonArray["config"]["encoding"] = "FLAC";
$jsonArray["config"]["sampleRateHertz"] = 16000;
$jsonArray["config"]["languageCode"] = "ja-JP";
$jsonArray["config"]["enableWordTimeOffsets"] = false;

// $apikey = getenv("SPEECHAPIKEY");


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

    $log->addWarning("body --> ${body}\n");

   $result = json_decode($body, true);

   $transtext = $result["results"][0]["alternatives"][0]["transcript"];

   return $transtext;
}


function displayShortHelp( $bote, $evente ) {

     $helpstr = "参加ありがとうございます\n以下のコマンドをメッセージに打ち込むことができます\n\n";
     $helpstr .= "#help 利用方法表示\n";
     $helpstr .= "#map 地図表示URL表示\n";
     $helpstr .= "#list 一覧表表示URL表示\n";


      $bote->replyText($evente->getReplyToken(), $helpstr);
}


function displayHelp( $bote, $evente ) {

    $helpstr = "利用方法\n\n";
    $helpstr .= "システムの目的\n";

    $helpstr .= "LINEで皆様が投稿した位置情報,テキスト,写真,動画,音声をクラウド上のシートに保存して利用するためのシステムです。位置情報がはいっていると投稿した情報を地図で確認できます\n\n";

    $helpstr .= "グループでの利用\n";


    $helpstr .= "LINEの上で同じ情報を投稿する皆様とグループを作成して、そのグループにこのシステムを追加していただけると、他の人の投稿を見ながら投稿情報をクラウド上のシートに集めることができます\n";
    $helpstr .= "ただしグループに参加した方で災害情報収集用のチャットボットと友達になっていない方は必ずチャットボットと友達になっておいて下さい。\n";
    $helpstr .= "チャットボットと友達になっていないと投稿情報に投稿者のユーザ名が残らないので地図表示を行う場合うまく表示されなくなります。\n\n";

    $helpstr .= "位置情報の投稿\n";
    $helpstr .= "位置情報を投稿してからテキスト、写真、動画、音声を投稿してください\n";
    $helpstr .= "音声は1分間までの投稿が可能です。音声投稿は音声をテキスト化したテキストと音声データが保存されます\n";


 //    $helpstr .="位置情報投稿 line://nv/location \n";

     $helpstr .="同じ場所で連続して投稿する場合は最初に1回だけ位置情報を投稿してください。場所を変えて投稿する場合は最初に1回位置情報を投稿してください。位置情報を投稿しないと地図に表示されないか地図上のあやまった位置に表示されます。\n\n";


     $helpstr .="LINEのグループで本システムを利用する場合テキスト投稿の先頭1文字を # (半角のシャープ) で開始した投稿はスプレッドシートに保存されません。グループ内での情報共有を投稿する場合ご利用下さい\n\n";


     $helpstr .= "\n\n特殊コマンド\n";

     $helpstr .= "#map 地図表示URL表示\n";

     $helpstr .= "#list 一覧表表示URL表示\n";

      $helpstr .= "#help HELPメッセージ表示\n";
      $bote->replyText($evente->getReplyToken(), $helpstr);
}
