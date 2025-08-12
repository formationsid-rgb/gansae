<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('view_stats')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

$response_data = [
    'success' => true,
    'data' => [
        'docs_by_type' => [],
        'upload_volume_by_month' => [],
        'user_activity' => [],
        'ocr_indexing_rate' => []
    ]
];

try {
    // Documents par type
    $stmt = $pdo->query("SELECT document_type, COUNT(*) as count FROM documents GROUP BY document_type");
    $docs_by_type = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $response_data['data']['docs_by_type'] = $docs_by_type ?: []; // Assurer que c'est un tableau

    // Volume de téléversement par mois
    $stmt = $pdo->query("SELECT DATE_FORMAT(uploaded_at, '%Y-%m') as month, COUNT(*) as count FROM documents GROUP BY month ORDER BY month ASC");
    $upload_volume_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response_data['data']['upload_volume_by_month'] = $upload_volume_by_month ?: []; // Assurer que c'est un tableau

    // Activité des utilisateurs
    $stmt = $pdo->query("SELECT u.username, COUNT(al.id) as action_count FROM audit_log al JOIN users u ON al.user_id = u.id GROUP BY u.username ORDER BY action_count DESC LIMIT 5");
    $user_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response_data['data']['user_activity'] = $user_activity ?: []; // Assurer que c'est un tableau

    // Taux d'indexation OCR
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM documents");
    $total_docs = $stmt_total->fetchColumn();

    $stmt_archived = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'archived'");
    $archived_docs = $stmt_archived->fetchColumn();

    $response_data['data']['ocr_indexing_rate'] = [
        'archived' => $archived_docs,
        'pending' => $total_docs - $archived_docs,
        'total' => $total_docs
    ];

} catch (PDOException $e) {
    error_log("Erreur API Get Statistics: " . $e->getMessage());
    $response_data = ['success' => false, 'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()]; // Afficher le message d'erreur PDO
}

echo json_encode($response_data);
?>
