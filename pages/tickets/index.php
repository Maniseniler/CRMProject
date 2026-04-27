<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

$currentStatus = $_GET['status'] ?? 'open';

if (!in_array($currentStatus, ['open', 'claimed', 'solved'], true)) $currentStatus = 'open';

$tickets = $db->getAllTickets($currentStatus);
$statusCounts = $db->countTicketsByStatus();

function priorityClass($priority) {
    $priority = strtolower($priority ?? 'normal');
    return match ($priority) {'high' => 'bg-error-container text-on-error-container','low' => 'bg-tertiary-container text-on-tertiary-container',default => 'bg-surface-variant text-on-surface-variant',};
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
    return match ($status) {'solved' => 'check_circle',default => '',};
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

function clientName($ticket) {
    $name = trim(($ticket['client_first_name'] ?? '') . ' ' . ($ticket['client_last_name'] ?? ''));
    return $name !== '' ? $name : 'Unknown Client';
}

function clientInitial($ticket) {
    $a = strtoupper(substr($ticket['client_first_name'] ?? '', 0, 1));
    $b = strtoupper(substr($ticket['client_last_name'] ?? '', 0, 1));
    return trim($a . $b) ?: '?';
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
    <title>Tickets Management</title>

    <?php include __DIR__ . "/../../includes/headerN.php"; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">

        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="font-h1 text-h1 text-on-surface mb-2">Tickets Management</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Track, assign, and resolve customer inquiries.</p>
            </div>
        </div>

        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-md mb-6 bg-surface-container-lowest p-2 rounded-xl border border-outline-variant shadow-sm">
            <div class="flex bg-surface-container rounded-lg p-1">
                <a href="?status=open" class="<?= tabClass('open', $currentStatus) ?>"><span class="w-2 h-2 rounded-full bg-primary"></span> Open <span class="text-outline font-mono text-mono normal-case"><?= $statusCounts['open'] ?? 0 ?></span></a>
                <a href="?status=claimed" class="<?= tabClass('claimed', $currentStatus) ?>">Claimed <span class="text-outline font-mono text-mono normal-case"><?= $statusCounts['claimed'] ?? 0 ?></span></a>
                <a href="?status=solved" class="<?= tabClass('solved', $currentStatus) ?>">Solved <span class="text-outline font-mono text-mono normal-case"><?= $statusCounts['solved'] ?? 0 ?></span></a>
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

                    <a href="/pages/tickets/ticket.php?id=<?= (int)$ticket['id'] ?>&from=tickets" class="group flex flex-col md:grid md:grid-cols-[80px_200px_minmax(250px,1fr)_120px_140px_140px] gap-2 md:gap-4 p-md bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm hover:bg-surface-container-low hover:border-primary-fixed-dim hover:shadow-md transition-all cursor-pointer">

                        <div class="flex justify-between md:block">
                            <span class="font-mono text-mono text-on-surface-variant">#TK-<?= (int)$ticket['id'] ?></span>
                            <div class="flex items-center gap-3 md:hidden">
                                <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-h3 text-h3"><?= htmlspecialchars(clientInitial($ticket)) ?></div>
                                <div class="text-sm"><?= htmlspecialchars(clientName($ticket)) ?></div>
                            </div>
                        </div>

                        <div class="hidden md:flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-h3 text-h3"><?= htmlspecialchars(clientInitial($ticket)) ?></div>
                            <div>
                                <div class="font-body-md text-body-md font-medium text-on-surface leading-tight"><?= htmlspecialchars(clientName($ticket)) ?></div>
                                <div class="font-body-sm text-body-sm text-on-surface-variant leading-tight">Client</div>
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

                        <div>
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

                        <div><span class="text-outline text-body-sm font-body-sm italic"><?= htmlspecialchars(assigneeName($ticket)) ?></span></div>

                    </a>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-md bg-surface-container-lowest rounded-xl border border-outline-variant text-on-surface-variant">No <?= htmlspecialchars($currentStatus) ?> tickets found.</div>
            <?php endif; ?>

        </div>

    </main>

    <script>
        document.getElementById('searchInput').addEventListener('input', function () {
            const search = this.value.toLowerCase();
            const rows = document.querySelectorAll('.group');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });
    </script>
</body>

</html>
