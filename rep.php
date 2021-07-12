<?php

require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__.'/php/report_post.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

$appname = getenv('APPLICATION_NAME');

$spreadsheetId = getenv('SPREADSHEET_ID');
//   書き込み対象シートの名前を取得しておく
$target_sheetname = GetTargetSheetName( $spreadsheetId);

# $log->warning("target_sheet  ${target_sheetname}\n");

if (count($_POST) ==0 ) {

	http_response_code( 400 );
	 $log->warning("no args");
	
	exit;
}
else {

	session_start();

        $token_str = $_POST["token"];
        $cmd =   $_POST["command"];
        
        $log->warning("token  ${token_str}\n");
        $log->warning("cmd  ${cmd}\n");

	if ( $_POST["command"] == "START" ) {
        # check token

               $url = 'https://api.line.me/oauth2/v2.1/verify?access_token='.$token_str;
	       $session = curl_init();
	       curl_setopt($session, CURLOPT_URL, $url );
	       curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	       $r = curl_exec($session);

	       curl_close($session);

	       $res = json_decode($r);


	       if ( $res == null ) {
                   http_response_code( 401 );
                   print("unauthorized\n");
		       print "error";
		   exit;
	       }

          //     $nowt = new DateTime("now");
	      // if (  $res->expires_in < $nowt ){

                            //  expire 
	      // }
 	      $_SESSION["token"] = $token_str;
               http_response_code( 200 );
                $log->warning("START \n");
	       #
	       print $r;
	       #
	       print $res->expires_in;
	     
        	#$_SESSION["token"] = $token_str;
	         exit;

	}

	#   token check
	if (  strcmp($token_str, $_SESSION["token"]) != 0  ) {
                   http_response_code( 401 );
                   $log->warning("unauthorized token is not same ${token_str}\n");
                  $log->warning("session token  ". $_SESSION["token"] );
                   print("unauthorized\n");
                   exit;
	       }

	if ( $_POST["command"] == "DATA" ) {

        #  data kind
        #   0   text
        #    1   image
        #    2   movie
        #    3  voice
        #    4  file
        
        
                $log->warning("data kind ");
                $log->warning($_POST["kind"] );
                
          

                $log->warning("data note ");
                $log->warning($_POST["note"] );
    	#  data regist
		#
		
		    $user = $_POST["user"];
		     $timestr = $_POST["postDate"];
		     
		     
		     $transact_id = $_POST["transact_id"];
		     
		     $lat = $_POST["lat"];
		     $lon = $_POST["lon"];
		     $pgname = "reportpost";    
		     
		
		if ( $_POST["kind"] ==  0 ) {  #  text
		
		     $tgText=$_POST["note"];
		     

		     $kind = "text";
		     

		     
		      AddText(  $user, $timestr, $lat, $lon, $tgText, $kind, $pgname , $transact_id);   #  エラーハンドリングが必要
		
		}
		
		if ( $_POST["kind"] ==  1 ) {  #  image
		
		       $kind = "image";
		       $ext = "jpg";
		       
		       $stream = base64_decode($_POST["image"]);
		       
		       $filename = upload_contents( $kind , $ext, 'application/octet-stream', $stream ,$appname );
		       
		       AddFileLink(  $user, $timestr, $lat, $lon, $filename, $kind, $pgname , $transact_id);
		
		
		
		}
		
		if ( $_POST["kind"] ==  2 ) {  #  video
		
		       $kind = "video";
		       $ext = "mp4";
		       
		       $stream = base64_decode($_POST["image"]);
		       
		       $filename = upload_contents( $kind , $ext, 'application/octet-stream', $stream ,$appname );
		       
		       AddFileLink(  $user, $timestr, $lat, $lon, $filename, $kind, $pgname , $transact_id);
		
		
		
		}
		
			
		#
               http_response_code( 200 );
                $log->warning("DATA\n");
              #  print("token " . $token_str);
              # $log->warning("\n session token " . $_SESSION["token"]);
                
                
                
#
	         exit;
	     }


	



	if ( $_POST["command"] == "END" ) {
               http_response_code( 200 );
        	unset($_SESSION["token"]);
	        session_destroy();
                print("END\n");

		}


       print( "command " .  $_POST["command"]);	
	exit;
}

// JSON文字列をobjectに変換
//   ⇒ 第2引数をtrueにしないとハマるので注意

//
// デバッグ用にダンプ
#
print "OK";
#var_dump($contents);
?>
