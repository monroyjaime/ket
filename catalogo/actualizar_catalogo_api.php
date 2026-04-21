<?php
// API para generar PDFs de un departamento especifico (tres versiones: sin precio, minorista, mayorista)
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
$python_path = '/home/jaime/catalogo_ket/venv/bin/python3';

// Variables de entorno
$env_cmd = "PLAYWRIGHT_BROWSERS_PATH=/opt/playwright-cache TMPDIR=/home/jaime/catalogo_ket/tmp";

// Los tres tipos de precio a generar
$tipos_precio = [
    'sin' => 'sin',
    'minorista' => 'minorista',
    'mayorista' => 'mayorista'
];

$resultados = [];
$todos_exitosos = true;

foreach ($tipos_precio as $tipo => $tipo_param) {
    // Comando para este tipo de precio
    $comando = "$env_cmd $python_path $script_path --dptos $dpto_id --calidad $calidad --tipo_precio $tipo_param 2>&1";
    
    $output = [];
    $return_code = 0;
    exec($comando, $output, $return_code);
    
    $resultados[$tipo] = [
        'success' => ($return_code === 0),
        'output' => implode("\n", $output),
        'return_code' => $return_code
    ];
    
    if ($return_code !== 0) {
        $todos_exitosos = false;
    }
}

// Buscar el tamaño de un archivo generado (para mostrar en el log)
$tamano = 0;
$rutas = [
    "/var/www/html/pdfs/catalogo_automotriz/catalogo_dptos_{$dpto_id}.pdf",
    "/var/www/html/pdfs/catalogo_ferretero/catalogo_dptos_{$dpto_id}.pdf",
    "/var/www/html/pdfs/catalogo_automotriz/conPrecio/catalogo_dptos_{$dpto_id}.pdf",
    "/var/www/html/pdfs/catalogo_ferretero/conPrecio/catalogo_dptos_{$dpto_id}.pdf",
    "/var/www/html/pdfs/catalogo_automotriz/conPrecioMayor/catalogo_dptos_{$dpto_id}.pdf",
    "/var/www/html/pdfs/catalogo_ferretero/conPrecioMayor/catalogo_dptos_{$dpto_id}.pdf"
];

foreach ($rutas as $ruta) {
    if (file_exists($ruta)) {
        $tamano = round(filesize($ruta) / 1024, 1);
        break;
    }
}

echo json_encode([
    'success' => $todos_exitosos,
    'resultados' => $resultados,
    'tamano' => $tamano,
    'tipos_generados' => array_keys(array_filter($resultados, function($r) { return $r['success']; }))
]);
?>