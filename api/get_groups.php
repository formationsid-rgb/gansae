<?php
// cit_sae/api/get_groups.php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $is_admin = has_permission('admin');

    $sql = "SELECT id, name, description FROM `groups`";
    $params = [];

    if (!$is_admin) {
        // Si l'utilisateur n'est pas admin, il ne peut voir que les groupes auxquels il appartient
        $sql .= " WHERE id IN (SELECT group_id FROM user_groups WHERE user_id = ?)";
        $params[] = $user_id;
    }

    $sql .= " ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $groups = $stmt->fetchAll();

    $formatted_groups = [];
    foreach ($groups as $group) {
        $formatted_groups[] = [
            'id' => $group['id'],
            'name' => htmlspecialchars($group['name']),
            'description' => htmlspecialchars($group['description'] ?? '')
        ];
    }

    echo json_encode(['success' => true, 'groups' => $formatted_groups]);

} catch (PDOException $e) {
    error_log("Erreur API Get Groups: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des groupes.']);
}
