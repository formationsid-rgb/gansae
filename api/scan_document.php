<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

protect_page(['scan']);

// Simulation d'une opération de scan
// En réalité, cela impliquerait une communication avec un scanner physique ou un service de scan.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les paramètres de scan (simulés)
    $source = $_POST['source'] ?? 'Scanner Twain';
    $resolution = $_POST['resolution'] ?? '300';
    $output_format = $_POST['output_format'] ?? 'PDF';
    $quality = $_POST['quality'] ?? 'Haute';
    $group_id = filter_input(INPUT_POST, 'document_group', FILTER_VALIDATE_INT); // Récupérer le group_id

    // Simuler un délai de traitement
    sleep(2); // Attendre 2 secondes pour simuler le scan

    // Simuler la création d'un fichier scanné
    $original_filename = "Document_Scanne_" . date('Ymd_His') . "." . strtolower($output_format);
    $unique_filename = generate_unique_filename($original_filename);
    $destination_path = UPLOAD_DIR . $unique_filename;

    // Create a placeholder file for simulation. In a real scenario, this would be the actual scanned file.
    // For demonstration, we'll create a dummy file.
    $dummy_content = "Contenu simulé du document scanné. Source: $source, Resolution: $resolution, Format: $output_format, Quality: $quality.";
    if (file_put_contents($destination_path, $dummy_content) !== false) {
        try {
            $user_id = $_SESSION['user_id'];
            $file_size = filesize($destination_path); // Size of the simulated file
            $file_type = strtolower($output_format);

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
            $full_text_content = $original_filename . " " . $source . " " . $resolution . " " . $output_format;

            $stmt = $pdo->prepare("INSERT INTO documents (user_id, filename, original_filename, file_path, file_size, file_type, group_id, status, full_text_content) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_indexing', ?)");
            $stmt->execute([$user_id, $unique_filename, $original_filename, $destination_path, $file_size, $file_type, $group_id, $full_text_content]);

            log_audit_action('Document scanné', 'Fichier "' . $original_filename . '" scanné avec les paramètres: Source=' . $source . ', Résolution=' . $resolution . ', Format=' . $output_format, $user_id);
            echo json_encode(['success' => true, 'message' => 'Document scanné et enregistré avec succès.']);

        } catch (PDOException $e) {
            if (file_exists($destination_path)) {
                unlink($destination_path);
            }
            error_log("Erreur API Scan: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du document scanné dans la base de données: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du fichier scanné simulé.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
}
