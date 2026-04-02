<?php
// actualizarFoto_simple.php - Con logging detallado
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . "/php/dbcat.php");

header('Content-Type: application/json');

// Log para depuración
$logFile = '/tmp/fotos_debug.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - REQUEST\n", FILE_APPEND);
file_put_contents($logFile, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($logFile, "FILES: " . print_r($_FILES, true) . "\n", FILE_APPEND);
file_put_contents($logFile, "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'no') . "\n", FILE_APPEND);
file_put_contents($logFile, "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

$response = ['success' => false, 'message' => ''];

// Verificar admin
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

if ($role != 1 || $isAdmin != 1) {
    $response['message'] = 'Acceso denegado';
    echo json_encode($response);
    exit;
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

try {
    // Verificar datos - con mensajes específicos
    if (!isset($_POST['codigo'])) {
        throw new Exception('Falta campo codigo');
    }
    if (!isset($_POST['dpto_id'])) {
        throw new Exception('Falta campo dpto_id');
    }
    if (!isset($_FILES['archivo'])) {
        throw new Exception('Falta archivo');
    }
    if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error en archivo: ' . $_FILES['archivo']['error']);
    }
    
    $codigo = $_POST['codigo'];
    $dptoId = (int)$_POST['dpto_id'];
    
    file_put_contents($logFile, "Procesando: codigo=$codigo, dptoId=$dptoId\n", FILE_APPEND);
    
    // Obtener ruta del departamento
    $db = new DB();
    $deptoResult = $db->consultas("SELECT img_route FROM departamentos WHERE id = $dptoId");
    
    if (empty($deptoResult)) {
        throw new Exception('Departamento no encontrado');
    }
    
    $imgRoute = $deptoResult[0]->img_route;
    $imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
    $imgRoute = ltrim($imgRoute, '/');
    
    if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }
    
    $nombreArchivo = $codigo . '.jpg';
    $directorioDestino = $docRoot . '/' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    $rutaRelativa = $imgRoute . $nombreArchivo;
    
    file_put_contents($logFile, "Destino: $rutaCompleta\n", FILE_APPEND);
    
    // Crear directorio si no existe
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0775, true);
    }
    
    // Mover archivo
    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaCompleta)) {
        throw new Exception('Error al guardar archivo');
    }
    
    file_put_contents($logFile, "Archivo guardado\n", FILE_APPEND);
    
    // Actualizar BD
    $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->querySet($updateQuery);
    
    if ($result === 1) {
        $response = [
            'success' => true,
            'message' => 'Foto actualizada correctamente',
            'ruta' => $rutaRelativa
        ];
        file_put_contents($logFile, "BD actualizada\n", FILE_APPEND);
    } else {
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }
        throw new Exception('Error al actualizar BD');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

echo json_encode($response);
file_put_contents($logFile, "RESPONSE: " . json_encode($response) . "\n\n", FILE_APPEND);
?>