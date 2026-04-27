<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

$from = $_GET['from'] ?? 'tickets';
$clientId = $_GET['id_client'] ?? null;

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

$ticket = $db->getTicketById($id);
$replies = $db->getTicketReplies($id);
$email = $db->getGmailMessageFromTicket($id);

if (!$ticket) die("Ticket introuvable.");

$client = null;
if (!empty($ticket['client_id'])) $client = $db->getClientById($ticket['client_id']);


$assignedUser = null;
if (!empty($ticket['claimed_by'])) $assignedUser = $db->getUserById($ticket['claimed_by']);


function initialsFromName($firstName, $lastName) {
    $a = strtoupper(substr($firstName ?? '', 0, 1));
    $b = strtoupper(substr($lastName ?? '', 0, 1));
    return trim($a . $b) ?: '?';
}

function timeAgoFr($datetime) {
    if (!$datetime) return '';
    $seconds = time() - strtotime($datetime);
    if ($seconds < 60) return "À l'instant";
    if ($seconds < 3600) return "Il y a " . floor($seconds / 60) . " min";
    if ($seconds < 86400) return "Il y a " . floor($seconds / 3600) . " h";
    return "Il y a " . floor($seconds / 86400) . " j";
}

function formatDuration($minutes) {
    if (!$minutes) return '-';
    $secondsTotal = $minutes * 60;
    $days = floor($secondsTotal / 86400);
    $secondsTotal %= 86400;
    $hours = floor($secondsTotal / 3600);
    $secondsTotal %= 3600;
    $mins = floor($secondsTotal / 60);
    $seconds = $secondsTotal % 60;
    $result = '';
    if ($days > 0) $result .= $days . ' j ';
    if ($hours > 0) $result .= $hours . ' h ';
    if ($mins > 0) $result .= $mins . ' min ';
    if ($seconds > 0) $result .= $seconds . ' s';
    return trim($result);
}

function statusBadgeClass($status) {
    $status = strtolower($status ?? 'open');
    return match ($status) {'claimed' => 'bg-amber-50 text-amber-700 border border-amber-200/50','solved'  => 'bg-green-50 text-green-700 border border-green-200/50',default   => 'bg-blue-50 text-blue-700 border border-blue-200/50',};
}

function priorityBadgeClass($priority) {
    $priority = strtolower($priority ?? 'normal');
    return match ($priority) {'high' => 'bg-red-50 text-error border border-red-200/50','low'  => 'bg-slate-50 text-slate-600 border border-slate-200/50',default => 'bg-amber-50 text-amber-700 border border-amber-200/50',};
}

