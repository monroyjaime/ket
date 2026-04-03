<?php
// upload_test.php - Con errores visibles
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// No enviar headers aún para poder ver errores
session_start();

$docRoot = $_SERVER['DOCUMENT_ROOT'];

// Verificar admin
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

// Si no es admin, mostrar error en texto plano
if ($role != 1 || $isAdmin != 1) {
    header('Content-Type: text/plain');
    echo "ERROR: No admin - usr_admin=$isAdmin, role=$role";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: text/plain');
    echo "ERROR: Method not POST - " . $_SERVER['REQUEST_METHOD'];
    exit;
}

// Verificar archivo
if (!isset($_FILES['archivo'])) {
    header('Content-Type: text/plain');
    echo "ERROR: No file received";
    echo "\nPOST: " . print_r($_POST, true);
    echo "\nFILES: " . print_r($_FILES, true);
    exit;
}

if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: text/plain');
    echo "ERROR: File error - " . $_FILES['archivo']['error'];
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
    // Ahora sí, devolver JSON
    header('Content-Type: application/json');
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
    header('Content-Type: text/plain');
    echo "ERROR: Cannot move file to " . $rutaCompleta;
    echo "\nTemp file: " . $archivo['tmp_name'];
    echo "\nError: " . print_r(error_get_last(), true);
}
?>