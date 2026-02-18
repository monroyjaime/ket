<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("dbcat_async.php");

$db = new DBAsync();
$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

if ($numUsr <= 0) {
    echo json_encode([]);
    exit;
}

try {
    // Obtener productos del carrito con información completa
    $carrito = $db->consultaSegura(
        "SELECT pc.product_code, pc.cantidad, pc.precio, pc.tiempo_entrega,pc.orden,
                p.name, p.unit, p.current_stock, p.stock_lleg, p.cost_max, p.cost_mayor, p.cost_min, p.costo, p.relacionado 
         FROM presupuesto_carrito pc
         INNER JOIN productos p ON pc.product_code = p.code
         WHERE pc.user_num = $1 
         ORDER BY pc.orden"
    );
    
    $resultado = [];
    
    foreach ($carrito as $item) {
        $obj = new stdClass();
        $obj->code = $item->product_code;
        $obj->name = $item->name;
        $obj->unidad = $item->unit;
        $obj->stock = intval($item->current_stock);
        $obj->llegando = intval($item->stock_lleg);
        $obj->prec_min = floatval($item->cost_max);
        $obj->prec_may = floatval($item->cost_mayor);
        $obj->prec_3 = floatval($item->cost_min);      // Precio 3 - NUEVO
        $obj->costo = floatval($item->costo);
        $obj->cantidad = intval($item->cantidad);
        $obj->precio = floatval($item->precio);
        $obj->relacionado = $item->relacionado; // NUEVO CAMPO
        $obj->tiempo_entrega = intval($item->tiempo_entrega);
        
        $resultado[] = $obj;
    }
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    error_log("Error en getCarritoCurrentData: " . $e->getMessage());
    echo json_encode([]);
}
?>