<?php
// test_minimal.php - Con guardado real
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$docRoot = $_SERVER['DOCUMENT_ROOT'];

// Verificar admin
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

header('Content-Type: application/json');

if ($role != 1 || $isAdmin != 1) {
    echo json_encode(['success' => false, 'message' => 'No admin']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not POST']);
    exit;
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error en archivo']);
    exit;
}

$codigo = $_POST['codigo'] ?? 'unknown';
$dptoId = $_POST['dpto_id'] ?? 0;

$archivo = $_FILES['archivo'];

// Crear directorio de prueba
$testDir = $docRoot . '/catalogo/images/test_uploads/';
if (!file_exists($testDir)) {
    mkdir($testDir, 0777, true);
}

$nombreArchivo = $codigo . '.jpg';
$rutaCompleta = $testDir . $nombreArchivo;

// Mover el archivo
if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
    echo json_encode([
        'success' => true,
        'message' => 'Archivo guardado',
        'ruta' => $rutaCompleta,
        'url' => '/catalogo/images/test_uploads/' . $nombreArchivo
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar',
        'error' => error_get_last()
    ]);
}
?>