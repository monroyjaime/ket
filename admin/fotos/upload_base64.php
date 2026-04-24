<?php
// upload_base64.php - Versión robusta con devolución de dpto_id
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Verificar autenticación
$isAdmin = $_SESSION['usr_admin'] ?? 0;
$role = $_SESSION['role'] ?? -1;

if ($role != 1 || $isAdmin != 1) {
    $response['message'] = 'No autorizado';
    echo json_encode($response);
    exit;
}

// Intentar leer de diferentes fuentes
$input_raw = file_get_contents('php://input');
$input_post = $_POST;
$input_json = json_decode($input_raw, true);

// Log para depuración
$debug = [
    'input_raw_length' => strlen($input_raw),
    'input_raw_preview' => substr($input_raw, 0, 200),
    'input_post' => $input_post,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'no',
    'request_method' => $_SERVER['REQUEST_METHOD']
];
file_put_contents('/tmp/upload_debug.log', date('Y-m-d H:i:s') . "\n" . print_r($debug, true) . "\n", FILE_APPEND);

// Determinar cuál fuente usar
if ($input_json && isset($input_json['codigo'])) {
    $data = $input_json;
} elseif (!empty($input_post)) {
    $data = $input_post;
} else {
    $response['message'] = 'No se recibieron datos';
    $response['debug'] = $debug;
    echo json_encode($response);
    exit;
}

$codigo = $data['codigo'] ?? '';
$dptoId = (int)($data['dpto_id'] ?? 0);
$imagen_base64 = $data['imagen_base64'] ?? '';

if (empty($codigo) || empty($dptoId) || empty($imagen_base64)) {
    $response['message'] = 'Datos incompletos';
    $response['received'] = array_keys($data);
    echo json_encode($response);
    exit;
}

// Decodificar Base64
$imagen_data = base64_decode($imagen_base64);
if ($imagen_data === false || empty($imagen_data)) {
    $response['message'] = 'Error al decodificar la imagen';
    echo json_encode($response);
    exit;
}

$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . "/php/dbcat.php");

$db = new DB();

// Obtener ruta del departamento
$deptoQuery = "SELECT img_route FROM departamentos WHERE id = $dptoId";
$deptoResult = $db->consultas($deptoQuery);

if (empty($deptoResult)) {
    $response['message'] = 'Departamento no encontrado';
    echo json_encode($response);
    exit;
}

$imgRoute = $deptoResult[0]->img_route;
$imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
$imgRoute = ltrim($imgRoute, '/');
if (substr($imgRoute, -1) !== '/') {
    $imgRoute .= '/';
}

if (empty($imgRoute)) {
    $imgRoute = 'catalogo/images/';
}

$nombreArchivo = $codigo . '.jpg';
$directorioDestino = $docRoot . '/' . $imgRoute;
$rutaCompleta = $directorioDestino . $nombreArchivo;

if (!file_exists($directorioDestino)) {
    if (!mkdir($directorioDestino, 0775, true)) {
        $response['message'] = 'No se pudo crear el directorio';
        echo json_encode($response);
        exit;
    }
}

if (file_put_contents($rutaCompleta, $imagen_data) === false) {
    $response['message'] = 'Error al guardar el archivo';
    echo json_encode($response);
    exit;
}

$updateQuery = "UPDATE productos SET photo_url = '$nombreArchivo' WHERE code = '$codigo'";
$db->querySet($updateQuery);

// ============================================
// DEVOLVER dpto_id EN LA RESPUESTA
// ============================================
$response['success'] = true;
$response['message'] = 'Foto actualizada correctamente';
$response['dpto_id'] = $dptoId;        // ← NUEVO: departamento afectado
$response['codigo'] = $codigo;          // ← NUEVO: código del producto
$response['url'] = '/' . $imgRoute . $nombreArchivo;

echo json_encode($response);
?>