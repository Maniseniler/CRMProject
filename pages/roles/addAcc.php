<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

if ($_SESSION['user']['role'] != 'Admin') die("Accès refusé");

$message = "";
$roles = $db->getAllRoles();

if (isset($_POST['add_user'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $role_id    = trim($_POST['role_id'] ?? '');

    if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($password) && !empty($role_id)) {
        $db->addUser($first_name, $last_name, $email, $password, $role_id);
        header("Location: /pages/roles/manage.php");
        exit;
    } else $message = "Veuillez remplir tous les champs.";
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create New User</title>

    <?php include __DIR__ . '/../../includes/headerN.php'; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">

        <header class="sticky top-0 z-10 bg-white/80 backdrop-blur-md border-b border-[#E5E5E1] px-lg py-sm flex items-center justify-between">
            <div class="flex items-center gap-xs">
                <button onclick="window.location.href='./manage.php'" type="button" class="flex items-center text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20 rounded-md p-1"><span class="material-symbols-outlined text-[20px]">arrow_back</span><span class="ml-1 font-body-sm font-medium">Accounts</span></button>
            </div>
            <div class="text-outline font-body-sm hidden md:block">Workspace / Accounts / New User</div>
        </header>

        <div class="max-w-[800px] mx-auto w-full px-md md:px-lg py-xl pb-24">

            <div class="mb-lg">
                <h1 class="font-h1 text-h1 text-on-surface mb-xs">Create New User</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Provision a new internal account and assign workspace roles.</p>

                <?php if (!empty($message)): ?>
                    <p class="mt-4 text-sm font-medium text-error"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            </div>

            <div class="bg-surface-container-lowest border border-[#E5E5E1] rounded-lg shadow-[0_2px_4px_rgba(0,0,0,0.04)] overflow-hidden">

                <form class="divide-y divide-[#E5E5E1]" method="POST">

                    <div class="p-md lg:p-lg flex flex-col md:flex-row gap-lg">
                        <div class="md:w-1/3">
                            <h2 class="font-h3 text-h3 text-on-surface mb-base">Personal Details</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Basic information to identify the user.</p>
                        </div>

                        <div class="md:w-2/3 space-y-sm">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">

                                <div>
                                    <label class="block font-body-sm font-bold text-on-surface mb-base" for="first_name">First Name</label>
                                    <input id="first_name" name="first_name" type="text" placeholder="Ibrahim" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT px-3 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                                </div>

                                <div>
                                    <label class="block font-body-sm font-bold text-on-surface mb-base" for="last_name">Last Name</label>
                                    <input id="last_name" name="last_name" type="text" placeholder="Bouchaala" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT px-3 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                                </div>

                            </div>

                            <div>
                                <label class="block font-body-sm font-bold text-on-surface mb-base" for="email">Email Address</label>
                                <input id="email" name="email" type="email" placeholder="ibrahim.bouchaala@gmail.com" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT px-3 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>

                        </div>
                    </div>

                    <div class="p-md lg:p-lg flex flex-col md:flex-row gap-lg">
                        <div class="md:w-1/3">
                            <h2 class="font-h3 text-h3 text-on-surface mb-base">Security</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Initial authentication credentials.</p>
                        </div>

                        <div class="md:w-2/3 space-y-sm">
                            <div>
                                <label class="block font-body-sm font-bold text-on-surface mb-base" for="password">Password</label>
                                <div class="relative">
                                    <input id="password" name="password" type="password" placeholder="••••••••" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" required>
                                    <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-outline hover:text-on-surface transition-colors"><span id="passwordIcon" class="material-symbols-outlined text-[20px]">visibility_off</span></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-md lg:p-lg flex flex-col md:flex-row gap-lg bg-surface-container/30">
                        <div class="md:w-1/3">
                            <h2 class="font-h3 text-h3 text-on-surface mb-base">Workspace Role</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Define permissions and access levels.</p>
                        </div>

                        <div class="md:w-2/3 space-y-3">

                            <?php foreach ($roles as $role): ?>
                                <?php
                                    $roleName = strtolower($role['name']);
                                    $description = match ($roleName) {'admin' => 'Full access to workspace settings, billing, and all user accounts.','manager' => 'Can manage teams, view all client data, but no access to billing.','commercial' => 'Standard agent access. Can view and interact with assigned clients only.',default => 'Workspace access for this role.'};
                                    $checked = (string)($role['id']) === (string)($_POST['role_id'] ?? '') ? 'checked' : '';
                                ?>

                                <label class="relative flex items-start p-4 cursor-pointer bg-surface-container-lowest border border-[#E5E5E1] rounded-lg hover:border-primary/50 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                    <div class="flex items-center h-5">
                                        <input name="role_id" type="radio" value="<?= $role['id'] ?>" class="w-4 h-4 text-primary border-outline focus:ring-primary focus:ring-offset-surface-container-lowest" <?= $checked ?> required>
                                    </div>

                                    <div class="ml-3 flex flex-col">
                                        <span class="font-body-sm font-bold text-on-surface flex items-center gap-2">
                                            <?= htmlspecialchars($role['name']) ?>
                                            <?php if ($roleName === 'manager'): ?>
                                                <span class="bg-surface-container text-on-surface-variant px-1.5 py-0.5 rounded text-[10px] uppercase tracking-wider font-semibold">Recommended</span>
                                            <?php endif; ?>
                                        </span>

                                        <span class="font-body-sm text-body-sm text-on-surface-variant mt-0.5"><?= htmlspecialchars($description) ?></span>
                                    </div>
                                </label>

                            <?php endforeach; ?>

                        </div>
                    </div>

                    <div class="p-md lg:p-lg flex items-center justify-end gap-sm bg-surface-bright">
                        <button type="button" onclick="window.location.href='./manage.php'" class="px-4 py-2 border rounded">Cancel</button>
                        <button type="submit" name="add_user" class="bg-primary hover:bg-primary-container text-on-primary font-body-sm font-semibold px-4 py-2.5 rounded-md shadow-sm transition-colors flex items-center gap-2"><span>Create User</span><span class="material-symbols-outlined text-[18px]">person_add</span></button>
                    </div>

                </form>

            </div>
        </div>
    </main>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.textContent = 'visibility';
            } else {
                passwordInput.type = 'password';
                passwordIcon.textContent = 'visibility_off';
            }
        }
    </script>
</body>

</html>
