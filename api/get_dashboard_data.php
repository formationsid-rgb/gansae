<?php
require_once '../includes/functions.php';
protect_page();

header('Content-Type: application/json');

try {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    $is_admin = has_permission('admin');

    // Exemple de requêtes pour récupérer les données du tableau de bord
    $data = [];

    // Récupérer le nombre total de documents
    $stmt = $pdo->query("SELECT COUNT(*) FROM documents");
    $data['total_documents'] = $stmt->fetchColumn();

    // Récupérer le nombre de documents en attente d'indexation
    $stmt = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'pending_indexing'");
    $data['pending_indexing'] = $stmt->fetchColumn();

    // Récupérer le nombre de documents archivés
    $stmt = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'archived'");
    $data['archived_documents'] = $stmt->fetchColumn();

    // Récupérer les statistiques d'audit
    $stmt = $pdo->query("SELECT COUNT(*) FROM audit_log");
    $data['total_audit_entries'] = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données du tableau de bord: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données du tableau de bord: ' . $e->getMessage()]);
}
?>
