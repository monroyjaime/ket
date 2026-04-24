<?php
// upload_final.php - Solución robusta con carpeta temporal personalizada

// --- 1. CONFIGURACIÓN FORZADA DE CARPETA TEMPORAL ---
// Esta es la solución clave. Definimos una ruta fija y le decimos a PHP que la use.
$customTempDir = '/home/jaime/catalogo_ket/tmp';

// Verificamos si la carpeta existe. Si no, la creamos.
if (!is_dir($customTempDir)) {
    mkdir($customTempDir, 0700, true);
}

// ¡Aquí forzamos a PHP a usar NUESTRA carpeta, sobreescribiendo cualquier otra configuración!
ini_set('upload_tmp_dir', $customTempDir);
// También la configuramos como variable de entorno para Playwright y otros procesos.
putenv("TMPDIR=$customTempDir");

// --- 2. RESTO DEL SCRIPT (Sin cambios importantes, pero incluido por completitud) ---
session_start();

// Verificar autenticación RÁPIDAMENTE
$isAdmin = $_SESSION['usr_admin'] ?? 0;
$role = $_SESSION['role'] ?? -1;

if ($role != 1 || $isAdmin != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Cerramos la sesión para liberar el archivo de sesión
session_write_close();

header('Content-Type: application/json');

// Función para enviar respuesta JSON
function sendJsonResponse($success, $message, $extra = []) {
    $response = array_merge(['success' => $success, 'message' => $message], $extra);
    echo json_encode($response);
    exit;
}

try {
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
    
    // Verificar que el temporal existe (ahora debería ser nuestra carpeta)
    if (!file_exists($archivo['tmp_name'])) {
        // Añadimos información de depuración en caso de que falle
        sendJsonResponse(false, 'El archivo temporal no existe. Dir: ' . ini_get('upload_tmp_dir'));
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
    
    // Respaldar foto existente si existe
    $backupName = null;
    if (file_exists($rutaCompleta)) {
        $timestamp = date('Ymd_His');
        $nombreBase = pathinfo($nombreArchivo, PATHINFO_FILENAME);
        $backupName = $nombreBase . "_backup_{$timestamp}.jpg";
        $rutaBackup = $directorioDestino . $backupName;
        copy($rutaCompleta, $rutaBackup);
    }
    
    // Guardar el nuevo archivo
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        sendJsonResponse(false, 'Error al guardar el archivo');
    }
    
    // Actualizar base de datos
    $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->querySet($updateQuery);
    
    if ($result !== 1) {
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
        'timestamp' => time(),
        'dpto_id' => $dptoId,        // ← NUEVO: departamento afectado
        'codigo' => $codigo           // ← NUEVO: código del producto
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage());
}
?>