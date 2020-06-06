<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__.'/php/report_bot.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

$uname = $_SESSION['username']
$log->addWarning("username  ${uname}\n");

readfile(__DIR__ . '/index.html'); 