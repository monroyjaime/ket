<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Respuesta inicial para debug
header('Content-Type: application/json');

try {
    // Verificar sesión primero
    if (!isset($_SESSION['usr_num']) || $_SESSION['usr_num'] <= 0) {
        throw new Exception('Usuario no autenticado');
    }
    
    $numUsr = $_SESSION['usr_num'];
    
    // Leer JSON del body
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (!$data || !isset($data['presupuesto_id'])) {
        throw new Exception('Datos inválidos');
    }
    
    $presupuesto_id = intval($data['presupuesto_id']);
    
    if ($presupuesto_id <= 0) {
        throw new Exception('ID de presupuesto inválido');
    }
    
    // Incluir DB después de las validaciones básicas
    require_once("../../php/dbcat_async.php");
    $db = new DBAsync();
    
    // 1. Limpiar carrito actual
    $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    
    // 2. Obtener detalles del presupuesto
    $detalles = $db->consultaSegura(
        "SELECT product_code, cantidad, precio, tiempo_entrega 
         FROM presupuesto_detail 
         WHERE pres_idx = $1",
        [$presupuesto_id]
    );
    
    if (empty($detalles)) {
        throw new Exception('No se encontraron productos en el presupuesto');
    }
    
    // 3. Insertar productos en el carrito (manteniendo precios históricos)
    $productosCargados = 0;
    $productosNoEncontrados = 0;
    
    foreach ($detalles as $detalle) {
        // Verificar que el producto existe antes de insertar
        $productoExiste = $db->consultaSegura(
            "SELECT code FROM productos WHERE code = $1",
            [$detalle->product_code]
        );
        
        if (!empty($productoExiste)) {
            // Insertar con precio histórico del presupuesto original
            $db->consultaSegura(
                "INSERT INTO presupuesto_carrito 
                (user_num, product_code, cantidad, precio, tiempo_entrega) 
                VALUES ($1, $2, $3, $4, $5)",
                [
                    $numUsr,
                    $detalle->product_code,
                    $detalle->cantidad,
                    $detalle->precio,  // Precio histórico del presupuesto original
                    $detalle->tiempo_entrega
                ]
            );
            $productosCargados++;
        } else {
            // Producto no encontrado en la base de datos actual
            $productosNoEncontrados++;
            error_log("Producto no encontrado al precargar: " . $detalle->product_code);
        }
    }
    
    // Mensaje informativo
    $mensaje = 'Presupuesto precargado correctamente';
    if ($productosNoEncontrados > 0) {
        $mensaje .= ". " . $productosNoEncontrados . " productos no se encontraron en el catálogo actual.";
    }
    
    // Éxito
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'productos_cargados' => $productosCargados,
        'total_productos' => count($detalles),
        'productos_no_encontrados' => $productosNoEncontrados
    ]);
    
} catch (Exception $e) {
    // Error en formato JSON
    error_log("Error en precargarPresupuestoCarrito: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>