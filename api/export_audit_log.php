<?php
// cit_sae/api/export_audit_log.php
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';
require_once '../vendor/autoload.php'; // Inclure l'autoloader de Composer

use Dompdf\Dompdf;
use Dompdf\Options;

// Vérification renforcée des permissions
if (!is_logged_in() || !has_permission('view_audit_log')) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

try {
    // Récupérer les logs d'audit
    $stmt = $pdo->prepare("
        SELECT al.timestamp, u.username, al.action, al.details, al.ip_address
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.timestamp DESC
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $format = $_GET['format'] ?? 'csv'; // Récupérer le format demandé

    if ($format === 'pdf') {
        // Configuration de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        // Contenu HTML pour le PDF
        $html = '<h1>Journal d\'Audit CIT SAE</h1>';
        $html .= '<table border="1" cellspacing="0" cellpadding="5" style="width:100%; border-collapse: collapse;">';
        $html .= '<thead><tr>';
        $html .= '<th style="background-color:#f2f2f2;">Date/Heure</th>';
        $html .= '<th style="background-color:#f2f2f2;">Utilisateur</th>';
        $html .= '<th style="background-color:#f2f2f2;">Action</th>';
        $html .= '<th style="background-color:#f2f2f2;">Détails</th>';
        $html .= '<th style="background-color:#f2f2f2;">Adresse IP</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($logs as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['timestamp']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['username'] ?? 'System') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['action']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['details']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['ip_address']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Format paysage pour plus de colonnes
        $dompdf->render();

        // Nettoyer le buffer de sortie avant d'envoyer le PDF
        while (ob_get_level() > 0) ob_end_clean();

        $dompdf->stream('journal_audit_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
        exit();

    } else { // Format CSV par défaut
        // Nettoyer le buffer de sortie
        while (ob_get_level() > 0) ob_end_clean();

        // Définir les headers CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="journal_audit_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // En-têtes CSV
        fputcsv($output, [
            'Date/Heure',
            'Utilisateur',
            'Action',
            'Détails',
            'Adresse IP'
        ], ';');

        foreach ($logs as $row) {
            fputcsv($output, [
                htmlspecialchars($row['timestamp']),
                htmlspecialchars($row['username'] ?? 'System'),
                htmlspecialchars($row['action']),
                htmlspecialchars($row['details']),
                htmlspecialchars($row['ip_address'])
            ], ';');
        }

        fclose($output);
        exit();
    }

} catch (Exception $e) {
    error_log("Export audit log error: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    echo "Erreur lors de l'export: " . htmlspecialchars($e->getMessage());
    exit();
}
