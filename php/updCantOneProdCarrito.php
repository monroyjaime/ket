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

$code = $_POST['code'] ?? '';
$cantidad = intval($_POST['cantidad'] ?? 0);

if (empty($code)) {
    echo '0';
    exit;
}

try {
    // Verificar si el producto ya está en el carrito
    $existe = $db->consultaSegura(
        "SELECT COUNT(*) as count FROM presupuesto_carrito WHERE user_num = $1 AND product_code = $2",
        [$numUsr, $code]
    );
    
    if ($existe[0]->count > 0) {
        if ($cantidad > 0) {
            // Actualizar cantidad
            $result = $db->consultaSegura(
                "UPDATE presupuesto_carrito SET cantidad = $1 WHERE user_num = $2 AND product_code = $3",
                [$cantidad, $numUsr, $code]
            );
        } else {
            // Eliminar si la cantidad es 0
            $result = $db->consultaSegura(
                "DELETE FROM presupuesto_carrito WHERE user_num = $1 AND product_code = $2",
                [$numUsr, $code]
            );
        }
    } else {
        if ($cantidad > 0) {
            // Insertar nuevo registro
            $result = $db->consultaSegura(
                "INSERT INTO presupuesto_carrito (user_num, product_code, cantidad, precio, tiempo_entrega) VALUES ($1, $2, $3, 0, 0)",
                [$numUsr, $code, $cantidad]
            );
        }
    }
    
    echo '1';
    
} catch (Exception $e) {
    error_log("Error en updCantOneProdCarrito: " . $e->getMessage());
    echo '0';
}
?>