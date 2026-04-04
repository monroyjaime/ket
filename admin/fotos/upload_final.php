<?php
// upload_final.php - Con debugging detallado
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
    $response['debug_imgRoute_original'] = $imgRoute;
    
    $imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
    $imgRoute = ltrim($imgRoute, '/');
    
    if (empty($imgRoute)) {
        $imgRoute = 'catalogo/images/departamentos/';
    }
    
    if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }
    
    $response['debug_imgRoute_limpia'] = $imgRoute;
    
    $nombreArchivo = $codigo . '.jpg';
    $directorioDestino = $docRoot . '/' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    
    $response['debug_docRoot'] = $docRoot;
    $response['debug_directorioDestino'] = $directorioDestino;
    $response['debug_rutaCompleta'] = $rutaCompleta;
    $response['debug_nombreArchivo'] = $nombreArchivo;
    
    // Verificar si el directorio existe
    if (!file_exists($directorioDestino)) {
        $response['debug_directorio_existe'] = false;
        // Intentar crear el directorio
        if (mkdir($directorioDestino, 0775, true)) {
            $response['debug_directorio_creado'] = true;
        } else {
            $response['debug_error_creacion'] = error_get_last();
            throw new Exception('No se pudo crear el directorio: ' . $directorioDestino);
        }
    } else {
        $response['debug_directorio_existe'] = true;
        $response['debug_directorio_permisos'] = substr(sprintf('%o', fileperms($directorioDestino)), -4);
        $response['debug_directorio_writable'] = is_writable($directorioDestino);
        
        if (!is_writable($directorioDestino)) {
            chmod($directorioDestino, 0775);
            clearstatcache();
            $response['debug_permisos_cambiados'] = is_writable($directorioDestino);
        }
    }
    
    // Verificar el archivo temporal
    if (!file_exists($archivo['tmp_name'])) {
        throw new Exception('El archivo temporal no existe: ' . $archivo['tmp_name']);
    }
    $response['debug_temp_file_exists'] = true;
    $response['debug_temp_file_size'] = filesize($archivo['tmp_name']);
    
    // Verificar espacio en disco
    $freeSpace = disk_free_space($directorioDestino);
    $response['debug_free_space_bytes'] = $freeSpace;
    $response['debug_free_space_mb'] = round($freeSpace / 1024 / 1024, 2);
    
    if ($freeSpace < $archivo['size']) {
        throw new Exception('No hay suficiente espacio en disco');
    }
    
    // Intentar guardar el archivo
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        $error = error_get_last();
        $response['debug_move_error'] = $error;
        throw new Exception('Error al guardar: ' . ($error['message'] ?? 'desconocido'));
    }
    
    $response['debug_file_saved'] = true;
    
    // Actualizar base de datos
    $rutaRelativa = $nombreArchivo;
    $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->querySet($updateQuery);
    
    if ($result !== 1) {
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }
        throw new Exception('Error al actualizar la base de datos');
    }
    
    $response['success'] = true;
    $response['message'] = 'Foto actualizada correctamente';
    $response['url'] = '/' . $imgRoute . $nombreArchivo;
    
    // Limpiar datos de debug en producción (opcional)
    // unset($response['debug_*']);
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>