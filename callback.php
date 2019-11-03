<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__.'/php/report_bot.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));


$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LineMessageAPIChannelAccessToken'));

$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);

$sign = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

$events = $bot->parseEventRequest(file_get_contents('php://input'), $sign);

date_default_timezone_set('Asia/Tokyo');


$slack_hook_url = getenv('SlackHookURL');

$slack_dist_channel  = getenv('SlackdistChannel');





$page = 1;
$action ="";

$score = -1;



foreach ($events as $event) {



   if ($event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage) {  // Location event


        $title = $event->getTitle();
        $address = $event->getAddress();
        $latitude = $event->getLatitude();
        $longitude = $event->getLongitude();




       $tst =  AddLocationLink( $response, $event );

        if ( $tst ) {
          $bot->replyText($event->getReplyToken(), "入力位置情報 ${title} ${address} ${latitude} ${longitude}");
          }
        else
           {
                    $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\n入力位置情報 ${title} ${address} ${latitude} ${longitude}");



             }
         continue;


      }




      if ($event instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage) {  //  イメージメッセージの場合

            $message_id = $event->getMessageId();



            $response = $bot->getMessageContent($message_id );

            if ($response->isSucceeded()) {





                $filepath =  upload_contents( 'image' , 'jpg', 'application/octet-stream', $response );


                $tst = AddFileLink( $response, $event, $filepath, "image"  );

                if ( $tst ) {
                                $bot->replyText($event->getReplyToken(), "画像共有   ${filepath} ");

                }
                else {

                                       $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\n画像共有   ${filepath} ");
                }






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


                //  mp4 ファイルの保存
                $tmp4 = make_filename_path( "voice", "mp4" );

                $fcontents = $response->getRawBody();

                file_put_contents( $tmp4, $fcontents );


                $tflc = make_filename_path( "voice", "flac" );


                //  mp4  -> flac への変換
                shell_exec("ffmpeg -i ${tmp4} -vn -ar 16000 -ac 1 -acodec flac -f flac ${tflc}");

                //  mp4 ファイルの削除

                unlink( $tmp4 );

                //  flac ファイルのテキスト変換

                $voicetext = getTextFromAudio( $tflc );


                unlink( $tflc );

                $tst =  AddAudioFileLink( $response, $event, $filepath, "voice" ,${voicetext} );


                if ( $tst ) {
                $bot->replyText($event->getReplyToken(), "音声共有   ${filepath} ${voicetext}");
                  }
                else  {
                    $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\n音声共有   ${filepath} ${voicetext}");

                }



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


                 $tst =  AddFileLink( $response, $event, $filepath, "video"  );

                 if ( $tst ) {
                     $bot->replyText($event->getReplyToken(), "ビデオ共有   ${filepath} ");
                     }
                  else {
                     $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\nビデオ共有   ${filepath} ");

                  }

                continue;


				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}





            $bot->replyText($event->getReplyToken(), "ビデオイベント   共有エラー");


          continue;



        }

    if ($event instanceof \LINE\LINEBot\Event\MessageEvent\FileMessage) {  //  ファイルメッセージの場合 debug  2019.11.2

             $message_id = $event->getMessageId();

            $response = $bot->getMessageContent($message_id );
            
            $fname = $event->getFileName();
            
            $fpath = pathinfo($fname);
            
            $ext = $fpath['extension'];
            
                  


          $log->addWarning("file name   ${fname}\n");
          $log->addWarning("extention  ${ext}\n");        

           $filepath =  upload_contents( 'file' , $ext, 'application/octet-stream', $response );



            $tst = AddFileLink( $response, $event, $filepath, "file"  );


            if ( $tst ) {
            $bot->replyText($event->getReplyToken(), "ファイルアップロード  ${fname}   ${filepath} ");
              }
            else  {

                $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\nファイルアップロード  ${fname}   ${filepath}");
            }

          continue;

        }


   if ($event instanceof \LINE\LINEBot\Event\JoinEvent) {  // Join event add


    $log->addWarning("join event!\n");
   //$bot->replyText($event->getReplyToken(), "友達追加ありがとうございます");
   displayShortHelp( $bot, $event );

     //  firstmessage( $bot, $event,0);
       continue;

   }


  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent) ||
      !($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {

      if (!($event instanceof \LINE\LINEBot\Event\PostbackEvent) ) {

          displayShortHelp( $bot, $event );
        // $bot->replyText($event->getReplyToken(), " なんかのイベント発生");

             continue;
      }
     else  {

       $bot->replyText($event->getReplyToken(), "post back event");
         continue;
        }

      }



     if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {  //  テキストメッセージの場合
            $tgText=$event->getText();


           //  テキスト１文字目が # の場合はコメントとみなしてスキップする  20190621

           $chktext  = substr( $tgText, 0, 1 );


           if ( strcmp($chktext, "#" ) == 0 ) {


                   $spreadsheetId = getenv('SPREADSHEET_ID');

                    if ( strcmp($tgText, "#map" ) == 0 ) {   //  display map URL

                       $bot->replyText($event->getReplyToken(), "地図表示     https://reportmap.herokuapp.com/?sheetid=${spreadsheetId}");   //map urL
                       }

                   if ( strcmp($tgText, "#sheet" ) == 0 ) {   //  display sheet URL

                       $bot->replyText($event->getReplyToken(), "集計シート (閲覧)     https://docs.google.com/spreadsheets/d/${spreadsheetId}/edit?usp=sharing");   //sheet urL
                       }
                  if ( strcmp($tgText, "#list" ) == 0 ) {   //  display sheet URL

                       $bot->replyText($event->getReplyToken(), "集計シート (閲覧)     https://docs.google.com/spreadsheets/d/${spreadsheetId}/edit?usp=sharing");   //sheet urL
                       }



                    if ( strcmp($tgText, "#help" ) == 0 ) {   //  display help
                         displayHelp( $bot, $event );

                       }


                   continue;
                   }

            $tst = AddText(  $event  );

            if ( $tst ) {



                $bot->replyText($event->getReplyToken(), "テキストメッセージ    ${tgText}");
                }
             else {

                             $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\nテキストメッセージ    ${tgText}");
             }


          continue;

        }





        $bot->replyText($event->getReplyToken(), "その他メッセージ   ");

   }
