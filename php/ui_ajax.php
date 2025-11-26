<?php
// ui_ajax.php - SOLO para AJAX, nada de HTML

// LIMPIAR ABSOLUTAMENTE TODO
while (ob_get_level()) ob_end_clean();

if ($_POST['accion'] === 'estadisticas') {
    header('Content-Type: application/json');
    
    try {
        // Incluir sin output de ninguna clase
        ob_start();
        require_once("dbcat.php");
        $potential_output = ob_get_clean();
        
        // Si hay output, es un problema pero continuamos
        if (!empty($potential_output)) {
            error_log("Output no deseado en dbcat: " . $potential_output);
        }
        
        $db = new DB();
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
    exit;
}

// Si llegamos aquí, no era una request AJAX válida
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Acción no válida']);