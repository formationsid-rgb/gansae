    <?php
    require_once '../includes/functions.php';
    require_once '../includes/db_connect.php';
    require_once '../vendor/autoload.php'; // Inclure l'autoloader de Composer

    use thiagoalmeida\TesseractOCR\TesseractOCR;

    header('Content-Type: application/json');

    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $document_id = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
        // $selection_coords = $_POST['selection_coords'] ?? null; // Pour l'OCR de zone, c'est plus complexe

        if (!$document_id) {
            echo json_encode(['success' => false, 'message' => 'ID de document manquant ou invalide.']);
            exit();
        }

        try {
            // Récupérer le chemin du fichier depuis la base de données
            $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ? AND user_id = ?");
            $stmt->execute([$document_id, $_SESSION['user_id']]);
            $document = $stmt->fetch();

            if (!$document || !file_exists($document['file_path'])) {
                echo json_encode(['success' => false, 'message' => 'Document non trouvé ou fichier manquant.']);
                exit();
            }

            $image_path = $document['file_path'];
            $extracted_text = '';

            // Vérifier si le fichier est un PDF. Tesseract ne traite pas directement les PDF.
            // Pour les PDF, il faut d'abord les convertir en images (JPEG/PNG) page par page.
            // Cela nécessite Ghostscript et Imagick/GD, ce qui est une autre couche de complexité.
            // Pour l'instant, nous allons supposer que Tesseract est appelé sur une image.
            // Si vous avez des PDF, vous devrez ajouter une étape de conversion PDF vers Image.
            // Exemple simple pour une image:
            $file_extension = pathinfo($image_path, PATHINFO_EXTENSION);
            if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'tiff'])) {
                try {
                    $tesseract = new TesseractOCR($image_path);
                    $tesseract->lang('fra', 'eng'); // Spécifiez les langues installées
                    // Si Tesseract n'est pas dans le PATH, vous devrez spécifier son chemin complet:
                    // $tesseract->executable('C:\\Program Files\\Tesseract-OCR\\tesseract.exe');
                    $extracted_text = $tesseract->run();
                    log_audit_action('Extraction OCR réelle', 'Texte extrait pour le document ID ' . $document_id, $_SESSION['user_id']);

                } catch (\Exception $e) {
                    error_log("Erreur Tesseract OCR: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'extraction OCR: ' . $e->getMessage()]);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Type de fichier non supporté pour l\'OCR direct (seules les images sont supportées pour l\'instant).']);
                exit();
            }

            echo json_encode(['success' => true, 'extracted_text' => $extracted_text]);

        } catch (PDOException $e) {
            error_log("Erreur API Extract OCR: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du document pour OCR.']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
    }
    ?>
    