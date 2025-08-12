<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_permission('manage_users')) {
    error_log("Accès non autorisé à add_user.php. User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ", Role: " . ($_SESSION['user_role'] ?? 'N/A'));
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $groups = isset($_POST['groups']) ? (array)$_POST['groups'] : [];

    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis.']);
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
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Le nom d\'utilisateur ou l\'email existe déjà.']);
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password, $role]);
        $new_user_id = $pdo->lastInsertId();

        if (!empty($groups)) {
            $stmt_group = $pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
            foreach ($groups as $group_id) {
                // Validate group_id to prevent invalid insertions
                if (!filter_var($group_id, FILTER_VALIDATE_INT)) {
                    throw new PDOException("Invalid group ID provided: " . htmlspecialchars($group_id));
                }
                $stmt_group->execute([$new_user_id, $group_id]);
            }
        }

        $pdo->commit();
        log_audit_action('Ajout utilisateur', 'Nouvel utilisateur "' . $username . '" (' . $role . ') ajouté.', $_SESSION['user_id']);
        echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté avec succès.']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Log the specific error message for debugging
        error_log("Erreur API Add User: " . $e->getMessage());
        // Provide a more informative message to the user if possible, or a generic one
        $errorMessage = 'Erreur lors de l\'ajout de l\'utilisateur.';
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
