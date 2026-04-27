<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/../config/db.php";

session_start();

$client = new Google_Client();
$client->setClientId('');
$client->setClientSecret('');
$client->setRedirectUri('');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
	$access_token = $token['access_token'];
	$refresh_token = $token['refresh_token'];
	$expires_at = time() + $token['expires_in'];
	
	$db->saveGmailToken($access_token, $refresh_token, $expires_at);

    $_SESSION['gmail_token'] = $token;

    header("Location: /gmail/index.php");
}