<?php
require_once __DIR__ . "/../config/db.php";
$currentPath = $_SERVER['PHP_SELF'];

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

$gmailStatusClasses = [
    "Active" => "border-2 border-green-500 text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30",
    "Expired" => "border-2 border-red-500 text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30",
    "Not Active" => "border-2 border-gray-500 text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700/20",
    "Disconnected" => "border-2 border-yellow-500 text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-800/30"
];

function isActive($path) {
    global $currentPath;
    return $currentPath === $path;
}
function navClass($path) {
    return isActive($path)
        ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm border border-[#E5E5E1] dark:border-slate-800 rounded-lg font-semibold flex items-center gap-3 px-3 py-2 active:scale-[0.98] transition-transform'
        : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors duration-200 flex items-center gap-3 px-3 py-2 rounded-lg active:scale-[0.98] transition-transform';
}

function iconFill($path) {
    return isActive($path) ? "font-variation-settings: 'FILL' 1;" : "";
}
?>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">tailwind.config={darkMode:"class",theme:{extend:{colors:{"on-primary-container":"#eeefff","error":"#ba1a1a","surface-tint":"#0053db","secondary-fixed-dim":"#c0c1ff","on-primary-fixed-variant":"#003ea8","on-tertiary-fixed-variant":"#7d2d00","secondary-fixed":"#e1e0ff","surface-container":"#ededf9","surface-variant":"#e1e2ed","surface-dim":"#d9d9e5","outline":"#737686","on-secondary-fixed-variant":"#2f2ebe","on-tertiary-container":"#ffede6","surface-container-low":"#f3f3fe","background":"#faf8ff","inverse-primary":"#b4c5ff","tertiary":"#943700","tertiary-fixed":"#ffdbcd","primary":"#004ac6","surface-container-highest":"#e1e2ed","outline-variant":"#c3c6d7","on-secondary":"#ffffff","primary-container":"#2563eb","surface-container-lowest":"#ffffff","inverse-on-surface":"#f0f0fb","on-error-container":"#93000a","primary-fixed":"#dbe1ff","on-background":"#191b23","on-surface-variant":"#434655","on-secondary-container":"#fffbff","surface":"#faf8ff","on-surface":"#191b23","inverse-surface":"#2e3039","on-tertiary":"#ffffff","error-container":"#ffdad6","on-primary-fixed":"#00174b","secondary":"#4648d4","surface-container-high":"#e7e7f3","tertiary-container":"#bc4800","secondary-container":"#6063ee","surface-bright":"#faf8ff","on-error":"#ffffff","on-primary":"#ffffff","on-secondary-fixed":"#07006c","tertiary-fixed-dim":"#ffb596"},borderRadius:{"DEFAULT":"0.25rem","lg":"0.5rem","xl":"0.75rem","full":"9999px"},spacing:{"xs":"8px","container-max":"1440px","gutter":"20px","md":"24px","sm":"16px","base":"4px","xl":"48px","sidebar-width":"260px","lg":"32px"},fontFamily:{"h1":["Manrope"],"mono":["Inter"],"body-md":["Inter"],"label-caps":["Inter"],"body-sm":["Inter"],"h3":["Manrope"],"h2":["Manrope"]},fontSize:{"h1":["32px",{lineHeight:"40px",letterSpacing:"-0.02em",fontWeight:"700"}],"mono":["13px",{lineHeight:"20px",letterSpacing:"0",fontWeight:"500"}],"body-md":["15px",{lineHeight:"24px",letterSpacing:"0",fontWeight:"400"}],"label-caps":["12px",{lineHeight:"16px",letterSpacing:"0.05em",fontWeight:"600"}],"body-sm":["13px",{lineHeight:"20px",letterSpacing:"0",fontWeight:"400"}],"h3":["18px",{lineHeight:"26px",letterSpacing:"0",fontWeight:"600"}],"h2":["24px",{lineHeight:"32px",letterSpacing:"-0.01em",fontWeight:"600"}]}}}};</script>
<style>.material-symbols-outlined {font-family: 'Material Symbols Outlined';font-weight: normal;font-style: normal;font-size: 20px;display: inline-block;line-height: 1;text-transform: none;letter-spacing: normal;word-wrap: normal;white-space: nowrap;direction: ltr;}</style>
</head>
<body class="bg-background text-on-background min-h-screen font-body-md antialiased overflow-x-hidden">

<nav id="sidebar" class="fixed left-0 top-0 h-full w-[260px] border-r border-[#E5E5E1] dark:border-slate-800 bg-[#FBFBFA] dark:bg-slate-950 flex flex-col p-4 gap-2 z-50 font-manrope text-sm antialiased transform -translate-x-full md:translate-x-0 transition-transform duration-300">
    <div class="mb-6 px-2">
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">CRM Admin</h1>
        <p class="text-slate-500 dark:text-slate-400 text-xs mt-1"><?= htmlspecialchars($_SESSION['user']['role']) ?> Console</p>
    </div>

    <div class="flex-1 flex flex-col gap-1">
        <a href="/pages/dashboard.php" class="<?= navClass('/pages/dashboard.php') ?>">
            <span class="material-symbols-outlined" style="<?= iconFill('/pages/dashboard.php') ?>">dashboard</span>
            Dashboard
        </a>
        <a href="/pages/clients/index.php" class="<?= navClass('/pages/clients/index.php') ?>">
            <span class="material-symbols-outlined" style="<?= iconFill('/pages/clients/index.php') ?>">group</span>
            Clients
        </a>
        <a href="/pages/tickets/index.php" class="<?= navClass('/pages/tickets/index.php') ?>">
            <span class="material-symbols-outlined" style="<?= iconFill('/pages/tickets/index.php') ?>">confirmation_number</span>
            Tickets
        </a>
        <?php if ($_SESSION['user']['role'] == "Admin"): ?>
            <a href="/pages/roles/manage.php" class="<?= navClass('/pages/roles/manage.php') ?>">
                <span class="material-symbols-outlined" style="<?= iconFill('/pages/roles/manage.php') ?>">account_balance</span>
                Comptes
            </a>
        <?php endif; ?>
        <a href="/pages/profile/editProfile.php" class="<?= navClass('/pages/profile/editProfile.php') ?>">
            <span class="material-symbols-outlined" style="<?= iconFill('/pages/profile/editProfile.php') ?>">person</span>
            Profil
        </a>
        <a href="/gmail/index.php" class="<?= navClass('/gmail/index.php') ?> <?= $gmailStatusClasses[$gmailStatus] ?? '' ?>"> 
            <span class="material-symbols-outlined" style="<?= iconFill('/gmail/index.php') ?>">mail</span>
                Gmail
                <?php if ($gmailStatus !== "Active"): ?>
                <span class="ml-1 text-xs font-semibold <?= $gmailStatus === 'Expired' || $gmailStatus === 'Disconnected' ? 'text-red-600 dark:text-red-400' : 'text-yellow-700 dark:text-yellow-400' ?>"> <?= htmlspecialchars($gmailStatus) ?></span>
                <?php endif; ?>
        </a>
    </div>
    <div class="mt-auto pt-4 border-t border-[#E5E5E1] dark:border-slate-800">
        <a href="/logout.php" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors duration-200 flex items-center gap-3 px-3 py-2 rounded-lg active:scale-[0.98] transition-transform"> <span class="material-symbols-outlined">logout</span>Déconnexion</a>
    </div>
</nav>

<header class="fixed top-0 right-0 w-full md:w-[calc(100%-260px)] h-12 md:h-16 z-40 border-b border-[#E5E5E1] dark:border-slate-800 shadow-sm bg-white/90 dark:bg-slate-900/90 backdrop-blur-md flex justify-between items-center px-4 md:px-8 font-manrope text-sm text-blue-600 dark:text-blue-400">
    <button id="sidebarToggle" class="md:hidden p-2 rounded-md bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700">
        <span class="material-symbols-outlined">menu</span>
    </button>

    <div class="flex items-center gap-2 md:gap-4 w-full md:w-1/3">
        <div class="flex items-center gap-2 md:gap-3">
            <h1 class="font-h3 text-h3 md:text-h1 text-on-surface truncate">
                Bonjour, <?= htmlspecialchars($_SESSION['user']['first_name'] ?? $_SESSION['user']['name'] ?? 'User') ?>
            </h1>
        </div>
    </div>

    <div class="flex items-center gap-3 md:gap-6">
        <span class="px-2 py-1 rounded-full bg-secondary-container/20 text-secondary border border-secondary-container/30 font-label-caps text-[10px] md:text-label-caps">
            <?= htmlspecialchars($_SESSION['user']['role']) ?>
        </span>
    </div>
</header>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('-translate-x-full');
});
</script>
