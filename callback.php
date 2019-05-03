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
            
            $address = urlencode ( $tgText );
            
            $tgurl = "https://msearch.gsi.go.jp/address-search/AddressSearch?q=${address}";
     
 //    $bot->replyText($event->getReplyToken(), "位置情報を送ると近くのトイレを探します  line://nv/location または住所を入力して下さい ${tgText}");
     
     
           $timeout = "200";
     $log->addWarning("url  ${tgurl}\n");

       $retar = getApiDataCurl($tgurl, $timeout );
       
       if ( count($retar) == 0) {
       //    住所にデータがない  or 文字列が住所ではない
        $bot->replyText($event->getReplyToken(), "位置情報を送ると近くのトイレを探します  line://nv/location または住所を入力して下さい ${tgText}");
          }
          else  { //  該当住所があった
          
          
          $fst = $retar[0];
          
          $geom = $fst["geometry"];
          $log->addWarning("geom  ${geom}\n");     
          $coord = $geom["coordinates"];
          $log->addWarning("coord  ${coord}\n");
        
                  
          $lon = $coord[0];
          $lat = $coord[1];
          
          $status = SearchToiletData( $bot, $event, $lat, $lon, $query  );         
          
          $bot->replyText($event->getReplyToken(), "位置情報を送ると近くのトイレを探します  line://nv/location または住所を入力して下さい lat ${lat} lon ${lon}  ${tgText}");
          
          }
       
          continue;
          
        }
     
   
        $bot->replyText($event->getReplyToken(), "位置情報を送ると近くのトイレを探します  line://nv/location または住所を入力して下さい");
        
   }


function SearchToiletData( $bot, $event, $lat, $lon , $query ) {
   
   
        global  $log;

        
             
        $retar = GetToiletIndex( $lat, $lon );
        
                
        //$log->addWarning("\nreturn ->${retar}\n");
        
        
        
        $ft = $retar["features"];
        
        // $log->addWarning("  ft ${ft}\n");
        
        $firstd = $ft[0];
        
      //  $log->addWarning("  firstd ${firstd}\n");
            
        $properties = $firstd["properties"];
        
        
        $toiletname = $properties["name"];
        $sheetname = $properties["tbname"];
        
        $tgid = $properties["kid"];
        
        $tid = $properties["id"];
        
      //  "kid":2890,"tbname":"park_barrier_free_wc",
        
        

         if ( $toiletname ) {
         
     
           if ( $sheetname == "shinagawa_toilet" ) {  //  品川区データ
           
                $ret =  query_toilet( $bot, $event, $sheetname, $tid);
                 
                 $ret = $bot->replyText($event->getReplyToken(), "近くのトイレ  ${toiletname}");
                 return $ret;
           
           }
           
           
           if ( $sheetname == "taito_public_toilet" ) {  //  台東区データ
           
                $ret =  query_toilet( $bot, $event, $sheetname, $tid);
                 
                 $ret = $bot->replyText($event->getReplyToken(), "近くのトイレ  ${toiletname}");
                 return $ret;
           
           }
           
           
          if ( $sheetname == "suginami_kouen" ) {  //  杉並区データ
           
                $ret =  query_toilet( $bot, $event, $sheetname, $tid);
                 
                 $ret = $bot->replyText($event->getReplyToken(), "近くのトイレ  ${toiletname}");
                 return $ret;
           
           }
           
     
            if (( $sheetname == "park_barrier_free_wc" ) || ( $sheetname == " cultural_facilities_barrier_free_wc" ) ) {
            
                 $ret =  query_toilet( $bot, $event, $sheetname, $tid);
                 
                 $ret = $bot->replyText($event->getReplyToken(), "近くのトイレ  ${toiletname}");
                 return $ret;
                 }
                 
            $log->addWarning("  toiletname ${toiletname}\n");
            
            $ret = $bot->replyText($event->getReplyToken(), "近くのトイレ  ${toiletname}");
   
            return $ret;
            }
            
         else {
            $ret = $bot->replyText($event->getReplyToken(),
             "近くのトイレ情報がみつかりません。位置情報を送ると近くのトイレを探します  line://nv/location または住所を入力して下さい");
   
            return $ret;         
         
         }
  }
         //  Google Spread Sheet にトイレ情報をクエリかける
