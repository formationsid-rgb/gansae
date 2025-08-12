// Fonction utilitaire pour les requêtes AJAX
async function fetchData(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Indique que c'est une requête AJAX
        }
    };

    if (data) {
        if (method === 'POST' && !(data instanceof FormData)) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        } else {
            options.body = data; // Pour FormData (uploads)
        }
    }

    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            const errorText = await response.text();
            // Attempt to parse JSON error if available
            try {
                const errorJson = JSON.parse(errorText);
                throw new Error(errorJson.message || `HTTP error! status: ${response.status}, message: ${errorText}`);
            } catch (e) {
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }
        }
        return await response.json();
    } catch (error) {
        console.error("Fetch error: ", error);
        // Afficher un message d'erreur générique à l'utilisateur
        showFlashMessage("Une erreur est survenue : " + error.message, 'error');
        return { success: false, message: "Erreur de communication avec le serveur." };
    }
}

// Gestion de la navigation et affichage des sections
document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('nav a');
    const sections = document.querySelectorAll('.section-content');
    const currentSectionTitle = document.getElementById('current-section');

    function showSection(targetId) {
        // Masquer toutes les sections
        sections.forEach(section => {
            section.classList.add('hidden');
        });

        // Afficher la section cible
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            targetSection.classList.remove('hidden');
        }

        // Mettre à jour le lien de navigation actif
        navLinks.forEach(item => {
            item.classList.remove('bg-blue-50', 'text-blue-700');
            item.classList.add('hover:bg-blue-50', 'hover:text-blue-700');
        });
        const activeLink = document.querySelector(`nav a[href="#${targetId}"]`);
        if (activeLink) {
            activeLink.classList.add('bg-blue-50', 'text-blue-700');
            activeLink.classList.remove('hover:bg-blue-50', 'hover:text-blue-700');
            currentSectionTitle.textContent = activeLink.textContent.trim();
        }

        // Charger les données spécifiques à la section si nécessaire
        loadSectionData(targetId);
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            showSection(targetId);
        });
    });

    // Gérer l'état initial de la page (basé sur le hash de l'URL ou par défaut)
    const initialHash = window.location.hash.substring(1);
    if (initialHash && document.getElementById(initialHash)) {
        showSection(initialHash);
    } else {
        showSection('dashboard'); // Afficher le tableau de bord par défaut
    }

    // Fonction pour charger les données spécifiques à chaque section
    function loadSectionData(sectionId) {
        console.log("Loading data for section:", sectionId);
        switch (sectionId) {
            case 'dashboard':
                if (typeof loadDashboardData === 'function') loadDashboardData();
                break;
            case 'upload':
                if (typeof loadRecentFiles === 'function') loadRecentFiles();
                loadGroupsForDocumentAssignment('document_group'); // Charger les groupes pour l'assignation de documents
                break;
            case 'scan':
                // Le scan a sa propre initialisation dans scan.js
                loadGroupsForDocumentAssignment('scan_document_group'); // Charger les groupes pour l'assignation de documents scannés
                break;
            case 'indexing':
                if (typeof loadPendingIndexingFiles === 'function') loadPendingIndexingFiles(); // Charger les documents à indexer
                break;
            case 'search':
                if (typeof loadArchiversForSearch === 'function') loadArchiversForSearch(); // Charger les archiveurs pour le filtre
                if (typeof loadGroupsForSearch === 'function') loadGroupsForSearch(); // Charger les groupes pour le filtre de recherche
                break;
            case 'admin':
                // Les sous-sections admin sont chargées via leurs propres boutons
                break;
        }
    }

    // Gestion des modales (pour ajouter/modifier utilisateur, etc.)
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    document.querySelectorAll('.close-button').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
});

// Fonction pour afficher un message flash via JS (pour les réponses AJAX)
function showFlashMessage(message, type = 'success') {
    const flashContainer = document.querySelector('.p-6'); // Conteneur des messages flash
    if (!flashContainer) return;

    // Supprimer les messages flash existants pour éviter l'accumulation
    const existingAlerts = flashContainer.querySelectorAll('[role="alert"]');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `bg-${type}-100 border border-${type}-400 text-${type}-700 px-4 py-3 rounded relative mb-4`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        <strong class="font-bold">${type === 'success' ? 'Succès!' : 'Erreur!'}</strong>
        <span class="block sm:inline"> ${message}</span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">
            <svg class="fill-current h-6 w-6 text-${type}-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
        </span>
    `;
    flashContainer.prepend(alertDiv); // Ajouter au début du conteneur
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000); // Masquer après 5 secondes
}

// Fonction générique pour ouvrir une modale
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

// Fonction générique pour fermer une modale
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Fonction pour charger les groupes pour l'assignation de documents (upload/scan)
async function loadGroupsForDocumentAssignment(selectId = 'document_group') {
    const groupSelect = document.getElementById(selectId);
    if (!groupSelect) {
        console.warn(`Select element with ID '${selectId}' not found.`);
        return;
    }

    groupSelect.innerHTML = '<option value="">Chargement des groupes...</option>';
    const response = await fetchData('api/get_groups.php'); // Réutiliser l'API des groupes
    console.log("Response from get_groups.php for select " + selectId + ":", response);

    if (response.success) {
        groupSelect.innerHTML = '<option value="">Sélectionner un groupe (optionnel)</option>';
        if (response.groups.length > 0) {
            response.groups.forEach(group => {
                const option = document.createElement('option');
                option.value = group.id;
                option.textContent = group.name;
                groupSelect.appendChild(option);
            });
            console.log("Groups loaded successfully for select " + selectId + ".");
        } else {
            groupSelect.innerHTML = '<option value="">Aucun groupe disponible</option>';
            console.log("No groups available for select " + selectId + ".");
        }
    } else {
        console.error("Failed to load groups for document assignment:", response.message);
        groupSelect.innerHTML = '<option value="">Erreur de chargement des groupes</option>';
    }
}
