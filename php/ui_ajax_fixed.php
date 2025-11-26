<?php
// ui_ajax_fixed.php - Con dbcat limpio
header('Content-Type: application/json');

try {
    // Usar la versión limpia
    require_once("dbcat_web.php");
    $db = new DBWeb();
    
    $result = $db->consultas("SELECT COUNT(*) as total FROM productos");
    $total = $result[0]->total;
    
    $result = $db->consultas("SELECT COUNT(*) as con_stock FROM productos WHERE current_stock > 0");
    $con_stock = $result[0]->con_stock;
    
    $result = $db->consultas("SELECT MAX(updated_at) as ultima FROM productos");
    $ultima = $result[0]->ultima ?? 'Nunca';
    
    echo json_encode([
        'success' => true,
        'total_productos' => (int)$total,
        'con_stock' => (int)$con_stock,
        'ultima_actualizacion' => $ultima
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>