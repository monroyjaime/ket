<?php
session_start();
require_once("dbcat.php");

header('Content-Type: application/json; charset=utf-8');

// Verificar si hay sesión activa
if (!isset($_SESSION['usr_num'])) {
    echo json_encode(['success' => false, 'error' => 'No hay sesión activa']);
    exit;
}

// Obtener datos
$code = isset($_POST['code']) ? trim($_POST['code']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$usuario = isset($_POST['usuario']) ? intval($_POST['usuario']) : 0;

// Validaciones
if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Código del producto no especificado']);
    exit;
}

if (empty($descripcion)) {
    echo json_encode(['success' => false, 'error' => 'La descripción no puede estar vacía']);
    exit;
}

try {
    $db = new DB();
    
    // Verificar que el producto existe y tiene no_code = true
    $verificar = $db->consultas("SELECT no_code FROM productos WHERE code = $1", [$code]);
    
    if (empty($verificar)) {
        echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
        exit;
    }
    
    if (!$verificar[0]->no_code) {
        echo json_encode(['success' => false, 'error' => 'Este producto no permite descripción personalizada']);
        exit;
    }
    
    // Actualizar la descripción
    $resultado = $db->consultas(
        "UPDATE productos SET name = $1, updated_at = NOW() WHERE code = $2",
        [$descripcion, $code]
    );
    
    // Registrar en el historial si lo necesitas
    $db->consultas(
        "INSERT INTO historial_descripciones (producto_code, descripcion, usuario_id, fecha) VALUES ($1, $2, $3, NOW())",
        [$code, $descripcion, $usuario]
    );
    
    echo json_encode(['success' => true, 'message' => 'Descripción actualizada']);
    
} catch (Exception $e) {
    error_log("Error al guardar descripción: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>