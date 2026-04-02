<?php
// actualizarFoto.php
session_start();

$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . "/php/dbcat.php");

// Verificar administrador
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

if ($role == -1 || $isAdmin != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new DB();
    
    // Verificar datos recibidos
    if (!isset($_POST['codigo']) || !isset($_POST['dpto_id']) || !isset($_FILES['archivo'])) {
        throw new Exception('Datos incompletos');
    }
    
    $codigo = $_POST['codigo'];
    $dptoId = (int)$_POST['dpto_id'];
    $archivo = $_FILES['archivo'];
    
    // Validar archivo
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo: ' . $archivo['error']);
    }
    
    // Validar tipo de archivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if ($mime !== 'image/jpeg' && $mime !== 'image/jpg') {
        throw new Exception('Solo se permiten archivos JPG. Tipo detectado: ' . $mime);
    }
    
    // Validar tamaño (máximo 2MB)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        throw new Exception('El archivo no debe superar los 2MB');
    }
    
    // Obtener la ruta del departamento
    $queryDepto = "SELECT img_route FROM departamentos WHERE id = $dptoId";
    $deptoResult = $db->consultas($queryDepto);
    
    if (empty($deptoResult)) {
        throw new Exception('Departamento no encontrado');
    }
    
    $imgRoute = $deptoResult[0]->img_route;
    
    // Limpiar ruta (eliminar dominio si existe)
    $imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
    $imgRoute = ltrim($imgRoute, '/');
    
    if (empty($imgRoute)) {
        $imgRoute = 'catalogo/images/departamentos/';
    }
    
    if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }
    
    // Nombre del archivo
    $nombreArchivo = $codigo . '.jpg';
    
    // Ruta de destino
    $directorioDestino = $docRoot . '/' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    $rutaRelativa = $imgRoute . $nombreArchivo;
    
    // Crear directorio si no existe
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0775, true);
    }
    
    // Verificar permisos
    if (!is_writable($directorioDestino)) {
        chmod($directorioDestino, 0775);
    }
    
    // Mover el archivo
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Actualizar base de datos usando querySet()
    $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->querySet($updateQuery);
    
    if ($result === 1) {
        echo json_encode([
            'success' => true,
            'message' => 'Foto actualizada correctamente',
            'nombre_archivo' => $nombreArchivo,
            'ruta' => $rutaRelativa
        ]);
    } else {
        // Si falla la BD, eliminar el archivo subido
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }
        throw new Exception('Error al actualizar la base de datos');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>