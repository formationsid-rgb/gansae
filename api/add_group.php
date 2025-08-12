<?php
// cit_sae/api/add_group.php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('manage_groups')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Le nom du groupe est requis.']);
        exit();
    }

    try {
        // Vérifier si le nom du groupe existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Un groupe avec ce nom existe déjà.']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO `groups` (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);

        log_audit_action('Ajout groupe', 'Nouveau groupe "' . $name . '" ajouté.', $_SESSION['user_id']);
        echo json_encode(['success' => true, 'message' => 'Groupe ajouté avec succès.']);

    } catch (PDOException $e) {
        error_log("Erreur API Add Group: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du groupe.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
}
?>
