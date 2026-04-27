<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

$user_id = $_SESSION['user']['id'];
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');

    if ($first_name === '' || $last_name === '' || $email === '') {
        $message = "Veuillez remplir tous les champs du profil.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Veuillez saisir une adresse email valide.";
        $messageType = "error";
    } elseif ($db->isEmailTakenByAnotherUser($email, $user_id)) {
        $message = "Cette adresse email est déjà utilisée par un autre utilisateur.";
        $messageType = "error";
    } else {
        try {
            $db->updateProfile($user_id, $first_name, $last_name, $email);
            $updatedUser = $db->getUserById($user_id);
            if ($updatedUser) {
                $_SESSION['user']['first_name'] = $updatedUser['first_name'];
                $_SESSION['user']['last_name']  = $updatedUser['last_name'];
                $_SESSION['user']['email']      = $updatedUser['email'];
            }
            $message = "Profil mis à jour avec succès.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Erreur lors de la mise à jour du profil.";
            $messageType = "error";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_password'])) {
    $old_password     = trim($_POST['old_password'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($old_password === '' || $new_password === '' || $confirm_password === '') {
        $message = "Veuillez remplir tous les champs du mot de passe.";
        $messageType = "error";
    } elseif (!$db->getUserById($user_id)) {
        $message = "Utilisateur introuvable.";
        $messageType = "error";
    } elseif (!$db->verifyUserPassword($user_id, $old_password)) {
        $message = "L'ancien mot de passe est incorrect.";
        $messageType = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "La confirmation du nouveau mot de passe ne correspond pas.";
        $messageType = "error";
    } elseif (strlen($new_password) < 6) {
        $message = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        $messageType = "error";
    } else {
        try {
            $db->updatePassword($user_id, $new_password);
            $message = "Mot de passe mis à jour avec succès.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Erreur lors de la mise à jour du mot de passe.";
            $messageType = "error";
        }
    }
}

$user = $db->getUserById($user_id);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil</title>

    <?php include __DIR__ . '/../../includes/headerN.php'; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
        <div class="max-w-container-max mx-auto p-lg">

            <div class="mb-lg">
                <h2 class="font-h2 text-h2 text-on-surface">Paramètres du profil</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Gérez vos informations personnelles et préférences de compte.</p>

                <?php if ($message !== ''): ?>
                    <p class="mt-3 text-sm font-medium <?= $messageType === 'success' ? 'text-green-600' : 'text-error' ?>"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-md items-start">
                <div class="lg:col-span-2 flex flex-col gap-md">

                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-md shadow-[0px_2px_4px_rgba(0,0,0,0.04)]">
                        <h3 class="font-h3 text-h3 text-on-surface border-b border-outline-variant pb-sm mb-md">Informations générales</h3>

                        <form method="POST" class="space-y-sm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-sm">

                                <div class="flex flex-col">
                                    <label class="font-label-caps text-label-caps text-on-surface-variant mb-base" for="prenom">PRÉNOM</label>
                                    <input id="prenom" name="first_name" type="text" class="bg-surface border border-outline-variant rounded-md px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                                </div>

                                <div class="flex flex-col">
                                    <label class="font-label-caps text-label-caps text-on-surface-variant mb-base" for="nom">NOM</label>
                                    <input id="nom" name="last_name" type="text" class="bg-surface border border-outline-variant rounded-md px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                                </div>

                            </div>

                            <div class="flex flex-col mt-sm">
                                <label class="font-label-caps text-label-caps text-on-surface-variant mb-base" for="email">EMAIL</label>
                                <input id="email" name="email" type="email" class="bg-surface border border-outline-variant rounded-md px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors w-full" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>

                            <div class="border-t border-outline-variant pt-md flex justify-end gap-3">
                                <button type="submit" name="update_profile" class="font-label-caps text-label-caps text-on-primary bg-primary hover:bg-primary-container py-2 px-6 rounded-lg shadow-sm transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">save</span> SAUVEGARDER</button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-md shadow-[0px_2px_4px_rgba(0,0,0,0.04)]">
                        <h3 class="font-h3 text-h3 text-on-surface border-b border-outline-variant pb-sm mb-md">Changer le mot de passe</h3>

                        <form method="POST" class="space-y-sm">

                            <div class="flex flex-col">
                                <label class="font-label-caps text-label-caps text-on-surface-variant mb-base" for="ancien-mdp">ANCIEN MOT DE PASSE</label>
                                <input id="ancien-mdp" name="old_password" type="password" class="bg-surface border border-outline-variant rounded-md px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors w-full" required>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-sm mt-sm pb-md">

                                <div class="flex flex-col">
                                    <label class="font-label-caps text-label-caps text-on-surface-variant mb-base" for="nouveau-mdp">NOUVEAU MOT DE PASSE</label>
                                    <input id="nouveau-mdp" name="new_password" type="password" class="bg-surface border border-outline-variant rounded-md px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors w-full" required>
                                </div>

                                <div class="flex flex-col">
                                    <label class="font-label-caps text-label-caps text-on-surface-variant mb-base" for="confirmer-mdp">CONFIRMER LE NOUVEAU MOT DE PASSE</label>
                                    <input id="confirmer-mdp" name="confirm_password" type="password" class="bg-surface border border-outline-variant rounded-md px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors w-full" required>
                                </div>

                            </div>

                            <div class="border-t border-outline-variant pt-md flex justify-end">
                                <button type="submit" name="update_password" class="font-label-caps text-label-caps text-on-primary bg-primary hover:bg-primary-container py-2 px-6 rounded-lg shadow-sm transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">lock</span> METTRE À JOUR LE MOT DE PASSE</button>
                            </div>

                        </form>
                    </div>

                </div>

                <div class="lg:col-span-1 bg-surface-container-lowest border border-outline-variant rounded-xl p-md shadow-[0px_2px_4px_rgba(0,0,0,0.04)] flex flex-col items-center text-center">
                    <h3 class="font-h3 text-h3 text-on-surface mb-base"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h3>

                    <span class="font-label-caps text-label-caps text-primary bg-primary-fixed py-1 px-3 rounded-full mb-md"><?= htmlspecialchars($_SESSION['user']['role'] ?? 'Utilisateur') ?></span>

                    <div class="w-full border-t border-outline-variant my-sm"></div>

                    <div class="w-full text-left space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="font-body-sm text-body-sm text-on-surface-variant">Email</span>
                            <span class="font-body-sm text-body-sm text-on-surface font-semibold break-all text-right"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>
</body>

</html>
