<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../../php/dbcat_async.php");

header('Content-Type: application/json');

// Obtener parámetros
$presupuesto_num = isset($_GET['presupuesto_num']) ? intval($_GET['presupuesto_num']) : 0;
$presupuesto_id = isset($_GET['presupuesto_id']) ? intval($_GET['presupuesto_id']) : 0;
$calidad = isset($_GET['calidad']) ? $_GET['calidad'] : 'web';
$mostrarPrecio = isset($_GET['mostrar_precio']) ? intval($_GET['mostrar_precio']) : 0;
$async = isset($_GET['async']) ? $_GET['async'] : 0;

// Crear instancia de DB
$db = new DBAsync();

// Si recibimos presupuesto_id (idx), convertirlo a presupuesto_num
if ($presupuesto_num == 0 && $presupuesto_id > 0) {
    $result = $db->consultaSegura("SELECT presupuesto_num FROM presupuesto_gen WHERE idx = $1", [$presupuesto_id]);
    if (!empty($result)) {
        $presupuesto_num = $result[0]->presupuesto_num;
    }
}

// Validar que tenemos un número de presupuesto
if ($presupuesto_num == 0) {
    echo json_encode(['success' => false, 'error' => 'No se especificó presupuesto (presupuesto_num o presupuesto_id)']);
    exit;
}

// Validar calidad
if (!in_array($calidad, ['web', 'impresion'])) {
    $calidad = 'web';
}

// Verificar que el presupuesto existe (usando presupuesto_num, NO idx)
$result = $db->consultaSegura("SELECT presupuesto_num FROM presupuesto_gen WHERE presupuesto_num = $1", [$presupuesto_num]);
if (empty($result)) {
    echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado: ' . $presupuesto_num]);
    exit;
}

// Ruta del PDF
$pdf_path = "/var/www/html/pdfs/presupuestos/presupuesto_{$presupuesto_num}.pdf";
$pdf_url = "/pdfs/presupuestos/presupuesto_{$presupuesto_num}.pdf";

// Eliminar PDF existente si existe
if (file_exists($pdf_path)) {
    unlink($pdf_path);
}

// Modo asíncrono
if ($async == 1) {
    $status_file = "/tmp/presupuesto_{$presupuesto_num}_status.json";
    file_put_contents($status_file, json_encode([
        'status' => 'processing', 
        'started_at' => time(),
        'pdf_url' => $pdf_url
    ]));
    
    $script_path = '/home/jaime/catalogo_ket/generar_presupuesto_pdf.py';
    $python_path = '/home/jaime/catalogo_ket/venv/bin/python3';
    $comando = "$python_path $script_path --presupuesto $presupuesto_num --calidad $calidad --mostrar_precio $mostrarPrecio > /tmp/presupuesto_{$presupuesto_num}.log 2>&1 &";
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

if ($return_code === 0 && file_exists($pdf_path) && filesize($pdf_path) > 0) {
    // 🔴 REDIRIGIR AL PDF EN LUGAR DE DEVOLVER JSON
    header('Location: ' . $pdf_url);
    exit;
} else {
    // Si hay error, mostrar mensaje
    echo "Error generando PDF:<br>";
    echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
}