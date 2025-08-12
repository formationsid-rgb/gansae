<?php
error_reporting(0);
ini_set('display_errors', 0);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cit_sae_db');

$admin_username = 'admin';
$admin_password = '@dmin';
$admin_email = 'admin@citsae.com';

$message = '';
$success = false;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $message .= "Base de données '" . DB_NAME . "' créée ou déjà existante.<br>";

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('admin', 'archivist', 'contributor', 'viewer') DEFAULT 'viewer',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `groups` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE,
        `description` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `user_groups` (
        `user_id` INT NOT NULL,
        `group_id` INT NOT NULL,
        PRIMARY KEY (`user_id`, `group_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `group_id` INT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `original_filename` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `file_size` INT NOT NULL,
        `file_type` VARCHAR(50) NOT NULL,
        `document_type` VARCHAR(100),
        `reference` VARCHAR(100),
        `document_date` DATE,
        `extracted_text` TEXT,
        `full_text_content` LONGTEXT,
        `status` ENUM('archived', 'pending_indexing', 'deleted') DEFAULT 'pending_indexing',
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    ALTER TABLE `documents` ADD FULLTEXT(`full_text_content`);

    CREATE TABLE IF NOT EXISTS `audit_log` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `action` VARCHAR(255) NOT NULL,
        `details` TEXT,
        `ip_address` VARCHAR(45),
        `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `ocr_templates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL UNIQUE,
        `document_type` VARCHAR(100),
        `zones` JSON NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    $message .= "Tables créées ou déjà existantes.<br>";

    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE username = ?");
    $stmt->execute([$admin_username]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$admin_username, $admin_email, $hashed_password]);
        $message .= "Utilisateur administrateur créé.<br>";
    } else {
        $message .= "Utilisateur administrateur déjà existant.<br>";
    }

    $uploads_dir = __DIR__ . '/uploads';
    if (!is_dir($uploads_dir)) {
        if (mkdir($uploads_dir, 0775, true)) {
            $message .= "Dossier 'uploads' créé avec succès.<br>";
        } else {
            $message .= "<span style='color:red;'>Erreur: Impossible de créer le dossier 'uploads'.</span><br>";
        }
    } else {
        $message .= "Dossier 'uploads' déjà existant.<br>";
    }

    $message .= "<br><span style='color:green;font-weight:bold;'>Installation terminée avec succès !</span><br>";
    $message .= "Identifiants par défaut :<br>";
    $message .= "Nom d'utilisateur: <strong>" . $admin_username . "</strong><br>";
    $message .= "Mot de passe: <strong>" . $admin_password . "</strong><br>";
    $success = true;

} catch (PDOException $e) {
    $message = "<span style='color:red;font-weight:bold;'>Erreur d'installation:</span> " . $e->getMessage() . "<br>";
    $message .= "<span style='color:red;'>Vérifiez MySQL et les identifiants de connexion.</span>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation CIT SAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #EFF2F5; color: #2A4365; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
        <h1 class="text-2xl font-bold text-blue-800 mb-6">Installation CIT SAE</h1>
        <div class="text-left text-sm mb-6">
            <?php echo $message; ?>
        </div>
        <?php if ($success): ?>
            <p class="text-gray-600 mb-4">Redirection vers la page de connexion dans 5 secondes...</p>
            <script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 5000);
            </script>
            <a href="login.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Aller à la page de connexion
            </a>
        <?php else: ?>
            <p class="text-gray-600">Veuillez résoudre les erreurs ci-dessus et recharger la page.</p>
        <?php endif; ?>
    </div>
</body>
</html>
