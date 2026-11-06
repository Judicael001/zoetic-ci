<?php
require_once __DIR__ . '/_auth.php';
redirect_if_admin_logged_in();

include("../src/include/head.php");

$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? 'info';

/**
 * Escape a value for HTML output.
 *
 * @param mixed $value
 * @return string
 */
function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-white via-green-50 to-light px-4 py-12">

    <div class="w-full max-w-4xl overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-gray-200">

        <div class="grid lg:grid-cols-2">

            <!-- LEFT SIDE -->
            <div class="hidden lg:flex flex-col justify-center gap-6 bg-primary p-12 text-white">

                <div class="space-y-3">

                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold uppercase tracking-[0.18em]">
                        <i class="fa-solid fa-lock"></i>
                        Espace Administrateur
                    </span>

                    <h2 class="text-4xl font-title font-bold leading-tight">
                        Connexion sécurisée
                    </h2>

                    <p class="max-w-lg text-gray-100/90">
                        Connectez-vous à votre tableau de bord ZoeTIC pour gérer les formations, articles et utilisateurs.
                    </p>
                </div>

                <div class="rounded-3xl bg-white/10 p-6 text-sm text-gray-100">

                    <p class="font-semibold uppercase tracking-[0.18em] text-white/80">
                        Sécurité
                    </p>

                    <div class="mt-4 space-y-3">

                        <p class="flex items-center gap-3">
                            <i class="fa-solid fa-check text-secondary"></i>
                            Authentification sécurisée
                        </p>

                        <p class="flex items-center gap-3">
                            <i class="fa-solid fa-check text-secondary"></i>
                            Session protégée
                        </p>

                        <p class="flex items-center gap-3">
                            <i class="fa-solid fa-check text-secondary"></i>
                            Accès administrateur privé
                        </p>

                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="p-10 sm:p-12">

                <div class="mb-8">

                    <span class="inline-flex items-center gap-2 rounded-full bg-secondary/10 px-3 py-1 text-sm font-semibold text-secondary">
                        <i class="fa-solid fa-user-lock"></i>
                        Login Admin
                    </span>

                    <h1 class="mt-5 text-3xl font-title font-bold text-dark">
                        Bienvenue
                    </h1>

                    <p class="mt-3 text-gray-600">
                        Connectez-vous à votre espace d’administration.
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
                <form action="connect/connexion_admin.php" method="POST" class="space-y-6">

                    <!-- EMAIL -->
                    <label class="block">

                        <span class="text-sm font-semibold text-gray-700">
                            Adresse e-mail
                        </span>

                        <input
                            type="email"
                            name="email"
                            required
                            placeholder="admin@zoetic.ci"
                            class="mt-2 w-full rounded-3xl border border-gray-200 bg-white px-4 py-3 text-gray-900 shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />

                    </label>

                    <!-- PASSWORD -->
                    <label class="block">

                        <span class="text-sm font-semibold text-gray-700">
                            Mot de passe
                        </span>

                        <input
                            type="password"
                            name="password"
                            required
                            placeholder="Entrez votre mot de passe"
                            class="mt-2 w-full rounded-3xl border border-gray-200 bg-white px-4 py-3 text-gray-900 shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />

                    </label>

                    <!-- REMEMBER -->
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <label class="inline-flex items-center gap-2 text-sm text-gray-600">

                            <input
                                type="checkbox"
                                name="remember"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />

                            Se souvenir de moi

                        </label>
                        <!--
                        <a
                            href="#"
                            class="text-sm font-semibold text-primary hover:text-secondary"
                        >
                            Mot de passe oublié ?
                        </a>
                        -->
                    </div>

                    <!-- BUTTON -->
                    <button
                        type="submit"
                        class="w-full rounded-3xl bg-primary px-6 py-3 text-base font-semibold text-white shadow-xl shadow-primary/20 transition hover:bg-[#35721e]"
                    >
                        Se connecter
                    </button>

                    <!-- REGISTER 
                    <p class="text-center text-sm text-gray-600">

                        Pas encore de compte ?

                        <a
                            href="inscription.php"
                            class="font-semibold text-primary hover:text-secondary"
                        >
                            Créer un compte admin
                        </a>

                    </p>
                    -->
                </form>
            </div>
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
            button.innerText = 'Connexion en cours...';
        }

    });

}

</script>

</body>
</html>