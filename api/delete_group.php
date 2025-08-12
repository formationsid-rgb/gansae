<?php
// cit_sae/api/delete_group.php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('manage_groups')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$group_id) {
        echo json_encode(['success' => false, 'message' => 'ID de groupe manquant ou invalide.']);
        exit();
    }

    try {
        $pdo->beginTransaction(); // Début de la transaction

        // Vérifier si le groupe existe
        $stmt = $pdo->prepare("SELECT name FROM `groups` WHERE id = ?");
        $stmt->execute([$group_id]);
        $group_to_delete = $stmt->fetch();

        if (!$group_to_delete) {
            echo json_encode(['success' => false, 'message' => 'Groupe non trouvé.']);
            exit();
        }

        // Supprimer les associations utilisateur-groupe
        $pdo->prepare("DELETE FROM user_groups WHERE group_id = ?")->execute([$group_id]);

        // Mettre à jour les documents associés à ce groupe (SET NULL)
        $pdo->prepare("UPDATE documents SET group_id = NULL WHERE group_id = ?")->execute([$group_id]);

        // Supprimer le groupe
        $stmt = $pdo->prepare("DELETE FROM `groups` WHERE id = ?");
        $stmt->execute([$group_id]);

        $pdo->commit(); // Valider la transaction

        if ($stmt->rowCount() > 0) {
            log_audit_action('Suppression groupe', 'Groupe ID ' . $group_id . ' (' . $group_to_delete['name'] . ') supprimé.', $_SESSION['user_id']);
            echo json_encode(['success' => true, 'message' => 'Groupe supprimé avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Échec de la suppression du groupe.']);
        }

    } catch (PDOException $e) {
        $pdo->rollBack(); // Annuler la transaction en cas d'erreur
        error_log("Erreur API Delete Group: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du groupe.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
}
?>
