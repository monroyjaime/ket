<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../../php/dbcat_async.php");

header('Content-Type: application/json');

$db = new DBAsync();
$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

if ($numUsr <= 0) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

// LEER EL JSON DEL BODY
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (empty($data) {
    echo json_encode(['success' => false, 'error' => 'Datos vacíos']);
    exit;
}

$presupuesto_id = intval($data['presupuesto_id'] ?? 0);
$usuario_id = intval($data['usuario_id'] ?? $numUsr);

if ($presupuesto_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de presupuesto inválido']);
    exit;
}

try {
    // INICIAR TRANSACCIÓN
    $db->beginTransaction();
    
    // 1. LIMPIAR CARRITO ACTUAL
    $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$usuario_id]
    );
    
    // 2. OBTENER DETALLES DEL PRESUPUESTO
    $detalles = $db->consultaSegura(
        "SELECT product_code, cantidad, precio, tiempo_entrega 
         FROM presupuesto_detail 
         WHERE pres_idx = $1",
        [$presupuesto_id]
    );
    
    if (empty($detalles)) {
        throw new Exception('No se encontraron productos en el presupuesto seleccionado');
    }
    
    // 3. INSERTAR PRODUCTOS EN EL CARRITO
    $productosCargados = 0;
    foreach ($detalles as $detalle) {
        // Verificar que el producto existe
        $producto = $db->consultaSegura(
            "SELECT code FROM productos WHERE code = $1",
            [$detalle->product_code]
        );
        
        if (!empty($producto)) {
            $db->consultaSegura(
                "INSERT INTO presupuesto_carrito 
                (user_num, product_code, cantidad, precio, tiempo_entrega) 
                VALUES ($1, $2, $3, $4, $5)",
                [
                    $usuario_id,
                    $detalle->product_code,
                    $detalle->cantidad,
                    $detalle->precio,
                    $detalle->tiempo_entrega
                ]
            );
            $productosCargados++;
        }
    }
    
    // CONFIRMAR TRANSACCIÓN
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Presupuesto precargado correctamente',
        'productos_cargados' => $productosCargados,
        'total_productos' => count($detalles)
    ]);
    
} catch (Exception $e) {
    // REVERTIR EN CASO DE ERROR
    $db->rollback();
    
    error_log("Error en precargarPresupuestoCarrito: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>