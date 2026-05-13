<?php
// API para generar PDFs - Soporte para departamentos y líneas completas
header('Content-Type: application/json');

// ============================================
// 1. VERIFICAR SOLICITUDES DE LÍNEA COMPLETA (primero)
// ============================================

// Verificar si es solicitud de estado (check_status)
if (isset($_POST['check_status']) && isset($_POST['linea'])) {
    $linea = $_POST['linea'];
    $tipo_precio = $_POST['tipo_precio'] ?? 'sin';
    $status_file = "/tmp/linea_{$linea}_{$tipo_precio}.status";
    if (file_exists($status_file)) {
        $status = json_decode(file_get_contents($status_file), true);
        echo json_encode($status);
    } else {
        echo json_encode(['status' => 'unknown']);
    }
    exit;
}

// Verificar si es solicitud de línea completa
if (isset($_POST['linea'])) {
    $linea = $_POST['linea'];
    $tipo_precio = $_POST['tipo_precio'] ?? 'sin';
    $calidad = $_POST['calidad'] ?? 'impresion';
    $async = isset($_POST['async']) ? intval($_POST['async']) : 0;
    
    $script_path = '/home/jaime/catalogo_ket/generar_catalogo_3x7.py';
    $python_path = '/home/jaime/catalogo_ket/venv/bin/python3';
    $env_cmd = "PLAYWRIGHT_BROWSERS_PATH=/opt/playwright-cache TMPDIR=/home/jaime/catalogo_ket/tmp";
    
    // Determinar carpeta de salida según línea y tipo
    $carpeta = ($linea == 'A') ? 'catalogo_automotriz' : 'catalogo_ferretero';
    if ($tipo_precio == 'minorista') {
        $subcarpeta = 'conPrecio';
        $nombre_archivo = "catalogo_linea_{$linea}_minor.pdf";
    } elseif ($tipo_precio == 'mayorista') {
        $subcarpeta = 'conPrecioMayor';
        $nombre_archivo = "catalogo_linea_{$linea}_mayor.pdf";
    } else {
        $subcarpeta = 'print';
        $nombre_archivo = "catalogo_linea_{$linea}.pdf";
    }
    
    $ruta_pdf = "/var/www/html/pdfs/{$carpeta}/{$subcarpeta}/{$nombre_archivo}";
    $url_pdf = "/pdfs/{$carpeta}/{$subcarpeta}/{$nombre_archivo}";
    
    // Crear directorio si no existe
    $dir_pdf = dirname($ruta_pdf);
    if (!file_exists($dir_pdf)) {
        mkdir($dir_pdf, 0775, true);
    }
    
    // Modo asíncrono
    if ($async == 1) {
        // Eliminar PDF existente si existe (forzar regeneración)
        if (file_exists($ruta_pdf)) {
            unlink($ruta_pdf);
            error_log("PDF existente eliminado: " . $ruta_pdf);
        }
        // Crear archivo de estado inicial
        $status_file = "/tmp/linea_{$linea}_{$tipo_precio}.status";
        file_put_contents($status_file, json_encode([
            'status' => 'processing',
            'progreso' => 0,
            'mensaje' => 'Iniciando proceso...',
            'started_at' => time()
        ]));
        
        // Ejecutar en segundo plano
        $comando = "$env_cmd $python_path $script_path --linea $linea --calidad $calidad --tipo_precio $tipo_precio > /tmp/linea_{$linea}_{$tipo_precio}.log 2>&1 &";
        exec($comando);
        
        echo json_encode([
            'success' => true,
            'processing' => true,
            'pdf_url' => $url_pdf,
            'message' => 'Generación iniciada en segundo plano'
        ]);
        exit;
    }
    
    // Modo síncrono (para pruebas)
    $comando = "$env_cmd $python_path $script_path --linea $linea --calidad $calidad --tipo_precio $tipo_precio 2>&1";
    exec($comando, $output, $return_code);
    
    if ($return_code === 0 && file_exists($ruta_pdf)) {
        echo json_encode(['success' => true, 'pdf_url' => $url_pdf]);
    } else {
        echo json_encode(['success' => false, 'error' => implode("\n", $output)]);
    }
    exit;
}

// ============================================
// 2. VERIFICAR SOLICITUDES DE DEPARTAMENTO
// ============================================

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

// Buscar el tamaño de un archivo generado
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