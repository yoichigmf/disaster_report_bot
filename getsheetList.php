<?php

require_once __DIR__ . '/vendor/autoload.php';

require 'functions.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

date_default_timezone_set('Asia/Tokyo');








    header("Content-Type: application/json; charset=UTF-8"); //ヘッダー情報の明記。必須。
    $ary_sel_obj = []; //配列宣言
    $inputid = filter_input(INPUT_POST,"sheetid"); //変数の出力。jQueryで指定したキー値optを用いる

   $spreadsheetId = getenv('SPREADSHEET_ID');
   if (isset($inputid)){
     $spreadsheetId = $inputid;
   }

   $client = getGoogleSheetClient();


   $service = new Google_Service_Sheets($client);

   $response = $service->spreadsheets->get($spreadsheetId);
   foreach($response->getSheets() as $s) {
        $sheets[] = $s['properties']['title'];
    }

    $retar = array( "sheetnames" => $sheets ); // set sheetnames list

     $retjson = json_encode( $retar );      // make json
     echo $retjson;


?>
