<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Modifier la protection de page pour renvoyer une réponse JSON en cas d'échec
if (!is_logged_in()) {
    error_log("Accès non authentifié à get_recent_files.php.");
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit();
}

try {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    $is_admin = has_permission('admin');

    // Récupérer le paramètre de tri
    $sort_by = $_GET['sort_by'] ?? 'uploaded_at DESC';
    $allowed_sorts = [
        'uploaded_at DESC', 'uploaded_at ASC',
        'file_size DESC', 'file_size ASC',
        'original_filename ASC'
    ];
    if (!in_array($sort_by, $allowed_sorts)) {
        $sort_by = 'uploaded_at DESC'; // Valeur par défaut si le tri est invalide
    }

    $sql = "SELECT id, original_filename, uploaded_at, status, file_size FROM documents";
    $params = [];

    if (!$is_admin) {
        // Si l'utilisateur n'est pas admin, filtrer par les groupes auxquels il appartient
        $stmt_groups = $pdo->prepare("SELECT group_id FROM user_groups WHERE user_id = ?");
        $stmt_groups->execute([$user_id]);
        $user_groups = $stmt_groups->fetchAll(PDO::FETCH_COLUMN);

        if (empty($user_groups)) {
            // Si l'utilisateur n'appartient à aucun groupe, il ne voit aucun document
            $sql .= " WHERE 1=0";
            error_log("get_recent_files: User " . $user_id . " has no groups, returning no documents.");
        } else {
            $group_placeholders = implode(',', array_fill(0, count($user_groups), '?'));
            $sql .= " WHERE group_id IN (" . $group_placeholders . ")";
            $params = array_merge($params, $user_groups);
            error_log("get_recent_files: User " . $user_id . " is in groups: " . implode(', ', $user_groups));
        }
    }

    $sql .= " ORDER BY " . $sort_by . " LIMIT 10"; // Limite à 10 fichiers récents

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter la taille du fichier en MB pour l'affichage côté client
    foreach ($files as &$file) {
        $file['file_size_mb'] = round($file['file_size'] / (1024 * 1024), 2);
    }
    unset($file); // Rompre la référence sur le dernier élément

    error_log("get_recent_files: Found " . count($files) . " recent files. Query: " . $sql . ", Params: " . json_encode($params));

    echo json_encode(['success' => true, 'files' => $files]);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des fichiers récents: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des fichiers récents: ' . $e->getMessage()]);
}
