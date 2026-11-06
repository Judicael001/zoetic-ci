<?php
require_once '_auth.php';
redirect_if_admin_logged_in();

include("../src/include/head.php");

$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-white via-green-50 to-light px-4 py-10">

    <div class="max-w-6xl w-full grid gap-8 lg:grid-cols-2 shadow-2xl rounded-3xl overflow-hidden bg-white">

        <!-- LEFT -->
        <div class="hidden lg:flex flex-col justify-between bg-gradient-to-b from-primary via-green-700 to-dark p-10 text-white">

            <div>
                <div class="inline-flex items-center gap-3 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold uppercase tracking-[0.18em]">
                    <i class="fa-solid fa-user-shield"></i>
                    Espace Admin
                </div>

                <h1 class="mt-10 text-4xl font-title font-bold leading-tight">
                    Création d’un administrateur
                </h1>

                <p class="mt-6 max-w-xl text-gray-100/90 text-lg leading-8">
                    Gérez votre plateforme ZoeTIC avec un accès sécurisé et professionnel.
                </p>
            </div>

            <div class="rounded-3xl bg-white/10 p-6 ring-1 ring-white/10">
                <p class="text-sm uppercase tracking-[0.18em] text-white/70">
                    Fonctionnalités
                </p>

                <ul class="mt-4 space-y-3 text-sm text-gray-100/85">
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-check text-secondary"></i>
                        Authentification sécurisée
                    </li>

                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-check text-secondary"></i>
                        Gestion des rôles
                    </li>

                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-check text-secondary"></i>
                        Tableau de bord moderne
                    </li>
                </ul>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="p-8 sm:p-10 lg:p-14">

            <div class="mb-8">
                <span class="inline-flex items-center gap-2 rounded-full bg-secondary/10 px-3 py-1 text-sm font-semibold text-secondary">
                    <i class="fa-solid fa-building-user"></i>
                    Admin ZoeTIC
                </span>

                <h2 class="mt-6 text-3xl font-title font-bold text-dark">
                    Créer un compte
                </h2>

                <p class="mt-3 text-gray-600">
                    Remplissez les informations ci-dessous.
                </p>
            </div>

            <!-- MESSAGE -->
            <?php if (!empty($message)) : ?>
                <div class="mb-6 rounded-3xl border px-5 py-4 text-sm shadow-sm
                    <?php echo $status === 'success'
                        ? 'border-green-200 bg-green-50 text-green-700'
                        : 'border-red-200 bg-red-50 text-red-700'; ?>">

                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <!-- FORM -->
            <form action="connect/ins_admin.php" method="POST" class="space-y-6">

                <div class="grid gap-6 sm:grid-cols-2">

                    <!-- NAME -->
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">
                            Nom complet
                        </span>

                        <input
                            type="text"
                            name="name"
                            required
                            placeholder="Jean Dupont"
                            class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </label>

                    <!-- EMAIL -->
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">
                            Adresse email
                        </span>

                        <input
                            type="email"
                            name="email"
                            required
                            placeholder="admin@zoetic.ci"
                            class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </label>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    <!-- PHONE -->
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">
                            Téléphone
                        </span>

                        <input
                            type="tel"
                            name="phone"
                            placeholder="+225 07 00 00 00 00"
                            class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </label>

                    <!-- ROLE -->
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">
                            Rôle
                        </span>

                        <select
                            name="role"
                            class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        >
                            <option value="admin">Admin</option>
                            <option value="editor">Éditeur</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    <!-- PASSWORD -->
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">
                            Mot de passe
                        </span>

                        <input
                            type="password"
                            name="password"
                            required
                            placeholder="********"
                            class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </label>

                    <!-- CONFIRM -->
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">
                            Confirmation
                        </span>

                        <input
                            type="password"
                            name="confirm_password"
                            required
                            placeholder="********"
                            class="mt-2 w-full rounded-3xl border border-gray-200 px-4 py-3 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />
                    </label>
                </div>

                <!-- TERMS -->
                <div class="flex items-start gap-3 text-sm text-gray-600">

                    <input
                        id="terms"
                        type="checkbox"
                        required
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                    />

                    <label for="terms" class="leading-6">
                        J’accepte les conditions générales.
                    </label>
                </div>

                <!-- BUTTON -->
                <button
                    type="submit"
                    class="w-full rounded-3xl bg-primary px-6 py-3 text-base font-semibold text-white shadow-xl shadow-primary/20 transition hover:bg-[#35721e]"
                >
                    Créer le compte
                </button>

                <p class="text-center text-sm text-gray-600">
                    Déjà inscrit ?
                    <a href="connexion.php" class="font-semibold text-primary hover:text-secondary">
                        Connectez-vous
                    </a>
                </p>

            </form>
        </div>
    </div>
</div>

<script>
const form = document.querySelector('form');

if (form) {
    form.addEventListener('submit', () => {

        const button = form.querySelector('button[type="submit"]');

        if (button) {
            button.disabled = true;
            button.innerText = 'Création en cours...';
        }
    });
}
</script>

</body>
</html>