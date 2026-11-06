<?php
require_once '_auth.php';
redirect_if_not_admin_logged_in();

// Détruire la session et rediriger vers la page de connexion
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: connexion.php?message=' . urlencode('Vous avez été déconnecté avec succès.') . '&status=success');
exit;
?>