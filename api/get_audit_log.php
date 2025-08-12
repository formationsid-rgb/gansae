<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('view_audit_log')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT al.timestamp, al.action, al.details, al.ip_address, u.username
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.timestamp DESC
        LIMIT 50
    "); // Limiter à 50 pour l'exemple
    $logs = $stmt->fetchAll();

    $formatted_logs = [];
    foreach ($logs as $log) {
        $formatted_logs[] = [
            'timestamp' => date('d/m/Y H:i:s', strtotime($log['timestamp'])),
            'username' => htmlspecialchars($log['username'] ?? 'N/A'),
            'action' => htmlspecialchars($log['action']),
            'details' => htmlspecialchars($log['details']),
            'ip_address' => htmlspecialchars($log['ip_address'])
        ];
    }

    echo json_encode(['success' => true, 'logs' => $formatted_logs]);

} catch (PDOException $e) {
    error_log("Erreur API Audit Log: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du journal d\'audit.']);
}
?>
