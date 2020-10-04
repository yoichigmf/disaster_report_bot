<?php
require_once __DIR__ . '/vendor/autoload.php';

//  Google Spread Sheet 用クライアント作成
function getGoogleSheetClient() {


   $auth_str = getenv('authstr');

   $json = json_decode($auth_str, true);


     $client = new Google_Client();

    $client->setAuthConfig( $json );


    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);



    $client->setApplicationName('ReadSheet');

    return $client;


}

function GetSheet( $sheetid, $sheetname, $client ) {
//  $client = getGoogleSheetClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('ReadSheet');

    $service = new Google_Service_Sheets($client);

    $response = $service->spreadsheets_values->get($sheetid, $sheetname);

    $values = $response->getValues();

    return $values;
    //var_dump( $values );

}

function GetFirstSheetName( $spreadsheetID, $client ){

  $service = new Google_Service_Sheets($client);

  $response = $service->spreadsheets->get($spreadsheetID);
  foreach($response->getSheets() as $s) {
       $sheets[] = $s['properties']['title'];
   }

   $ret = $sheets[0];
   return $ret ;

}
function Getsheets($spreadsheetID, $client) {
    $sheets = array();


    $sheetService = new Google_Service_Sheets($client);
    $spreadSheet = $sheetService->spreadsheets->get($spreadsheetID);
    $sheets = $spreadSheet->getSheets();
    foreach($sheets as $sheet) {
        $sheets[] = $sheet->properties->sheetId;
    }
    return $sheets;
}

//   return text dtring of attribute line
//   format    html  xml    default   xml
//
function makeatrributetext( $date, $kind, $stext, $url ,$format ) {

   $rettext = "<attribute><date>${date}</date><kind>${kind}</kind>";
   
   switch( $kind ) {
      case 'location':
          $rettext = $rettext . "<address>${stext}</address></attribute>";
       
           break;
           
      case 'text':
           $rettext = $rettext . "<text>${stext}</text></attribute>";
           break;
           
      case 'voice':
      
           $rettext = $rettext . "<url>${url}</url><text>${stext}</text></attribute>";
           break;
           
 
 
      case  'image':
      case  'video': 
      case  'file':   
             $rettext = $rettext . "<url>${url}</url></attribute>";
             break;
      
      
          
   
   }
   
   return $rettext;

}

