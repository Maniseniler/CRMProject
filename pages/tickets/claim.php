<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../gmail/send.php";

$gmailToken = $db->getGmailToken();

if (!$gmailToken || !is_array($gmailToken) || empty($gmailToken['access_token'])) {
    header("Location: /gmail/index.php");
    exit();
}

if (!in_array($_SESSION['user']['role'], ['Admin', 'Manager', 'Commercial'])) die("Accès refusé");

$ticket_id = $_POST['ticket_id'];
$client_id = $_POST['client_id'];
$user_id = $_SESSION['user']['id'];

$ticket = $db->getTicketById($ticket_id);

if ($ticket && empty($ticket['claimed_by'])) {
    $db->claimTicket($ticket_id, $user_id);
	$gmailMessage = $db->getGmailMessageFromTicket($ticket_id);
    $client = $db->getClientById($ticket['client_id']);
    $autoReplyBody = "Ticket #{$ticket_id} pris en charge\n\n". "Bonjour,\n\n". "Votre ticket est en cours de traitement par notre équipe.\n\n". "Cordialement.";
	
	sendClientEmail($client['email'],"Re: " .  $gmailMessage['subject'],$autoReplyBody,true,$gmailMessage['message_id_header'] ?? null,$gmailMessage['thread_id'] ?? null);
}

header("Location: ./ticket.php?id=" . $ticket_id);
exit();