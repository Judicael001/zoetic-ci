<?php
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function require_admin_login(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: connexion.php?message=' . urlencode('Connectez-vous pour accéder au tableau de bord.') . '&status=error');
        exit;
    }
}

function require_super_admin(): void {
    require_admin_login();
    if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
        header('Location: dashboard.php?message=' . urlencode('Accès refusé : privilèges insuffisants.') . '&status=error');
        exit;
    }
}

function redirect_if_admin_logged_in(): void {
    if (!empty($_SESSION['admin_id'])) {
        header('Location: dashboard.php');
        exit;
    }
}

function redirect_if_not_admin_logged_in(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: connexion.php?message=' . urlencode('Vous devez être connecté pour accéder à cette page.') . '&status=error');
        exit;
    }
}

function enforce_admin_csrf(): void {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function validate_admin_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
