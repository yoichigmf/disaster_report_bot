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

 if ( ! empty($sheetid)  ){
     $spreadsheetId = $sheetid;
 }

$client = getGoogleSheetClient();
 if( empty($sheetname)  ) {
     $sheetname  = getenv('SHEET_NAME');
     if( empty($sheetname)  ) {
          //$sheetname = 'シート1';
          $sheetname = GetFirstSheetName(  $spreadsheetId, $client );
     }
 }




$sheetd = GetSheet( $spreadsheetId, $sheetname, $client );



$isdone = false;


$geojson = array(
   'type'      => 'FeatureCollection',
   'features'  => array()
);


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
     
     $client_name = $col[7];
     
 
 ###  add 20210712  native client data
 if ( strcmp( $client_name ,'reportpost') ==0 ) {
 
        $xcod = (double)$cols[6];    //  coordinate
        $ycod = (double)$cols[5];
         $log->addWarning("reportpost\n");
    continue;
 }
 


 if ( strcmp( $kind ,'location' ) == 0 ) {   //  if record is location data
 
          $log->addWarning("line\n");

   //  echo "\nkind ${kind}  ";  sample



        $xcod = (double)$cols[6];    //  coordinate
        $ycod = (double)$cols[5];

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

         $atrar = array();

              //             $log->addWarning("feature id == ${arkey}  user == ${userd}");
         $feature = array(
           'id' => $arkey,
           'type' => 'Feature',
           'geometry' => array(
           'type' => 'Point',
       # Pass Longitude and Latitude Columns here
             'coordinates' => array((double)$xcod, (double)$ycod)
              ),
   # Pass other attribute columns here
           'properties' => array(
              'user' => $userd,
              'date' => $dated,
              'kind' => $kind,
              'text' => $stext,
              'url' => $url,
       'proplist' => $atrar
       )
   );

         array_push($geojson['features'], $feature);

       }    // location
       else  {



       if ( $index > 0 ){


           if (array_key_exists( $userd, $uid_ar)){


                   $ukeyd = $uid_ar[$userd];
                   $ukey = $userd . "_" . $ukeyd ;
                   //$arkey = $ukey;
                  }
            else  {


                  //  $output_ar[$arkey]['attribute'] = array();

                     $ukey = $arkey;

                  }
                  $attr = array();

                  /*   $attr['日付'] = $dated;
                     $attr['ユーザ'] = $userd;
                     $attr['種別'] = $kind;
                     $attr['TEXT'] = $stext;
                     $attr['url'] = $url;
*/

                     $atrdata = array(
                       'date'=> $dated,
                       'user' => $userd,
                       'kind' => $kind,
                       'text' => $stext,
                       'url'=> $url
                     );

                     $log->addWarning("attribute add  ${ukey}");
                     foreach ( $geojson['features'] as &$feat){

                          $fkey = $feat["id"];

                        //   $log->addWarning("fkey == ${fkey}");

                           if ( $feat["id"] === $ukey ){
                             $log->addWarning("add attribute success ============== ${ukey}");

                              array_push(  $feat["properties"]["proplist"], $atrdata );
                           }

                     }

          }
       }

     }  //  foreach

     $retjson = json_encode( $geojson  );      // make json
     echo $retjson;

?>
