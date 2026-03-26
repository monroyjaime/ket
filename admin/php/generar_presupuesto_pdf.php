<?php
// Endpoint para generar PDF de presupuesto
session_start();
require_once("../../php/dbcat_async.php");

header('Content-Type: application/json');

// Verificar que se recibió el ID
if (!isset($_GET['presupuesto_id'])) {
    echo json_encode(['success' => false, 'error' => 'No se especificó presupuesto']);
    exit;
}

$mostrarPrecio = isset($_GET['mostrar_precio']) ? intval($_GET['mostrar_precio']) : 0;

error_log("=== generar_presupuesto_pdf.php ===");
error_log("presupuesto_id: " . $presupuesto_id);
error_log("calidad: " . $calidad);
error_log("mostrar_precio: " . $mostrarPrecio);

$presupuesto_id = intval($_GET['presupuesto_id']);
$calidad = isset($_GET['calidad']) ? $_GET['calidad'] : 'web';

// Validar calidad
if (!in_array($calidad, ['web', 'impresion'])) {
    $calidad = 'web';
}

// Verificar que el presupuesto existe y obtener num_valery
$db = new DBAsync();
$result = $db->consultaSegura("SELECT num_valery FROM presupuesto_gen WHERE idx = $1", [$presupuesto_id]);

if (empty($result)) {
    echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado']);
    exit;
}

$num_valery = $result[0]->num_valery;

// Ruta del PDF existente - usar num_valery
$pdf_path = "/var/www/html/pdfs/presupuestos/presupuesto_{$num_valery}.pdf";
$pdf_url = "/pdfs/presupuestos/presupuesto_{$num_valery}.pdf";

// Si el PDF ya existe y no se fuerza regeneración, devolver la ruta
$forzar = isset($_GET['forzar']) && $_GET['forzar'] == '1';
if (file_exists($pdf_path) && !$forzar) {
    echo json_encode([
        'success' => true,
        'pdf_url' => $pdf_url,
        'cached' => true
    ]);
    exit;
}

// Generar nuevo PDF
$script_path = '/home/jaime/catalogo_ket/generar_presupuesto_pdf.py';
$python_path = '/home/jaime/catalogo_ket/venv/bin/python3';

$comando = "$python_path $script_path --presupuesto $presupuesto_id --calidad $calidad --mostrar_precio $mostrarPrecio 2>&1";

$output = [];
$return_code = 0;
exec($comando, $output, $return_code);

if ($return_code === 0) {
    echo json_encode([
        'success' => true,
        'pdf_url' => $pdf_url,
        'cached' => false,
        'output' => implode("\n", $output)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => implode("\n", $output),
        'return_code' => $return_code
    ]);
}