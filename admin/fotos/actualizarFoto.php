<?php
// actualizarFoto.php
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

try {
    $db = new DB();
    
    // Verificar que se recibieron los datos necesarios
    if (!isset($_POST['codigo']) || !isset($_POST['dpto_id']) || !isset($_FILES['archivo'])) {
        throw new Exception('Datos incompletos');
    }
    
    $codigo = pg_escape_string($_POST['codigo']);
    $dptoId = (int)$_POST['dpto_id'];
    $archivo = $_FILES['archivo'];
    
    // Validar archivo
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo: ' . $archivo['error']);
    }
    
    // Validar tipo de archivo
    $tipoArchivo = mime_content_type($archivo['tmp_name']);
    if ($tipoArchivo !== 'image/jpeg' && $tipoArchivo !== 'image/jpg') {
        throw new Exception('Solo se permiten archivos JPG');
    }
    
    // Validar tamaño (máximo 2MB)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        throw new Exception('El archivo no debe superar los 2MB');
    }
    
    // Obtener la ruta de la carpeta de imágenes del departamento
    $queryDepto = "SELECT img_route FROM departamentos WHERE id = $dptoId";
    $deptoResult = $db->consultas($queryDepto);
    
    if (empty($deptoResult)) {
        throw new Exception('No se encontró el departamento');
    }
    
    $imgRoute = $deptoResult[0]->img_route;
    
    // Si img_route está vacío, usar la ruta por defecto
    if (empty($imgRoute)) {
        $imgRoute = 'catalogo/images/departamentos/';
    }
    
    // Asegurar que la ruta termine con /
    if (substr($imgRoute, -1) !== '/') {
        $imgRoute .= '/';
    }
    
    // Generar el nombre del archivo según el código del producto
    // Reemplazar guiones por puntos y asegurar extensión .jpg
    $nombreArchivo = $codigo . '.jpg';    
    // Construir la ruta completa
    $directorioDestino = '../../' . $imgRoute;
    $rutaCompleta = $directorioDestino . $nombreArchivo;
    $rutaRelativa = $imgRoute . $nombreArchivo;
    
    // Crear el directorio si no existe
    if (!file_exists($directorioDestino)) {
        if (!mkdir($directorioDestino, 0777, true)) {
            throw new Exception('No se pudo crear el directorio: ' . $directorioDestino);
        }
    }
    
    // Verificar permisos de escritura
    if (!is_writable($directorioDestino)) {
        throw new Exception('No hay permisos de escritura en: ' . $directorioDestino);
    }
    
    // Procesar la imagen (redimensionar si es necesario)
    $img = imagecreatefromjpeg($archivo['tmp_name']);
    if (!$img) {
        throw new Exception('No se pudo procesar la imagen. Asegúrate de que sea un JPG válido.');
    }
    
    // Obtener dimensiones originales
    $width = imagesx($img);
    $height = imagesy($img);
    
    // Redimensionar si es muy grande (máximo 800px de ancho o alto)
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
    }
    
    // Guardar la imagen optimizada (calidad 85)
    if (!imagejpeg($img, $rutaCompleta, 85)) {
        throw new Exception('Error al guardar la imagen');
    }
    
    imagedestroy($img);
    
    // Actualizar la base de datos con la nueva ruta de la foto
    $queryUpdate = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
    $result = $db->ejecutar($queryUpdate);
    
    if ($result) {
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