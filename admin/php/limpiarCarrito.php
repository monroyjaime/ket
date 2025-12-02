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

try {
    // Limpiar carrito del usuario actual
    $result = $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    
    // Verificar si se limpió correctamente
    $carritoActual = $db->consultaSegura(
        "SELECT COUNT(*) as count FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    
    $countAfter = $carritoActual[0]->count ?? 0;
    
    if ($countAfter == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Carrito limpiado correctamente',
            'productos_eliminados' => true
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se pudieron eliminar todos los productos',
            'productos_restantes' => $countAfter
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en limpiarCarrito.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>