<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
    exit();
}

$document_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$document_id) {
    echo json_encode(['success' => false, 'message' => 'ID de document manquant ou invalide.']);
    exit();
}

try {
    // Récupérer le chemin du fichier et vérifier la propriété (ou les permissions admin)
    $stmt = $pdo->prepare("SELECT file_path, original_filename, user_id, group_id FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();

    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Document non trouvé.']);
        exit();
    }

    // Vérifier si l'utilisateur est le propriétaire du document ou un administrateur
    // Ou si l'utilisateur est archiviste et appartient au groupe du document
    $can_delete = false;
    if ($document['user_id'] == $_SESSION['user_id']) {
        $can_delete = true; // Propriétaire peut supprimer
    } elseif (has_permission('admin')) {
        $can_delete = true; // Admin peut supprimer tout
    } elseif (has_permission('archivist')) { // Vérifier si l'utilisateur est archiviste
        // Si le document a un group_id, vérifier si l'archiviste appartient à ce groupe
        if ($document['group_id']) {
            $stmt_check_group = $pdo->prepare("SELECT COUNT(*) FROM user_groups WHERE user_id = ? AND group_id = ?");
            $stmt_check_group->execute([$_SESSION['user_id'], $document['group_id']]);
            if ($stmt_check_group->fetchColumn() > 0) {
                $can_delete = true;
            }
        }
        // Si le document n'a pas de group_id, un archiviste ne peut pas le supprimer (logique actuelle)
    }

    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de supprimer ce document.']);
        exit();
    }

    // Supprimer le fichier du système de fichiers
    if (file_exists($document['file_path'])) {
        if (!unlink($document['file_path'])) {
            // Si la suppression du fichier échoue, on ne supprime pas l'entrée de la DB
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du fichier physique.']);
            exit();
        }
    }

    // Supprimer l'entrée de la base de données
    $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);

    log_audit_action('Suppression document', 'Document "' . $document['original_filename'] . '" (ID: ' . $document_id . ') supprimé.', $_SESSION['user_id']);
    echo json_encode(['success' => true, 'message' => 'Document supprimé avec succès.']);

} catch (PDOException $e) {
    error_log("Erreur API Delete Document: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du document.']);
}
?>
