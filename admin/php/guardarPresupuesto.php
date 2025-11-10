<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TEMPORAL: Mostrar debug siempre
echo "<pre>";
echo "=== DEBUG GUARDAR PRESUPUESTO ===\n";

session_start();
require_once("../../php/dbcat_async.php");

$db = new DBAsync();
$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

echo "Usuario: $numUsr\n";

if ($numUsr <= 0) {
    echo "ERROR: Usuario no autenticado\n";
    exit;
}

if (isset($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
    
    echo "Datos recibidos:\n";
    print_r($data);
    
    echo "\nCampos específicos:\n";
    echo "descuento_texto: '" . ($data['descuento_texto'] ?? 'NO EXISTE') . "'\n";
    echo "descuento_monto: " . ($data['descuento_monto'] ?? 'NO EXISTE') . "\n";
    echo "recargo_texto: '" . ($data['recargo_texto'] ?? 'NO EXISTE') . "'\n";
    echo "recargo_monto: " . ($data['recargo_monto'] ?? 'NO EXISTE') . "\n";
    
    // Verificar tipos
    echo "\nTipos de datos:\n";
    echo "descuento_texto tipo: " . gettype($data['descuento_texto'] ?? 'null') . "\n";
    echo "descuento_monto tipo: " . gettype($data['descuento_monto'] ?? 'null') . "\n";
    echo "recargo_texto tipo: " . gettype($data['recargo_texto'] ?? 'null') . "\n";
    echo "recargo_monto tipo: " . gettype($data['recargo_monto'] ?? 'null') . "\n";
    
} else {
    echo "ERROR: No se recibió data en POST\n";
    echo "Contenido de POST:\n";
    print_r($_POST);
}

echo "=== FIN DEBUG ===\n";
echo "</pre>";
exit;
?>