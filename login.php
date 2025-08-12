<?php
require_once 'includes/functions.php';

// Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = "Veuillez remplir tous les champs.";
    } else {
        global $pdo; // Accéder à l'objet PDO global
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time(); // Pour la gestion de l'inactivité

                log_audit_action('Connexion réussie', 'Utilisateur ' . $user['username'] . ' connecté.', $user['id']);
                redirect('dashboard.php');
            } else {
                $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
                log_audit_action('Tentative de connexion échouée', 'Nom d\'utilisateur: ' . $username, null);
            }
        } catch (PDOException $e) {
            $error_message = "Erreur de base de données lors de la connexion.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - CIT SAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #EFF2F5; color: #2A4365; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <div class="flex justify-center mb-6">
            <img src="assets/img/logo_cit_sae.png" alt="Logo CIT SAE" class="w-20 h-20">
        </div>
        <h1 class="text-2xl font-bold text-center text-blue-800 mb-6">Connexion à CIT SAE</h1>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Erreur!</strong>
                <span class="block sm:inline"> <?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Nom d'utilisateur ou Email:</label>
                <input type="text" id="username" name="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Mot de passe:</label>
                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors">
                    Se connecter
                </button>
                <a href="#" class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800">
                    Mot de passe oublié?
                </a>
            </div>
        </form>
        <p class="text-center text-gray-500 text-xs mt-6">
            &copy;<?php echo date('Y'); ?> CIT SAE. Tous droits réservés.
        </p>
    </div>
</body>
</html>
