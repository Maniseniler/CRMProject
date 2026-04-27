<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

if ($_SESSION['user']['role'] != 'Admin') die("Accès refusé");

$message = "";

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    if ($id != $_SESSION['user']['id']) {
        $db->deleteUser($id);
        $message = "Compte supprimé avec succès.";
    } else $message = "Vous ne pouvez pas supprimer votre propre compte.";
}

$roles = $db->getAllRoles();
$users = $db->getAllUsers();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Comptes</title>

    <?php include __DIR__ . '/../../includes/headerN.php'; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">

        <div class="mb-8 flex justify-between items-start">
            <div>
                <h2 class="font-h1 text-h1 text-on-background">Comptes</h2>
                <p class="font-body-md text-on-surface-variant mt-1">Manage system access and user roles.</p>

                <?php if (!empty($message)): ?>
                    <p class="text-green-600"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            </div>

            <button onclick="window.location.href='./addAcc.php'" class="bg-primary hover:bg-primary-container text-on-primary font-body-sm font-semibold px-4 py-2.5 rounded-md shadow-sm transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">add</span> Ajouter un Compte</button>
        </div>

        <div class="flex justify-between items-center mb-sm">
            <div class="flex gap-sm w-full max-w-lg">
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input id="searchInput" class="w-full pl-10 pr-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-DEFAULT font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none" placeholder="Filter by name, email or ID..." type="text">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-gutter items-start">

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0_2px_4px_rgba(0,0,0,0.04)] overflow-hidden flex flex-col h-full">

                <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low/50">
                    <h3 class="font-h3 text-h3 text-on-surface">Active Accounts</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">

                        <thead>
                            <tr class="bg-surface-container-lowest border-b border-outline-variant">
                                <th class="px-md py-3 text-on-surface-variant w-16">ID</th>
                                <th class="px-md py-3 text-on-surface-variant">Name</th>
                                <th class="px-md py-3 text-on-surface-variant">Email</th>
                                <th class="px-md py-3 text-on-surface-variant w-32">Role</th>
                                <th class="px-md py-3 text-right w-32">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-outline-variant/50">

                            <?php foreach ($users as $user): ?>
                                <?php
                                $isCurrentUser = $user['id'] == $_SESSION['user']['id'];

                                $roleClass = match (strtolower($user['role_name'])) {
                                    'admin' => 'bg-tertiary-container/20 text-tertiary-container border border-tertiary-container/30',
                                    'manager' => 'bg-secondary-container/20 text-secondary border border-secondary-container/30',
                                    default => 'bg-surface-variant text-on-surface-variant border border-outline-variant',
                                };
                                ?>

                                <tr class="<?= $isCurrentUser ? 'bg-primary-fixed/10 hover:bg-primary-fixed/20' : 'hover:bg-surface-container' ?> transition-colors group">

                                    <form method="POST">

                                        <td class="px-md py-4 text-on-surface-variant font-mono">
                                            #<?= $user['id'] ?>
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        </td>

                                        <td class="px-md py-4 flex items-center gap-2">
                                            <?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?>

                                            <?php if ($isCurrentUser): ?>
                                                <span class="bg-primary text-on-primary px-2 py-0.5 rounded-full text-[10px]">YOU</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-md py-4 text-on-surface-variant font-mono"><?= htmlspecialchars($user['email']) ?></td>

                                        <td class="px-md py-4">
                                            <span class="px-2.5 py-1 rounded-full text-xs <?= $roleClass ?>"><?= htmlspecialchars($user['role_name']) ?></span>
                                        </td>

                                        <td class="px-md py-4 text-right">
                                            <div class="flex justify-end gap-3 items-center">

                                                <?php if ($isCurrentUser): ?>
                                                    <a href="../profile/editProfile.php" class="text-primary flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">edit</span> Modifier</a>
                                                <?php else: ?>
                                                    <a href="./editAcc.php?id=<?= $user['id'] ?>" class="text-primary flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">edit</span> Modifier</a>
                                                <?php endif; ?>

                                            </div>
                                        </td>

                                    </form>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

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