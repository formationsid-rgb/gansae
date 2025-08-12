<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('manage_users')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant ou invalide.']);
        exit();
    }

    // Empêcher un admin de se supprimer lui-même
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        exit();
    }

    try {
        $pdo->beginTransaction(); // Début de la transaction

        // Optionnel: Vérifier si l'utilisateur existe avant de tenter de supprimer
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_to_delete = $stmt->fetch();

        if (!$user_to_delete) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé.']);
            exit();
        }

        // Supprimer les associations de groupes
        $pdo->prepare("DELETE FROM user_groups WHERE user_id = ?")->execute([$user_id]);

        // Supprimer l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit(); // Valider la transaction

        if ($stmt->rowCount() > 0) {
            log_audit_action('Suppression utilisateur', 'Utilisateur ID ' . $user_id . ' (' . $user_to_delete['username'] . ') supprimé.', $_SESSION['user_id']);
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Échec de la suppression de l\'utilisateur.']);
        }

    } catch (PDOException $e) {
        $pdo->rollBack(); // Annuler la transaction en cas d'erreur
        error_log("Erreur API Delete User: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'utilisateur.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
}
?>
