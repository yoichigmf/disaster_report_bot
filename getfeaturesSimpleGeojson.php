<?php

require_once __DIR__ . '/vendor/autoload.php';

require 'functions.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

date_default_timezone_set('Asia/Tokyo');

header("Content-Type: application/json; charset=UTF-8"); //ヘッダー情報の明記。必須。



 $sheetname = filter_input(INPUT_GET,"sheetname"); //変数の出力。jQueryで指定したキー値optを用いる

 $sheetid= filter_input(INPUT_GET,"sheetid"); //変数の出力。jQueryで指定したキー値optを用いる

//$sheetname = "Copy";
//  add  


$log->addWarning("sheetname == ${sheetname}  sheetid == ${sheetid}");
$envname  = getenv('SHEET_NAME');
$envid= getenv('SPREADSHEET_ID');
 //$sheetname = 'シート1';
 $spreadsheetId = getenv('SPREADSHEET_ID');

 if ( ! empty($sheetid) ){
     $spreadsheetId = $sheetid;
 }

$client = getGoogleSheetClient();
 if(empty($sheetname) ) {
     $sheetname  = getenv('SHEET_NAME');
     if(empty($sheetname)) {
          //$sheetname = 'シート1';
          $sheetname = GetFirstSheetName( $spreadsheetId, $client );
     }
 }




$sheetd = GetSheet( $spreadsheetId, $sheetname, $client );



$isdone = false;


$geojson = array(
   'type'      => 'FeatureCollection',
   'features'  => array()
);



$uid_ar = array();   //  array of user id



$ckey = 0;



foreach ($sheetd as $index => $cols) {

//echo "\nindex ${index}  ";  //////

     $dated = $cols[0];
     $userd = $cols[1];

     $kind = $cols[2];
     $url  = $cols[3];

     $stext = $cols[4];


 if ( strcmp( $kind ,'location' ) == 0 ) {   //  if record is location data

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

        // $atrar = array();

              //             $log->addWarning("feature id == ${arkey}  user == ${userd}");
              
         $attr = makeattributetext( $dated, $kind, $stext, $url, 'xml');
          //$attrtext = '"attribute":"'. $attr . '"';
          
         $feature = array(
           'id' => $arkey,
           'type' => 'Feature',
           'geometry' => array(
           'type' => 'Point',
       # Pass Longitude and Latitude Columns here
             'coordinates' => array((double)$xcod, (double)$ycod)
              ),
   # Pass other attribute columns here
            //   'properties' => $attrtext
           'properties' => array(
        'attr' => "",
        'location' => $attr
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
                 // $attr = array();


                   $attrtext2 = makeattributetext( $dated, $kind, $stext, $url, 'xml');
                 //    $feature2 = array(
                   //    'id' => $ukey,
                   //    'type' => 'Feature',
                  //     'geometry' => array(
                  //     'type' => 'Point',
                   # Pass Longitude and Latitude Columns here
                  //       'coordinates' => array((double)$xcod, (double)$ycod)
                  //        ),
               # Pass other attribute columns here
                    //   'properties' => array(
                   //       'user' => $userd,
                     //     'date' => $dated,
                      //    'kind' => $kind,
                       //   'text' => $stext,
                       //   'url' => $url
                //   'attrs' => $atrar
                   //)
               //);


            //         $log->addWarning("attribute add  ${ukey}");
                     foreach ( $geojson['features'] as &$feat){
                     
                         $fprop = $feat['properties']['attr'];
                         $nattr = substr($fprop, 0, -1) . $attrtext2 .'"' ;
                         
                         $feat['properties']['attr'] = $nattr;
                         

                        //  $fkey = $feat["id"];

                        //   $log->addWarning("fkey == ${fkey}");

                           if ( $feat["id"] === $ukey ){

                            // $poip = $feat['geometry'][ 'coordinates'];

                             // $geomp = array('type' => 'Point',
                             //        'coordinates' => array($poip[0], $poip[1])
                              // );

                             // $feature2["geometry"]  = $geomp;

                            // $log->addWarning("add attribute success ============== ${ukey}");


                            // array_push( $geojson['features'], $feature2 );

                             break;
                           }

                       unset( $feat );

                     }

              //unset( $feature2 );

          }
       }

     }  //  foreach

     unset( $uid_ar );
     unset( $cols  );

     $retjson = json_encode( $geojson  );      // make json
     echo $retjson;

?>
