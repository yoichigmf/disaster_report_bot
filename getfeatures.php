<?php

require_once __DIR__ . '/vendor/autoload.php';

require 'functions.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

date_default_timezone_set('Asia/Tokyo');

header("Content-Type: application/json; charset=UTF-8"); //ヘッダー情報の明記。必須。



 $sheetname = filter_input(INPUT_POST,"sheetname"); //変数の出力。jQueryで指定したキー値optを用いる

 $sheetid= filter_input(INPUT_POST,"sheetid"); //変数の出力。jQueryで指定したキー値optを用いる

$envname  = getenv('SHEET_NAME');
$envid= getenv('SPREADSHEET_ID');
 //$sheetname = 'シート1';
 $spreadsheetId = getenv('SPREADSHEET_ID');

 if ( $sheetid ){
     $spreadsheetId = $sheetid;
 }

$client = getGoogleSheetClient();
 if( !$sheetname ) {
     $sheetname  = getenv('SHEET_NAME');
     if( !$sheetname ) {
          //$sheetname = 'シート1';
          $sheetname = GetFirstSheetName( $spreadsheetID, $client );
     }
 }




$sheetd = GetSheet( $spreadsheetId, $sheetname, $client );



$isdone = false;

$output_ar = array();    // array of output data

$uid_ar = array();   //  array of user id

$non_loc_ar = array();  // array of non location data

$ckey = 0;

$non_locr = array();    //  arrray of non location data for a user

foreach ($sheetd as $index => $cols) {

//echo "\nindex ${index}  ";  //////

     $dated = $cols[0];
     $userd = $cols[1];

     $kind = $cols[2];
     $url  = $cols[3];

     $stext = $cols[4];


 if ( strcmp( $kind ,'location' ) == 0 ) {   //  if record is location data

   //  echo "\nkind ${kind}  ";  sample



        $xcod =$cols[6];    //  coordinate
        $ycod = $cols[5];

        if (array_key_exists( $userd, $uid_ar)){   //  is the user id in the array ?

            $ckey = $uid_ar[$userd] + 1;
            $uid_ar[$userd] = $ckey;
               }
        else   {
            $ckey = 0;
            $uid_ar[$userd] = $ckey;

            //$non_loc_ar[$userd] = array();
            }

         $arkey = $userd . "_" . $ckey ;

         $atrarray = array();

         $location_rec = array();

         $head = array();

         $head['vkey'] = $arkey;
         $head['user'] = $userd;
         $head['date'] = $dated;
         $head['x'] = $xcod;
         $head['y'] = $ycod;
         $head['kind'] = $kind;
         $head['stext'] = $stext;

         $attr = array();

         $attr['日付'] = $dated;
         $attr['ユーザ'] = $userd;
         $attr['種別'] = $kind;
         $attr['TEXT'] = $stext;
         $attr['url'] = $url;

         $atrarray[] = $attr;

         $location_rec[ 'location'] = $head;

         $location_rec[ 'attribute'] = $atrarray;


         $output_ar[$arkey] =$location_rec;

         //echo ${topc};
        // echo sprintf(' \\"type\\":\\"Feature\\",\\"geometry\\":{\\"type\\": \\"Point\\", \\"coordinates\\":[%s,%s]}, \\"properties\\":{\\"日付\\":\\"%s\\",\\"ユーザ\\":\\"%s\\",\\"種別\\":\\"%s\\",\\"uid\\":\\"%d\\",\\"url\\":\\"%s\\",\\"テキスト\\":\\"%s\\"}}',$xcod,$ycod, $dated,$userd,$kind,$ckey,$url,$stext);



       }    // location
       else  {



       if ( $index > 0 ){


           if (array_key_exists( $userd, $uid_ar)){


                   $ukey = $uid_ar[$userd];


                  }
            else  {


                    $output_ar[$arkey]['attribute'] = array();
                  }
                  $attr = array();

                     $attr['日付'] = $dated;
                     $attr['ユーザ'] = $userd;
                     $attr['種別'] = $kind;
                     $attr['TEXT'] = $stext;
                     $attr['url'] = $url;

          // $non_locr = array( "日付"=> $dated,"ユーザ"=>$userd, "種別"=>$kind, 'url'=>$url, 'TEXT'=> $stext );

           $output_ar[$arkey]['attribute'] [] = $attr;
          }
       }

     }  //  foreach

     $retjson = json_encode( $output_ar );      // make json
     echo $retjson;

?>
