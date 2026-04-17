<?php
// upload_final.php - Con respaldo y control de caché
// Forzar modo de errores silencioso (solo JSON)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// Función para enviar respuesta JSON
function sendJsonResponse($success, $message, $extra = []) {
    $response = array_merge(['success' => $success, 'message' => $message], $extra);
    echo json_encode($response);
    exit;
}

try {
    // Verificar autenticación
    $isAdmin = $_SESSION['usr_admin'] ?? 0;
    $role = $_SESSION['role'] ?? -1;
    
    if ($role != 1 || $isAdmin != 1) {
        sendJsonResponse(false, 'No autorizado');
    }
    
    // Verificar datos
    if (!isset($_POST['codigo']) || !isset($_POST['dpto_id']) || !isset($_FILES['archivo'])) {
        sendJsonResponse(false, 'Datos incompletos');
    }
    
    $codigo = $_POST['codigo'];
    $dptoId = (int)$_POST['dpto_id'];
    $archivo = $_FILES['archivo'];
    
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, 'Error en archivo: ' . $archivo['error']);
    }
    
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    require_once($docRoot . "/php/dbcat.php");
    
    $db = new DB();
    
    // Obtener ruta del departamento
    $deptoQuery = "SELECT img_route FROM departamentos WHERE id = $dptoId";
    $deptoResult = $db->consultas($deptoQuery);
    
    if (empty($deptoResult)) {
        sendJsonResponse(false, 'Departamento no encontrado');
    }
    
    $imgRoute = $deptoResult[0]->img_route;
    
    // Limpiar la ruta (eliminar protocolo y dominio)
    $imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
    $imgRoute = ltrim($imgRoute, '/');
    if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }
    
    if (empty($imgRoute)) {
        $imgRoute = 'catalogo/images/departamentos/';
    }
    
    $nombreArchivo = $codigo . '.jpg';
    $directorioDestino = $docRoot . '/' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    $rutaRelativa = $nombreArchivo;
    
    // Crear directorio si no existe
    if (!file_exists($directorioDestino)) {
        if (!mkdir($directorioDestino, 0775, true)) {
            sendJsonResponse(false, 'No se pudo crear el directorio: ' . $directorioDestino);
        }
    }
    
    // ============================================
    // RESPALDAR FOTO EXISTENTE SI EXISTE
    // ============================================
    $backupName = null;
    if (file_exists($rutaCompleta)) {
        $timestamp = date('Ymd_His');
        $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
        $nombreBase = pathinfo($nombreArchivo, PATHINFO_FILENAME);
        $backupName = $nombreBase . "_backup_{$timestamp}." . $extension;
        $rutaBackup = $directorioDestino . $backupName;
        if (copy($rutaCompleta, $rutaBackup)) {
            error_log("Backup creado: " . $rutaBackup);
        } else {
            error_log("No se pudo crear backup de: " . $rutaCompleta);
        }
    }
    
    // Guardar el nuevo archivo (usando move_uploaded_file que es más seguro)
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        // Si falla, intentar con copy como fallback
        if (!copy($archivo['tmp_name'], $rutaCompleta)) {
            $error = error_get_last();
            sendJsonResponse(false, 'Error al guardar archivo: ' . ($error['message'] ?? 'desconocido'));
        }
    }
    
    // Actualizar base de datos
    $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->querySet($updateQuery);
    
    if ($result !== 1) {
        // Si falla la BD, restaurar backup si existe
        if (isset($rutaBackup) && file_exists($rutaBackup)) {
            copy($rutaBackup, $rutaCompleta);
        }
        sendJsonResponse(false, 'Error al actualizar la base de datos');
    }
    
    // Eliminar backups antiguos (más de 30 días)
    $files = glob($directorioDestino . "*_backup_*.jpg");
    $now = time();
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > 30 * 24 * 60 * 60) {
            unlink($file);
        }
    }
    
    sendJsonResponse(true, 'Foto actualizada correctamente', [
        'url' => '/' . $imgRoute . $nombreArchivo,
        'backup' => $backupName,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage());
}
?>