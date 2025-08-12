<?php
// Assurez-vous que functions.php est inclus avant d'utiliser ses fonctions
if (!function_exists('is_logged_in')) {
    require_once 'functions.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Système d'Archivage Électronique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Chart.js pour les statistiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar bg-white w-64 shadow-md h-full fixed">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <img src="assets/img/logo_cit_sae.png" alt="Logo CIT SAE" class="w-10 h-10">
                    <h1 class="font-bold text-xl text-blue-800">CIT SAE</h1>
                </div>
            </div>
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="#dashboard" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <img src="assets/img/icon_dashboard.png" alt="Icône Tableau de bord" class="w-5 h-5">
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <?php if (has_permission('upload')): ?>
                    <li>
                        <a href="#upload" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 hover:text-blue-700">
                            <img src="assets/img/icon_upload.png" alt="Icône Téléversement" class="w-5 h-5">
                            <span>Téléversement</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('scan')): ?>
                    <li>
                        <a href="#scan" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 hover:text-blue-700">
                            <img src="assets/img/icon_scan.png" alt="Icône Scanner" class="w-5 h-5">
                            <span>Scanner</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('indexing')): ?>
                    <li>
                        <a href="#indexing" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 hover:text-blue-700">
                            <img src="assets/img/icon_indexing.png" alt="Icône Indexation" class="w-5 h-5">
                            <span>Indexation</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('search')): ?>
                    <li>
                        <a href="#search" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 hover:text-blue-700">
                            <img src="assets/img/icon_search.png" alt="Icône Recherche" class="w-5 h-5">
                            <span>Recherche</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (has_permission('admin')): // Afficher la section Admin uniquement pour les admins ?>
                    <li>
                        <a href="#admin" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 hover:text-blue-700">
                            <img src="assets/img/icon_admin.png" alt="Icône Administration" class="w-5 h-5">
                            <span>Administration</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
