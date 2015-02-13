<?php

// retrieve post data
$email = '';
$chdata = "Submit=Subscribe&pf_CharSet=utf-8";

foreach($_GET as $key=>$value) {
	if(is_array($value)) {
		foreach($value as $v) {
			$chdata .= "&" . $key . "=" . $v;
		}
	} else {
		$chdata .= "&" . $key . "=" . $value;
		if ($key == "pf_Email") {
			$email = $value;
		}
		if ($key == "redirect") {
			$redirect = $value;
		}
	}
}
foreach($_POST as $key=>$value) {
	if(is_array($value)) {
		foreach($value as $v) {
			$chdata .= "&" . $key . "=" . $v;
		}
	} else {
		$chdata .= "&" . $key . "=" . $value;
		if ($key == "pf_Email") {
			$email = $value;
		}
		if ($key == "redirect") {
			$redirect = $value;
		}
	}
}

// send post data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://www.anpdm.com/public/process-subscription-form.aspx?');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $chdata);

curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (isset($redirect)) {
	header ("HTTP/1.1 301 Moved Permanently");
	header ("Location: $redirect");
	exit;
} else {
	echo $status;	
	return $status;	
}
?>