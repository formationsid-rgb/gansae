<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Use protect_page for consistent access control and JSON response for AJAX
protect_page(['indexing']);

try {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    $is_admin = has_permission('admin');

    // Retrieve documents pending indexing
    $sql = "SELECT d.id, d.original_filename, d.uploaded_at, u.username as uploaded_by
            FROM documents d
            LEFT JOIN users u ON d.user_id = u.id
            WHERE d.status = 'pending_indexing'";

    $params = [];

    if (!$is_admin) {
        // Non-admins can see documents they uploaded (regardless of group_id)
        // OR documents assigned to groups they belong to.
        $sql .= " AND (d.user_id = ? ";
        $params[] = $user_id;

        // Get user's groups
        $stmt_groups = $pdo->prepare("SELECT group_id FROM user_groups WHERE user_id = ?");
        $stmt_groups->execute([$user_id]);
        $user_groups = $stmt_groups->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($user_groups)) {
            $group_placeholders = implode(',', array_fill(0, count($user_groups), '?'));
            $sql .= " OR d.group_id IN (" . $group_placeholders . ")";
            $params = array_merge($params, $user_groups);
        }
        $sql .= ")"; // Close the OR condition
    }

    $sql .= " ORDER BY d.uploaded_at ASC"; // Oldest first

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'documents' => $documents,
    ]);

} catch (PDOException $e) {
    error_log("Pending files error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des documents: ' . $e->getMessage(),
    ]);
}
