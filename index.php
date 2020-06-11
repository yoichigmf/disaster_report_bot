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

$code = $_GET['code'];

$state = $_GET['state'];

$session_state = $_SESSION['_line_state'];
unset($_SESSION['_line_state']);
if ($session_state !== $state) {
    echo 'アクセスエラー';
    exit;
}

readfile(__DIR__ . '/map.png');
