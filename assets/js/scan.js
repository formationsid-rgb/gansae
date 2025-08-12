// assets/js/scan.js
document.addEventListener('DOMContentLoaded', () => {
    const scanBtn = document.getElementById('scan-btn');
    const scanStatus = document.getElementById('scan-status');
    const scannerSelect = document.getElementById('scanner-source'); // Ajoutez un <select> pour les scanners
    const configureScanOptionsBtn = document.querySelector('.configure-scan-options-btn');
    const scanResolutionSelect = document.getElementById('scan-resolution');
    const scanOutputFormatSelect = document.getElementById('scan-output-format');
    const scanQualitySelect = document.getElementById('scan-quality');
    const scanDocumentGroupSelect = document.getElementById('scan_document_group');


    let DWObject; // Variable pour l'objet Dynamsoft Web TWAIN

    // Initialiser le contrôle TWAIN
    function initDWT() {
        // Vérifiez si Dynamsoft est disponible
        if (typeof Dynamsoft === 'undefined' || typeof Dynamsoft.DWT === 'undefined') {
            scanStatus.textContent = 'Composant de scan non chargé. Veuillez installer le SDK Dynamsoft Web TWAIN.';
            scanStatus.className = 'mt-4 text-sm text-red-600';
            scanBtn.disabled = true;
            return;
        }

        Dynamsoft.DWT.Containers = [{ ContainerId: 'dwt-container', Width: '100%', Height: '400px' }]; // Un conteneur HTML pour l'aperçu
        Dynamsoft.DWT.ProductKey = 'YOUR-PRODUCT-KEY'; // REMPLACEZ PAR VOTRE CLÉ DE PRODUIT DYNAMSOFT
        Dynamsoft.DWT.ResourcesPath = 'assets/dwt-resources'; // Chemin vers les ressources du SDK (à copier manuellement)
        Dynamsoft.DWT.Load(); // Charge le contrôle
        DWObject = Dynamsoft.DWT.Get; // Obtient l'instance du contrôle

        // Événements et gestion des erreurs
        DWObject.RegisterEvent('OnPostTransfer', function() {
            scanStatus.textContent = 'Image transférée. Prêt à téléverser.';
            scanStatus.className = 'mt-4 text-sm text-green-600';
        });
        DWObject.RegisterEvent('OnScanError', function(errorCode, errorString) {
            scanStatus.textContent = `Erreur de scan: ${errorString}`;
            scanStatus.className = 'mt-4 text-sm text-red-600';
            scanBtn.disabled = false;
        });
        DWObject.RegisterEvent('OnRuntimeStarted', function() {
            // Le runtime est démarré, on peut maintenant lister les sources
            DWObject.GetSources(true, function() {
                scannerSelect.innerHTML = '';
                if (DWObject.SourceCount > 0) {
                    for (let i = 0; i < DWObject.SourceCount; i++) {
                        const option = document.createElement('option');
                        option.value = i;
                        option.textContent = DWObject.GetSourceNameByIndex(i);
                        scannerSelect.appendChild(option);
                    }
                    DWObject.SelectSourceByIndex(0); // Sélectionne le premier scanner par défaut
                    scanBtn.disabled = false; // Activer le bouton Scan
                    scanStatus.textContent = 'Prêt à scanner.';
                    scanStatus.className = 'mt-4 text-sm text-gray-600';
                } else {
                    scanStatus.textContent = 'Aucun scanner détecté.';
                    scanStatus.className = 'mt-4 text-sm text-red-600';
                    scanBtn.disabled = true;
                }
            }, function(errorCode, errorString) {
                scanStatus.textContent = `Erreur lors du listage des scanners: ${errorString}`;
                scanStatus.className = 'mt-4 text-sm text-red-600';
                scanBtn.disabled = true;
            });
        });
        DWObject.RegisterEvent('OnRuntimeAbnormalExit', function() {
            scanStatus.textContent = 'Le composant de scan a cessé de fonctionner. Veuillez le redémarrer.';
            scanStatus.className = 'mt-4 text-sm text-red-600';
            scanBtn.disabled = true;
        });
    }

    // Appeler l'initialisation au chargement de la page
    initDWT();

    scanBtn.addEventListener('click', () => {
        if (!DWObject || DWObject.SourceCount === 0) {
            scanStatus.textContent = 'Aucun scanner sélectionné ou disponible.';
            scanStatus.className = 'mt-4 text-sm text-red-600';
            return;
        }

        scanStatus.textContent = 'Numérisation en cours...';
        scanStatus.className = 'mt-4 text-sm text-yellow-600';
        scanBtn.disabled = true;

        const selectedSourceIndex = scannerSelect.value;
        DWObject.SelectSourceByIndex(selectedSourceIndex);

        const resolution = parseInt(scanResolutionSelect.value);
        const outputFormat = scanOutputFormatSelect.value;
        const quality = scanQualitySelect.value; // Non utilisé directement par DWT, mais peut être pour le traitement post-scan
        const documentGroup = scanDocumentGroupSelect.value; // Récupérer le groupe sélectionné

        let imageType;
        switch (outputFormat) {
            case 'PDF': imageType = Dynamsoft.DWT.EnumDWT_ImageType.TIFF; break; // DWT télécharge en TIFF pour PDF multi-pages
            case 'JPEG': imageType = Dynamsoft.DWT.EnumDWT_ImageType.JPG; break;
            case 'TIFF': imageType = Dynamsoft.DWT.EnumDWT_ImageType.TIFF; break;
            default: imageType = Dynamsoft.DWT.EnumDWT_ImageType.TIFF;
        }

        DWObject.AcquireImage(
            {
                IfShowUI: false, // Ne pas afficher l'interface utilisateur du scanner
                PixelType: Dynamsoft.DWT.EnumDWT_PixelType.TWPT_RGB,
                Resolution: resolution,
                IfFeederEnabled: true, // Utiliser le chargeur automatique
                IfDuplexEnabled: false // Pas de recto-verso
            },
            function() { // OnAcquireImageSuccess
                scanStatus.textContent = 'Scan terminé. Téléversement...';
                // Préparer les données supplémentaires pour l'upload
                const extraUploadData = {
                    source: scannerSelect.options[scannerSelect.selectedIndex].text,
                    resolution: resolution,
                    output_format: outputFormat,
                    quality: quality,
                    document_group: documentGroup // Ajouter le groupe
                };

                // Téléverser les images scannées vers le serveur
                DWObject.HttpUploadAll(
                    'api/scan_document.php', // Votre API de téléversement
                    'document_file', // Nom du champ de fichier attendu par votre API (Dynamsoft utilise 'RemoteFile')
                    imageType, // Format d'upload
                    function() { // OnHttpUploadSuccess
                        scanStatus.textContent = 'Document scanné et téléversé avec succès.';
                        scanStatus.className = 'mt-4 text-sm text-green-600';
                        scanBtn.disabled = false;
                        showFlashMessage('Document scanné et téléversé avec succès.', 'success');
                        // Recharger les fichiers récents si nécessaire
                        if (typeof loadRecentFiles === 'function') loadRecentFiles();
                        if (typeof loadDashboardData === 'function') loadDashboardData();
                        if (typeof loadPendingIndexingFiles === 'function') loadPendingIndexingFiles();
                    },
                    function(errorCode, errorString, sHttpResponse) { // OnHttpUploadError
                        scanStatus.textContent = `Erreur de téléversement: ${errorString}`;
                        scanStatus.className = 'mt-4 text-sm text-red-600';
                        scanBtn.disabled = false;
                        showFlashMessage(`Erreur de téléversement: ${errorString}`, 'error');
                    },
                    extraUploadData // Passer les données supplémentaires
                );
            },
            function(errorCode, errorString) { // OnAcquireImageFailure
                scanStatus.textContent = `Échec du scan: ${errorString}`;
                scanStatus.className = 'mt-4 text-sm text-red-600';
                scanBtn.disabled = false;
            }
        );
    });

    // Gestion du bouton "Configurer les options de scan"
    configureScanOptionsBtn.addEventListener('click', () => {
        openModal('scan-options-modal');
    });
});
