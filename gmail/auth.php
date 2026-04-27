<?php
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();
$client->setAccessType('offline');
$client->setPrompt('consent');
$client->setClientId('');
$client->setClientSecret('');
$client->setRedirectUri('');

$client->addScope(Google_Service_Gmail::GMAIL_READONLY);
$client->addScope(Google_Service_Gmail::GMAIL_SEND);

header('Location: ' . $client->createAuthUrl());
exit();