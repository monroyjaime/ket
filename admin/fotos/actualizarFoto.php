<?php
// actualizarFoto.php - VERSIÓN FINAL CORREGIDA
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . "/php/dbcat.php");

header('Content-Type: application/json');

// Verificar administrador
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

if ($role != 1 || $isAdmin != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Validar datos recibidos
    if (!isset($_POST['codigo'])) {
        throw new Exception('Falta el código del producto');
    }
    if (!isset($_POST['dpto_id'])) {
        throw new Exception('Falta el ID del departamento');
    }
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error en la subida del archivo');
    }
    
    $codigo = trim($_POST['codigo']);
    $dptoId = (int)$_POST['dpto_id'];
    $archivo = $_FILES['archivo'];
    
    // Validar tipo de archivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if ($mime !== 'image/jpeg' && $mime !== 'image/jpg') {
        throw new Exception('Solo se permiten archivos JPG. Tipo: ' . $mime);
    }
    
    // Conectar a BD
    $db = new DB();
    
    // Obtener ruta del departamento
    $deptoQuery = "SELECT img_route FROM departamentos WHERE id = $dptoId";
    $deptoResult = $db->consultas($deptoQuery);
    
    if (empty($deptoResult)) {
        throw new Exception('Departamento no encontrado (ID: ' . $dptoId . ')');
    }
    
    $imgRoute = $deptoResult[0]->img_route;
    
    // Limpiar ruta
    $imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
    $imgRoute = ltrim($imgRoute, '/');
    
    if (empty($imgRoute)) {
        $imgRoute = 'catalogo/images/departamentos/';
    }
    
    if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }
    
    // Preparar nombres de archivo
    $nombreArchivo = $codigo . '.jpg';
    $directorioDestino = $docRoot . '/' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    $rutaRelativa = $imgRoute . $nombreArchivo;
    
    // Crear directorio si no existe
    if (!file_exists($directorioDestino)) {
        if (!mkdir($directorioDestino, 0775, true)) {
            throw new Exception('No se pudo crear el directorio: ' . $directorioDestino);
        }
    }
    
    // Verificar permisos
    if (!is_writable($directorioDestino)) {
        chmod($directorioDestino, 0775);
    }
    
    // Guardar archivo (sobrescribir si existe)
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        throw new Exception('Error al guardar el archivo en: ' . $rutaCompleta);
    }
    
    // Actualizar base de datos usando querySet()
    $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->querySet($updateQuery);
    
    if ($result !== 1) {
        // Si falla la BD, eliminar el archivo
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }
        throw new Exception('Error al actualizar la base de datos');
    }
    
    // Éxito
    echo json_encode([
        'success' => true,
        'message' => 'Foto actualizada correctamente',
        'codigo' => $codigo,
        'ruta' => $rutaRelativa,
        'url' => '/' . $rutaRelativa
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>