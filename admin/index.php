<?php
require_once __DIR__ . '/_auth.php';

/**
 * Ce fichier empêche l'affichage de l'arborescence des fichiers 
 * lorsqu'un utilisateur accède directement à l'URL /admin/
 */

if (!empty($_SESSION['admin_id'])) {
    // L'utilisateur est connecté, on l'envoie sur le dashboard
    header('Location: dashboard.php');
} else {
    // L'utilisateur n'est pas connecté, on l'envoie sur la page de login
    header('Location: connexion.php');
}
exit;