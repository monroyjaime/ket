<?php
// upload_final_debug.php - Con más información de error
session_start();
header('Content-Type: application/json');

// Verificar autenticación
$isAdmin = $_SESSION['usr_admin'] ?? 0;
$role = $_SESSION['role'] ?? -1;

$response = ['success' => false, 'message' => ''];

if ($role != 1 || $isAdmin != 1) {
    $response['message'] = 'No autorizado';
    echo json_encode($response);
    exit;
}

// Verificar datos
if (!isset($_POST['codigo']) || !isset($_POST['dpto_id']) || !isset($_FILES['archivo'])) {
    $response['message'] = 'Datos incompletos';
    echo json_encode($response);
    exit;
}

$codigo = $_POST['codigo'];
$dptoId = (int)$_POST['dpto_id'];
$archivo = $_FILES['archivo'];

if ($archivo['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Error en archivo: ' . $archivo['error'];
    echo json_encode($response);
    exit;
}

$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . "/php/dbcat.php");

try {
    $db = new DB();
    
    // Obtener ruta del departamento
    $deptoQuery = "SELECT img_route FROM departamentos WHERE id = $dptoId";
    $deptoResult = $db->consultas($deptoQuery);
    
    if (empty($deptoResult)) {
        throw new Exception('Departamento no encontrado');
    }
    
    $imgRoute = $deptoResult[0]->img_route;
    $imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
    $imgRoute = ltrim($imgRoute, '/');
    
    if (empty($imgRoute)) {
        $imgRoute = 'catalogo/images/departamentos/';
    }
    
    if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }
    
    $nombreArchivo = $codigo . '.jpg';
    $directorioDestino = $docRoot . '/' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    $rutaRelativa = $nombreArchivo;
    
    // Verificar/Crear directorio
    if (!file_exists($directorioDestino)) {
        if (!mkdir($directorioDestino, 0775, true)) {
            throw new Exception('No se pudo crear el directorio: ' . $directorioDestino);
        }
    }
    
    // Verificar permisos
    if (!is_writable($directorioDestino)) {
        // Intentar cambiar permisos
        chmod($directorioDestino, 0775);
        clearstatcache();
        if (!is_writable($directorioDestino)) {
            throw new Exception('No hay permisos de escritura en: ' . $directorioDestino);
        }
    }
    
    // Verificar el archivo temporal
    if (!file_exists($archivo['tmp_name'])) {
        throw new Exception('El archivo temporal no existe: ' . $archivo['tmp_name']);
    }
    
    // Intentar guardar el archivo
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        $error = error_get_last();
        throw new Exception('Error al guardar: ' . ($error['message'] ?? 'desconocido'));
    }
    
    // Actualizar base de datos
    $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->querySet($updateQuery);
    
    if ($result !== 1) {
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }
        throw new Exception('Error al actualizar la base de datos');
    }
    
    $response = [
        'success' => true,
        'message' => 'Foto actualizada correctamente',
        'url' => '/' . $rutaRelativa
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>