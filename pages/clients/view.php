<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

$id = $_GET['id'] ?? null;
if (!$id) die("Client introuvable");

$client = $db->getClientById($id);
if (!$client) die("Client introuvable");

$messages = $db->getClientConversation($id);
$allTickets = $db->getTicketsByClient($id);
$tab = $_GET['tab'] ?? 'conversation';
$currentStatus = $_GET['status'] ?? 'open';

if (!in_array($currentStatus, ['open', 'claimed', 'solved'], true)) $currentStatus = 'open';

$statusCounts = ['open' => 0, 'claimed' => 0, 'solved' => 0];
foreach ($allTickets as $ticket) {
    $status = strtolower($ticket['status'] ?? 'open');
    if (isset($statusCounts[$status])) $statusCounts[$status]++;
}

$tickets = array_values(array_filter($allTickets, function ($ticket) use ($currentStatus) {return strtolower($ticket['status'] ?? 'open') === $currentStatus;}));

function initialsFromName($firstName, $lastName) {
    $a = strtoupper(substr($firstName ?? '', 0, 1));
    $b = strtoupper(substr($lastName ?? '', 0, 1));
    return trim($a . $b) ?: '?';
}

function timeAgoFr($datetime) {
    if (!$datetime) return '';
    $seconds = time() - strtotime($datetime);
    if ($seconds < 60) return "À l'instant";
    $minutes = floor($seconds / 60);
    if ($minutes < 60) return "Il y a {$minutes} min";
    $hours = floor($minutes / 60);
    $remainingMinutes = $minutes % 60;
    if ($hours < 24) return "Il y a {$hours} h" . ($remainingMinutes > 0 ? " {$remainingMinutes} min" : "");
    $days = floor($hours / 24);
    $remainingHours = $hours % 24;
    return "Il y a {$days} j" . ($remainingHours > 0 ? " {$remainingHours} h" : "");
}

function priorityClass($priority) {
    $priority = strtolower($priority ?? 'normal');
    return match ($priority) {'high' => 'bg-error-container text-on-error-container','low' => 'bg-tertiary-container text-on-tertiary-container', default => 'bg-surface-variant text-on-surface-variant',};
}

function priorityIcon($priority) {
    $priority = strtolower($priority ?? 'normal');
    return match ($priority) {'high' => 'warning','low' => 'arrow_downward',default => 'remove',};
}

function statusClass($status) {
    $status = strtolower($status ?? 'open');
    return match ($status) {'claimed' => 'border border-secondary text-secondary','solved' => 'bg-surface-variant text-on-surface-variant',default => 'bg-primary-container text-on-primary-container',};
}

function statusIcon($status) {
    $status = strtolower($status ?? 'open');
    return match ($status) {'solved' => 'check_circle', default => '',};
}

function statusLabel($status) {
    return ucfirst(strtolower($status ?? 'open'));
}

function timeAgo($datetime) {
    if (!$datetime) return '';
    $seconds = time() - strtotime($datetime);
    if ($seconds < 60) return 'Updated just now';
    if ($seconds < 3600) return 'Updated ' . floor($seconds / 60) . 'm ago';
    if ($seconds < 86400) return 'Updated ' . floor($seconds / 3600) . 'h ago';
    return 'Updated ' . floor($seconds / 86400) . 'd ago';
}

function assigneeName($ticket) {
    $name = trim(($ticket['claimed_first_name'] ?? '') . ' ' . ($ticket['claimed_last_name'] ?? ''));
    return $name !== '' ? $name : 'Unassigned';
}

