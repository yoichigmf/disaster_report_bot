<?php
//  read configuration sheet
require_once __DIR__ . '/vendor/autoload.php';

require 'functions.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

date_default_timezone_set('Asia/Tokyo');

header("Content-Type: application/json; charset=UTF-8"); //ヘッダー情報の明記。必須。



 $sheetid= filter_input(INPUT_POST,"sheetid"); //変数の出力。jQueryで指定したキー値optを用いる


$envid= getenv('SPREADSHEET_ID');
 //$sheetname = 'シート1';
 $spreadsheetId = getenv('SPREADSHEET_ID');

 if ( $sheetid ){
     $spreadsheetId = $sheetid;
 }

$client = getGoogleSheetClient();


$sheetname = "config";

$sheetd = GetSheet( $spreadsheetId, $sheetname, $client );



$baselayer_ar = array();    // array of output data

$overlay_ar = array();   //  array of user id


$output_ar = array();    //  arrray of output data



foreach ($sheetd as $index => $cols) {

//echo "\nindex ${index}  ";  //////

$kind = $cols[0];

 if ( strcmp( $kind ,'#baselayer' ) == 0 ) {   //  start base layers mode

   //  echo "\nkind ${kind}  ";  sample

        $base_layer = array();

        $name =$cols[1];    //
        $kind = $cols[2];
        $url  = $cols[3];
        $attribute = $cols[4];
        $maxzoom = $cols[5];
        $minzoom = $cols[6];
        $legend  = $cols[7];
        $opacity = $cols[8];

        $base_layer["name"]=$name;
        $base_layer["kind"]= $kind;
        $base_layer["url"] = $url;
        $base_layer["attribute"] = $attribute;
        $base_layer["maxzoom"] = $maxzoom;
        $base_layer["minzoom"] = $minzoom;
        $base_layer["legend"] = $legend;

        $base_layer["opacity"] = $opacity;

        $baselayer_ar[] = $base_layer;


       }
     elseif ( strcmp( $kind ,'#overlay' ) == 0 ){

       $ovly_layer = array();

       $name =$cols[1];
       $kind = $cols[2];
       $url  = $cols[3];
       $attribute = $cols[4];
       $maxzoom = $cols[5];
       $minzoom = $cols[6];
       $legend  = $cols[7];
       $opacity = $cols[8];

       $ovly_layer["name"]=$name;
       $ovly_layer["kind"]= $kind;
       $ovly_layer["url"] = $url;
       $ovly_layer["attribute"] = $attribute;
       $ovly_layer["maxzoom"] = $maxzoom;
       $ovly_layer["minzoom"] = $minzoom;
       $ovly_layer["legend"] = $legend;
       $ovly_layer["opacity"] = $opacity;

       $overlay_ar[] = $ovly_layer;

      }

     }  //  foreach

     $output_ar["baselayers"]= $baselayer_ar;
     $output_ar["overlaylayers"]= $overlay_ar;

     $retjson = json_encode( $output_ar );      // make json
     echo $retjson;

?>
