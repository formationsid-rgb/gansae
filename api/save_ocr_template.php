<?php
// cit_sae/api/save_ocr_template.php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('manage_ocr_templates')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = trim($_POST['template_name'] ?? '');
    $document_type = trim($_POST['document_type'] ?? '');
    $zones_json = $_POST['zones'] ?? ''; // JSON string of zones

    if (empty($template_name) || empty($zones_json)) {
        echo json_encode(['success' => false, 'message' => 'Le nom du modèle et les zones sont requis.']);
        exit();
    }

    $zones_array = json_decode($zones_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($zones_array)) {
        echo json_encode(['success' => false, 'message' => 'Format des zones invalide. Le format doit être un tableau JSON.']);
        exit();
    }

    try {
        // Check if template name already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ocr_templates WHERE name = ?");
        $stmt->execute([$template_name]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Un modèle avec ce nom existe déjà.']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO ocr_templates (name, document_type, zones) VALUES (?, ?, ?)");
        $stmt->execute([$template_name, $document_type, $zones_json]);

        log_audit_action('Ajout modèle OCR', 'Modèle OCR "' . $template_name . '" ajouté.', $_SESSION['user_id']);
        echo json_encode(['success' => true, 'message' => 'Modèle OCR enregistré avec succès.']);

    } catch (PDOException $e) {
        error_log("Erreur API Save OCR Template: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du modèle OCR.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
}
?>
