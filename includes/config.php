<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // À changer si votre utilisateur MySQL n'est pas 'root'
define('DB_PASS', '');     // À changer si votre mot de passe MySQL n'est pas vide
define('DB_NAME', 'cit_sae_db');

// Chemins de l'application
define('BASE_URL', 'http://localhost/cit_sae/'); // Assurez-vous que cela correspond à votre URL WAMP
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // Chemin absolu vers le dossier d'uploads

// Paramètres de session
define('SESSION_NAME', 'citsae_session');
define('SESSION_LIFETIME', 3600); // Durée de vie de la session en secondes (1 heure)

// Autres configurations
define('APP_NAME', 'CIT SAE');
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_PASSWORD', 'adminpassword'); // Sera haché lors de l'installation

// Types de documents supportés pour l'upload
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'tiff']);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB

// Rôles utilisateurs
define('ROLE_ADMIN', 'admin');
define('ROLE_ARCHIVIST', 'archivist');
define('ROLE_CONTRIBUTOR', 'contributor'); // Nouveau rôle
define('ROLE_VIEWER', 'viewer');

// Chemin vers le dossier vendor pour les dépendances Composer
define('VENDOR_PATH', __DIR__ . '/../vendor/');
?>
