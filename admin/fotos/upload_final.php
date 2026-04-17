<?php
// Forzar visualización de errores en el navegador
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// También crear un archivo de log personalizado
$debug_log = '/tmp/upload_debug.log';
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Iniciando upload_final.php\n", FILE_APPEND);


// upload_final.php - Con respaldo y control de caché
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
    
    if (!empty($imgRoute) && substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }

    if (empty($imgRoute)) {
        //$imgRoute = 'catalogo/images/departamentos/';
        $imgRoute = 'catalogo/images/';
    }
    
    /*if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }*/
    
    $nombreArchivo = $codigo . '.jpg';
    $directorioDestino = $docRoot . '/' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;

    // Logs personalizados
    file_put_contents('/tmp/upload_debug.log', "=== upload_final.php ===\n", FILE_APPEND);
    file_put_contents('/tmp/upload_debug.log', "docRoot: $docRoot\n", FILE_APPEND);
    file_put_contents('/tmp/upload_debug.log', "imgRoute: $imgRoute\n", FILE_APPEND);
    file_put_contents('/tmp/upload_debug.log', "directorioDestino: $directorioDestino\n", FILE_APPEND);
    file_put_contents('/tmp/upload_debug.log', "rutaCompleta: $rutaCompleta\n", FILE_APPEND);
    file_put_contents('/tmp/upload_debug.log', "nombreArchivo: $nombreArchivo\n", FILE_APPEND);
    file_put_contents('/tmp/upload_debug.log', "archivo tmp_name: " . $archivo['tmp_name'] . "\n", FILE_APPEND);

    // Verificar directorio
    if (file_exists($directorioDestino)) {
        error_log("Directorio SI existe");
        error_log("Directorio es escribible: " . (is_writable($directorioDestino) ? 'SI' : 'NO'));
    } else {
        error_log("Directorio NO existe");
    }



    $rutaRelativa = $nombreArchivo;
    
    // Crear directorio si no existe
    if (!file_exists($directorioDestino)) {
        if (!mkdir($directorioDestino, 0777, true)) {
            throw new Exception('No se pudo crear el directorio: ' . $directorioDestino);
        }
    }

    // Verificar permisos de escritura
    if (!is_writable($directorioDestino)) {
        throw new Exception('Directorio no tiene permisos de escritura: ' . $directorioDestino);
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
            $response['backup'] = $backupName;
            error_log("Backup creado: " . $rutaBackup);
        } else {
            error_log("No se pudo crear backup de: " . $rutaCompleta);
        }
    }


    error_log("Intentando copiar archivo...");
    error_log("Origen: " . $archivo['tmp_name']);
    error_log("Destino: " . $rutaCompleta);

    // Guardar el nuevo archivo
    if (!copy($archivo['tmp_name'], $rutaCompleta)) {
       $error = error_get_last();
        $response['message'] = 'Error al copiar archivo: ' . ($error['message'] ?? 'desconocido');
        $response['debug'] = [
            'origen' => $archivo['tmp_name'],
            'destino' => $rutaCompleta,
            'directorio_existe' => file_exists($directorioDestino),
            'directorio_escribible' => is_writable($directorioDestino)
        ];
        echo json_encode($response);
        exit;
    } else {
        error_log("Archivo copiado exitosamente");
    }

    
    // Guardar el nuevo archivo (usando el método que funcionó)
    if (!copy($archivo['tmp_name'], $rutaCompleta)) {
        $error = error_get_last();
        throw new Exception('Error al copiar archivo: ' . ($error['message'] ?? 'desconocido'));
    }
    
    // Eliminar el archivo temporal
    if (file_exists($archivo['tmp_name'])) {
        unlink($archivo['tmp_name']);
    }
    
    // Actualizar base de datos
    $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->querySet($updateQuery);
    
    if ($result !== 1) {
        // Si falla la BD, restaurar backup si existe
        if (isset($rutaBackup) && file_exists($rutaBackup)) {
            copy($rutaBackup, $rutaCompleta);
        }
        throw new Exception('Error al actualizar la base de datos');
    }
    
    // Eliminar backups antiguos (más de 30 días)
    $files = glob($directorioDestino . "*_backup_*.jpg");
    $now = time();
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > 30 * 24 * 60 * 60) {
            unlink($file);
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'Foto actualizada correctamente',
        'url' => '/' . $imgRoute . $nombreArchivo,
        'backup' => $backupName,
        'timestamp' => time()  // Para forzar recarga de caché
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>