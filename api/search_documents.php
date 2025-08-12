<?php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

protect_page(['search']);

try {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    $is_admin = has_permission('admin');

    // Paramètres de recherche
    $search_query = trim($_GET['query'] ?? '');
    $document_type = trim($_GET['document_type'] ?? '');
    $start_date = trim($_GET['start_date'] ?? '');
    $end_date = trim($_GET['end_date'] ?? '');
    $reference = trim($_GET['reference'] ?? '');
    $archiver_id = filter_input(INPUT_GET, 'archiver_id', FILTER_VALIDATE_INT);
    $status = trim($_GET['status'] ?? '');
    $group_id = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);

    // Paramètres de pagination
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $limit = 10; // Nombre de résultats par page
    $offset = ($page - 1) * $limit;

    $sql_conditions = [];
    $params = [];

    // Condition de base : les documents doivent être archivés ou en attente d'indexation (pas supprimés)
    $sql_conditions[] = "(d.status = 'archived' OR d.status = 'pending_indexing')";

    // Recherche en texte intégral sur le contenu combiné
    if (!empty($search_query)) {
        $sql_conditions[] = "MATCH(d.full_text_content) AGAINST(? IN BOOLEAN MODE)";
        $params[] = $search_query . '*'; // Ajouter un joker pour la correspondance partielle
    }

    // Filtres avancés
    if (!empty($document_type)) {
        $sql_conditions[] = "d.document_type = ?";
        $params[] = $document_type;
    }
    if (!empty($start_date)) {
        $sql_conditions[] = "d.document_date >= ?";
        $params[] = $start_date;
    }
    if (!empty($end_date)) {
        $sql_conditions[] = "d.document_date <= ?";
        $params[] = $end_date;
    }
    if (!empty($reference)) {
        $sql_conditions[] = "d.reference LIKE ?";
        $params[] = "%$reference%";
    }
    if ($archiver_id) {
        $sql_conditions[] = "d.user_id = ?";
        $params[] = $archiver_id;
    }
    if (!empty($status)) {
        $sql_conditions[] = "d.status = ?";
        $params[] = $status;
    }
    if ($group_id) {
        $sql_conditions[] = "d.group_id = ?";
        $params[] = $group_id;
    }

    // Contrôle d'accès spécifique à l'utilisateur pour les non-administrateurs
    if (!$is_admin) {
        $user_access_conditions = ["d.user_id = ?"];
        $user_access_params = [$user_id];

        $stmt_groups = $pdo->prepare("SELECT group_id FROM user_groups WHERE user_id = ?");
        $stmt_groups->execute([$user_id]);
        $user_groups = $stmt_groups->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($user_groups)) {
            $group_placeholders = implode(',', array_fill(0, count($user_groups), '?'));
            $user_access_conditions[] = "d.group_id IN (" . $group_placeholders . ")";
            $user_access_params = array_merge($user_access_params, $user_groups);
        }
        $sql_conditions[] = "(" . implode(' OR ', $user_access_conditions) . ")";
        $params = array_merge($params, $user_access_params);
    }

    $where_clause = '';
    if (!empty($sql_conditions)) {
        $where_clause = " WHERE " . implode(' AND ', $sql_conditions);
    }

    // Compter les résultats totaux
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM documents d LEFT JOIN users u ON d.user_id = u.id LEFT JOIN `groups` g ON d.group_id = g.id " . $where_clause);
    $stmt_count->execute($params);
    $total_results = $stmt_count->fetchColumn();
    $total_pages = ceil($total_results / $limit);

    // Récupérer les documents
    $sql = "SELECT d.id, d.original_filename, d.uploaded_at, d.document_type, d.reference, d.document_date, d.status, g.name as group_name
            FROM documents d
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN `groups` g ON d.group_id = g.id
            " . $where_clause . "
            ORDER BY d.uploaded_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute($params);

    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format de la date du document pour l'affichage
    foreach ($documents as &$doc) {
        $doc['document_date'] = $doc['document_date'] ? date('d/m/Y', strtotime($doc['document_date'])) : 'N/A';
    }
    unset($doc); // Supprimer la référence

    echo json_encode([
        'success' => true,
        'documents' => $documents,
        'total_results' => $total_results,
        'total_pages' => $total_pages,
        'current_page' => $page,
    ]);

} catch (PDOException $e) {
    error_log("Erreur de recherche : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche : ' . $e->getMessage()]);
}
