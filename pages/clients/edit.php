<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

$client = $db->getClientById($id);
if (!$client) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $address    = trim($_POST['address'] ?? '');

    if ($first_name && $last_name) {
        $existingClient = $db->getClientByEmail($email);
        if ($email && $existingClient && $existingClient['id'] != $id) {
            $message = "Cet email est déjà utilisé pour un autre client.";
        } else {
            $db->updateClient($id, $first_name, $last_name, $email, $phone, $address);
            header("Location: index.php");
            exit();
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires.";
    }
}

$clientEmails = $db->getClientConversation($id);
$clientTickets = $db->getTicketsByClient($id);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modifier le Client</title>

    <?php include __DIR__ . "/../../includes/headerN.php"; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
        <div class="w-full max-w-2xl mt-8">

            <div class="mb-8">
                <div class="flex items-center gap-2 text-slate-500 mb-2 font-body-sm">
                    <a class="hover:text-primary" href="/pages/clients/index.php">Clients</a>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <span class="text-on-surface font-medium">
                        <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>
                    </span>
                </div>

                <h1 class="font-h1 text-h1 text-on-surface mb-2">Modifier le client</h1>
                <p class="font-body-md text-on-surface-variant">Mettre à jour les informations du client.</p>

                <?php if (!empty($message)): ?>
                    <p class="mt-4 text-sm text-error"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow p-md">
                <form method="POST" class="space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="font-body-sm font-semibold">Prénom</label>
                            <input class="w-full px-3 py-2 border rounded-lg" name="first_name" value="<?= htmlspecialchars($client['first_name']) ?>" required>
                        </div>

                        <div>
                            <label class="font-body-sm font-semibold">Nom</label>
                            <input class="w-full px-3 py-2 border rounded-lg" name="last_name" value="<?= htmlspecialchars($client['last_name']) ?>" required>
                        </div>
                    </div>

                    <div>
                        <label class="font-body-sm font-semibold">Email</label>
                        <input class="w-full px-3 py-2 border rounded-lg" name="email" type="email" value="<?= htmlspecialchars($client['email']) ?>">
                    </div>

                    <div>
                        <label class="font-body-sm font-semibold">Téléphone</label>
                        <input class="w-full px-3 py-2 border rounded-lg" name="phone" value="<?= htmlspecialchars($client['phone']) ?>">
                    </div>

                    <div>
                        <label class="font-body-sm font-semibold">Adresse</label>
                        <input class="w-full px-3 py-2 border rounded-lg" name="address" value="<?= htmlspecialchars($client['address']) ?>">
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="index.php" class="px-4 py-2 border rounded-lg">Annuler</a>
                        <button class="px-4 py-2 bg-primary text-white rounded-lg">Sauvegarder</button>
                    </div>

                </form>
            </div>

            <div class="mt-8 border border-red-200 bg-red-50/30 rounded-xl p-md">
                <h3 class="font-h3 text-h3 text-error mb-2">Zone de danger</h3>
                <div class="flex items-center justify-between">
                    <p class="font-body-sm text-body-sm text-on-surface-variant max-w-sm">Supprimer définitivement ce client utilisateur et toutes les données associées non transférées.</p>
                    <button id="openDeleteModal" class="px-4 py-2 font-body-sm font-semibold text-error bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors">Supprimer le client</button>
                </div>
            </div>

            <div id="deleteModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 max-w-md w-full shadow-lg">
                    <h3 class="text-lg font-semibold text-red-600 mb-4">Supprimer ce client ?</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300 mb-4">
                        <?= htmlspecialchars($client['first_name'] ?? '') ?> <?= htmlspecialchars($client['last_name'] ?? '') ?> <br>
                        Emails: <?= count($clientEmails ?? []) ?>, Tickets: <?= count($clientTickets ?? []) ?>
                    </p>
                    <div class="flex justify-end gap-2">
                        <button id="cancelDelete" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Annuler</button>
                        <a href="/pages/clients/index.php?delete=<?= $client['id'] ?>" id="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Confirmer</a>
                    </div>
                </div>
            </div>

        </div>
    </main>
    <script>
    const openBtn = document.getElementById('openDeleteModal');
    const modal = document.getElementById('deleteModal');
    const cancelBtn = document.getElementById('cancelDelete');

    openBtn.addEventListener('click', () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    cancelBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
    </script>
</body>

</html>
