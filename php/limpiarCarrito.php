<?php
// limpiarCarrito.php - RUTA: /php/limpiarCarrito.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// IMPORTANTE: Ajustar la ruta del include según dónde esté dbcat_async.php
// Como este archivo está en /php/, y dbcat_async.php está en /php/
require_once("dbcat_async.php");

header('Content-Type: application/json');

// Para debugging
error_log("limpiarCarrito.php llamado - SESSION: " . print_r($_SESSION, true));

$db = new DBAsync();
$numUsr = isset($_SESSION['usr_num']) ? intval($_SESSION['usr_num']) : -1;

if ($numUsr <= 0) {
    error_log("Usuario no autenticado: $numUsr");
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado: ' . $numUsr]);
    exit;
}

try {
    // Para debugging
    error_log("Intentando limpiar carrito para usuario: $numUsr");
    
    // Verificar si el usuario tiene productos en el carrito primero
    $carritoActual = $db->consultaSegura(
        "SELECT COUNT(*) as count FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    
    $countBefore = $carritoActual[0]->count ?? 0;
    error_log("Productos en carrito antes: $countBefore");
    
    if ($countBefore == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'El carrito ya estaba vacío',
            'productos_eliminados' => 0
        ]);
        exit;
    }
    
    // Limpiar carrito del usuario actual
    $result = $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    
    // Verificar si se limpió correctamente
    $carritoDespues = $db->consultaSegura(
        "SELECT COUNT(*) as count FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    
    $countAfter = $carritoDespues[0]->count ?? 0;
    error_log("Productos en carrito después: $countAfter");
    
    if ($countAfter == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Carrito limpiado correctamente',
            'productos_eliminados' => $countBefore,
            'productos_restantes' => 0
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se pudieron eliminar todos los productos',
            'productos_eliminados' => $countBefore - $countAfter,
            'productos_restantes' => $countAfter
        ]);
    }
    
} catch (Exception $e) {
    $errorMsg = "Error en limpiarCarrito.php: " . $e->getMessage();
    error_log($errorMsg);
    echo json_encode([
        'success' => false,
        'error' => $errorMsg
    ]);
}
?>