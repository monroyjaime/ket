<?php
// test_minimal.php - Script mínimo para probar subida
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Verificar admin
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

header('Content-Type: application/json');

if ($role != 1 || $isAdmin != 1) {
    echo json_encode(['success' => false, 'message' => 'No admin']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not POST']);
    exit;
}

// Verificar datos
if (empty($_FILES)) {
    echo json_encode(['success' => false, 'message' => 'No files received']);
    exit;
}

// Verificar que tenemos el archivo
if (!isset($_FILES['archivo'])) {
    echo json_encode(['success' => false, 'message' => 'Missing archivo field']);
    exit;
}

$archivo = $_FILES['archivo'];

// Devolver información del archivo recibido
echo json_encode([
    'success' => true,
    'message' => 'File received',
    'file_info' => [
        'name' => $archivo['name'],
        'type' => $archivo['type'],
        'size' => $archivo['size'],
        'error' => $archivo['error'],
        'tmp_name' => $archivo['tmp_name']
    ],
    'post' => $_POST
]);
?>