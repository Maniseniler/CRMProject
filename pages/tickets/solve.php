<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../gmail/send.php";

$gmailToken = $db->getGmailToken();

if (!$gmailToken || !is_array($gmailToken) || empty($gmailToken['access_token'])) {
    header("Location: /gmail/index.php");
    exit();
}

$ticket_id = $_POST['ticket_id'];
$client_id = $_POST['client_id'];
$user_id = $_SESSION['user']['id'];

$ticket = $db->getTicketById($ticket_id);
$gmailMessage = $db->getGmailMessageFromTicket($ticket_id);

if (!$ticket) die("Ticket introuvable");

if ($ticket['claimed_by'] != $user_id) die("Vous devez d'abord claim ce ticket.");

$db->solveTicket($ticket_id, $user_id);

$client = $db->getClientById($ticket['client_id']);
$autoReplyBody = "Ticket #{$ticket_id} resolu\n\n". "Bonjour,\n\n". "Votre demande a été résolue. Merci de nous envoyer votre feedback si besoin.\n\n". "Cordialement.";
sendClientEmail($client['email'],"Re: " . $gmailMessage['subject'],$autoReplyBody,true,$gmailMessage['message_id_header'] ?? null,$gmailMessage['thread_id'] ?? null);

header("Location: ./ticket.php?id=" . $ticket_id );
exit();