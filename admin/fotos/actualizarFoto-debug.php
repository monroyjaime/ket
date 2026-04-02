<?php
// actualizarFoto_debug.php - VERSIÓN DE DEPURACIÓN
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once("../../php/dbcat.php");

// ============================================
// VERIFICACIÓN DE AUTORIZACIÓN
// ============================================
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

if ($role == -1 || $isAdmin != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

// Crear archivo de log
$logFile = '../../logs/fotos_debug.log';
$logDir = dirname($logFile);
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

function writeLog($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL, FILE_APPEND);
}

writeLog("=== INICIO ACTUALIZAR FOTO ===");
writeLog("POST: " . print_r($_POST, true));
writeLog("FILES: " . print_r($_FILES, true));

try {
    $db = new DB();
    writeLog("DB conectada");
    
    // Verificar que se recibieron los datos necesarios
    if (!isset($_POST['codigo']) || !isset($_POST['dpto_id']) || !isset($_FILES['archivo'])) {
        throw new Exception('Datos incompletos: ' . print_r($_POST, true));
    }
    
    $codigo = pg_escape_string($_POST['codigo']);
    $dptoId = (int)$_POST['dpto_id'];
    $archivo = $_FILES['archivo'];
    
    writeLog("Procesando: codigo=$codigo, dptoId=$dptoId");
    writeLog("Archivo: name={$archivo['name']}, size={$archivo['size']}, error={$archivo['error']}");
    
    // Validar archivo
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo: ' . $archivo['error']);
    }
    
    // Validar tipo de archivo
    $tipoArchivo = mime_content_type($archivo['tmp_name']);
    writeLog("Tipo MIME: $tipoArchivo");
    
    if ($tipoArchivo !== 'image/jpeg' && $tipoArchivo !== 'image/jpg') {
        throw new Exception('Solo se permiten archivos JPG. Tipo detectado: ' . $tipoArchivo);
    }
    
    // Validar tamaño (máximo 2MB)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        throw new Exception('El archivo no debe superar los 2MB');
    }
    
    // Obtener la ruta de la carpeta de imágenes del departamento
    $queryDepto = "SELECT img_route FROM departamentos WHERE id = $dptoId";
    writeLog("Query depto: $queryDepto");
    
    $deptoResult = $db->consultas($queryDepto);
    writeLog("Resultado depto: " . print_r($deptoResult, true));
    
    if (empty($deptoResult)) {
        throw new Exception('No se encontró el departamento con ID: ' . $dptoId);
    }
    
    $imgRoute = $deptoResult[0]->img_route;
    writeLog("imgRoute original: $imgRoute");
    
    // LIMPIAR LA RUTA
    // Eliminar el protocolo (http:// o https://)
    $imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
    // Eliminar la parte del dominio si quedó
    $imgRoute = preg_replace('#^ketelectropartes\.com/#', '', $imgRoute);
    // Eliminar slash inicial
    $imgRoute = ltrim($imgRoute, '/');
    
    // Si img_route está vacío después de limpiar, usar la ruta por defecto
    if (empty($imgRoute)) {
        $imgRoute = 'catalogo/images/departamentos/';
    }
    
    // Asegurar que la ruta termine con /
    if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }
    
    writeLog("imgRoute limpia: $imgRoute");
    
    // Generar el nombre del archivo
    $nombreArchivo = $codigo . '.jpg';
    writeLog("nombreArchivo: $nombreArchivo");
    
    // Construir la ruta completa
    $directorioDestino = '../../' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    $rutaRelativa = $imgRoute . $nombreArchivo;
    
    writeLog("directorioDestino: $directorioDestino");
    writeLog("rutaCompleta: $rutaCompleta");
    writeLog("rutaRelativa: $rutaRelativa");
    
    // Verificar si el directorio existe
    if (!file_exists($directorioDestino)) {
        writeLog("Directorio no existe, intentando crear: $directorioDestino");
        if (!mkdir($directorioDestino, 0777, true)) {
            throw new Exception('No se pudo crear el directorio: ' . $directorioDestino);
        }
        writeLog("Directorio creado exitosamente");
    }
    
    // Verificar permisos de escritura
    writeLog("Verificando permisos de escritura en: $directorioDestino");
    writeLog("is_writable: " . (is_writable($directorioDestino) ? 'true' : 'false'));
    writeLog("fileperms: " . decoct(fileperms($directorioDestino)));
    
    if (!is_writable($directorioDestino)) {
        // Intentar cambiar permisos
        chmod($directorioDestino, 0777);
        clearstatcache();
        if (!is_writable($directorioDestino)) {
            throw new Exception('No hay permisos de escritura en: ' . $directorioDestino);
        }
        writeLog("Permisos corregidos");
    }
    
    // Procesar la imagen
    writeLog("Intentando abrir imagen: " . $archivo['tmp_name']);
    $img = imagecreatefromjpeg($archivo['tmp_name']);
    if (!$img) {
        throw new Exception('No se pudo procesar la imagen. Asegúrate de que sea un JPG válido.');
    }
    writeLog("Imagen abierta correctamente");
    
    // Obtener dimensiones originales
    $width = imagesx($img);
    $height = imagesy($img);
    writeLog("Dimensiones originales: {$width}x{$height}");
    
    // Redimensionar si es necesario
    $maxSize = 800;
    $newWidth = $width;
    $newHeight = $height;
    
    if ($width > $maxSize || $height > $maxSize) {
        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = intval($height * ($maxSize / $width));
        } else {
            $newHeight = $maxSize;
            $newWidth = intval($width * ($maxSize / $height));
        }
        
        $newImg = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($img);
        $img = $newImg;
        writeLog("Imagen redimensionada a: {$newWidth}x{$newHeight}");
    }
    
    // Guardar la imagen
    writeLog("Guardando imagen en: $rutaCompleta");
    if (!imagejpeg($img, $rutaCompleta, 85)) {
        throw new Exception('Error al guardar la imagen en: ' . $rutaCompleta);
    }
    writeLog("Imagen guardada exitosamente");
    
    imagedestroy($img);
    
    // Actualizar la base de datos
    $queryUpdate = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    writeLog("Query update: $queryUpdate");
    
    $result = $db->ejecutar($queryUpdate);
    writeLog("Resultado update: " . ($result ? 'true' : 'false'));
    
    if ($result) {
        $response = [
            'success' => true,
            'message' => 'Foto actualizada correctamente',
            'nombre_archivo' => $nombreArchivo,
            'ruta' => $rutaRelativa
        ];
        writeLog("Respuesta exitosa: " . json_encode($response));
        echo json_encode($response);
    } else {
        // Si falla la BD, eliminar el archivo subido
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
            writeLog("Archivo eliminado por fallo en BD");
        }
        throw new Exception('Error al actualizar la base de datos');
    }
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    writeLog("ERROR: " . $errorMsg);
    writeLog("Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $errorMsg
    ]);
}

writeLog("=== FIN ACTUALIZAR FOTO ===");
?>