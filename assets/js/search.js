// cit_sae/assets/js/search.js
document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('search-form');
    const advancedSearchToggle = document.getElementById('advanced-search-toggle');
    const advancedSearchDiv = document.getElementById('advanced-search');
    const searchResultsTableBody = document.getElementById('search-results-table-body');
    const searchResultsCount = document.getElementById('search-results-count');
    const paginationInfo = document.getElementById('pagination-info');
    const prevPageBtn = document.getElementById('prev-page-btn');
    const nextPageBtn = document.getElementById('next-page-btn');
    const searchArchiverSelect = document.getElementById('search-archiver');
    const searchGroupSelect = document.getElementById('search-group'); // Nouveau select pour les groupes

    // Modale de visualisation de document (réutilisée depuis upload.js)
    const viewDocumentModal = document.getElementById('view-document-modal');
    const viewDocumentTitle = document.getElementById('view-document-title');
    const documentViewerIframe = document.getElementById('document-viewer-iframe');
    const viewDocumentDetails = document.getElementById('view-document-details');

    let currentPage = 1;
    let totalPages = 0;
    let currentSearchParams = {}; // Pour stocker les paramètres de la dernière recherche

    if (!searchForm || !advancedSearchToggle || !advancedSearchDiv || !searchResultsTableBody || !searchResultsCount || !paginationInfo || !prevPageBtn || !nextPageBtn || !searchArchiverSelect || !searchGroupSelect || !viewDocumentModal) {
        console.warn("Search section elements not found. Skipping search functionality setup.");
        return;
    }

    advancedSearchToggle.addEventListener('click', () => {
        advancedSearchDiv.classList.toggle('hidden');
    });

    searchForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        currentPage = 1; // Réinitialiser à la première page pour une nouvelle recherche
        performSearch();
    });

    prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            performSearch(false); // Ne pas réinitialiser les paramètres
        }
    });

    nextPageBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            performSearch(false); // Ne pas réinitialiser les paramètres
        }
    });

    async function performSearch(resetParams = true) {
        searchResultsTableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Recherche en cours...</td></tr>';
        searchResultsCount.textContent = 'Recherche en cours...';
        paginationInfo.textContent = 'Page ... sur ...';
        prevPageBtn.disabled = true;
        nextPageBtn.disabled = true;

        if (resetParams) {
            currentSearchParams = {
                query: document.getElementById('simple-search').value,
                document_type: document.getElementById('search-doc-type').value,
                start_date: document.getElementById('search-start-date').value,
                end_date: document.getElementById('search-end-date').value,
                reference: document.getElementById('search-reference').value,
                archiver_id: document.getElementById('search-archiver').value,
                status: document.getElementById('search-status').value,
                group_id: document.getElementById('search-group').value // Ajouter le filtre de groupe
            };
        }

        const params = new URLSearchParams(currentSearchParams);
        params.append('page', currentPage);

        const response = await fetchData(`api/search_documents.php?${params.toString()}`);

        if (response.success) {
            searchResultsTableBody.innerHTML = ''; // Clear previous results
            if (response.documents.length > 0) {
                response.documents.forEach(doc => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="assets/img/icon_pdf.png" alt="Icône de fichier PDF" class="w-6 h-6 mr-2">
                                <div class="text-sm font-medium text-gray-900">${doc.original_filename}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${doc.document_type || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${doc.document_date || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${doc.reference || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${doc.status}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${doc.group_name || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="text-blue-600 hover:underline mr-3 view-document-btn" data-id="${doc.id}">Voir</button>
                            <button class="text-blue-600 hover:underline download-document-btn" data-id="${doc.id}">Télécharger</button>
                        </td>
                    `;
                    searchResultsTableBody.appendChild(row);
                });
            } else {
                searchResultsTableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Aucun document trouvé.</td></tr>';
            }

            searchResultsCount.textContent = `${response.total_results} document(s) trouvé(s)`;
            totalPages = response.total_pages;
            currentPage = response.current_page;
            paginationInfo.textContent = `Page ${currentPage} sur ${totalPages}`;

            prevPageBtn.disabled = (currentPage === 1);
            nextPageBtn.disabled = (currentPage === totalPages || totalPages === 0);

        } else {
            searchResultsTableBody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">${response.message}</td></tr>`;
            searchResultsCount.textContent = 'Erreur de recherche';
            paginationInfo.textContent = 'Page 0 sur 0';
        }
    }

    // Charger la liste des archiveurs pour la recherche avancée
    async function loadArchiversForSearch() {
        const response = await fetchData('api/get_users.php'); // Réutiliser l'API des utilisateurs
        if (response.success) {
            searchArchiverSelect.innerHTML = '<option value="">Tous les utilisateurs</option>';
            response.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.username;
                searchArchiverSelect.appendChild(option);
            });
        } else {
            console.error("Failed to load archivers:", response.message);
        }
    }

    // Charger la liste des groupes pour la recherche avancée
    async function loadGroupsForSearch() {
        const response = await fetchData('api/get_groups.php'); // Nouvelle API pour les groupes
        if (response.success) {
            searchGroupSelect.innerHTML = '<option value="">Tous les groupes</option>';
            response.groups.forEach(group => {
                const option = document.createElement('option');
                option.value = group.id;
                option.textContent = group.name;
                searchGroupSelect.appendChild(option);
            });
        } else {
            console.error("Failed to load groups for search:", response.message);
        }
    }

    // Gestion des boutons "Voir" et "Télécharger" dans les résultats de recherche
    searchResultsTableBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('view-document-btn')) {
            const documentId = e.target.dataset.id;
            const response = await fetchData(`api/get_document_details.php?id=${documentId}`);
            if (response.success) {
                viewDocumentTitle.textContent = response.document.original_filename;
                documentViewerIframe.src = response.document.file_url;
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
        } else if (e.target.classList.contains('download-document-btn')) {
            const documentId = e.target.dataset.id;
            // Pour le téléchargement, on peut simplement rediriger vers l'API de téléchargement
            window.location.href = `api/download_document.php?id=${documentId}`;
        }
    });

    // Initialisation de la recherche et des filtres
    // loadArchiversForSearch(); // Cette ligne sera appelée par main.js
    // loadGroupsForSearch(); // Cette ligne sera appelée par main.js
});
