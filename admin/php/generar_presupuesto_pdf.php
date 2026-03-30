<?php
// Endpoint para generar PDF de presupuesto
session_start();
require_once("../../php/dbcat_async.php");

header('Content-Type: application/json');

// Obtener parámetros
$presupuesto_id = isset($_GET['presupuesto_id']) ? intval($_GET['presupuesto_id']) : 0;
$calidad = isset($_GET['calidad']) ? $_GET['calidad'] : 'web';
$mostrarPrecio = isset($_GET['mostrar_precio']) ? intval($_GET['mostrar_precio']) : 0;
$async = isset($_GET['async']) ? $_GET['async'] : 0; // Nuevo: modo asíncrono

// Validar calidad
if (!in_array($calidad, ['web', 'impresion'])) {
    $calidad = 'web';
}

// Validar ID
if ($presupuesto_id == 0) {
    echo json_encode(['success' => false, 'error' => 'No se especificó presupuesto']);
    exit;
}

// Verificar que el presupuesto existe y obtener num_valery
$db = new DBAsync();
$result = $db->consultaSegura("SELECT num_valery FROM presupuesto_gen WHERE idx = $1", [$presupuesto_id]);

if (empty($result)) {
    echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado']);
    exit;
}

$num_valery = $result[0]->num_valery;

// Ruta del PDF existente
$pdf_path = "/var/www/html/pdfs/presupuestos/presupuesto_{$num_valery}.pdf";
$pdf_url = "/pdfs/presupuestos/presupuesto_{$num_valery}.pdf";

// Si el PDF ya existe, devolver la ruta inmediatamente
if (file_exists($pdf_path)) {
    echo json_encode([
        'success' => true,
        'pdf_url' => $pdf_url,
        'cached' => true
    ]);
    exit;
}

// Modo asíncrono: iniciar proceso en segundo plano y devolver estado
if ($async == 1) {
    // Crear archivo de estado
    $status_file = "/tmp/presupuesto_{$presupuesto_id}_status.json";
    file_put_contents($status_file, json_encode(['status' => 'processing', 'started_at' => time()]));
    
    // Ejecutar en segundo plano (sin esperar resultado)
    $script_path = '/home/jaime/catalogo_ket/generar_presupuesto_pdf.py';
    $python_path = '/home/jaime/catalogo_ket/venv/bin/python3';
    $comando = "$python_path $script_path --presupuesto $presupuesto_id --calidad $calidad --mostrar_precio $mostrarPrecio > /tmp/presupuesto_{$presupuesto_id}.log 2>&1 &";
    exec($comando);
    
    echo json_encode([
        'success' => true,
        'processing' => true,
        'pdf_url' => $pdf_url,
        'status_url' => "/admin/php/check_pdf_status.php?presupuesto_id={$presupuesto_id}"
    ]);
    exit;
}

// Modo síncrono (original) - con timeout aumentado
set_time_limit(300); // 5 minutos
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