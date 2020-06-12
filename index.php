<?php
//header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__.'/php/report_bot.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));


if (!session_id()) {
    session_start();
}

$code = $_GET['code'];

$state = $_GET['state'];

$session_state = $_SESSION['_line_state'];
//unset($_SESSION['_line_state']);

$log->addWarning( "session state =${session_state}");


$log->addWarning( "state =${state}");
if ( !isset($code) or !isset($state) or !isset($session_state) ){
  $loginm = urlencode("地図閲覧のためにはLINEアカウントのログインが必要です"）;
  header( "Location:login.php?message=${loginm}" ) ;

    exit;
}
if ($session_state !== $state) {
  $loginm = urlencode("地図閲覧のためにはLINEアカウントのログインが必要です"）;
  header( "Location:login.php?message=${loginm}" ) ;

    exit;
}

//    id token の取得
$client_id = getenv("CLIENT_ID");
$redirect_uri = getenv("REDIRECT_URI");

$client_secret = getenv("CLIENT_SECRET");

$url = "https://api.line.me/oauth2/v2.1/token";

//----------------------------------------
// POSTパラメータの作成
//----------------------------------------
$query = "";
$query .= "grant_type=" . urlencode("authorization_code") . "&";
$query .= "code=" . urlencode($code) . "&";
$query .= "redirect_uri=" . urlencode($redirect_uri) . "&";
$query .= "client_id=" . urlencode($client_id) . "&";
$query .= "client_secret=" . urlencode($client_secret) . "&";

$header = array(
    "Content-Type: application/x-www-form-urlencoded",
    "Content-Length: " . strlen($query),
);

$context = array(
    "http" => array(
        "method"        => "POST",
        "header"        => implode("\r\n", $header),
        "content"       => $query,
        "ignore_errors" => true,
    ),
);
$res_json = file_get_contents($url, false, stream_context_create($context));

if (isset($res->error)) {

  $loginm = urlencode("ログインエラーが発生しました。<br />" . $res->error . '<br />'.$res->error_description）;
  header( "Location:login.php?message=${loginm}" ) ;

    exit;

}
//id_token(JWT)を分解
$val = explode(".", $res->id_token);
$data_json = base64_decode($val[1]);
$data = json_decode($data_json);

echo '$data= ';
print_r($data);
echo '<br /><br />';

//取得したデータを表示
print("[sub]:[" . $data->sub . "][対象ユーザーの識別子]<br />\n");

//readfile(__DIR__ . '/map.png');
