<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../../php/dbcat_async.php");

header('Content-Type: application/json');

// SIMPLIFICADO - Sin transacciones complejas
try {
    $db = new DBAsync();
    $numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

    if ($numUsr <= 0) {
        throw new Exception('Usuario no autenticado');
    }

    // LEER JSON
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);

    if (empty($data)) {
        throw new Exception('Datos vacíos');
    }

    $presupuesto_id = intval($data['presupuesto_id'] ?? 0);
    $usuario_id = intval($data['usuario_id'] ?? $numUsr);

    if ($presupuesto_id <= 0) {
        throw new Exception('ID de presupuesto inválido');
    }

    // 1. LIMPIAR CARRITO ACTUAL (SIMPLE)
    $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$usuario_id]
    );

    // 2. OBTENER DETALLES
    $detalles = $db->consultaSegura(
        "SELECT product_code, cantidad, precio, tiempo_entrega 
         FROM presupuesto_detail 
         WHERE pres_idx = $1",
        [$presupuesto_id]
    );

    if (empty($detalles)) {
        throw new Exception('No se encontraron productos en el presupuesto');
    }

    // 3. INSERTAR PRODUCTOS
    $productosCargados = 0;
    foreach ($detalles as $detalle) {
        // Insertar directamente sin verificar existencia (más rápido)
        $db->consultaSegura(
            "INSERT INTO presupuesto_carrito 
            (user_num, product_code, cantidad, precio, tiempo_entrega) 
            VALUES ($1, $2, $3, $4, $5)
            ON CONFLICT (user_num, product_code) DO UPDATE SET
            cantidad = EXCLUDED.cantidad,
            precio = EXCLUDED.precio,
            tiempo_entrega = EXCLUDED.tiempo_entrega",
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

    echo json_encode([
        'success' => true,
        'message' => 'Presupuesto precargado correctamente',
        'productos_cargados' => $productosCargados,
        'total_productos' => count($detalles)
    ]);

} catch (Exception $e) {
    // Log del error
    error_log("Error en precargarPresupuestoCarrito: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>