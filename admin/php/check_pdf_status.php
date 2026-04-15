<?php
// /var/www/html/admin/php/check_pdf_status.php
header('Content-Type: application/json');

$presupuesto_id = isset($_GET['presupuesto_id']) ? intval($_GET['presupuesto_id']) : 0;

if ($presupuesto_id == 0) {
    echo json_encode(['success' => false, 'error' => 'No se especificó presupuesto']);
    exit;
}

require_once("../../php/dbcat_async.php");
$db = new DBAsync();
$result = $db->consultaSegura("SELECT presupuesto_num FROM presupuesto_gen WHERE idx = $1", [$presupuesto_id]);

if (empty($result)) {
    echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado']);
    exit;
}

$num_valery = $result[0]->presupuesto_num ;
$pdf_path = "/var/www/html/pdfs/presupuestos/presupuesto_{$presupuesto_num}.pdf";
$pdf_url = "/pdfs/presupuestos/presupuesto_{$presupuesto_num}.pdf";

// Verificar si el PDF existe y tiene tamaño > 0
if (file_exists($pdf_path) && filesize($pdf_path) > 0) {
    echo json_encode([
        'success' => true,
        'ready' => true,
        'pdf_url' => $pdf_url
    ]);
} else {
    // Verificar estado del proceso
    $status_file = "/tmp/presupuesto_{$presupuesto_id}_status.json";
    $status = ['status' => 'pending'];
    if (file_exists($status_file)) {
        $status = json_decode(file_get_contents($status_file), true);
    }
    
    echo json_encode([
        'success' => true,
        'ready' => false,
        'status' => $status['status'] ?? 'pending'
    ]);
}