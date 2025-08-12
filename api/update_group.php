<?php
// cit_sae/api/update_group.php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('manage_groups')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$group_id || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides.']);
        exit();
    }

    try {
        // Vérifier si le nom du groupe existe déjà pour un AUTRE groupe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE name = ? AND id != ?");
        $stmt->execute([$name, $group_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Un groupe avec ce nom existe déjà.']);
            exit();
        }

        $stmt = $pdo->prepare("UPDATE `groups` SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $group_id]);

        log_audit_action('Mise à jour groupe', 'Groupe ID ' . $group_id . ' (' . $name . ') mis à jour.', $_SESSION['user_id']);
        echo json_encode(['success' => true, 'message' => 'Groupe mis à jour avec succès.']);

    } catch (PDOException $e) {
        error_log("Erreur API Update Group: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du groupe.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
}
?>
