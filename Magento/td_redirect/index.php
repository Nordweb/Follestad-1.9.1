<?php
session_start();
$tduid = $_GET['tduid'];
$url   = $_GET['url'];
$url = urldecode($url);

if(empty($url)) {
	$url = "http://follestad.no";
}

setcookie('TRADEDOUBLER', $tduid, (time()+3600*24*365), "/"/*, ".follestad.no"*/);
$_SESSION['TRADEDOUBLER'] = $tduid;


header('Location: ' . $url);