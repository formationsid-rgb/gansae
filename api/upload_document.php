<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

protect_page(['upload']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
    exit();
}

if (!isset($_FILES['document_file'])) {
    echo json_encode(['success' => false, 'message' => 'Aucun fichier n\'a été envoyé.']);
    exit();
}

$file_info = $_FILES['document_file'];
$validation = validate_uploaded_file($file_info);

if (!$validation['success']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit();
}

$original_filename = basename($file_info['name']);
$unique_filename = generate_unique_filename($original_filename);
$destination_path = UPLOAD_DIR . $unique_filename;

if (move_uploaded_file($file_info['tmp_name'], $destination_path)) {
    try {
        $user_id = $_SESSION['user_id'];
        $file_size = $file_info['size'];
        $file_type = $validation['extension'];
        $document_type = trim($_POST['document_type'] ?? '');
        $reference = trim($_POST['document_reference'] ?? '');
        $group_id = filter_input(INPUT_POST, 'document_group', FILTER_VALIDATE_INT);

        // Determine the group_id for the document
        if ($group_id) {
            // If a group is selected, validate user's permission to assign to it
            if (!has_permission('admin')) { // Only admin can assign to any group
                $stmt_check_group = $pdo->prepare("SELECT COUNT(*) FROM user_groups WHERE user_id = ? AND group_id = ?");
                $stmt_check_group->execute([$user_id, $group_id]);
                if ($stmt_check_group->fetchColumn() == 0) {
                    unlink($destination_path); // Delete uploaded file
                    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission d\'assigner ce document à ce groupe.']);
                    exit();
                }
            }
        } else {
            // If no group is selected, try to assign to the user's first group
            $stmt_default_group = $pdo->prepare("SELECT group_id FROM user_groups WHERE user_id = ? LIMIT 1");
            $stmt_default_group->execute([$user_id]);
            $default_group = $stmt_default_group->fetchColumn();
            $group_id = $default_group ?: null; // Use the user's first group or NULL if none
        }

        // Initialize full_text_content with basic metadata for search
        $full_text_content = $original_filename . " " . $document_type . " " . $reference;

        $stmt = $pdo->prepare("INSERT INTO documents (user_id, filename, original_filename, file_path, file_size, file_type, document_type, reference, group_id, status, full_text_content) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_indexing', ?)");
        $stmt->execute([$user_id, $unique_filename, $original_filename, $destination_path, $file_size, $file_type, $document_type, $reference, $group_id, $full_text_content]);

        log_audit_action('Téléversement de document', 'Fichier "' . $original_filename . '" téléversé.', $user_id);
        echo json_encode(['success' => true, 'message' => 'Fichier téléversé et enregistré avec succès.']);

    } catch (PDOException $e) {
        // If insertion fails, attempt to delete the uploaded file
        if (file_exists($destination_path)) {
            unlink($destination_path);
        }
        error_log("Erreur API Upload: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du document dans la base de données: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement du fichier téléversé. Vérifiez les permissions du dossier "uploads".']);
}