function  query_toilet( $bot, $event, $sheetname, $tid) {

global $log;

$turl = "https://script.google.com/macros/s/AKfycbwC3bl1dLdFpS8qqRJJbQbPs9YlzWG_UXiip5XoUzFwUIRyBSqf/exec?action=gettoilet&sheetname=${sheetname}&key=${tid}";

$timeout = "200";
  $log->addWarning("url  ${turl}\n");

   $retar = getApiDataCurl($turl, $timeout );
   
   
    $tgar  = $retar["response"];
    
    $log->addWarning("return  ${tgar}\n");
    
    $ttext ="近くのトイレ情報";
    
    $latdef = false;
    $londef = false;
  //  $vstext = "c1j0l0u0t0z0r0f0";    
    $vstext = "c1j0h0k0l0u0t0z0r0s0f1";
    
    foreach ( $tgar as $key => $value ) {
    
      if (is_display( $key )) {
      
      if ( $key === "緯度" ) {
      
        $lat = $value;
        $latdef = true;
        
        if ( $latdef and $londef ) {
           $ttext =  $ttext ."\n"."地図:https://maps.gsi.go.jp/#16/${lat}/${lon}/&base=std&ls=std&disp=1&vs=${vstext}";
         }
         continue;
      }
      
      if ( $key === "経度" ) {
      
         $londef = true;
      
          $lon = $value;
             if ( $latdef and $londef  ) {
            $ttext = $ttext ."\n"."地図:https://maps.gsi.go.jp/#16/${lat}/${lon}/&base=std&ls=std&disp=1&vs=${vstext}";
            }
            
          continue;
      }
      $ttext = $ttext ."\n". $key .":".$value;
      }
    
    }
    //$sisetumei = $tgar["施設名"];
   // $toiletname = $tgar["トイレ名"];
 
        $log->addWarning($ttext);
        
            $ret = $bot->replyText($event->getReplyToken(), $ttext);
   return $retar;
} 

function is_display( $keyt ) {

    if ( empty( $keyt ) ) {
         return false;
         }
    $notdisplay= array(
        "id",        
        "Baiduspider",      
        "施設通し番号",       
        "施設内トイレ通し番号",        
        "座標系"
    );
     foreach ($notdisplay as $keyword) {
        if ($keyt === $keyword) {
            return false;
        }
    }
        
         

    return true;
}






//  緯度経度情報から近隣トイレのインデックス情報を返す

function  GetToiletIndex( $lat, $lon ) {

// cx 入力  経度
//  cy 入力  緯度

//  bxo ボックス　緯度　最小値
//  byo ボックス　経度　最小値
//  bxc ボックス　緯度　最大値 
//  byc ボックス　経度　最大値
//  iwidth   イメージ幅
//  iheight  イメージ高さ

global $log;

$bxo = 139.2630463;
$byo = 35.58720779;
$byc =  35.8024559;
$bxc = 139.9567566;
$iheight = 330;
$iwidth = 1063;



$cx = $lon;
$cy = $lat;
 
$dx = $cx - $bxo;
$dy = $cy - $byo;

 // $log->addWarning("x ${cx}  y ${cy} \n");

// $log->addWarning("dx ${dx}  dy ${dy} \n");
$px = ($dx / ($bxc - $bxo) )* $iwidth;

$py = $iheight -( ( $dy/ ( $byc - $byo )) * $iheight );

//$log->addWarning("px ${px}  py ${py} \n");

$px = round( $px );
$py = round( $py );


$turl = "http://tk2-207-13336.vs.sakura.ne.jp/geoserver/toilet/wms?service=WMS&version=1.1.0&request=GetFeatureInfo&layers=toilet:boronoi&query_layers=toilet:boronoi&styles=&bbox=139.263046264648,35.5872077941895,139.956756591797,35.8024559020996&width=1063&height=330&srs=EPSG:4326&info_format=application/json&x=${px}&y=${py}";


  $timeout = "200";
//  $log->addWarning("url  ${turl}\n");

   $retar = getApiDataCurl($turl, $timeout );
   
   return $retar;
   
}

//  Google Sheet から列を取得する

function getApiDataCurl($url, $timeout )
{
   
global $log;


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout );

curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
//最大何回リダイレクトをたどるか
curl_setopt($ch,CURLOPT_MAXREDIRS,10);
//リダイレクトの際にヘッダのRefererを自動的に追加させる
curl_setopt($ch,CURLOPT_AUTOREFERER,true);

$content = trim(curl_exec($ch));
    

    $info    = curl_getinfo($ch);
    $errorNo = curl_errno($ch);
    
    curl_close($ch);
    
    

    //p
    
    
    // OK以外はエラーなので空白配列を返す
    if ($errorNo !== CURLE_OK) {
$log->addWarning("error status  ${errorNo}\n");
        return [];
    }

    // 200以外のステータスコードは失敗とみなし空配列を返す
    if ($info['http_code'] !== 200) {
    $erno = $info['http_code'];
   $log->addWarning("http error status  ${erno}\n");
        return [];
    }

   // print "\nok\n";
     $log->addWarning( "success content = ${content}\n" );
    

    // 文字列から変換
    $jsonArray = json_decode($content, true);

    return $jsonArray;
}
















?>
