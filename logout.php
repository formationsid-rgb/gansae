<?php
require_once 'includes/functions.php';

if (is_logged_in()) {
    log_audit_action('Déconnexion', 'Utilisateur ' . $_SESSION['username'] . ' déconnecté.', $_SESSION['user_id']);
    session_unset();     // Supprime toutes les variables de session
    session_destroy();   // Détruit la session
}
redirect('login.php');
?>