function tabClass($tab, $currentStatus) {
    if ($tab === $currentStatus) return 'px-4 py-1.5 rounded-md bg-surface-container-lowest shadow-sm text-on-surface font-label-caps text-label-caps uppercase transition-all flex items-center gap-2';
    return 'px-4 py-1.5 rounded-md text-on-surface-variant hover:text-on-surface font-label-caps text-label-caps uppercase transition-all flex items-center gap-2';
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Client Detail</title>

    <?php include __DIR__ . '/../../includes/headerN.php'; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
        <div class="max-w-container-max mx-auto px-lg py-md flex flex-col gap-md">

            <div class="flex items-center justify-between w-full mb-2">
                <button onclick="window.location.href='/pages/clients/index.php'" class="flex items-center gap-1 text-on-surface-variant hover:text-primary transition-colors font-body-sm text-body-sm"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Clients</button>
            </div>

            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-[0px_2px_4px_rgba(0,0,0,0.04)] p-md flex flex-col md:flex-row gap-lg items-start md:items-center">
                <div class="flex items-center gap-md flex-1">
                    <div class="h-16 w-16 rounded-xl bg-surface-container-high flex items-center justify-center text-primary font-h2 text-h2 border border-outline-variant/50"><?= htmlspecialchars(initialsFromName($client['first_name'] ?? '', $client['last_name'] ?? '')) ?></div>

                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="font-h2 text-h2 text-on-background"><?= htmlspecialchars(trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''))) ?></h1>
                            <span class="px-2 py-0.5 rounded-full bg-surface-container-high border border-outline-variant/50 font-mono text-[11px] text-on-surface-variant uppercase tracking-wider">#CLI-<?= (int)$client['id'] ?></span>
                        </div>
                        <p class="font-body-md text-body-md text-on-surface-variant">Client CRM profile</p>
                    </div>
                </div>

                <div class="flex flex-wrap md:flex-col gap-3 md:items-end flex-1 md:flex-none font-body-sm text-body-sm text-on-surface-variant">
                    <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">mail</span> <?= htmlspecialchars($client['email'] ?? '-') ?></div>
                    <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">call</span> <?= htmlspecialchars($client['phone'] ?? '-') ?></div>
                    <div class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">location_on</span> <?= htmlspecialchars($client['address'] ?? '-') ?></div>
                </div>
            </div>

            <div class="flex items-center gap-lg border-b border-outline-variant/50 px-2 mt-sm">
                <button onclick="window.location.href='view.php?id=<?= (int)$id ?>&tab=conversation'" class="pb-3 border-b-2 <?= $tab === 'conversation' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?> font-h3 text-h3 text-[16px] flex items-center gap-2 transition-colors"><span class="material-symbols-outlined text-[18px]">forum</span> Conversation Gmail</button>
                <button onclick="window.location.href='view.php?id=<?= (int)$id ?>&tab=tickets&status=<?= htmlspecialchars($currentStatus) ?>'" class="pb-3 border-b-2 <?= $tab === 'tickets' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?> font-h3 text-h3 text-[16px] flex items-center gap-2 transition-colors"><span class="material-symbols-outlined text-[18px]">confirmation_number</span> Tickets <span class="ml-1 bg-surface-container-high px-1.5 py-0.5 rounded text-[11px] font-mono"><?= $statusCounts['open'] ?? 0 ?></span></button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-lg mt-xs items-start">
                <div class="lg:col-span-12 flex flex-col gap-sm">

                    <?php if ($tab === 'conversation'): ?>
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $email): ?>
                                <?php
                                $isOutgoing = ($email['direction'] ?? '') === 'outgoing';
                                $displayName = $isOutgoing ? 'Gmail System' : trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));
                                $displayEmail = ($email['sender_email'] ?? '');
                                $avatarText = $isOutgoing ? 'GS' : initialsFromName($client['first_name'] ?? '', $client['last_name'] ?? '');
                                $avatarClass = $isOutgoing ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500';
                                ?>

                                <div class="bg-surface-container-low border border-outline-variant rounded-xl p-md">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex gap-3 items-start">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold <?= $avatarClass ?>"><?= htmlspecialchars($avatarText) ?></div>

                                            <div>
                                                <h4 class="font-h3 text-body-md <?= $isOutgoing ? 'text-primary' : 'text-gray-900' ?>"><?= htmlspecialchars($displayName ?: 'Client') ?></h4>
                                                <p class="text-body-sm text-gray-500"><?= htmlspecialchars($displayEmail) ?></p>
                                            </div>
                                        </div>

                                        <span class="text-body-sm text-gray-400"><?= htmlspecialchars(timeAgoFr($email['created_at'] ?? null)) ?></span>
                                    </div>

                                    <div class="space-y-4 text-gray-700 text-body-md pl-1">
                                        <div class="mb-2 text-xs space-y-1">
                                            <div><span class="font-semibold text-gray-500">Sujet:</span> <span class="px-2 py-1 rounded-md bg-primary/10 text-primary font-semibold inline-block"><?= htmlspecialchars($email['subject'] ?? '') ?></span></div>
                                        </div>

                                        <p><?= nl2br(htmlspecialchars($email['body'] ?? '')) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="bg-surface-container-low border border-outline-variant rounded-xl p-md text-body-md text-gray-500">Aucune conversation Gmail trouvée pour ce client.</div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($tab === 'tickets'): ?>

                        <div class="flex justify-between items-end mb-8">
                            <div>
                                <h2 class="font-h1 text-h1 text-on-surface mb-2">Tickets Management</h2>
                                <p class="font-body-md text-body-md text-on-surface-variant">Track, assign, and resolve customer inquiries.</p>
                            </div>
                        </div>

                        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-md mb-6 bg-surface-container-lowest p-2 rounded-xl border border-outline-variant shadow-sm">
                            <div class="flex bg-surface-container rounded-lg p-1">
                                <a href="?id=<?= (int)$id ?>&tab=tickets&status=open" class="<?= tabClass('open', $currentStatus) ?>"><span class="w-2 h-2 rounded-full bg-primary"></span> Open <span class="text-outline font-mono text-mono normal-case"><?= $statusCounts['open'] ?? 0 ?></span></a>
                                <a href="?id=<?= (int)$id ?>&tab=tickets&status=claimed" class="<?= tabClass('claimed', $currentStatus) ?>">Claimed <span class="text-outline font-mono text-mono normal-case"><?= $statusCounts['claimed'] ?? 0 ?></span></a>
                                <a href="?id=<?= (int)$id ?>&tab=tickets&status=solved" class="<?= tabClass('solved', $currentStatus) ?>">Solved <span class="text-outline font-mono text-mono normal-case"><?= $statusCounts['solved'] ?? 0 ?></span></a>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-sm">
                            <div class="flex gap-sm w-full max-w-lg">
                                <div class="relative w-full">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                                    <input id="searchInput" class="w-full pl-10 pr-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-DEFAULT font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none" placeholder="Filter by name, email or ID..." type="text">
                                </div>
                            </div>
                        </div>

                        <div class="hidden md:grid grid-cols-[80px_200px_minmax(250px,1fr)_120px_140px_140px] gap-4 px-md py-3 border-b border-outline-variant mb-3 text-outline font-label-caps text-label-caps uppercase tracking-wider">
                            <div>ID</div>
                            <div>Client</div>
                            <div>Issue Title &amp; Type</div>
                            <div>Priority</div>
                            <div>Status</div>
                            <div>Assignee</div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <?php if (!empty($tickets)): ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <a href="/pages/tickets/ticket.php?id=<?= (int)$ticket['id'] ?>&from=client&id_client=<?= (int)$id ?>" class="group flex flex-col md:grid md:grid-cols-[80px_200px_minmax(250px,1fr)_120px_140px_140px] gap-2 md:gap-4 p-md bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm hover:bg-surface-container-low hover:border-primary-fixed-dim hover:shadow-md transition-all cursor-pointer">

                                        <div class="font-mono text-mono text-on-surface-variant">#TK-<?= (int)$ticket['id'] ?></div>

                                        <div class="flex items-center gap-3 md:flex-col">
                                            <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-h3 text-h3"><?= htmlspecialchars(initialsFromName($client['first_name'] ?? '', $client['last_name'] ?? '')) ?></div>
                                            <div class="flex flex-col">
                                                <span class="font-body-md text-body-md font-medium text-on-surface leading-tight"><?= htmlspecialchars(trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')) ?: 'Unknown Client') ?></span>
                                                <span class="font-body-sm text-body-sm text-on-surface-variant leading-tight md:hidden">Client</span>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="font-body-md text-body-md font-semibold text-on-surface"><?= htmlspecialchars($ticket['title'] ?? 'Untitled Ticket') ?></div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-surface-variant text-on-surface-variant"><?= htmlspecialchars($ticket['issue_type'] ?? 'General') ?></span>
                                                <span class="text-[11px] text-outline"><?= htmlspecialchars(timeAgo($ticket['updated_at'] ?? $ticket['created_at'] ?? null)) ?></span>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-label-caps text-[11px] <?= priorityClass($ticket['priority'] ?? 'normal') ?>"><span class="material-symbols-outlined text-[14px]"><?= priorityIcon($ticket['priority'] ?? 'normal') ?></span> <?= htmlspecialchars(ucfirst(strtolower($ticket['priority'] ?? 'normal'))) ?></div>
                                        </div>

                                        <div class="flex flex-col items-start gap-1">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-label-caps text-[11px] <?= statusClass($ticket['status'] ?? 'open') ?>">
                                                <?php if (statusIcon($ticket['status'] ?? 'open') !== ''): ?>
                                                    <span class="material-symbols-outlined text-[14px]"><?= statusIcon($ticket['status'] ?? 'open') ?></span>
                                                <?php endif; ?>
                                                <?= htmlspecialchars(statusLabel($ticket['status'] ?? 'open')) ?>
                                            </div>

                                            <?php if (!empty($ticket['ai_confidence'])): ?>
                                                <div class="flex items-center gap-1 text-secondary text-[12px] font-medium"><span class="material-symbols-outlined text-[14px]">auto_awesome</span> <?= htmlspecialchars(rtrim(rtrim((string)$ticket['ai_confidence'], '0'), '.')) ?>% Match</div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <span class="text-outline text-body-sm font-body-sm italic"><?= htmlspecialchars(assigneeName($ticket)) ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-md bg-surface-container-lowest rounded-xl border border-outline-variant text-on-surface-variant">No <?= htmlspecialchars($currentStatus) ?> tickets found.</div>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </main>

    <script>
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const search = this.value.toLowerCase();
                const rows = document.querySelectorAll('.group');

                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(search) ? '' : 'none';
                });
            });
        }
    </script>
</body>

</html>
