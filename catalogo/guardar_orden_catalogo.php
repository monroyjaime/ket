<?php
// guardar_orden_catalogo.php
session_start();
require_once("../php/dbcat.php");

header('Content-Type: application/json');

// Verificar autenticación de administrador
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

if ($role != 1 || $isAdmin != 1) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$dpto_id = isset($input['dpto_id']) ? (int)$input['dpto_id'] : 0;
$ordenes = isset($input['ordenes']) ? $input['ordenes'] : [];

if ($dpto_id <= 0 || empty($ordenes)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$db = new DB();

try {
    // Actualizar cada producto con su nuevo orden
    foreach ($ordenes as $item) {
        $id = (int)$item['id'];
        $orden = (int)$item['orden'];
        $query = "UPDATE productos SET orden = $orden WHERE id = $id AND dpto_id = $dpto_id";
        $db->querySet($query);
    }
    
    echo json_encode(['success' => true, 'message' => 'Orden guardado correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>