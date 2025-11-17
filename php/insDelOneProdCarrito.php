<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("dbcat_async.php");

$db = new DBAsync();
$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

if ($numUsr <= 0) {
    echo '0';
    exit;
}

$action = $_POST['action'] ?? 0; // 1=agregar, 0=eliminar
$code = $_POST['code'] ?? '';
$precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0; // NUEVO: Recibir el precio

if (empty($code)) {
    echo '0';
    exit;
}

try {
    if ($action == 1) {
        // AGREGAR producto al carrito CON PRECIO
        $existe = $db->consultaSegura(
            "SELECT * FROM presupuesto_carrito WHERE user_num = $1 AND product_code = $2", 
            [$numUsr, $code]
        );
        
        if (empty($existe)) {
            // INSERT con el precio recibido
            $db->consultaSegura(
                "INSERT INTO presupuesto_carrito (user_num, product_code, cantidad, precio, tiempo_entrega) VALUES ($1, $2, 1, $3, 0)",
                [$numUsr, $code, $precio] // $3 es el precio
            );
            echo '1';
        } else {
            // Si ya existe, actualizar el precio también
            $db->consultaSegura(
                "UPDATE presupuesto_carrito SET cantidad = 1, precio = $3 WHERE user_num = $1 AND product_code = $2",
                [$numUsr, $code, $precio] // $3 es el precio
            );
            echo '1';
        }
        
    } else if ($action == 0) {
        // ELIMINAR producto del carrito
        $db->consultaSegura(
            "DELETE FROM presupuesto_carrito WHERE user_num = $1 AND product_code = $2",
            [$numUsr, $code]
        );
        echo '1';
    } else {
        echo '0';
    }
    
} catch (Exception $e) {
    error_log("Error en insDelOneProdCarrito: " . $e->getMessage());
    echo '0';
}
?>