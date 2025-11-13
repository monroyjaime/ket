<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../../php/dbcat_async.php");

header('Content-Type: application/json');

$db = new DBAsync();

try {
    // Obtener el próximo número de presupuesto
    $resultado = $db->consultaSegura(
        "SELECT COALESCE(MAX(presupuesto_num), 0) + 1 as proximo_numero FROM presupuesto_gen"
    );
    
    if (!empty($resultado)) {
        $proximoNumero = $resultado[0]->proximo_numero;
        echo json_encode([
            'success' => true,
            'proximo_numero' => $proximoNumero
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo obtener el próximo número'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>