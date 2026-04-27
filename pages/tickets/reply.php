<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../gmail/send.php";

$gmailToken = $db->getGmailToken();

if (!$gmailToken || !is_array($gmailToken) || empty($gmailToken['access_token'])) {
    header("Location: /gmail/index.php");
    exit();
}

$ticket_id = $_POST['ticket_id'] ?? null;
$client_id = $_POST['client_id'] ?? null;
$reply = trim($_POST['reply'] ?? '');
$user_id = $_SESSION['user']['id'] ?? null;

if (!$ticket_id || !$client_id || $reply === '') die("Données invalides.");

$ticket = $db->getTicketById($ticket_id);
if (!$ticket) die("Ticket introuvable");

if (($ticket['claimed_by'] ?? null) != $user_id) die("Seul l'utilisateur qui a claim ce ticket peut répondre.");

$gmailMessage = $db->getGmailMessageFromTicket($ticket_id);
$client = $db->getClientById($ticket['client_id']);

if (!$gmailMessage || !$client) die("Informations introuvables.");

$gmailMessageId = sendClientEmail($client['email'],"Re: " . $gmailMessage['subject'] ?? 'Réponse au ticket',$reply,false,$gmailMessage['message_id_header'] ?? null,$gmailMessage['thread_id'] ?? null);
$db->addTicketReply($ticket_id, $user_id, null, 'user', $reply);

header("Location: /pages/tickets/ticket.php?id=" . $ticket_id . "#bottom");
exit();