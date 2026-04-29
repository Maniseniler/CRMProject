<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $address    = trim($_POST['address'] ?? '');

    if ($first_name && $last_name) {
        if ($email && $db->getClientByEmail($email)) {
            $message = "Cet email est déjà utilisé pour un autre client.";
        } else {
            $db->addClient($first_name, $last_name, $email, $phone, $address);
            header("Location: index.php");
            exit();
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires.";
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add New Client</title>

    <?php include __DIR__ . '/../../includes/headerN.php'; ?>
</head>

<body>
    <main class="flex-1 mt-16 ml-0 md:ml-60 lg:ml-64 p-4 sm:p-6 lg:p-8 flex flex-col gap-6">
        <div class="max-w-4xl mx-auto">

            <div class="mb-lg">
                <nav class="flex items-center gap-2 text-on-surface-variant text-sm mb-2">
                    <a href="index.php" class="hover:text-primary">Clients</a>
                    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    <span class="text-on-surface font-medium">Ajouter</span>
                </nav>

                <h2 class="text-2xl font-bold text-on-surface">Ajouter un client</h2>
                <p class="text-on-surface-variant mt-1">Créer un nouveau client.</p>

                <?php if ($message): ?>
                    <p class="mt-3 text-sm text-error">
                        <?= htmlspecialchars($message) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="bg-white border rounded-lg shadow-sm">
                <form method="POST" class="p-6 space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label class="block font-semibold mb-1">Prénom</label>
                            <input name="first_name" placeholder="Ibrahim" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" required>
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Nom</label>
                            <input name="last_name" placeholder="Bouchaala" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" required>
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Email</label>
                            <input name="email" type="email" placeholder="ibrahim.bouchaala@gmail.com" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant" required>
                        </div>

                        <div>
                            <label class="block font-semibold mb-1">Téléphone</label>
                            <input name="phone" placeholder="20476972" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block font-semibold mb-1">Adresse</label>
                            <textarea name="address" placeholder="12 Rue Habib Bourguiba, 3000 Sfax, Tunisie" class="w-full bg-surface-bright border border-[#E5E5E1] rounded-DEFAULT pl-3 pr-10 py-2 text-on-surface font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors placeholder:text-outline-variant"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="index.php" class="px-4 py-2 border rounded">Annuler</a>
                        <button class="px-4 py-2 bg-primary text-white rounded">Enregistrer</button>
                    </div>

                </form>
            </div>

        </div>
    </main>
</body>

</html>