function senderLabel($reply) {
    if (($reply['sender_type'] ?? '') === 'client') return trim(($reply['client_first_name'] ?? '') . ' ' . ($reply['client_last_name'] ?? '')) ?: 'Client';
    return trim(($reply['user_first_name'] ?? '') . ' ' . ($reply['user_last_name'] ?? '')) ?: 'Agent';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tickets Management</title>

    <?php include __DIR__ . '/../../includes/headerN.php'; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
        <div class="max-w-container-max mx-auto px-lg py-sm">

			<nav class="flex items-center gap-2 text-gray-500 text-body-sm mb-4">
				<span class="hover:text-primary cursor-pointer" onclick="window.location.href='<?= $from === 'client' && $clientId  ? "/pages/clients/view.php?id=" . (int)$clientId . "&tab=tickets&status=" . urlencode($ticket['status'])  : "/pages/tickets/index.php" ?>'"> <?= $from === 'client' ? 'Client' : 'Tickets' ?></span>
				<span class="material-symbols-outlined text-[16px]">chevron_right</span>
				<span class="text-on-surface font-medium">Ticket #<?= (int)$id ?></span>
			</nav>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-lg pb-6 border-b border-gray-100">

                <div class="space-y-3">
                    <h1 class="font-h1 text-h1 text-gray-900 tracking-tight"><?= htmlspecialchars($ticket['title'] ?? 'Sans titre') ?></h1>

                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= statusBadgeClass($ticket['status'] ?? 'open') ?>"><?= htmlspecialchars(ucfirst($ticket['status'] ?? 'open')) ?></span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= priorityBadgeClass($ticket['priority'] ?? 'normal') ?>"><?= htmlspecialchars(ucfirst($ticket['priority'] ?? 'normal')) ?></span>

                        <?php if (!empty($ticket['ai_confidence'])): ?>
                            <div class="flex items-center gap-1.5 ml-2 px-3 py-1 bg-secondary-container/10 rounded-lg"><span class="material-symbols-outlined text-secondary text-[16px]">auto_awesome</span><span class="text-xs font-bold text-secondary">AI Confidence: <?= htmlspecialchars($ticket['ai_confidence']) ?>%</span></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <form method="POST" action="<?= (empty($ticket['claimed_by']) ? '../tickets/claim.php' : '../tickets/solve.php') ?>">
                        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                        <input type="hidden" name="client_id" value="<?= (int)($ticket['client_id'] ?? 0) ?>">

                        <?php if (empty($ticket['claimed_by']) && in_array($_SESSION['user']['role'], ['Admin', 'Manager', 'Commercial'])): ?>
                            <button type="submit" class="px-4 py-2 bg-primary text-white font-semibold text-body-sm rounded-lg hover:opacity-90 shadow-sm transition-all">Assigner</button>
                        <?php elseif ($ticket['claimed_by'] == $_SESSION['user']['id'] && $ticket['status'] !== 'solved'): ?>
                            <button type="submit" class="px-4 py-2 bg-primary text-white font-semibold text-body-sm rounded-lg hover:opacity-90 shadow-sm transition-all">Solve</button>
                        <?php endif; ?>
                    </form>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-lg">

                <div class="lg:col-span-8 flex flex-col gap-sm">

                    <?php if ($email): ?>
                        <div class="bg-surface-container-low border border-outline-variant rounded-xl p-md">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-500"><?= htmlspecialchars(initialsFromName($client['first_name'] ?? '', $client['last_name'] ?? '')) ?></div>
                                    <div>
                                        <h4 class="font-h3 text-body-md text-gray-900"><?= htmlspecialchars(trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')) ?: 'Client') ?></h4>
                                        <p class="text-body-sm text-gray-500"><?= htmlspecialchars($email['sender_email'] ?? '') ?></p>
                                    </div>
                                </div>
                                <span class="text-body-sm text-gray-400"><?= htmlspecialchars(timeAgoFr($email['created_at'] ?? null)) ?></span>
                            </div>

                            <div class="space-y-4 text-gray-700 text-body-md pl-1">
                                <p class="font-mono text-xs text-gray-400 uppercase tracking-wider">De: <?= htmlspecialchars($email['sender_email'] ?? '') ?><br/>À: <?= htmlspecialchars($email['receiver_email'] ?? '') ?><br/>Sujet: <?= htmlspecialchars($email['subject'] ?? '') ?></p>
                                <p><?= nl2br(htmlspecialchars($email['body'] ?? '')) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($replies as $reply): ?>
                        <?php
                        $firstName = ($reply['sender_type'] ?? '') === 'client' ? ($reply['client_first_name'] ?? '') : ($reply['user_first_name'] ?? '');
                        $lastName = ($reply['sender_type'] ?? '') === 'client' ? ($reply['client_last_name'] ?? '') : ($reply['user_last_name'] ?? '');
                        ?>

                        <div class="<?= ($reply['sender_type'] ?? '') === 'client' ? 'bg-surface-container-low border border-outline-variant' : 'bg-primary/5 border border-primary/20' ?> rounded-xl p-md">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-500"><?= htmlspecialchars(initialsFromName($firstName, $lastName)) ?></div>
                                    <div>
                                        <h4 class="font-h3 text-body-md <?= ($reply['sender_type'] ?? '') === 'client' ? 'text-gray-900' : 'text-primary' ?>"><?= htmlspecialchars(senderLabel($reply)) ?><?= ($reply['sender_type'] ?? '') !== 'client' ? ' (Agent)' : '' ?></h4>
                                        <p class="text-body-sm text-gray-500"><?= ($reply['sender_type'] ?? '') === 'client' ? htmlspecialchars($client['email'] ?? '') : htmlspecialchars($reply['user_email'] ?? '') ?></p>
                                    </div>
                                </div>
                                <span class="text-body-sm text-gray-400"><?= htmlspecialchars(timeAgoFr($reply['created_at'] ?? null)) ?></span>
                            </div>

                            <div class="space-y-4 text-gray-700 text-body-md pl-1">
                                <p><?= nl2br(htmlspecialchars($reply['message'] ?? '')) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($ticket['claimed_by'] == $_SESSION['user']['id'] && $ticket['status'] !== 'solved'): ?>
                        <div id="bottom" class="mt-4 bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                            <form method="POST" action="/pages/tickets/reply.php">
                                <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
                                <input type="hidden" name="client_id" value="<?= (int)($ticket['client_id'] ?? 0) ?>">
                                <textarea name="reply" rows="4" required class="w-full p-md border-none focus:ring-0 text-body-md resize-none" placeholder="Rédiger votre réponse ici..."></textarea>

                                <div class="p-4 border-t border-gray-100 flex justify-between items-center bg-[#FBFBFA]">
                                    <div class="flex gap-3 ml-auto">
                                        <button type="submit" class="px-5 py-2 bg-primary text-white font-semibold text-body-sm rounded-lg hover:bg-primary-container shadow-sm flex items-center gap-2"><span>Envoyer la réponse</span><span class="material-symbols-outlined text-[18px]">send</span></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="lg:col-span-4 flex flex-col gap-sm">

                    <div class="bg-secondary-container/5 border border-secondary-container/20 rounded-xl p-md relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-3"><span class="material-symbols-outlined text-secondary opacity-20 text-[48px]">auto_awesome</span></div>
                        <div class="flex items-center gap-2 mb-3"><span class="material-symbols-outlined text-secondary text-[18px]">summarize</span><h3 class="font-h3 text-body-md text-secondary">Résumé IA</h3></div>
                        <p class="text-body-sm text-gray-700 leading-relaxed relative z-10"><?= nl2br(htmlspecialchars($ticket['ai_summary'] ?? 'Aucun résumé IA disponible.')) ?></p>
                    </div>

                    <div class="bg-white border border-outline-variant rounded-xl p-md shadow-sm">
                        <h3 class="font-h3 text-body-md text-gray-900 mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-gray-400 text-[18px]">person</span> Informations Client</h3>

                        <div class="space-y-4">
                            <div>
                                <p class="text-label-caps text-gray-400 mb-0.5">NOM</p>
                                <p class="text-body-md font-semibold text-gray-900"><?= htmlspecialchars(trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')) ?: 'Client inconnu') ?></p>
                            </div>

                            <div>
                                <p class="text-label-caps text-gray-400 mb-0.5">EMAIL</p>
                                <p class="text-body-md text-primary font-medium"><?= htmlspecialchars($client['email'] ?? '-') ?></p>
                            </div>

                            <div>
                                <p class="text-label-caps text-gray-400 mb-0.5">TÉLÉPHONE</p>
                                <p class="text-body-md text-gray-900"><?= htmlspecialchars($client['phone'] ?? '-') ?></p>
                            </div>

                            <div class="pt-2 border-t border-gray-50 mt-2">
                                <?php if ($client): ?>
                                    <button onclick="window.location.href='/pages/clients/view.php?id=<?= (int)$client['id'] ?>'" class="w-full py-2 text-primary font-semibold text-body-sm hover:bg-primary/5 rounded-lg transition-all">Voir profil complet</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-outline-variant rounded-xl p-md shadow-sm">
                        <h3 class="font-h3 text-body-md text-gray-900 mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-gray-400 text-[18px]">info</span> Métadonnées</h3>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <p class="text-body-sm text-gray-500">Créé le</p>
                                <p class="text-body-sm font-medium text-gray-900"><?= htmlspecialchars($ticket['created_at'] ?? '-') ?></p>
                            </div>

                            <div class="flex justify-between items-center">
                                <p class="text-body-sm text-gray-500">Assigné à</p>
                                <p class="text-body-sm font-medium text-gray-900"><?= $assignedUser ? htmlspecialchars(trim(($assignedUser['first_name'] ?? '') . ' ' . ($assignedUser['last_name'] ?? ''))) : 'Non assigné' ?></p>
                            </div>

                            <div class="flex justify-between items-center">
                                <p class="text-body-sm text-gray-500">Dernière activité</p>
                                <p class="text-body-sm font-medium text-gray-900"><?= htmlspecialchars(timeAgoFr($ticket['updated_at'] ?? null)) ?></p>
                            </div>

                            <div class="flex justify-between items-center">
                                <p class="text-body-sm text-gray-500">Temps de résol.</p>
                                <p class="text-body-sm font-medium text-gray-900"><?= formatDuration($ticket['resolution_time_minutes']) ?></p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </main>

    <script>
        if (window.location.hash === "#bottom") {
            document.getElementById("bottom")?.scrollIntoView({ behavior: "smooth" });
        }
    </script>
</body>

</html>