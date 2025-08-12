<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Vérifier la session et les permissions
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Session expirée ou non authentifié.']);
    exit();
}

try {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    $is_admin = has_permission('admin');

    if ($is_admin) {
        // Admins voient tous les groupes
        $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name ASC");
    } else {
        // Autres utilisateurs voient seulement leurs groupes
        $stmt = $pdo->prepare("
            SELECT g.id, g.name 
            FROM groups g
            JOIN user_groups ug ON g.id = ug.group_id
            WHERE ug.user_id = ?
            ORDER BY g.name ASC
        ");
        $stmt->execute([$user_id]);
    }

    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'groups' => $groups]);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des groupes: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
