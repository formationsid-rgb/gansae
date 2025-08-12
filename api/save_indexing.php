<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

protect_page(['indexing']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_id = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
    $document_type = trim($_POST['document_type'] ?? '');
    $reference = trim($_POST['reference'] ?? '');
    $document_date = trim($_POST['document_date'] ?? '');
    $extracted_text = trim($_POST['extracted_text'] ?? '');

    if (!$document_id) {
        echo json_encode(['success' => false, 'message' => 'ID de document manquant ou invalide.']);
        exit();
    }

    try {
        // Fetch original filename to include in full_text_content
        $stmt_doc = $pdo->prepare("SELECT original_filename FROM documents WHERE id = ?");
        $stmt_doc->execute([$document_id]);
        $doc_info = $stmt_doc->fetch();

        if (!$doc_info) {
            echo json_encode(['success' => false, 'message' => 'Document non trouvé.']);
            exit();
        }

        // Construct full_text_content for search
        $full_text_content = $doc_info['original_filename'] . " " . $document_type . " " . $reference . " " . $document_date . " " . $extracted_text;

        $stmt = $pdo->prepare("UPDATE documents SET document_type = ?, reference = ?, document_date = ?, extracted_text = ?, full_text_content = ?, status = 'archived' WHERE id = ? AND (user_id = ? OR group_id IN (SELECT group_id FROM user_groups WHERE user_id = ?))");
        $stmt->execute([$document_type, $reference, $document_date, $extracted_text, $full_text_content, $document_id, $_SESSION['user_id'], $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            log_audit_action('Indexation de document', 'Document ID ' . $document_id . ' indexé et archivé.', $_SESSION['user_id']);
            echo json_encode(['success' => true, 'message' => 'Indexation enregistrée avec succès et document archivé.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Document non trouvé ou vous n\'avez pas la permission de le modifier.']);
        }

    } catch (PDOException $e) {
        error_log("Erreur API Save Indexing: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de l\'indexation: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
}
