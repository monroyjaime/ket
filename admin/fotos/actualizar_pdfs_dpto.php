<?php
// admin/fotos/actualizar_pdfs_dpto.php
session_start();
header('Content-Type: application/json');

$isAdmin = $_SESSION['usr_admin'] ?? 0;
$role = $_SESSION['role'] ?? -1;

if ($role != 1 || $isAdmin != 1) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$dpto_id = $input['dpto_id'] ?? 0;

if ($dpto_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Departamento no especificado']);
    exit;
}

// Ejecutar generación de PDFs
$script_path = '/home/jaime/catalogo_ket/generar_catalogo_3x7.py';
$python_path = '/home/jaime/catalogo_ket/venv/bin/python3';
$env_cmd = "PLAYWRIGHT_BROWSERS_PATH=/opt/playwright-cache TMPDIR=/home/jaime/catalogo_ket/tmp";

$tipos = ['sin', 'minorista', 'mayorista'];
$exitoso = true;

foreach ($tipos as $tipo) {
    $comando = "$env_cmd $python_path $script_path --dptos $dpto_id --calidad web --tipo_precio $tipo 2>&1";
    exec($comando, $output, $return_code);
    if ($return_code !== 0) {
        $exitoso = false;
    }
}

echo json_encode([
    'success' => $exitoso,
    'dpto_id' => $dpto_id,
    'message' => $exitoso ? 'PDFs actualizados correctamente' : 'Error en actualización'
]);
?>