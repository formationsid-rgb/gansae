// assets/js/upload.js
document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');
    const uploadForm = document.getElementById('upload-form');
    const uploadStatus = document.getElementById('upload-status');
    const recentFilesList = document.getElementById('recent-files-list');
    const sortRecentFilesSelect = document.getElementById('sort-recent-files');
    const configureArchiveOptionsBtn = document.querySelector('.configure-archive-options-btn');

    // Modale de visualisation de document
    const viewDocumentModal = document.getElementById('view-document-modal');
    const viewDocumentTitle = document.getElementById('view-document-title');
    const documentViewerIframe = document.getElementById('document-viewer-iframe');
    const viewDocumentDetails = document.getElementById('view-document-details');


    if (!dropzone || !fileInput || !uploadForm || !uploadStatus || !recentFilesList || !sortRecentFilesSelect || !configureArchiveOptionsBtn || !viewDocumentModal) {
        console.warn("Upload section elements not found. Skipping upload functionality setup.");
        return;
    }

    // Dropzone functionality
    dropzone.addEventListener('click', () => {
        fileInput.click();
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.add('active');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.remove('active');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        fileInput.files = e.dataTransfer.files;
        // Optionally display file names here
        if (fileInput.files.length > 0) {
            uploadStatus.textContent = `${fileInput.files.length} fichier(s) sélectionné(s) pour l'upload.`;
            uploadStatus.className = 'mt-4 text-sm text-blue-600';
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            uploadStatus.textContent = `${fileInput.files.length} fichier(s) sélectionné(s) pour l'upload.`;
            uploadStatus.className = 'mt-4 text-sm text-blue-600';
        } else {
            uploadStatus.textContent = '';
        }
    });

    // Handle form submission
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        uploadStatus.textContent = 'Téléversement en cours...';
        uploadStatus.className = 'mt-4 text-sm text-yellow-600';

        const formData = new FormData(uploadForm);

        const response = await fetchData('api/upload_document.php', 'POST', formData);

        if (response.success) {
            uploadStatus.textContent = response.message;
            uploadStatus.className = 'mt-4 text-sm text-green-600';
            uploadForm.reset(); // Clear the form
            loadRecentFiles(); // Reload recent files
            showFlashMessage(response.message, 'success');
            // Recharger les données du tableau de bord et de l'indexation si la section est active
            if (typeof loadDashboardData === 'function') loadDashboardData();
            if (typeof loadPendingIndexingFiles === 'function') loadPendingIndexingFiles();
        } else {
            uploadStatus.textContent = response.message;
            uploadStatus.className = 'mt-4 text-sm text-red-600';
            showFlashMessage(response.message, 'error');
        }
    });

    // Load recent files
    async function loadRecentFiles() {
        console.log("Loading recent files...");
        recentFilesList.innerHTML = '<p class="text-gray-500 text-center col-span-full">Chargement des fichiers...</p>';
        const sortBy = sortRecentFilesSelect.value;
        const response = await fetchData(`api/get_recent_files.php?sort_by=${sortBy}`);
        console.log("Response from get_recent_files.php:", response);

        if (response.success) {
            recentFilesList.innerHTML = ''; // Clear previous content
            if (response.files.length > 0) {
                response.files.forEach(file => {
                    const fileCard = document.createElement('div');
                    fileCard.className = 'document-card bg-white p-4 border border-gray-100 rounded-lg shadow-sm transition-transform';
                    fileCard.innerHTML = `
                        <div class="flex items-center space-x-3 mb-3">
                            <img src="assets/img/icon_pdf.png" alt="Icône de fichier ${file.file_type}" class="w-12 h-12">
                            <div>
                                <p class="font-medium truncate">${file.original_filename}</p>
                                <p class="text-xs text-gray-500">Ajouté le ${file.uploaded_at}</p>
                                <p class="text-xs text-gray-500">Statut: ${file.status}</p>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 border-t border-gray-100 pt-2">
                            <span>${file.file_size_mb} MB</span>
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:underline view-document-btn" data-id="${file.id}">Voir</button>
                                <button class="text-red-600 hover:underline delete-document-btn" data-id="${file.id}">Supprimer</button>
                            </div>
                        </div>
                    `;
                    recentFilesList.appendChild(fileCard);
                });
                console.log("Recent files loaded successfully.");
            } else {
                recentFilesList.innerHTML = '<p class="text-gray-500 text-center col-span-full">Aucun fichier récent trouvé.</p>';
                console.log("No recent files found.");
            }
        } else {
            recentFilesList.innerHTML = `<p class="text-red-500 text-center col-span-full">${response.message}</p>`;
            console.error("Failed to load recent files:", response.message);
        }
    }

    sortRecentFilesSelect.addEventListener('change', loadRecentFiles);

    // Gestion des boutons "Voir" et "Supprimer"
    recentFilesList.addEventListener('click', async (e) => {
        if (e.target.classList.contains('view-document-btn')) {
            const documentId = e.target.dataset.id;
            const response = await fetchData(`api/get_document_details.php?id=${documentId}`);
            if (response.success) {
                viewDocumentTitle.textContent = response.document.original_filename;
                documentViewerIframe.src = response.document.file_url; // Assurez-vous que l'URL est correcte
                viewDocumentDetails.innerHTML = `
                    <strong>Type:</strong> ${response.document.document_type || 'N/A'} |
                    <strong>Référence:</strong> ${response.document.reference || 'N/A'} |
                    <strong>Date:</strong> ${response.document.document_date || 'N/A'} |
                    <strong>Statut:</strong> ${response.document.status} |
                    <strong>Groupe:</strong> ${response.document.group_name || 'N/A'}
                `;
                openModal('view-document-modal');
            } else {
                showFlashMessage(response.message, 'error');
            }
        } else if (e.target.classList.contains('delete-document-btn')) {
            const documentId = e.target.dataset.id;
            if (confirm('Êtes-vous sûr de vouloir supprimer ce document ? Cette action est irréversible.')) {
                const formData = new FormData();
                formData.append('id', documentId);
                const response = await fetchData('api/delete_document.php', 'POST', formData);
                if (response.success) {
                    showFlashMessage(response.message, 'success');
                    loadRecentFiles(); // Recharger la liste des fichiers
                    if (typeof loadDashboardData === 'function') loadDashboardData();
                    if (typeof loadPendingIndexingFiles === 'function') loadPendingIndexingFiles();
                } else {
                    showFlashMessage(response.message, 'error');
                }
            }
        }
    });

    // Gestion du bouton "Configurer les options d'archivage"
    configureArchiveOptionsBtn.addEventListener('click', () => {
        openModal('archive-options-modal');
    });

    // Initial load of recent files when the page loads (handled by main.js)
    // loadRecentFiles();
});
