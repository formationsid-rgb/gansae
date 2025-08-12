// assets/js/indexing.js
document.addEventListener('DOMContentLoaded', () => {
    const ocrContainer = document.getElementById('ocr-container');
    const ocrImage = document.getElementById('ocr-image');
    const selectAreaBtn = document.getElementById('select-area-btn');
    const extractTextBtn = document.getElementById('extract-text-btn');
    const extractedTextarea = document.getElementById('extracted-text');
    const indexingForm = document.getElementById('indexing-form');
    const ocrStatus = document.getElementById('ocr-status');
    const documentIdIndexing = document.getElementById('document-id-indexing');
    const indexingDocumentSelect = document.getElementById('indexing-document-select');
    const docTypeIndexing = document.getElementById('doc-type-indexing');
    const referenceIndexing = document.getElementById('reference-indexing');
    const dateIndexing = document.getElementById('date-indexing');


    let isSelecting = false;
    let startX, startY;
    let currentSelection = null; // Pour stocker l'élément de sélection

    if (!ocrContainer || !ocrImage || !selectAreaBtn || !extractTextBtn || !extractedTextarea || !indexingForm || !ocrStatus || !documentIdIndexing || !indexingDocumentSelect) {
        console.warn("Indexing section elements not found. Skipping indexing functionality setup.");
        return;
    }

    // Fonction pour charger les documents en attente d'indexation
    async function loadPendingIndexingFiles() {
        console.log("Loading pending indexing files...");
        indexingDocumentSelect.innerHTML = '<option value="">Chargement des documents...</option>';
        ocrImage.src = 'assets/img/ocr_document_example.png'; // Réinitialiser l'image
        documentIdIndexing.value = '';
        indexingForm.reset();
        extractedTextarea.value = '';
        if (currentSelection) {
            currentSelection.remove();
            currentSelection = null;
        }
        ocrStatus.textContent = 'Sélectionnez un document ci-dessus pour commencer l\'indexation.';
        ocrStatus.className = 'mt-4 text-sm text-gray-600';


        const response = await fetchData('api/get_pending_indexing_files.php');
        console.log("Response from get_pending_indexing_files.php:", response);

        if (response.success) {
            indexingDocumentSelect.innerHTML = '<option value="">-- Sélectionner un document --</option>';
            if (response.documents.length > 0) {
                response.documents.forEach(doc => {
                    const option = document.createElement('option');
                    option.value = doc.id;
                    option.textContent = `${doc.original_filename} (Ajouté le ${doc.uploaded_at})`;
                    indexingDocumentSelect.appendChild(option);
                });
                console.log("Pending indexing files loaded successfully.");
            } else {
                indexingDocumentSelect.innerHTML = '<option value="">Aucun document en attente d\'indexation.</option>';
                console.log("No pending indexing documents found.");
            }
        } else {
            indexingDocumentSelect.innerHTML = `<option value="">Erreur: ${response.message}</option>`;
            showFlashMessage(response.message, 'error');
            console.error("Failed to load pending indexing files:", response.message);
        }
    }

    // Gérer la sélection d'un document
    indexingDocumentSelect.addEventListener('change', async () => {
        const documentId = indexingDocumentSelect.value;
        if (documentId) {
            console.log("Fetching details for document ID:", documentId);
            const response = await fetchData(`api/get_document_details.php?id=${documentId}`);
            console.log("Response from get_document_details.php:", response);
            if (response.success) {
                const doc = response.document;
                documentIdIndexing.value = doc.id;
                ocrImage.src = doc.file_url; // Afficher le document
                docTypeIndexing.value = doc.document_type || '';
                referenceIndexing.value = doc.reference || '';
                dateIndexing.value = doc.document_date || '';
                extractedTextarea.value = doc.extracted_text || '';
                ocrStatus.textContent = 'Document chargé. Vous pouvez maintenant extraire le texte.';
                ocrStatus.className = 'mt-4 text-sm text-gray-600';
                if (currentSelection) {
                    currentSelection.remove();
                    currentSelection = null;
                }
                console.log("Document details loaded successfully.");
            } else {
                showFlashMessage(response.message, 'error');
                ocrStatus.textContent = 'Erreur lors du chargement du document.';
                ocrStatus.className = 'mt-4 text-sm text-red-600';
                console.error("Failed to load document details:", response.message);
            }
        } else {
            // Réinitialiser si aucun document n'est sélectionné
            documentIdIndexing.value = '';
            ocrImage.src = 'assets/img/ocr_document_example.png';
            indexingForm.reset();
            extractedTextarea.value = '';
            if (currentSelection) {
                currentSelection.remove();
                currentSelection = null;
            }
            ocrStatus.textContent = 'Sélectionnez un document ci-dessus pour commencer l\'indexation.';
            ocrStatus.className = 'mt-4 text-sm text-gray-600';
            console.log("Document selection cleared.");
        }
    });


    selectAreaBtn.addEventListener('click', () => {
        if (!documentIdIndexing.value) {
            ocrStatus.textContent = "Veuillez d'abord sélectionner un document à indexer.";
            ocrStatus.className = 'mt-4 text-sm text-red-600';
            return;
        }
        isSelecting = true;
        ocrContainer.style.cursor = 'crosshair';
        if (currentSelection) {
            currentSelection.remove(); // Supprimer l'ancienne sélection
            currentSelection = null;
        }
        ocrStatus.textContent = 'Cliquez et glissez pour sélectionner une zone.';
        ocrStatus.className = 'mt-4 text-sm text-blue-600';
    });

    ocrContainer.addEventListener('mousedown', (e) => {
        if (!isSelecting) return;

        // Clear any existing selections
        if (currentSelection) {
            currentSelection.remove();
        }

        const rect = ocrContainer.getBoundingClientRect();
        startX = e.clientX - rect.left;
        startY = e.clientY - rect.top;

        const selection = document.createElement('div');
        selection.className = 'ocr-selection';
        selection.style.left = startX + 'px';
        selection.style.top = startY + 'px';
        ocrContainer.appendChild(selection);
        currentSelection = selection;
    });

    ocrContainer.addEventListener('mousemove', (e) => {
        if (!isSelecting || !currentSelection) return;

        const rect = ocrContainer.getBoundingClientRect();
        const currentX = e.clientX - rect.left;
        const currentY = e.clientY - rect.top;

        const width = Math.abs(currentX - startX);
        const height = Math.abs(currentY - startY);

        currentSelection.style.width = width + 'px';
        currentSelection.style.height = height + 'px';

        if (currentX < startX) {
            currentSelection.style.left = currentX + 'px';
        }
        if (currentY < startY) {
            currentSelection.style.top = currentY + 'px';
        }
    });

    ocrContainer.addEventListener('mouseup', () => {
        isSelecting = false;
        ocrContainer.style.cursor = 'default';
        if (currentSelection) {
            ocrStatus.textContent = 'Zone sélectionnée. Cliquez sur "Extraire le texte".';
            ocrStatus.className = 'mt-4 text-sm text-green-600';
        }
    });

    extractTextBtn.addEventListener('click', async () => {
        if (!documentIdIndexing.value) {
            ocrStatus.textContent = "Veuillez d'abord sélectionner un document à indexer.";
            ocrStatus.className = 'mt-4 text-sm text-red-600';
            return;
        }
        if (!currentSelection) {
            ocrStatus.textContent = "Veuillez d'abord sélectionner une zone du document.";
            ocrStatus.className = 'mt-4 text-sm text-red-600';
            return;
        }

        ocrStatus.textContent = 'Extraction du texte en cours...';
        ocrStatus.className = 'mt-4 text-sm text-yellow-600';
        extractTextBtn.disabled = true;

        // Note: Pour une extraction OCR de zone réelle, vous devriez envoyer les coordonnées
        // au serveur et le serveur devrait recadrer l'image avant de la passer à Tesseract.
        // Pour cette démo, nous envoyons juste l'ID du document et Tesseract traitera l'image entière.
        // La sélection de zone est principalement visuelle ici.

        const formData = new FormData();
        formData.append('document_id', documentIdIndexing.value);
        // formData.append('selection_coords', JSON.stringify(selectionCoords)); // Si vous implémentez l'OCR de zone côté serveur

        const response = await fetchData('api/extract_ocr_text.php', 'POST', formData);

        if (response.success) {
            extractedTextarea.value = response.extracted_text;
            ocrStatus.textContent = 'Texte extrait avec succès.';
            ocrStatus.className = 'mt-4 text-sm text-green-600';
            showFlashMessage('Texte OCR extrait.', 'success');
        } else {
            extractedTextarea.value = '';
            ocrStatus.textContent = response.message;
            ocrStatus.className = 'mt-4 text-sm text-red-600';
            showFlashMessage(response.message, 'error');
        }
        extractTextBtn.disabled = false;
    });

    // Handle indexing form submission
    indexingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!documentIdIndexing.value) {
            ocrStatus.textContent = "Veuillez d'abord sélectionner un document à indexer.";
            ocrStatus.className = 'mt-4 text-sm text-red-600';
            return;
        }

        ocrStatus.textContent = 'Enregistrement de l\'indexation...';
        ocrStatus.className = 'mt-4 text-sm text-yellow-600';

        const formData = new FormData(indexingForm);

        const response = await fetchData('api/save_indexing.php', 'POST', formData);

        if (response.success) {
            ocrStatus.textContent = response.message;
            ocrStatus.className = 'mt-4 text-sm text-green-600';
            indexingForm.reset(); // Clear the form
            extractedTextarea.value = ''; // Clear extracted text
            if (currentSelection) {
                currentSelection.remove();
                currentSelection = null;
            }
            ocrImage.src = 'assets/img/ocr_document_example.png'; // Réinitialiser l'image
            showFlashMessage(response.message, 'success');
            // Recharger les données du tableau de bord et les fichiers à indexer
            if (typeof loadDashboardData === 'function') loadDashboardData();
            loadPendingIndexingFiles(); // Recharger la liste des documents à indexer
            if (typeof loadRecentFiles === 'function') loadRecentFiles(); // Pour mettre à jour le statut dans la liste récente
        } else {
            ocrStatus.textContent = response.message;
            ocrStatus.className = 'mt-4 text-sm text-red-600';
            showFlashMessage(response.message, 'error');
        }
    });

    // Initial load when section is shown (handled by main.js loadSectionData)
    // loadPendingIndexingFiles(); // Cette ligne sera appelée par main.js
});
