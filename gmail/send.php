<?php
$gmail = require __DIR__ . '/service.php';

function sendClientEmail($to, $subject, $body, $isAuto = false, $replyToMessageId = null, $threadId = null) {
    global $gmail;

    if (!$gmail) {
        throw new Exception("Cannot send email: Gmail client not initialized or token invalid.");
    }

    $rawMessage  = "To: {$to}\r\n";
    $rawMessage .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $rawMessage .= "MIME-Version: 1.0\r\n";
    $rawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $rawMessage .= "Content-Transfer-Encoding: 7bit\r\n";

    if ($replyToMessageId) {
        $rawMessage .= "In-Reply-To: {$replyToMessageId}\r\n";
        $rawMessage .= "References: {$replyToMessageId}\r\n";
    }

    if ($isAuto) $rawMessage .= "X-CRM-Auto: 1\r\n";

    $rawMessage .= "\r\n";
    $rawMessage .= $body;

    $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

    $message = new Google_Service_Gmail_Message();
    $message->setRaw($encodedMessage);

    if ($threadId) $message->setThreadId($threadId);

    $sentMessage = $gmail->users_messages->send('me', $message);

    return $sentMessage->getId();
}