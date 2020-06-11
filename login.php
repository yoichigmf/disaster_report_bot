<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__.'/php/report_bot.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));


if (!session_id()) {
    session_start();
}

$message = $_GET['message'];

$base_url = "https://access.line.me/oauth2/v2.1/authorize";
$client_id = getenv("CLIENT_ID");
$redirect_uri = getenv("REDIRECT_URI");

$_SESSION['_line_state'] = sha1(time());

$query = "";
$query .= "response_type=" . urlencode("code") . "&";
$query .= "client_id=" . urlencode($client_id) . "&";
$query .= "redirect_uri=" . urlencode($redirect_uri) . "&";
$query .= "state=" . urlencode($_SESSION['_line_state']) . "&";
$query .= "scope=" . urlencode("openid") . "&";

$url = $base_url . '?' . $query;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=10.0, user-scalable=yes">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="margin: 10px 0;">
    <div class="panel panel-default">
        <div class="panel-heading">
            災害情報調査結果地図
        </div>
        <div class="panel-body">
            <p>ボットと友達のLINEユーザでログインしてください。</p>
            <a href="<?php echo $url; ?>">
                <img src="images/btn_login_base.png">
            </a>
        </div>

        <div class="panel-body" id="message">
          <p>
                <?php
                   if ( !isset($message)){
                       echo $message;
                     }
                      ?>
            </p>
        </div>
    </div>
</div>
</body>
</html>
