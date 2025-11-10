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

// LEER EL JSON DIRECTAMENTE DEL BODY
$json_input = file_get_contents('php://input');
echo "Raw input:\n";
echo $json_input . "\n\n";

if (!empty($json_input)) {
    $data = json_decode($json_input, true);
    
    echo "Datos JSON decodificados:\n";
    print_r($data);
    
    echo "\nCampos específicos:\n";
    echo "descuento_texto: '" . ($data['descuento_texto'] ?? 'NO EXISTE') . "'\n";
    echo "descuento_monto: " . ($data['descuento_monto'] ?? 'NO EXISTE') . "\n";
    echo "recargo_texto: '" . ($data['recargo_texto'] ?? 'NO EXISTE') . "'\n";
    echo "recargo_monto: " . ($data['recargo_monto'] ?? 'NO EXISTE') . "\n";
    
} else {
    echo "ERROR: No se recibió JSON en el body\n";
    echo "Contenido de POST:\n";
    print_r($_POST);
    echo "Contenido de GET:\n";
    print_r($_GET);
}

echo "=== FIN DEBUG ===\n";
echo "</pre>";
exit;
?>