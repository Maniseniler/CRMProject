<?php
session_start();
require_once "../config/db.php";

if (isset($_SESSION['user'])) {
    header("Location: /pages/dashboard.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $user = $db->getUserByEmail($email);

    if ($user && $password === $user["password"]) {
        $_SESSION["user"] = ["id" => $user["id"],"name" => $user["first_name"] . " " . $user["last_name"],"email" => $user["email"],"role" => $user["role_name"]];

        header("Location: dashboard.php");
        exit();
    } else $error = "Email ou mot de passe incorrect.";
}
?>
<!DOCTYPE html>
<html class="light" lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">tailwind.config = {darkMode:"class",theme:{extend:{colors:{"on-primary-container":"#eeefff","error":"#ba1a1a","surface-tint":"#0053db","secondary-fixed-dim":"#c0c1ff","on-primary-fixed-variant":"#003ea8","on-tertiary-fixed-variant":"#7d2d00","secondary-fixed":"#e1e0ff","surface-container":"#ededf9","surface-variant":"#e1e2ed","surface-dim":"#d9d9e5","outline":"#737686","on-secondary-fixed-variant":"#2f2ebe","on-tertiary-container":"#ffede6","surface-container-low":"#f3f3fe","background":"#faf8ff","inverse-primary":"#b4c5ff","tertiary":"#943700","tertiary-fixed":"#ffdbcd","primary":"#004ac6","surface-container-highest":"#e1e2ed","outline-variant":"#c3c6d7","on-secondary":"#ffffff","primary-container":"#2563eb","surface-container-lowest":"#ffffff","inverse-on-surface":"#f0f0fb","on-error-container":"#93000a","primary-fixed-dim":"#b4c5ff","on-tertiary-fixed":"#360f00","primary-fixed":"#dbe1ff","on-background":"#191b23","on-surface-variant":"#434655","on-secondary-container":"#fffbff","surface":"#faf8ff","on-surface":"#191b23","inverse-surface":"#2e3039","on-tertiary":"#ffffff","error-container":"#ffdad6","on-primary-fixed":"#00174b","secondary":"#4648d4","surface-container-high":"#e7e7f3","tertiary-container":"#bc4800","secondary-container":"#6063ee","surface-bright":"#faf8ff","on-error":"#ffffff","on-primary":"#ffffff","on-secondary-fixed":"#07006c","tertiary-fixed-dim":"#ffb596"},borderRadius:{"DEFAULT":"0.25rem","lg":"0.5rem","xl":"0.75rem","full":"9999px"},spacing:{"xs":"8px","container-max":"1440px","gutter":"20px","md":"24px","sm":"16px","base":"4px","xl":"48px","sidebar-width":"260px","lg":"32px"},fontFamily:{"h1":["Manrope"],"mono":["Inter"],"body-md":["Inter"],"label-caps":["Inter"],"body-sm":["Inter"],"h3":["Manrope"],"h2":["Manrope"]},fontSize:{"h1":["32px",{lineHeight:"40px",letterSpacing:"-0.02em",fontWeight:"700"}],"mono":["13px",{lineHeight:"20px",letterSpacing:"0",fontWeight:"500"}],"body-md":["15px",{lineHeight:"24px",letterSpacing:"0",fontWeight:"400"}],"label-caps":["12px",{lineHeight:"16px",letterSpacing:"0.05em",fontWeight:"600"}],"body-sm":["13px",{lineHeight:"20px",letterSpacing:"0",fontWeight:"400"}],"h3":["18px",{lineHeight:"26px",letterSpacing:"0",fontWeight:"600"}],"h2":["24px",{lineHeight:"32px",letterSpacing:"-0.01em",fontWeight:"600"}]}}}};</script>
    <style>
        .material-symbols-outlined {font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;}
        .bg-mesh {background-color: theme('colors.background');background-image: radial-gradient(at 0% 0%, theme('colors.primary-fixed') 0px, transparent 50%),radial-gradient(at 100% 100%, theme('colors.secondary-fixed') 0px, transparent 50%);}
    </style>
</head>

<body class="bg-mesh min-h-screen flex items-center justify-center p-md antialiased text-on-surface selection:bg-primary selection:text-on-primary">

    <main class="w-full max-w-[440px] bg-surface-container-lowest rounded-xl shadow-[0px_10px_25px_rgba(0,0,0,0.08)] border border-outline-variant p-lg flex flex-col relative overflow-hidden">

        <div class="absolute top-0 left-0 w-full h-1 bg-primary"></div>

        <div class="flex flex-col items-center mb-xl text-center mt-sm">
            <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center mb-sm border border-outline-variant text-primary"><span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">dataset</span></div>
            <span class="font-label-caps text-label-caps text-primary tracking-wider uppercase mb-base">CRM Admin</span>
            <h1 class="font-h2 text-h2 text-on-surface">Connexion</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">Accédez à votre espace de travail sécurisé.</p>
        </div>

        <form action="#" class="flex flex-col gap-md" method="POST">

            <div class="flex flex-col gap-base">
                <label class="font-body-sm text-body-sm font-semibold text-on-surface" for="email">Email</label>
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined absolute left-3 text-outline text-[20px] pointer-events-none">mail</span>
                    <input id="email" name="email" type="email" placeholder="ibrahim.bouchaala@gmail.com" class="w-full pl-10 pr-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-md font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" required>
                </div>
            </div>

            <div class="flex flex-col gap-base">
                <div class="flex items-center justify-between">
                    <label class="font-body-sm text-body-sm font-semibold text-on-surface" for="password">Mot de passe</label>
                </div>

                <div class="relative flex items-center">
                    <span class="material-symbols-outlined absolute left-3 text-outline text-[20px] pointer-events-none">lock</span>
                    <input id="password" name="password" type="password" placeholder="••••••••" class="w-full pl-10 pr-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-md font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" required>
                </div>
            </div>

            <?php if (!empty($error)) : ?>
                <p style="color:red;"><?= $error ?></p>
            <?php endif; ?>

            <button type="submit" class="w-full bg-primary text-on-primary font-body-md text-body-md font-medium py-2.5 px-4 rounded-md hover:bg-on-primary-fixed-variant focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface-container-lowest transition-all mt-sm flex items-center justify-center gap-2">Se connecter<span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>
        </form>

        <div class="mt-lg pt-sm border-t border-surface-variant text-center">
            <p class="font-body-sm text-body-sm text-outline">En vous connectant, vous acceptez nos conditions d'utilisation.</p>
        </div>

    </main>

</body>

</html>
