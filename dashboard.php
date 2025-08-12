<?php
require_once 'includes/functions.php';
protect_page(); // Protège la page, redirige si non connecté

// Gestion de l'inactivité de session
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    $_SESSION['error_message'] = "Votre session a expiré en raison de l'inactivité.";
    redirect('login.php');
}
$_SESSION['last_activity'] = time(); // Mettre à jour le temps de dernière activité

// Inclure l'en-tête (qui contient la barre latérale)
include_once 'includes/header.php';
?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto ml-64">
            <!-- Header -->
            <header class="bg-white shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <h2 id="current-section" class="text-xl font-semibold text-gray-800">Tableau de bord</h2>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <img src="assets/img/icon_notification.png" alt="Icône Notification" class="w-6 h-6 cursor-pointer">
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">3</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <img src="assets/img/avatar_default.png" alt="Photo de profil utilisateur" class="w-8 h-8 rounded-full">
                            <span class="font-medium"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?></span>
                            <a href="logout.php" class="text-sm text-blue-600 hover:underline ml-2">Déconnexion</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div class="p-6">
                <?php display_flash_messages(); ?>
            </div>

            <!-- Dashboard Section -->
            <main class="p-6">
                <div id="dashboard" class="section-content">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500">Documents archivés</p>
                                    <p class="text-2xl font-bold mt-1" id="total-documents">...</p>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <img src="assets/img/icon_documents.png" alt="Icône Documents" class="w-6 h-6">
                                </div>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500">Documents à indexer</p>
                                    <p class="text-2xl font-bold mt-1" id="pending-indexing">...</p>
                                </div>
                                <div class="bg-yellow-100 p-3 rounded-full">
                                    <img src="assets/img/icon_waiting.png" alt="Icône Attente" class="w-6 h-6">
                                </div>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500">Utilisateurs actifs</p>
                                    <p class="text-2xl font-bold mt-1" id="active-users">...</p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full">
                                    <img src="assets/img/icon_users.png" alt="Icône Utilisateurs" class="w-6 h-6">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-lg">Activité récente</h3>
                            <button class="text-blue-600 text-sm font-medium">Voir tout</button>
                        </div>
                        <div class="space-y-4" id="recent-activity-list">
                            <!-- Les activités récentes seront chargées ici via AJAX -->
                            <p class="text-gray-500 text-center">Chargement des activités...</p>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div id="upload" class="section-content hidden">
                    <?php if (has_permission('upload')): ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
                        <h3 class="font-semibold text-lg mb-4">Téléversement de documents</h3>
                        <form id="upload-form" enctype="multipart/form-data">
                            <div class="dropzone border-2 border-dashed rounded-lg p-8 text-center cursor-pointer mb-4" id="dropzone">
                                <img src="assets/img/icon_cloud_upload.png" alt="Icône de téléversement" class="mx-auto w-16 h-16 mb-4">
                                <p class="text-gray-500 mb-1">Glissez-déposez vos fichiers ici ou cliquez pour sélectionner</p>
                                <p class="text-sm text-gray-400">Formats supportés : PDF, JPEG, PNG, TIFF</p>
                                <input type="file" id="file-input" name="document_file" class="hidden" multiple>
                            </div>
                            <div class="mb-4">
                                <label for="document_type" class="block text-sm font-medium text-gray-700 mb-1">Type de document (optionnel)</label>
                                <input type="text" id="document_type" name="document_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Ex: Facture, Contrat">
                            </div>
                            <div class="mb-4">
                                <label for="document_reference" class="block text-sm font-medium text-gray-700 mb-1">Référence (optionnel)</label>
                                <input type="text" id="document_reference" name="document_reference" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Ex: REF-2023-001">
                            </div>
                            <div class="mb-4">
                                <label for="document_group" class="block text-sm font-medium text-gray-700 mb-1">Groupe (Service)</label>
                                <select id="document_group" name="document_group" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    <!-- Options chargées dynamiquement par JS -->
                                    <option value="">Sélectionner un groupe</option>
                                </select>
                            </div>
                            <div class="flex justify-between items-center">
                                <button type="submit" class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700 transition-colors">
                                    Téléverser
                                </button>
                                <button type="button" class="text-blue-600 underline text-sm configure-archive-options-btn">
                                    Configurer les options d'archivage
                                </button>
                            </div>
                        </form>
                        <div id="upload-status" class="mt-4 text-sm"></div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-lg">Fichiers récents</h3>
                            <div class="relative">
                                <select class="appearance-none bg-white border border-gray-300 rounded px-3 py-1 pr-8 text-sm" id="sort-recent-files">
                                    <option value="uploaded_at DESC">Trier par date (récent)</option>
                                    <option value="uploaded_at ASC">Trier par date (ancien)</option>
                                    <option value="file_size DESC">Trier par taille (décroissant)</option>
                                    <option value="file_size ASC">Trier par taille (croissant)</option>
                                    <option value="original_filename ASC">Trier par nom (A-Z)</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="recent-files-list">
                            <!-- Les fichiers récents seront chargés ici via AJAX -->
                            <p class="text-gray-500 text-center col-span-full">Chargement des fichiers...</p>
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="text-red-500">Vous n'avez pas la permission d'accéder à la section de téléversement.</p>
                    <?php endif; ?>
                </div>

                <!-- Scan Section -->
                <div id="scan" class="section-content hidden">
                    <?php if (has_permission('scan')): ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
                        <h3 class="font-semibold text-lg mb-4">Scanner des documents</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2">
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 aspect-[4/3] flex items-center justify-center">
                                    <!-- Conteneur pour le contrôle TWAIN -->
                                    <div id="dwt-container" style="width: 100%; height: 100%;"></div>
                                </div>
                                <div class="mt-4 flex space-x-3">
                                    <button id="scan-btn" class="flex-1 bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2">
                                        <img src="assets/img/icon_scanner.png" alt="Icône Scanner" class="w-4 h-4">
                                        <span>Scanner</span>
                                    </button>
                                    <button type="button" class="flex-1 bg-white border border-gray-300 text-gray-700 rounded px-4 py-2 hover:bg-gray-50 transition-colors configure-scan-options-btn">
                                        Configurer
                                    </button>
                                </div>
                                <div id="scan-status" class="mt-4 text-sm text-center"></div>
                            </div>
                            <div>
                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                    <h4 class="font-medium mb-3">Paramètres du scan</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                                            <select id="scanner-source" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                                <!-- Options chargées par le JS du SDK -->
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Résolution (DPI)</label>
                                            <select id="scan-resolution" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                                <option value="150">150</option>
                                                <option value="300" selected>300</option>
                                                <option value="600">600</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Format de sortie</label>
                                            <select id="scan-output-format" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                                <option value="PDF" selected>PDF</option>
                                                <option value="JPEG">JPEG</option>
                                                <option value="TIFF">TIFF</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Qualité</label>
                                            <select id="scan-quality" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                                <option value="Standard">Standard</option>
                                                <option value="Haute" selected>Haute</option>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label for="scan_document_group" class="block text-sm font-medium text-gray-700 mb-1">Groupe (Service)</label>
                                            <select id="scan_document_group" name="document_group" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                                <!-- Options chargées dynamiquement par JS -->
                                                <option value="">Sélectionner un groupe</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="text-red-500">Vous n'avez pas la permission d'accéder à la section de scan.</p>
                    <?php endif; ?>
                </div>

                <!-- Indexing Section -->
                <div id="indexing" class="section-content hidden">
                    <?php if (has_permission('indexing')): ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
                        <h3 class="font-semibold text-lg mb-4">Indexation OCR</h3>
                        <div class="mb-4">
                            <label for="indexing-document-select" class="block text-sm font-medium text-gray-700 mb-1">Sélectionner un document à indexer</label>
                            <select id="indexing-document-select" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">Chargement des documents...</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2 relative">
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 aspect-[4/3] flex items-center justify-center" id="ocr-container">
                                    <img src="assets/img/ocr_document_example.png" alt="Document pour OCR" id="ocr-image" class="max-w-full max-h-full">
                                </div>
                                <div class="mt-4 flex space-x-3">
                                    <button id="select-area-btn" class="flex-1 bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700 transition-colors">
                                        Sélectionner une zone
                                    </button>
                                    <button id="extract-text-btn" class="flex-1 bg-white border border-gray-300 text-gray-700 rounded px-4 py-2 hover:bg-gray-50 transition-colors">
                                        Extraire le texte
                                    </button>
                                </div>
                                <div id="ocr-status" class="mt-4 text-sm text-center"></div>
                            </div>
                            <div>
                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                    <h4 class="font-medium mb-3">Métadonnées du document</h4>
                                    <form id="indexing-form">
                                        <input type="hidden" id="document-id-indexing" name="document_id">
                                        <div>
                                            <label for="doc-type-indexing" class="block text-sm font-medium text-gray-700 mb-1">Type de document</label>
                                            <select id="doc-type-indexing" name="document_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                                <option value="">Sélectionner...</option>
                                                <option value="Facture">Facture</option>
                                                <option value="Contrat">Contrat</option>
                                                <option value="Note interne">Note interne</option>
                                                <option value="Courrier">Courrier</option>
                                            </select>
                                        </div>
                                        <div class="mt-4">
                                            <label for="reference-indexing" class="block text-sm font-medium text-gray-700 mb-1">Référence</label>
                                            <input type="text" id="reference-indexing" name="reference" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Saisir une référence">
                                        </div>
                                        <div class="mt-4">
                                            <label for="date-indexing" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                            <input type="date" id="date-indexing" name="document_date" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                        </div>
                                        <div class="mt-4">
                                            <label for="extracted-text" class="block text-sm font-medium text-gray-700 mb-1">Texte extrait</label>
                                            <textarea class="w-full border border-gray-300 rounded px-3 py-2 text-sm h-32" placeholder="Le texte extrait apparaîtra ici..." id="extracted-text" name="extracted_text"></textarea>
                                        </div>
                                        <button type="submit" class="w-full bg-green-600 text-white rounded px-4 py-2 hover:bg-green-700 transition-colors mt-4">
                                            Enregistrer l'indexation
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="text-red-500">Vous n'avez pas la permission d'accéder à la section d'indexation.</p>
                    <?php endif; ?>
                </div>

                <!-- Search Section -->
                <div id="search" class="section-content hidden">
                    <?php if (has_permission('search')): ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
                        <h3 class="font-semibold text-lg mb-4">Recherche de documents</h3>
                        <form id="search-form">
                            <div class="flex flex-col md:flex-row md:items-end md:space-x-4 space-y-4 md:space-y-0">
                                <div class="flex-1">
                                    <label for="simple-search" class="block text-sm font-medium text-gray-700 mb-1">Recherche simple</label>
                                    <div class="relative">
                                        <input type="text" id="simple-search" name="query" placeholder="Rechercher un document..." class="w-full border border-gray-300 rounded px-4 py-2 pl-10">
                                        <img src="assets/img/icon_search_blue.png" alt="Icône Recherche" class="absolute left-3 top-2.5 w-4 h-4 opacity-60">
                                    </div>
                                </div>
                                <button type="submit" class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-700 transition-colors whitespace-nowrap">
                                    Rechercher
                                </button>
                                <button type="button" id="advanced-search-toggle" class="bg-white border border-gray-300 text-gray-700 rounded px-4 py-2 hover:bg-gray-50 transition-colors whitespace-nowrap">
                                    Recherche avancée
                                </button>
                            </div>

                            <div id="advanced-search" class="mt-6 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label for="search-doc-type" class="block text-sm font-medium text-gray-700 mb-1">Type de document</label>
                                        <select id="search-doc-type" name="document_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                            <option value="">Tous les types</option>
                                            <option value="Facture">Facture</option>
                                            <option value="Contrat">Contrat</option>
                                            <option value="Courrier">Courrier</option>
                                            <option value="Note interne">Note interne</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="search-start-date" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                                        <input type="date" id="search-start-date" name="start_date" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label for="search-end-date" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                                        <input type="date" id="search-end-date" name="end_date" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label for="search-reference" class="block text-sm font-medium text-gray-700 mb-1">Référence</label>
                                        <input type="text" id="search-reference" name="reference" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label for="search-archiver" class="block text-sm font-medium text-gray-700 mb-1">Archiveur</label>
                                        <select id="search-archiver" name="archiver_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                            <option value="">Tous les utilisateurs</option>
                                            <!-- Options chargées dynamiquement via JS -->
                                        </select>
                                    </div>
                                    <div>
                                        <label for="search-status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                                        <select id="search-status" name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                            <option value="">Tous les statuts</option>
                                            <option value="archived">Archivé</option>
                                            <option value="pending_indexing">En attente d'indexation</option>
                                            <option value="deleted">Supprimé</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="search-group" class="block text-sm font-medium text-gray-700 mb-1">Groupe (Service)</label>
                                        <select id="search-group" name="group_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                            <option value="">Tous les groupes</option>
                                            <!-- Options chargées dynamiquement via JS -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-lg">Résultats de recherche</h3>
                            <div class="text-sm text-gray-500" id="search-results-count">0 documents trouvés</div>
                        </div>
                        <div class="overflow-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Groupe</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="search-results-table-body">
                                    <!-- Les résultats de recherche seront chargés ici via AJAX -->
                                    <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Aucun document trouvé.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-between items-center">
                            <div class="text-sm text-gray-500" id="pagination-info">Page 0 sur 0</div>
                            <div class="flex space-x-2">
                                <button id="prev-page-btn" class="px-3 py-1 border border-gray-300 rounded text-sm bg-white hover:bg-gray-50" disabled>Précédent</button>
                                <button id="next-page-btn" class="px-3 py-1 border border-gray-300 rounded text-sm bg-white hover:bg-gray-50" disabled>Suivant</button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="text-red-500">Vous n'avez pas la permission d'accéder à la section de recherche.</p>
                    <?php endif; ?>
                </div>

                <!-- Admin Section -->
                <div id="admin" class="section-content hidden">
                    <?php if (has_permission('admin')): ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
                        <h3 class="font-semibold text-lg mb-6">Administration</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                            <?php if (has_permission('manage_users')): ?>
                            <div class="bg-blue-50 p-6 rounded-lg border border-blue-100">
                                <h4 class="font-medium text-blue-800 mb-2 flex items-center">
                                    <img src="assets/img/icon_users_admin.png" alt="Icône Utilisateurs" class="w-5 h-5 mr-2">
                                    Gestion des Utilisateurs
                                </h4>
                                <p class="text-sm text-gray-700 mb-4">Gérer les comptes et les permissions des utilisateurs.</p>
                                <button id="manage-users-btn" class="text-blue-600 hover:underline text-sm font-medium">
                                    Accéder à la gestion
                                </button>
                            </div>
                            <?php endif; ?>
                            <?php if (has_permission('manage_ocr_templates')): ?>
                            <div class="bg-green-50 p-6 rounded-lg border border-green-100">
                                <h4 class="font-medium text-green-800 mb-2 flex items-center">
                                    <img src="assets/img/icon_templates.png" alt="Icône Modèles" class="w-5 h-5 mr-2">
                                    Modèles OCR
                                </h4>
                                <p class="text-sm text-gray-700 mb-4">Configurer les modèles d'extraction et de renommage.</p>
                                <button id="configure-ocr-models-btn" class="text-green-600 hover:underline text-sm font-medium">
                                    Configurer les modèles
                                </button>
                            </div>
                            <?php endif; ?>
                            <?php if (has_permission('view_stats')): ?>
                            <div class="bg-purple-50 p-6 rounded-lg border border-purple-100">
                                <h4 class="font-medium text-purple-800 mb-2 flex items-center">
                                    <img src="assets/img/icon_stats.png" alt="Icône Statistiques" class="w-5 h-5 mr-2">
                                    Statistiques
                                </h4>
                                <p class="text-sm text-gray-700 mb-4">Consulter les métriques et l'utilisation du système.</p>
                                <button id="view-statistics-btn" class="text-purple-600 hover:underline text-sm font-medium">
                                    Voir les statistiques
                                </button>
                            </div>
                            <?php endif; ?>
                            <?php if (has_permission('view_audit_log')): ?>
                            <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-100">
                                <h4 class="font-medium text-yellow-800 mb-2 flex items-center">
                                    <img src="assets/img/icon_audit.png" alt="Icône Journal" class="w-5 h-5 mr-2">
                                    Journal d'audit
                                </h4>
                                <p class="text-sm text-gray-700 mb-4">Consulter les traces d'activité du système.</p>
                                <button id="view-audit-log-btn" class="text-yellow-600 hover:underline text-sm font-medium">
                                    Voir le journal
                                </button>
                            </div>
                            <?php endif; ?>
                            <?php if (has_permission('manage_security')): ?>
                            <div class="bg-red-50 p-6 rounded-lg border border-red-100">
                                <h4 class="font-medium text-red-800 mb-2 flex items-center">
                                    <img src="assets/img/icon_security.png" alt="Icône Sécurité" class="w-5 h-5 mr-2">
                                    Sécurité
                                </h4>
                                <p class="text-sm text-gray-700 mb-4">Gérer les clés de chiffrement et accès.</p>
                                <button id="manage-security-btn" class="text-red-600 hover:underline text-sm font-medium">
                                    Paramètres de sécurité
                                </button>
                            </div>
                            <?php endif; ?>
                            <?php if (has_permission('manage_groups')): ?>
                            <div class="bg-indigo-50 p-6 rounded-lg border border-indigo-100">
                                <h4 class="font-medium text-indigo-800 mb-2 flex items-center">
                                    <img src="assets/img/icon_users.png" alt="Icône Groupes" class="w-5 h-5 mr-2">
                                    Gestion des Groupes
                                </h4>
                                <p class="text-sm text-gray-700 mb-4">Gérer les groupes d'utilisateurs et les services.</p>
                                <button id="manage-groups-btn" class="text-indigo-600 hover:underline text-sm font-medium">
                                    Accéder à la gestion
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- User Management Sub-section -->
                        <div id="user-management-section" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mt-6 hidden">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium">Utilisateurs du système</h4>
                                <button id="add-user-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                    Ajouter un utilisateur
                                </button>
                            </div>
                            <div class="overflow-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom d'utilisateur</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Groupes</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="users-table-body">
                                        <!-- Les utilisateurs seront chargés ici via AJAX -->
                                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Chargement des utilisateurs...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Audit Log Sub-section -->
                        <div id="audit-log-section" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mt-6 hidden">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium">Journal d'audit</h4>
                                <div class="flex space-x-2">
                                    <button id="export-audit-log-csv-btn" class="text-sm text-blue-600 hover:underline">Exporter CSV</button>
                                    <button id="export-audit-log-pdf-btn" class="text-sm text-blue-600 hover:underline">Exporter PDF</button>
                                </div>
                            </div>
                            <div class="overflow-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Heure</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Détails</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="audit-log-table-body">
                                        <!-- Les logs d'audit seront chargés ici via AJAX -->
                                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Chargement du journal d'audit...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Group Management Sub-section -->
                        <div id="group-management-section" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mt-6 hidden">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium">Groupes (Services)</h4>
                                <button id="add-group-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                    Ajouter un groupe
                                </button>
                            </div>
                            <div class="overflow-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom du groupe</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="groups-table-body">
                                        <!-- Les groupes seront chargés ici via AJAX -->
                                        <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Chargement des groupes...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <?php else: ?>
                        <p class="text-red-500">Vous n'avez pas la permission d'accéder à la section d'administration.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modales Globales -->

    <!-- Modale pour les options d'archivage (Upload) -->
    <div id="archive-options-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Options d'Archivage</h2>
            <p class="text-gray-700">Ceci est une modale de configuration des options d'archivage. Vous pouvez ajouter ici des champs pour définir des règles de nommage, des tags par défaut, etc.</p>
            <div class="mt-4">
                <label for="archive-naming-convention" class="block text-sm font-medium text-gray-700 mb-1">Convention de nommage</label>
                <input type="text" id="archive-naming-convention" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Ex: {type}_{date}_{reference}">
            </div>
            <button class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">Enregistrer les options</button>
        </div>
    </div>

    <!-- Modale pour les options de scan (Scanner) -->
    <div id="scan-options-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Options de Scan</h2>
            <p class="text-gray-700">Ceci est une modale de configuration avancée du scanner. Vous pouvez ajouter ici des options spécifiques au pilote TWAIN ou des profils de scan.</p>
            <div class="mt-4">
                <label for="scan-profile" class="block text-sm font-medium text-gray-700 mb-1">Profil de scan</label>
                <select id="scan-profile" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    <option>Standard</option>
                    <option>Haute Qualité</option>
                    <option>Recto-verso</option>
                </select>
            </div>
            <button class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">Enregistrer les options</button>
        </div>
    </div>

    <!-- Modale pour la visualisation de document (Upload, Search) -->
    <div id="view-document-modal" class="modal">
        <div class="modal-content !max-w-3xl !w-full !h-5/6 flex flex-col">
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-semibold text-gray-800 mb-4" id="view-document-title"></h2>
            <div class="flex-grow overflow-auto border border-gray-300 rounded-lg p-2 bg-gray-100">
                <iframe id="document-viewer-iframe" src="" class="w-full h-full" frameborder="0"></iframe>
            </div>
            <div class="mt-4 text-sm text-gray-600" id="view-document-details"></div>
        </div>
    </div>

    <!-- Modale pour configurer les modèles OCR (Admin) -->
    <div id="ocr-models-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Configuration des Modèles OCR</h2>
            <p class="text-gray-700">Gérez les modèles d'extraction OCR. Vous pouvez définir des zones prédéfinies pour différents types de documents (factures, contrats) afin d'automatiser l'extraction de données.</p>
            <form id="ocr-template-form" class="mt-4">
                <div class="mb-4">
                    <label for="ocr-template-name" class="block text-sm font-medium text-gray-700 mb-1">Nom du modèle</label>
                    <input type="text" id="ocr-template-name" name="template_name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Ex: Modèle Facture Fournisseur" required>
                </div>
                <div class="mb-4">
                    <label for="ocr-template-doc-type" class="block text-sm font-medium text-gray-700 mb-1">Type de document associé</label>
                    <input type="text" id="ocr-template-doc-type" name="document_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Ex: Facture">
                </div>
                <div class="mb-4">
                    <label for="ocr-template-zones" class="block text-sm font-medium text-gray-700 mb-1">Zones d'extraction (JSON)</label>
                    <textarea id="ocr-template-zones" name="zones" class="w-full border border-gray-300 rounded px-3 py-2 text-sm h-24" placeholder='[{"name": "Date", "coords": "x,y,w,h"}, {"name": "Montant", "coords": "x,y,w,h"}]' required></textarea>
                    <p class="text-xs text-gray-500 mt-1">Format JSON attendu: `[{"name": "NomChamp", "coords": "x,y,width,height"}]`</p>
                </div>
                <button type="submit" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">Enregistrer le modèle</button>
            </form>
            <div id="ocr-template-status" class="mt-4 text-sm text-center"></div>
        </div>
    </div>

    <!-- Modale pour les statistiques (Admin) -->
    <div id="statistics-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Statistiques du Système</h2>
            <p class="text-gray-700">Statistiques détaillées sur l'utilisation du système :</p>
            <div class="mt-4">
                <h4 class="font-medium text-gray-800 mb-2">Documents par type</h4>
                <canvas id="docsByTypeChart"></canvas>
            </div>
            <div class="mt-4">
                <h4 class="font-medium text-gray-800 mb-2">Volume de téléversement par mois</h4>
                <canvas id="uploadVolumeChart"></canvas>
            </div>
            <div class="mt-4">
                <h4 class="font-medium text-gray-800 mb-2">Taux d'indexation OCR</h4>
                <canvas id="ocrIndexingRateChart"></canvas>
            </div>
            <div class="mt-4">
                <h4 class="font-medium text-gray-800 mb-2">Activité des utilisateurs (Top 5)</h4>
                <div id="userActivityList" class="p-4 bg-gray-50 border rounded-lg">
                    <p class="text-gray-500">Chargement de l'activité...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale pour les paramètres de sécurité (Admin) -->
    <div id="security-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Paramètres de Sécurité</h2>
            <p class="text-gray-700">Ceci est une modale pour gérer les paramètres de sécurité avancés. Vous pouvez inclure des options pour :</p>
            <ul class="list-disc list-inside text-gray-700 mt-2">
                <li>Gestion des clés de chiffrement des documents</li>
                <li>Configuration de l'authentification à deux facteurs (2FA)</li>
                <li>Politiques de mots de passe</li>
                <li>Gestion des certificats SSL</li>
            </ul>
            <div class="mt-4">
                <label for="security-2fa" class="block text-sm font-medium text-gray-700 mb-1">Activer 2FA</label>
                <input type="checkbox" id="security-2fa" class="form-checkbox h-5 w-5 text-blue-600">
            </div>
            <button class="mt-6 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">Enregistrer les paramètres</button>
        </div>
    </div>

    <!-- Modale pour ajouter/modifier un utilisateur -->
    <div id="add-user-modal" class="modal">
        <div class="modal-content">
            <!-- Contenu injecté par JS -->
        </div>
    </div>
    <div id="edit-user-modal" class="modal">
        <div class="modal-content">
            <!-- Contenu injecté par JS -->
        </div>
    </div>

    <!-- Modale pour ajouter/modifier un groupe -->
    <div id="add-group-modal" class="modal">
        <div class="modal-content">
            <!-- Contenu injecté par JS -->
        </div>
    </div>
    <div id="edit-group-modal" class="modal">
        <div class="modal-content">
            <!-- Contenu injecté par JS -->
        </div>
    </div>

<?php include_once 'includes/footer.php'; ?>
