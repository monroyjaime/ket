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

$action = intval($_POST['action'] ?? 0); // 1=insert, 0=delete
$code = $_POST['code'] ?? '';

if (empty($code)) {
    echo '0';
    exit;
}

try {
    if ($action == 1) {
        // Insertar o actualizar producto en carrito
        $existe = $db->consultaSegura(
            "SELECT COUNT(*) as count FROM presupuesto_carrito WHERE user_num = $1 AND product_code = $2",
            [$numUsr, $code]
        );
        
        if ($existe[0]->count > 0) {
            // Ya existe, actualizar cantidad a 1 por si acaso
            $result = $db->consultaSegura(
                "UPDATE presupuesto_carrito SET cantidad = 1 WHERE user_num = $1 AND product_code = $2",
                [$numUsr, $code]
            );
        } else {
            // Insertar nuevo
            $result = $db->consultaSegura(
                "INSERT INTO presupuesto_carrito (user_num, product_code, cantidad, precio, tiempo_entrega) VALUES ($1, $2, 1, 0, 0)",
                [$numUsr, $code]
            );
        }
    } else {
        // Eliminar producto del carrito
        $result = $db->consultaSegura(
            "DELETE FROM presupuesto_carrito WHERE user_num = $1 AND product_code = $2",
            [$numUsr, $code]
        );
    }
    
    echo '1';
    
} catch (Exception $e) {
    error_log("Error en insDelOneProdCarrito: " . $e->getMessage());
    echo '0';
}
?>