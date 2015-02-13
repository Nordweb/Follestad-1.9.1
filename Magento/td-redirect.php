<?php
session_start();
// Default landing page
$defaultUrl = "http://follestad.no/";
// The domain under which this script is installed
$domain = "follestad.no";

$currentPixel = array();

$currentPixel[ 'orgID' ] = '1699947';			//	Info from Trade Doubler
$currentPixel[ 'eventID' ] = '256477';		//	Info from Trade Doubler
$currentPixel[ 'secretcode' ] = '752103633';		//	Info from Trade Doubler
$currentPixel[ 'tduid' ] = '';		//	ID of current transfer

$currentPixel[ 'event_type' ] = 'sale';		// current event type
$currentPixel[ 'currency' ]	= 'NOK';		//what money do you use EUR, USD,NOK
if ($currentPixel[ 'event_type' ] == 'sale') {
	$currentPixel[ 'eventID' ] = '';			// uniqe ID of current event if sale
	$currentPixel[ 'pixelURL' ] = 'https://tbs.tradedoubler.com/report?';		// URL to send data	
} else {
	$currentPixel[ 'eventID' ] = ''; 		// uniqe ID of current event if lead
	$currentPixel[ 'pixelURL' ] = 'https://tbl.tradedoubler.com/report?';		// URL to send data
}
$currentPixel[ 'orderValue' ] = '0.00';		// Total price
$currentPixel[ 'checksum' ] = "v04" . md5($currentPixel[ 'secretcode' ] . $currentPixel[ 'eventID' ] . $currentPixel[ 'orderValue' ]);		// checksum

$orders = array();			// array with full list of products
$orderContainer = '';
$currentPixel[ 'orderContainer' ] = '';		// full order as string

foreach ($orders as $value) {
	$f1 = $value['f1'];					//product ID (artno)
	$f2 = $value['f2'];					//product name
	$f3 = $value['f3'];					//product price
	$f4 = $value['f4'];					//quantity ordered.
	$orderContainer .= 'f1=' . $f1 . '&f2=' . $f2 . '&f3=' . $f3 . '&f4=' . $f4 . '|';
}
	$currentPixel[ 'orderContainer' ] = urlencode ( $orderContainer );


if (!empty($tduid)) {
	$cookieDomain = "." . $domain;
	setcookie("TRADEDOUBLER", $tduid, (time() + 3600 * 24 * 365), "/", $cookieDomain);
// If you do not use the built-in session functionality in PHP, modify
// the following expression to work with your session handling routines.
	$_SESSION["TRADEDOUBLER"] = $tduid;
}

//		Where to go after

if (empty($_GET["url"])) { 
	$url = $defaultUrl;
} else {
	$url = urldecode(substr(strstr($_SERVER["QUERY_STRING"], "url"), 4));
}

header("Location: " . $url);