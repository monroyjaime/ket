<?php
// API para generar un PDF de un departamento especifico
header('Content-Type: application/json');

// Verificar que se recibio el ID
if (!isset($_POST['dpto_id'])) {
    echo json_encode(['success' => false, 'error' => 'No se especifico departamento']);
    exit;
}

$dpto_id = intval($_POST['dpto_id']);
$calidad = isset($_POST['calidad']) ? $_POST['calidad'] : 'web';

// Validar calidad
if (!in_array($calidad, ['web', 'impresion'])) {
    $calidad = 'web';
}

// Ruta del script Python
$script_path = '/home/jaime/catalogo_ket/generar_catalogo_3x7.py';

// Comando a ejecutar
$comando = "python3 $script_path --dptos $dpto_id --calidad $calidad 2>&1";

// Ejecutar el comando
$output = [];
$return_code = 0;
exec($comando, $output, $return_code);

// Verificar resultado
if ($return_code === 0) {
    // Buscar el tamano del archivo generado
    $ruta_pdf_auto = "/var/www/html/pdfs/catalogo_automotriz/catalogo_dptos_{$dpto_id}.pdf";
    $ruta_pdf_ferre = "/var/www/html/pdfs/catalogo_ferretero/catalogo_dptos_{$dpto_id}.pdf";
    
    $tamano = 0;
    if (file_exists($ruta_pdf_auto)) {
        $tamano = round(filesize($ruta_pdf_auto) / 1024, 1);
    } elseif (file_exists($ruta_pdf_ferre)) {
        $tamano = round(filesize($ruta_pdf_ferre) / 1024, 1);
    }
    
    echo json_encode([
        'success' => true,
        'output' => implode("\n", $output),
        'tamano' => $tamano
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => implode("\n", $output),
        'return_code' => $return_code
    ]);
}