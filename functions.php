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

  $response = $service->spreadsheets->get($spreadsheetId);
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
