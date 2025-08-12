<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En production, loggez l'erreur et affichez un message générique
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    die("Impossible de se connecter à la base de données. Veuillez réessayer plus tard ou contacter l'administrateur.");
}
?>
