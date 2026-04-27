<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

if ($_SESSION['user']['role'] != 'Admin') die("Accès refusé");

$message = "";
$roles = $db->getAllRoles();

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) die("ID utilisateur manquant.");

$user = $db->getUserById($id);

if (!$user) die("Utilisateur introuvable.");

if (isset($_POST['update_user'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $role_id    = trim($_POST['role_id'] ?? '');

    if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($role_id)) {
        $db->updateUser($id, $first_name, $last_name, $email, $role_id);

        if (!empty($password)) { $db->updatePassword($id,$password);}

        header("Location: /pages/roles/manage.php");
        exit;
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }

    $user['first_name'] = $first_name;
    $user['last_name']  = $last_name;
    $user['email']      = $email;
    $user['role_id']    = $role_id;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modifier le compte</title>

    <?php include __DIR__ . '/../../includes/headerN.php'; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
        <div class="w-full max-w-2xl mt-8">

            <div class="mb-8">
                <div class="flex items-center gap-2 text-slate-500 mb-2 font-body-sm">
                    <a class="hover:text-primary transition-colors" href="/pages/roles/manage.php">Comptes</a>
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                    <span class="text-on-surface font-medium"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                </div>

                <h1 class="font-h1 text-h1 text-on-surface mb-2">Modifier le compte</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Mettez à jour les informations et le rôle de cet utilisateur interne.</p>

                <?php if (!empty($message)): ?>
                    <p class="mt-4 text-sm font-medium text-error"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            </div>

            <div class="bg-surface-container-lowest border border-[#E5E5E1] rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] overflow-hidden">
                <form class="p-md space-y-6" method="POST">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="space-y-1.5">
                            <label class="font-body-sm text-body-sm font-semibold text-on-surface block" for="first_name">Prénom</label>
                            <input class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" id="first_name" name="first_name" type="text" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-body-sm text-body-sm font-semibold text-on-surface block" for="last_name">Nom</label>
                            <input class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" id="last_name" name="last_name" type="text" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>

                    </div>

                    <div class="space-y-1.5">
                        <label class="font-body-sm text-body-sm font-semibold text-on-surface block" for="email">Email professionnel</label>
                        <input class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-body-sm text-body-sm font-semibold text-on-surface block flex justify-between items-center" for="password">Mot de passe <span class="text-primary font-medium text-xs">Réinitialiser</span></label>

                        <div class="relative">
                            <input class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" id="password" name="password" placeholder="••••••••••••" type="password">
                            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors" type="button" onclick="togglePassword()"><span id="passwordIcon" class="material-symbols-outlined text-sm">visibility</span></button>
                        </div>

                        <p class="font-body-sm text-xs text-on-surface-variant">Laissez vide pour conserver le mot de passe actuel.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-body-sm text-body-sm font-semibold text-on-surface block" for="role_id">Rôle de l'utilisateur</label>

                        <div class="relative">
                            <select class="w-full px-3 py-2 bg-white border border-[#E5E5E1] rounded-lg font-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-shadow appearance-none cursor-pointer" id="role_id" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= ($role['id'] == $user['role_id']) ? 'selected' : '' ?>><?= htmlspecialchars($role['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                        </div>
                    </div>

                    <hr class="border-[#E5E5E1] my-6" />

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button class="px-4 py-2 font-body-sm font-semibold text-slate-600 bg-white border border-[#E5E5E1] rounded-lg hover:bg-slate-50 transition-colors" type="button" onclick="window.location.href='/pages/roles/manage.php'">Annuler</button>
                        <button class="px-4 py-2 font-body-sm font-semibold text-white bg-primary rounded-lg hover:bg-blue-700 transition-colors shadow-sm" type="submit" name="update_user">Sauvegarder les modifications</button>
                    </div>
                </form>
            </div>

            <div class="mt-8 border border-red-200 bg-red-50/30 rounded-xl p-md">
                <h3 class="font-h3 text-h3 text-error mb-2">Zone de danger</h3>

                <div class="flex items-center justify-between">
                    <p class="font-body-sm text-body-sm text-on-surface-variant max-w-sm">Supprimer définitivement ce compte utilisateur et toutes les données associées non transférées.</p>
                    <button id="openDeleteModal" class="px-4 py-2 font-body-sm font-semibold text-error bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors">Supprimer le compte</button>
                </div>
            </div>
            <div id="deleteModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 max-w-md w-full shadow-lg">
                    <h3 class="text-lg font-semibold text-red-600 mb-4">Supprimer ce compte ?</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300 mb-4">
                        <?= htmlspecialchars($user['first_name'] ?? '') ?> <?= htmlspecialchars($user['last_name'] ?? '') ?> <br>
                    </p>
                    <div class="flex justify-end gap-2">
                        <button id="cancelDelete" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Annuler</button>
                        <a href="/pages/roles/manage.php?delete=<?= $user['id'] ?>" id="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Confirmer</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                passwordIcon.textContent = 'visibility';
            }
        }

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