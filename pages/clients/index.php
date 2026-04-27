<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

$message = "";

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    if ($id > 0) {
        $db->deleteClient($id);
        $message = "Client supprimé avec succès.";
    } else $message = "Client invalide.";
}

$clients = $db->getAllClients();

function clientInitials($firstName, $lastName) {
    $a = strtoupper(substr($firstName ?? '', 0, 1));
    $b = strtoupper(substr($lastName ?? '', 0, 1));
    return trim($a . $b) ?: '?';
}

function avatarClass($index) {
    $classes = ['bg-secondary-container text-on-secondary','bg-tertiary-container text-on-tertiary','bg-primary-container text-on-primary',];
    return $classes[$index % count($classes)];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Clients</title>

    <?php include __DIR__ . "/../../includes/headerN.php"; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
        
        <div class="flex justify-between items-center mb-md">
            <div>
                <h1 class="font-h1 text-h1 text-on-surface">Clients</h1>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1"> Manage and view all your client relationships.</p>

                <?php if (!empty($message)): ?>
                    <p class="text-success mt-2"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($_SESSION['user']['role'] == "Admin" || $_SESSION['user']['role'] == "Manager"): ?>
                <a href="add.php" class="bg-primary hover:bg-primary-container text-on-primary font-body-sm font-semibold px-4 py-2.5 rounded-md shadow-sm transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">add</span>Ajouter un client</a>
            <?php endif; ?>
        </div>

        <div class="flex justify-between items-center mb-sm">
            <div class="flex gap-sm w-full max-w-lg">
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input id="searchInput" class="w-full pl-10 pr-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-DEFAULT font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none" placeholder="Filter by name, email or ID..." type="text">
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant shadow-sm rounded-lg overflow-hidden flex flex-col">
            <div class="overflow-x-auto">

                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead class="bg-surface-container-low sticky top-0 z-10 border-b border-outline-variant">
                        <tr>
                            <th class="font-label-caps text-label-caps text-on-surface-variant p-sm font-semibold w-24">ID</th>
                            <th class="font-label-caps text-label-caps text-on-surface-variant p-sm font-semibold">Nom Complet</th>
                            <th class="font-label-caps text-label-caps text-on-surface-variant p-sm font-semibold">Email</th>
                            <th class="font-label-caps text-label-caps text-on-surface-variant p-sm font-semibold">Téléphone</th>

                            <?php if ($_SESSION['user']['role'] == "Admin" || $_SESSION['user']['role'] == "Manager"): ?>
                                <th class="font-label-caps text-label-caps text-on-surface-variant p-sm font-semibold text-right w-32">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody class="font-body-sm text-body-sm text-on-surface">
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $index => $client): ?>

                                <tr
                                    class="border-b border-outline-variant/50 hover:bg-surface-container-low transition-colors group cursor-pointer <?= $index % 2 === 1 ? 'bg-surface-bright' : '' ?>"
                                    onclick="window.location.href='view.php?id=<?= (int)$client['id'] ?>'"
                                >
                                    <td class="p-sm font-mono text-outline">
                                        CLI-<?= str_pad((string)$client['id'], 3, '0', STR_PAD_LEFT) ?>
                                    </td>

                                    <td class="p-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full <?= avatarClass($index) ?> flex items-center justify-center font-bold text-xs">
                                                <?= htmlspecialchars(clientInitials($client['first_name'], $client['last_name'])) ?>
                                            </div>

                                            <span class="font-medium text-on-surface">
                                                <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="p-sm text-on-surface-variant">
                                        <?= htmlspecialchars($client['email'] ?? '-') ?>
                                    </td>

                                    <td class="p-sm text-on-surface-variant">
                                        <?= htmlspecialchars($client['phone'] ?? '-') ?>
                                    </td>

                                    <?php if ($_SESSION['user']['role'] == "Admin" || $_SESSION['user']['role'] == "Manager"): ?>
                                        <td class="p-sm" onclick="event.stopPropagation();">
                                            <div class="flex justify-end gap-3 items-center">
                                                <a href="edit.php?id=<?= (int)$client['id'] ?>" class="text-primary hover:text-primary-container font-medium text-sm transition-colors flex items-center gap-1" title="Modifier"><span class="material-symbols-outlined text-[16px]">edit</span>Modifier</a>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-md text-center text-on-surface-variant"> Aucun client trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>

    </main>

    <script>
        document.getElementById('searchInput').addEventListener('input', function () {
            const search = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });
    </script>
</body>

</html>
