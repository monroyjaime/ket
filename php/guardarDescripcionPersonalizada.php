<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("dbcat.php");

header('Content-Type: application/json; charset=utf-8');

// Verificar si hay sesión activa
if (!isset($_SESSION['usr_num'])) {
    echo json_encode(['success' => false, 'error' => 'No hay sesión activa', 'session' => $_SESSION]);
    exit;
}

// Obtener datos
$code = isset($_POST['code']) ? trim($_POST['code']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$usuario = isset($_POST['usuario']) ? intval($_POST['usuario']) : 0;

error_log("Guardando descripción - Code: $code, Desc: $descripcion, Usuario: $usuario");

// Validaciones
if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Código del producto no especificado', 'post_data' => $_POST]);
    exit;
}

if (empty($descripcion)) {
    echo json_encode(['success' => false, 'error' => 'La descripción no puede estar vacía']);
    exit;
}

try {
    $db = new DB();
    
    // Verificar que el producto existe
    $verificar = $db->consultas("SELECT code, no_code FROM productos WHERE code = $1", [$code]);
    
    if (empty($verificar)) {
        echo json_encode(['success' => false, 'error' => 'Producto no encontrado', 'code_buscado' => $code]);
        exit;
    }
    
    // Verificar si tiene permiso para editar (no_code = 't' o true)
    $producto = $verificar[0];
    $no_code_val = $producto->no_code;
    
    // Convertir a booleano para verificación
    $es_no_code = false;
    if (is_bool($no_code_val)) {
        $es_no_code = $no_code_val;
    } elseif (is_string($no_code_val)) {
        $es_no_code = ($no_code_val === 't' || $no_code_val === 'true' || $no_code_val === '1');
    } elseif (is_numeric($no_code_val)) {
        $es_no_code = ($no_code_val == 1);
    }
    
    if (!$es_no_code) {
        echo json_encode(['success' => false, 'error' => 'Este producto no permite descripción personalizada', 'no_code_value' => $no_code_val]);
        exit;
    }
    
    // Actualizar la descripción
    $resultado = $db->consultas(
        "UPDATE productos SET name = $1, updated_at = NOW() WHERE code = $2",
        [$descripcion, $code]
    );
    
    // Registrar en el historial si tienes la tabla
    try {
        $db->consultas(
            "INSERT INTO historial_descripciones (producto_code, descripcion, usuario_id, fecha) VALUES ($1, $2, $3, NOW())",
            [$code, $descripcion, $usuario]
        );
    } catch (Exception $e) {
        // Si la tabla no existe, solo registrar en log
        error_log("Nota: Tabla historial_descripciones no existe o error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Descripción actualizada',
        'new_descripcion' => $descripcion
    ]);
    
} catch (Exception $e) {
    error_log("Error al guardar descripción: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'error' => 'Error en el servidor: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>