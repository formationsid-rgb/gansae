<?php
require_once 'config.php';
require_once 'db_connect.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté.
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirige l'utilisateur vers une autre page ou renvoie une réponse JSON pour les requêtes AJAX.
 * @param string $location
 * @param bool $is_api_request Indique si la redirection est demandée depuis un script API.
 * @param string $message Message à inclure dans la réponse JSON.
 */
function redirect($location, $is_api_request = false, $message = 'Accès non autorisé ou session expirée.') {
    if ($is_api_request) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    } else {
        header("Location: " . BASE_URL . $location);
        exit();
    }
}

/**
 * Vérifie si la requête est une requête AJAX.
 * @return bool
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Vérifie si l'utilisateur a les permissions requises.
 * @param string|array $required_permissions La permission requise (ex: 'admin', 'upload') ou un tableau de permissions.
 * @return bool
 */
function has_permission($required_permissions) {
    if (!is_logged_in()) {
        return false;
    }
    $user_role = $_SESSION['user_role'] ?? '';

    // Définir les permissions pour chaque rôle
    $role_capabilities = [
        ROLE_ADMIN => [
            'dashboard', 'upload', 'scan', 'indexing', 'search', 'admin',
            'manage_users', 'manage_ocr_templates', 'view_stats', 'view_audit_log', 'manage_security', 'manage_groups'
        ],
        ROLE_ARCHIVIST => [
            'dashboard', 'upload', 'scan', 'indexing', 'search',
            'manage_ocr_templates', 'view_stats', 'view_audit_log', 'manage_groups' // Archivists can manage OCR templates, view stats/audit, and manage groups they belong to.
        ],
        ROLE_CONTRIBUTOR => [
            'dashboard', 'upload', 'scan', 'indexing', 'search'
        ],
        ROLE_VIEWER => [
            'dashboard', 'search'
        ]
    ];

    // If the user is admin, they have all permissions
    if ($user_role === ROLE_ADMIN) {
        return true;
    }

    // Convert to array if a single permission is passed
    if (!is_array($required_permissions)) {
        $required_permissions = [$required_permissions];
    }

    // Check if the user's role has all required permissions
    foreach ($required_permissions as $permission) {
        if (!isset($role_capabilities[$user_role]) || !in_array($permission, $role_capabilities[$user_role])) {
            return false; // User does not have this permission
        }
    }

    return true; // User has all required permissions
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * @param string $role Le rôle à vérifier
 * @return bool
 */
function has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    $user_role = $_SESSION['user_role'] ?? '';
    return $user_role === $role;
}

/**
 * Protège une page en redirigeant si l'utilisateur n'est pas connecté ou n'a pas les permissions nécessaires.
 * @param string|array|null $required_permissions La permission requise (ex: 'admin', 'upload') ou un tableau de permissions.
 */
function protect_page($required_permissions = null) {
    $is_api = is_ajax_request();

    if (!is_logged_in()) {
        $_SESSION['error_message'] = "Votre session a expiré ou vous n'êtes pas connecté.";
        redirect('login.php', $is_api, "Non authentifié. Veuillez vous reconnecter.");
    }

    if ($required_permissions && !has_permission($required_permissions)) {
        $_SESSION['error_message'] = "Vous n'avez pas les permissions nécessaires pour accéder à cette page.";
        redirect('dashboard.php', $is_api, "Accès non autorisé. Permissions insuffisantes.");
    }
}

/**
 * Enregistre une action dans le journal d'audit.
 * @param string $action
 * @param string $details
 * @param int|null $user_id
 */
function log_audit_action($action, $details = '', $user_id = null) {
    global $pdo;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);

    try {
        $stmt = $pdo->prepare("INSERT INTO `audit_log` (`user_id`, `action`, `details`, `ip_address`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip_address]);
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement de l'audit: " . $e->getMessage());
    }
}

/**
 * Génère un nom de fichier unique pour éviter les collisions.
 * @param string $original_filename
 * @return string
 */
function generate_unique_filename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $unique_name = uniqid() . '_' . time() . '.' . $extension;
    return $unique_name;
}

/**
 * Valide un fichier téléversé.
 * @param array $file_info Le tableau $_FILES['nom_du_champ']
 * @return array Tableau associatif avec 'success' (bool) et 'message' (string)
 */
function validate_uploaded_file($file_info) {
    if (!isset($file_info['error']) || is_array($file_info['error'])) {
        return ['success' => false, 'message' => 'Paramètres d\'upload invalides.'];
    }

    switch ($file_info['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'Aucun fichier n\'a été envoyé.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'Taille du fichier dépassée.'];
        default:
            return ['success' => false, 'message' => 'Erreur inconnue lors de l\'upload.'];
    }

    if ($file_info['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Le fichier est trop volumineux. (Max: ' . (MAX_FILE_SIZE / (1024 * 1024)) . ' MB)'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_info['tmp_name']);
    $allowed_mime_types = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpeg',
        'image/png' => 'png',
        'image/tiff' => 'tiff',
        'image/x-tiff' => 'tiff'
    ];

    if (!array_key_exists($mime_type, $allowed_mime_types)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé. Types acceptés: PDF, JPEG, PNG, TIFF.'];
    }

    return ['success' => true, 'extension' => $allowed_mime_types[$mime_type]];
}

/**
 * Affiche un message flash (succès ou erreur).
 */
function display_flash_messages() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">';
        echo '<strong class="font-bold">Succès!</strong>';
        echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['success_message']) . '</span>';
        echo '<span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display=\'none\';">';
        echo '<svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>';
        echo '</span>';
        echo '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
        echo '<strong class="font-bold">Erreur!</strong>';
        echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['error_message']) . '</span>';
        echo '<span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display=\'none\';">';
        echo '<svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>';
        echo '</span>';
        echo '</div>';
        unset($_SESSION['error_message']);
    }
}
