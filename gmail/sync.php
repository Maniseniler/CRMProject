<?php

$start = microtime(true);
$emailCount = 0;
$ticketCount = 0;
$syncStatus = "Success";

$gmail = require __DIR__ . '/service.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../ai/classify_email.php';
require_once __DIR__ . '/send.php';

function cleanEmailBody($body) {
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = preg_replace('/On\s.+?wrote:\s*/is', '', $body);
    $body = preg_replace('/Le\s.+?a écrit\s*:\s*/is', '', $body);
    $body = preg_replace('/^>.*$/m', '', $body);
    $body = preg_split('/(-{2,}|_{2,}|From:|De:|Envoyé:)/i', $body)[0];
    $body = preg_replace("/\n{3,}/", "\n\n", $body);
    return trim($body);
}

function extractEmail($text) {
    preg_match('/<(.+?)>/', $text, $matches);
    return strtolower(trim($matches[1] ?? $text));
}

function getHeaderValue($headers, $name) {
    foreach ($headers as $header) {
        if (strtolower($header->getName()) === strtolower($name)) {
            return $header->getValue();
        }
    }
    return null;
}

$crmEmail = "crmprojetsupport@gmail.com";

try {
    $messages = $gmail->users_messages->listUsersMessages('me', ['maxResults' => 5]);

    if ($messages->getMessages()) {
        foreach ($messages->getMessages() as $msg) {
            $existingMessage = $db->getGmailMessageByGmailId($msg->getId());
            if ($existingMessage) {
                continue;
            }

            $message = $gmail->users_messages->get('me', $msg->getId());
            $threadId = $message->getThreadId();

            $payload = $message->getPayload();
            $headers = $payload->getHeaders();

            $originalMessageId = "";
            $from = "";
            $to = "";
            $subject = "";
            $body = "";

            foreach ($headers as $header) {
                if ($header->getName() === 'Message-ID') {
                    $originalMessageId = $header->getValue();
                }
                if ($header->getName() === 'From') {
                    $from = $header->getValue();
                }
                if ($header->getName() === 'To') {
                    $to = $header->getValue();
                }
                if ($header->getName() === 'Subject') {
                    $subject = $header->getValue();
                }
            }

            $fromEmail = extractEmail($from);
            $toEmail = extractEmail($to);

            if ($payload->getBody()->getData()) {
                $body = $payload->getBody()->getData();
            } else {
                $parts = $payload->getParts();
                if ($parts) {
                    foreach ($parts as $part) {
                        if ($part->getMimeType() === 'text/plain') {
                            $body = $part->getBody()->getData();
                            break;
                        }
                    }
                }
            }

            $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $body));
            $body = cleanEmailBody($body);

            $gmailDate = $message->getInternalDate();
            $created_at = date('Y-m-d H:i:s', $gmailDate / 1000);

            $client = null;
            $direction = null;

            if ($fromEmail !== $crmEmail) {
                $client = $db->getClientByEmail($fromEmail);
                $direction = "incoming";
            } else {
                $client = $db->getClientByEmail($toEmail);
                $direction = "outgoing";
            }

            if (!$client) continue;

            $db->saveGmailMessage($msg->getId(),$client['id'],$fromEmail,$toEmail,$subject,$body,$direction,$created_at,$threadId,$originalMessageId);

            $emailCount++;

            if ($direction === "incoming") {
                $savedMessage = $db->getGmailMessageByGmailId($msg->getId());

                if (!$savedMessage) {
                    continue;
                }

                $existingAnalysis = $db->getEmailAiAnalysisByMessageId($savedMessage['id']);

                if (!empty($threadId)) {
                    $ticket = $db->getTicketByGmailThreadId($threadId);
                    if ($ticket) {
                        $existingReply = $db->ticketReplyExistsByGmailMessageId($savedMessage['id']);
                        if (!$existingReply) {
                            $db->addTicketReply($ticket['id'],$client['id'],$savedMessage['id'],'client',$body);
                        }
                    }
                }

                $isReply = stripos($subject, 'Re:') === 0;

                if (!$existingAnalysis && !$isReply) {
                    $analysis = classifyEmailWithAI($subject, $body);

                    $db->saveEmailAiAnalysis($savedMessage['id'],$analysis['category'],$analysis['is_ticket'],$analysis['is_feedback'],$analysis['summary'],$analysis['detected_issue'],$analysis['confidence']);

                    if (
                        isset($analysis['is_ticket']) &&
                        (int)$analysis['is_ticket'] === 1 &&
                        ($analysis['detected_issue'] ?? '') !== 'api_error'
                    ) {
                        $ticketTitle = !empty(trim($subject)) ? trim($subject) : "Support request";

                        $ticketId = $db->createTicket($client['id'],$savedMessage['id'],$ticketTitle,$analysis['detected_issue'],$analysis['priority'],$analysis['summary'],$analysis['confidence']);

                        $ticketCount++;

                        $autoReplyBody = "Bonjour,\n\n". "Ticket #{$ticketId} - Under Review\n\n". "Votre problème est en cours de révision. ". "Un ticket #{$ticketId} a été créé et notre équipe reviendra vers vous rapidement.\n\n". "Cordialement.";

                        try {
                            sendClientEmail($client['email'], "Re: " . $subject,$autoReplyBody,true,$originalMessageId,$threadId);
                        } catch (Exception $e) {}
                    }
                }
            }
        }
    }
} catch (Exception $e) {$syncStatus = "Failed";}

$duration = round(microtime(true) - $start, 2);

if (method_exists($db, 'addSyncLog')) $db->addSyncLog($emailCount, $ticketCount, $duration, $syncStatus);


header("Location: /gmail/index.php");
exit;