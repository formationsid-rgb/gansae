<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('manage_users')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = trim($_POST['password'] ?? ''); // Optionnel, si le mot de passe est modifié
    $groups = isset($_POST['groups']) ? (array)$_POST['groups'] : []; // Récupérer les groupes

    if (!$user_id || empty($username) || empty($email) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format d\'email invalide.']);
        exit();
    }

    $allowed_roles = [ROLE_ADMIN, ROLE_ARCHIVIST, ROLE_CONTRIBUTOR, ROLE_VIEWER];
    if (!in_array($role, $allowed_roles)) {
        echo json_encode(['success' => false, 'message' => 'Rôle invalide.']);
        exit();
    }

    try {
        // Vérifier si le nom d'utilisateur ou l'email existe déjà pour un AUTRE utilisateur
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Le nom d\'utilisateur ou l\'email est déjà utilisé par un autre compte.']);
            exit();
        }

        $pdo->beginTransaction(); // Début de la transaction

        $sql = "UPDATE users SET username = ?, email = ?, role = ?";
        $params = [$username, $email, $role];

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params[] = $hashed_password;
        }
        $sql .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Mettre à jour les groupes de l'utilisateur
        $pdo->prepare("DELETE FROM user_groups WHERE user_id = ?")->execute([$user_id]);
        if (!empty($groups)) {
            $stmt_group = $pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
            foreach ($groups as $group_id) {
                // Validate group_id to prevent invalid insertions
                if (!filter_var($group_id, FILTER_VALIDATE_INT)) {
                    throw new PDOException("Invalid group ID provided: " . htmlspecialchars($group_id));
                }
                $stmt_group->execute([$user_id, $group_id]);
            }
        }

        $pdo->commit(); // Valider la transaction

        log_audit_action('Mise à jour utilisateur', 'Utilisateur ID ' . $user_id . ' (' . $username . ') mis à jour.', $_SESSION['user_id']);
        echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès.']);

    } catch (PDOException $e) {
        $pdo->rollBack(); // Annuler la transaction en cas d'erreur
        error_log("Erreur API Update User: " . $e->getMessage());
        $errorMessage = 'Erreur lors de la mise à jour de l\'utilisateur.';
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $errorMessage = 'Un utilisateur avec ce nom d\'utilisateur ou cet email existe déjà.';
        } elseif (strpos($e->getMessage(), 'Cannot add or update a child row: a foreign key constraint fails') !== false) {
            $errorMessage = 'Un ou plusieurs groupes sélectionnés sont invalides.';
        }
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
}
