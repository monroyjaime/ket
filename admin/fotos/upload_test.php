<?php
// upload_test.php - Con verificación de sesión
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$docRoot = $_SERVER['DOCUMENT_ROOT'];

// Verificar admin con sesión
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

header('Content-Type: application/json');

if ($role != 1 || $isAdmin != 1) {
    echo json_encode([
        'success' => false, 
        'message' => 'No autenticado',
        'session' => session_id()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not POST']);
    exit;
}

// Verificar archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error en archivo']);
    exit;
}

$codigo = $_POST['codigo'] ?? 'test';
$dptoId = $_POST['dpto_id'] ?? 0;

$archivo = $_FILES['archivo'];

// Directorio donde se guardará
$uploadDir = $docRoot . '/catalogo/images/uploads_test/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$nombreArchivo = $codigo . '.jpg';
$rutaCompleta = $uploadDir . $nombreArchivo;
$urlRelativa = '/catalogo/images/uploads_test/' . $nombreArchivo;

// Mover el archivo
if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
    echo json_encode([
        'success' => true,
        'message' => 'Archivo guardado correctamente',
        'codigo' => $codigo,
        'dpto_id' => $dptoId,
        'ruta_fisica' => $rutaCompleta,
        'url' => $urlRelativa,
        'nombre_archivo' => $nombreArchivo
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al mover el archivo',
        'error' => error_get_last()
    ]);
}
?>