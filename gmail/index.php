<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/db.php";

$gmailToken = $db->getGmailToken();
$gmailStatus = "Not Active";
$tokenExpiresText = "No Token";
$healthIndicator = "Not Connected";
$Status = "#e01231";

$syncLogs = [];
if (method_exists($db, 'getRecentSyncLogs')) $syncLogs = $db->getRecentSyncLogs(5);

if ($gmailToken) {
    if (!empty($gmailToken['expires_at']) && $gmailToken['expires_at'] > time()) {
        $Status = "#137333";
        $gmailStatus = "Active";

        $secondsLeft = $gmailToken['expires_at'] - time();

        if ($secondsLeft > 86400) $timeText = floor($secondsLeft / 86400) . " days";
        elseif ($secondsLeft > 3600) $timeText = floor($secondsLeft / 3600) . " hours";
        else $timeText = floor($secondsLeft / 60) . " minutes";

        if ($secondsLeft > 0) {
            $tokenExpiresText = "Expires in " . $timeText;
            $healthIndicator = "Connected";
        } else {
            $tokenExpiresText = "Token Expired";
            $healthIndicator = "Disconnected";
        }
		
    } else {
        $Status = "#e01231";
        $gmailStatus = "Expired";
        $tokenExpiresText = "Token Expired";
        $healthIndicator = "Disconnected";
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Gmail Integration</title>
<?php include __DIR__ . '/../includes/headerN.php';?>
<main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
    <div class="mb-8">
        <h2 class="font-h1 text-h1 text-on-surface mb-2">Gmail Integration</h2>
        <p class="font-body-md text-body-md text-on-surface-variant">Manage your Google Workspace connection, sync settings, and AI routing preferences.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-md">

        <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant shadow-[0px_2px_4px_rgba(0,0,0,0.04)] rounded-xl p-md flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary text-[24px]">mail</span>
                        </div>
                        <div>
                            <h3 class="font-h3 text-h3 text-on-surface">Connected Account</h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">crmprojetsupport@gmail.com</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 px-3 py-1 bg-secondary-fixed rounded-full">
                        <div class="w-2 h-2 rounded-full bg-on-secondary-fixed-variant animate-pulse"></div>
                        <span class="font-label-caps text-label-caps text-on-secondary-fixed-variant">
                            <?= htmlspecialchars($gmailStatus) ?>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="bg-surface-container-low p-4 rounded-lg border border-outline-variant/50">
                        <span class="block font-label-caps text-label-caps text-on-surface-variant mb-1">HEALTH INDICATOR</span>
                        <div class="flex items-center gap-2 text-on-surface">
                            <span class="material-symbols-outlined text-primary text-[18px]">check_circle</span>
                            <span class="font-mono text-mono"><?= htmlspecialchars($healthIndicator) ?></span>
                        </div>
                    </div>

                    <div class="bg-surface-container-low p-4 rounded-lg border border-outline-variant/50">
                        <span class="block font-label-caps text-label-caps text-on-surface-variant mb-1">OAUTH TOKEN</span>
                        <div class="flex items-center gap-2 text-on-surface">
                            <span class="material-symbols-outlined text-outline text-[18px]">key</span>
                            <span class="font-mono text-mono"><?= htmlspecialchars($tokenExpiresText) ?></span>
                        </div>
                    </div>
                </div>
            </div>
			<?php if ($_SESSION['user']['role'] == "Admin"): ?>
            <div class="flex items-center gap-3 border-t border-outline-variant pt-4">
                <div class="flex-1"></div>

                <button onclick="window.location.href='./auth.php'" class="px-3 py-2 rounded-lg font-medium text-body-sm font-body-sm transition-colors <?= in_array($gmailStatus, ['Expired', 'Not Active', 'Disconnected']) ? 'text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-green-900/30' : 'text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30' ?>"> Connect Another Account</button>
            </div>
			<?php endif; ?>
        </div>

        <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant shadow-[0px_2px_4px_rgba(0,0,0,0.04)] rounded-xl p-md">
            <h3 class="font-h3 text-h3 text-on-surface mb-4">Recent Sync History</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-outline-variant/50">
                            <th class="py-2 font-label-caps text-label-caps text-on-surface-variant font-medium">TIMESTAMP</th>
                            <th class="py-2 font-label-caps text-label-caps text-on-surface-variant font-medium">STATUS</th>
                            <th class="py-2 font-label-caps text-label-caps text-on-surface-variant font-medium">ITEMS PROCESSED</th>
                            <th class="py-2 font-label-caps text-label-caps text-on-surface-variant font-medium">DURATION</th>
                        </tr>
                    </thead>

                    <tbody class="font-body-sm text-body-sm">
                        <?php if (!empty($syncLogs)): ?>
                            <?php foreach ($syncLogs as $index => $log): ?>
                                <tr class="<?= $index < count($syncLogs) - 1 ? 'border-b border-outline-variant/30' : '' ?> hover:bg-surface-container-low/50 transition-colors">
                                    <td class="py-3 font-mono text-mono text-on-surface">
                                        <?= htmlspecialchars(date('M d, H:i:s', strtotime($log['created_at']))) ?>
                                    </td>

                                    <td class="py-3">
                                        <div class="flex items-center gap-1.5 <?= strtolower($log['status']) === 'success' ? 'text-primary' : 'text-error' ?>">
                                            <span class="material-symbols-outlined text-[14px]">
                                                <?= strtolower($log['status']) === 'success' ? 'check_circle' : 'error' ?>
                                            </span>
                                            <?= htmlspecialchars($log['status']) ?>
                                        </div>
                                    </td>

                                    <td class="py-3 text-on-surface-variant">
                                        <?= (int)$log['processed_emails'] ?> emails, <?= (int)$log['tickets_created'] ?> tickets created
                                    </td>

                                    <td class="py-3 text-on-surface-variant">
                                        <?= htmlspecialchars($log['duration']) ?>s
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-on-surface-variant">
                                    No sync history yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
