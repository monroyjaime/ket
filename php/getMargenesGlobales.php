<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("dbcat_async.php");

$db = new DBAsync();

try {
    $margenes = $db->consultaSegura("SELECT ganancia_min_glob, descuento_max_glob FROM all_ket_values LIMIT 1");
    
    if (!empty($margenes)) {
        echo json_encode([
            'ganancia_min_glob' => floatval($margenes[0]->ganancia_min_glob),
            'descuento_max_glob' => floatval($margenes[0]->descuento_max_glob)
        ]);
    } else {
        echo json_encode([
            'ganancia_min_glob' => 0,
            'descuento_max_glob' => 0
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en getMargenesGlobales: " . $e->getMessage());
    echo json_encode([
        'ganancia_min_glob' => 0,
        'descuento_max_glob' => 0
    ]);
}
?>