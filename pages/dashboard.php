<?php
require_once "../includes/auth.php";
require_once "../config/db.php";

$totalClients = $db->countClients();
$totalUsers = $db->countUsers();
$rolesData = $db->countUsersByRole();

$gmailToken = $db->getGmailToken();
$gmailStatus = "Not connected";
$Status = "#e01231";

if ($gmailToken) {
    if (!empty($gmailToken['expires_at']) && $gmailToken['expires_at'] > time()) {
        $Status = "#137333";
        $gmailStatus = "Connected";
    } else {
        $Status = "#e01231";
        $gmailStatus = "Expired";
    }
}

$recentTickets = $db->getRecentTickets(3);
$gmailActivities = $db->getRecentGmailMessages(3);

function timeAgo($datetime) {
    $seconds = time() - strtotime($datetime);

    if ($seconds < 60) return $seconds . ' sec ago';
    elseif ($seconds < 3600) return floor($seconds / 60) . ' mins ago';
    elseif ($seconds < 86400) return floor($seconds / 3600) . ' hour' . (floor($seconds / 3600) > 1 ? 's' : '') . ' ago';
    else return floor($seconds / 86400) . ' day' . (floor($seconds / 86400) > 1 ? 's' : '') . ' ago';
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

function getPriorityLabel($priority) {
    $priority = strtolower(trim($priority));
    return match ($priority) {'high' => 'HIGH','medium' => 'MED','low' => 'LOW',default => strtoupper($priority),};
}

function getPriorityClasses($priority) {
    $priority = strtolower(trim($priority));
    return match ($priority) {'high' => 'bg-error-container text-on-error-container','medium' => 'bg-secondary-container/30 text-secondary','low' => 'bg-surface-variant text-on-surface-variant',default => 'bg-surface-variant text-on-surface-variant',};
}

function getTicketIdClasses($status) {
    $status = strtolower(trim($status));
    return match ($status) {'open' => 'bg-[#e6f4ea] text-[#137333]','claimed' => 'bg-secondary-container/30 text-secondary','solved' => 'bg-[#e8f0fe] text-[#1967d2]',default => 'bg-surface-container-highest text-on-surface-variant',};
}

function getStatusBadgeClasses($status) {
    $status = strtolower(trim($status));
    return match ($status) {'open' => 'bg-[#e6f4ea] text-[#137333]','claimed' => 'bg-secondary-container/30 text-secondary','solved' => 'bg-[#e8f0fe] text-[#1967d2]',default => 'bg-surface-variant text-on-surface-variant',};
}

function getGmailIcon($direction){
	return $direction === 'outgoing' ? 'send' : 'mail';
}


function getGmailColor($direction) {
    return $direction === 'outgoing' ? 'bg-primary-container text-on-primary-container' : 'bg-secondary-container text-on-secondary';
}

function renderGmailText($msg) {
    if ($msg['direction'] === 'outgoing') {
        $to = htmlspecialchars($msg['receiver_email'] ?? 'unknown');
        return '<span class="font-semibold">Auto-reply sent</span> to ' . $to;
    } else {
        $from = htmlspecialchars($msg['sender_email'] ?? 'unknown');
        return '<span class="font-semibold">Email received</span> from ' . $from;
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>

    <?php include __DIR__ . '/../includes/headerN.php'; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-md">

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] p-md flex flex-col gap-sm hover:border-outline transition-colors group">
                <div class="flex items-center justify-between">
                    <span class="font-body-sm text-body-sm text-on-surface-variant font-medium">Total Clients</span>
                    <div class="p-2 rounded-lg bg-surface-container-low text-primary group-hover:bg-primary/10 transition-colors"><span class="material-symbols-outlined text-[20px]">group</span></div>
                </div>
                <div class="flex items-end gap-3 mt-2">
                    <span class="font-h1 text-h1 text-on-surface"><?= $totalClients ?></span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] p-md flex flex-col gap-sm hover:border-outline transition-colors group">
                <div class="flex items-center justify-between">
                    <span class="font-body-sm text-body-sm text-on-surface-variant font-medium">Total Accounts</span>
                    <div class="p-2 rounded-lg bg-surface-container-low text-primary group-hover:bg-primary/10 transition-colors"><span class="material-symbols-outlined text-[20px]">account_balance</span></div>
                </div>

                <div class="flex flex-col gap-1 mt-2">
                    <span class="font-h1 text-h1 text-on-surface"><?= $totalUsers ?></span>

                    <div class="flex items-center gap-3 text-xs font-body-sm text-on-surface-variant mt-1">
                        <?php foreach ($rolesData as $role): ?>
                            <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-primary"></span><?= htmlspecialchars($role['total']) ?> <?= htmlspecialchars($role['name']) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] p-md flex flex-col gap-sm hover:border-outline transition-colors group">
                <div class="flex items-center justify-between">
                    <span class="font-body-sm text-body-sm text-on-surface-variant font-medium">Gmail Status</span>
                    <div class="p-2 rounded-lg bg-surface-container-low text-primary group-hover:bg-primary/10 transition-colors"><span class="material-symbols-outlined text-[20px]">mail</span></div>
                </div>

                <div class="flex flex-col gap-3 mt-2">
                    <div class="flex items-center gap-2">
                        <div class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[<?= $Status ?>] opacity-20"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-[<?= $Status ?>]"></span>
                        </div>
                        <span class="font-h2 text-h2 text-on-surface"><?= htmlspecialchars($gmailStatus) ?></span>
                    </div>

                    <span class="font-mono text-mono text-on-surface-variant text-[11px]">Email : crmprojetsupport@gmail.com</span>
                    <span class="font-mono text-mono text-on-surface-variant text-[11px]">Expiration : <?= !empty($gmailToken['expires_at']) ? date('Y-m-d H:i:s', $gmailToken['expires_at']) : 'Unknown' ?></span>
                </div>
            </div>

        </div>

        <div class="col-span-1 md:col-span-6 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] flex flex-col overflow-hidden">

            <div class="p-4 border-b border-outline-variant flex items-center justify-between bg-surface/50">
                <h3 class="font-h3 text-h3 text-on-surface">Recent Tickets</h3>
            </div>

            <div class="flex flex-col">
                <?php if (!empty($recentTickets)): ?>
                    <?php foreach ($recentTickets as $index => $ticket): ?>
                        <?php
                        $clientName = trim(($ticket['first_name'] ?? '') . ' ' . ($ticket['last_name'] ?? '')) ?: 'Unknown Client';
                        $borderClass = $index !== count($recentTickets) - 1 ? 'border-b border-outline-variant/30' : '';

                        $status = strtolower($ticket['status'] ?? 'open');
                        $metaLabel = 'Ouvert';
                        $metaValue = timeAgo($ticket['created_at']);

                        if ($status === 'solved') {
                            $metaLabel = 'Temps de résol.';
                            $metaValue = formatDuration($ticket['resolution_time_minutes'] ?? null);
                        } elseif ($status === 'claimed') {
                            $metaLabel = 'Dernière activité';
                            $metaValue = timeAgo($ticket['updated_at'] ?? $ticket['created_at']);
                        }
                        ?>

                        <a href="/pages/tickets/ticket.php?id=<?= (int)$ticket['id'] ?>" class="flex items-center justify-between p-4 <?= $borderClass ?> hover:bg-surface-container-low transition-colors cursor-pointer">

                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-8 h-8 rounded <?= getTicketIdClasses($ticket['status']) ?> flex items-center justify-center font-label-caps text-[10px] font-bold shrink-0">#<?= (int)$ticket['id'] ?></div>

                                <div class="flex flex-col min-w-0">
                                    <span class="font-body-sm font-medium text-on-surface truncate"><?= htmlspecialchars($ticket['title']) ?></span>
                                    <span class="font-body-sm text-xs text-on-surface-variant truncate"><?= htmlspecialchars($clientName) ?></span>

                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-full <?= getStatusBadgeClasses($ticket['status']) ?> font-label-caps text-[10px] shrink-0"><?= htmlspecialchars(ucfirst($ticket['status'])) ?></span>
                                        <span class="font-body-sm text-xs text-on-surface-variant"><?= htmlspecialchars($metaLabel) ?> : <?= htmlspecialchars($metaValue) ?></span>
                                    </div>
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded-full <?= getPriorityClasses($ticket['priority']) ?> font-label-caps text-[10px] shrink-0"><?= htmlspecialchars(getPriorityLabel($ticket['priority'])) ?></span>
                        </a>

                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-sm text-on-surface-variant">No recent tickets found.</div>
                <?php endif; ?>
            </div>

        </div>

        <div class="col-span-1 md:col-span-6 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] flex flex-col overflow-hidden">

            <div class="p-4 border-b border-outline-variant flex items-center justify-between bg-surface/50">
                <h3 class="font-h3 text-h3 text-on-surface">Recent Gmail Activity</h3>
                <span class="material-symbols-outlined text-outline text-[18px]">history</span>
            </div>

            <div class="p-6 relative">

                <?php if (!empty($gmailActivities)): ?>

                    <div class="absolute left-[35px] top-6 bottom-6 w-px bg-outline-variant/50"></div>

                    <?php foreach ($gmailActivities as $index => $msg): ?>
                        <?php $mb = $index === count($gmailActivities) - 1 ? '' : 'mb-6'; ?>

                        <div class="flex gap-4 <?= $mb ?> relative">
                            <div class="w-5 h-5 rounded-full <?= getGmailColor($msg['direction']) ?> flex items-center justify-center z-10 shrink-0 outline outline-4 outline-white"><span class="material-symbols-outlined text-[12px]"><?= getGmailIcon($msg['direction']) ?></span></div>

                            <div class="flex flex-col gap-1 mt-[-2px]">
                                <p class="font-body-sm text-sm text-on-surface"><?= renderGmailText($msg) ?></p>
                                <span class="font-mono text-xs text-on-surface-variant"><?= timeAgo($msg['created_at']) ?></span>
                            </div>
                        </div>

                    <?php endforeach; ?>

                <?php else: ?>
                    <div class="text-sm text-on-surface-variant">No Gmail activity yet.</div>
                <?php endif; ?>

            </div>

        </div>

    </main>
</body>

</html>