<?php

$db_server = '127.0.0.1';
$db_name = 'dbname';
$db_user = 'dbuser';
$db_pass = 'dbpass';
$tablename = "hgauth"; //this is the table name that will store your authorizations.

$authlink = "http://mydomain.com/path/to/index.php"; //name of the page your users will use to submit consent


function base64_url_encode($input) {
    return strtr(base64_encode(str_rot13($input)), '+/=', '-_,');
}

function base64_url_decode($input) {
    return str_rot13(base64_decode(strtr($input, '-_,', '+/=')));
}


?>