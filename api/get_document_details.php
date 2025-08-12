<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit();
}

$document_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$document_id) {
    echo json_encode(['success' => false, 'message' => 'ID de document manquant ou invalide.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = has_permission('admin');

try {
    $sql = "SELECT d.id, d.original_filename, d.file_path, d.file_type, d.document_type, d.reference, d.document_date, d.extracted_text, d.status, d.user_id, g.name as group_name
            FROM documents d
            LEFT JOIN `groups` g ON d.group_id = g.id
            WHERE d.id = ?";
    $params = [$document_id];

    if (!$is_admin) {
        // Filtrer par les groupes de l'utilisateur si non admin
        $stmt_groups = $pdo->prepare("SELECT group_id FROM user_groups WHERE user_id = ?");
        $stmt_groups->execute([$user_id]);
        $user_groups = $stmt_groups->fetchAll(PDO::FETCH_COLUMN);

        if (empty($user_groups)) {
            echo json_encode(['success' => false, 'message' => 'Document non trouvé ou vous n\'avez pas la permission d\'y accéder.']);
            exit();
        }
        $group_placeholders = implode(',', array_fill(0, count($user_groups), '?'));
        $sql .= " AND d.group_id IN (" . $group_placeholders . ")";
        $params = array_merge($params, $user_groups);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $document = $stmt->fetch();

    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Document non trouvé ou vous n\'avez pas la permission d\'y accéder.']);
        exit();
    }

    // Construire l'URL du fichier pour l'iframe
    // Assurez-vous que BASE_URL est correctement défini dans config.php
    $file_url = str_replace(UPLOAD_DIR, BASE_URL . 'uploads/', $document['file_path']);

    $formatted_document = [
        'id' => $document['id'],
        'original_filename' => htmlspecialchars($document['original_filename']),
        'file_url' => $file_url,
        'file_type' => htmlspecialchars($document['file_type']),
        'document_type' => htmlspecialchars($document['document_type'] ?? 'N/A'),
        'reference' => htmlspecialchars($document['reference'] ?? 'N/A'),
        'document_date' => $document['document_date'] ? date('d/m/Y', strtotime($document['document_date'])) : 'N/A',
        'extracted_text' => htmlspecialchars($document['extracted_text'] ?? ''),
        'status' => htmlspecialchars($document['status']),
        'group_name' => htmlspecialchars($document['group_name'] ?? 'N/A')
    ];

    echo json_encode(['success' => true, 'document' => $formatted_document]);

} catch (PDOException $e) {
    error_log("Erreur API Get Document Details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des détails du document.']);
}
?>
