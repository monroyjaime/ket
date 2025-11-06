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
$tiempo_entrega = intval($_POST['tiempo_entrega'] ?? 0);

if (empty($code)) {
    echo '0';
    exit;
}

try {
    // Actualizar tiempo de entrega
    $result = $db->consultaSegura(
        "UPDATE presupuesto_carrito SET tiempo_entrega = $1 WHERE user_num = $2 AND product_code = $3",
        [$tiempo_entrega, $numUsr, $code]
    );
    
    echo '1';
    
} catch (Exception $e) {
    error_log("Error en updTiempoOneProdCarrito: " . $e->getMessage());
    echo '0';
}
?>