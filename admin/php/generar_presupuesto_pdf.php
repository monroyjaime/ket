<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Endpoint para generar PDF de presupuesto
session_start();
require_once("../../php/dbcat_async.php");

header('Content-Type: application/json');

// Obtener parámetros
$presupuesto_num = isset($_GET['presupuesto_num']) ? intval($_GET['presupuesto_num']) : 0;
$presupuesto_id = isset($_GET['presupuesto_id']) ? intval($_GET['presupuesto_id']) : 0;
$calidad = isset($_GET['calidad']) ? $_GET['calidad'] : 'web';
$mostrarPrecio = isset($_GET['mostrar_precio']) ? intval($_GET['mostrar_precio']) : 0;
$async = isset($_GET['async']) ? $_GET['async'] : 0;

error_log("=== generar_presupuesto_pdf.php DEBUG ===");
error_log("presupuesto_num: " . $presupuesto_num);
error_log("presupuesto_id: " . $presupuesto_id);


// Si recibimos presupuesto_id (idx), convertirlo a presupuesto_num
if ($presupuesto_num == 0 && $presupuesto_id > 0) {
    $db = new DBAsync();
    // Buscar por idx, no por presupuesto_num
    $result = $db->consultaSegura("SELECT presupuesto_num FROM presupuesto_gen WHERE idx = $1", [$presupuesto_id]);
    error_log("Resultado consulta: " . print_r($result, true));
    
    if (!empty($result)) {
        error_log("Presupuesto NO encontrado para número: " . $presupuesto_num);
        $presupuesto_num = $result[0]->presupuesto_num;
    }
}

// Validar que tenemos un número de presupuesto..
if ($presupuesto_num == 0) {
    echo json_encode(['success' => false, 'error' => 'No se especificó presupuesto (presupuesto_num o presupuesto_id)']);
    exit;
}

// Validar calidad
if (!in_array($calidad, ['web', 'impresion'])) {
    $calidad = 'web';
}

// Validar ID
if ($presupuesto_num == 0) {
    echo json_encode(['success' => false, 'error' => 'No se especificó presupuesto']);
    exit;
}

// Verificar que el presupuesto existe y obtener num_valery
$db = new DBAsync();
$result = $db->consultaSegura("SELECT presupuesto_num  FROM presupuesto_gen WHERE idx = $1", [$presupuesto_id]);

if (empty($result)) {
    echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado']);
    exit;
}

$num_valery = $result[0]->presupuesto_num ;

// Ruta del PDF
$pdf_path = "/var/www/html/pdfs/presupuestos/presupuesto_{$presupuesto_num}.pdf";
$pdf_url = "/pdfs/presupuestos/presupuesto_{$presupuesto_num}.pdf";

// 🔴 ELIMINAR PDF EXISTENTE SIEMPRE (para forzar regeneración)
if (file_exists($pdf_path)) {
    unlink($pdf_path);
}

// Modo asíncrono: iniciar proceso en segundo plano
if ($async == 1) {
    // Crear archivo de estado (timestamp para saber que es nueva generación)
    $status_file = "/tmp/presupuesto_{$presupuesto_id}_status.json";
    file_put_contents($status_file, json_encode([
        'status' => 'processing', 
        'started_at' => time(),
        'pdf_url' => $pdf_url
    ]));
    
    // Ejecutar en segundo plano
    $script_path = '/home/jaime/catalogo_ket/generar_presupuesto_pdf.py';
    $python_path = '/home/jaime/catalogo_ket/venv/bin/python3';
    $comando = "$python_path $script_path --presupuesto $presupuesto_id --calidad $calidad --mostrar_precio $mostrarPrecio > /tmp/presupuesto_{$presupuesto_id}.log 2>&1 &";
    exec($comando);
    
    echo json_encode([
        'success' => true,
        'processing' => true,
        'pdf_url' => $pdf_url,
        'message' => 'Generando PDF en segundo plano'
    ]);
    exit;
}

// Modo síncrono (con timeout aumentado)
set_time_limit(300);
$script_path = '/home/jaime/catalogo_ket/generar_presupuesto_pdf.py';
$python_path = '/home/jaime/catalogo_ket/venv/bin/python3';
$comando = "$python_path $script_path --presupuesto $presupuesto_num --calidad $calidad --mostrar_precio $mostrarPrecio 2>&1";
$output = [];
$return_code = 0;
exec($comando, $output, $return_code);

if ($return_code === 0 && file_exists($pdf_path)) {
    echo json_encode([
        'success' => true,
        'pdf_url' => $pdf_url,
        'output' => implode("\n", $output)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => implode("\n", $output),
        'return_code' => $return_code
    ]);
}