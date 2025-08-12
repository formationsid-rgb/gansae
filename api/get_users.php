<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('manage_users')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

try {
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY username ASC");
    $users = $stmt->fetchAll();

    $formatted_users = [];
    foreach ($users as $user) {
        // Récupérer les groupes de chaque utilisateur
        $stmt_groups = $pdo->prepare("SELECT g.name FROM user_groups ug JOIN `groups` g ON ug.group_id = g.id WHERE ug.user_id = ?");
        $stmt_groups->execute([$user['id']]);
        $user_groups = $stmt_groups->fetchAll(PDO::FETCH_COLUMN);

        $formatted_users[] = [
            'id' => $user['id'],
            'username' => htmlspecialchars($user['username']),
            'email' => htmlspecialchars($user['email']),
            'role' => htmlspecialchars($user['role']),
            'groups' => $user_groups, // Ajouter les groupes
            'created_at' => date('d/m/Y H:i', strtotime($user['created_at']))
        ];
    }

    echo json_encode(['success' => true, 'users' => $formatted_users]);

} catch (PDOException $e) {
    error_log("Erreur API Get Users: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des utilisateurs.']);
}
?>
