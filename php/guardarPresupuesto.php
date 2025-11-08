<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("dbcat_async.php");

header('Content-Type: application/json');

$db = new DBAsync();
$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

if ($numUsr <= 0) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

try {
    // Obtener datos del POST
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    $data = json_decode($input['data'] ?? '{}', true);
    
    if (empty($data)) {
        throw new Exception('Datos del presupuesto vacíos o inválidos');
    }
    
    // Obtener información del cliente
    $clienteData = $db->consultaSegura(
        "SELECT code, full_name FROM cliente WHERE num = $1", 
        [$data['cliente']]
    );
    
    if (empty($clienteData)) {
        throw new Exception('Cliente no encontrado');
    }
    
    $clienteCode = $clienteData[0]->code;
    $clienteNombre = $clienteData[0]->full_name;
    
    // Insertar en presupuesto_gen
    $presupuestoGen = $db->consultaSegura(
        "INSERT INTO presupuesto_gen 
        (user_num, hora, archivado, presupuesto_num, status, fecha, num_valery, comentarios, cliente) 
        VALUES ($1, CURRENT_TIME, 0, $2, 1, CURRENT_DATE, $3, $4, $5) 
        RETURNING idx",
        [
            $data['usuario'],
            $data['numero'],
            $data['numero'], // num_valery usa el mismo número
            $data['comentario'] ?? '',
            $clienteCode
        ]
    );
    
    if (empty($presupuestoGen)) {
        throw new Exception('Error al crear el presupuesto general');
    }
    
    $presupuestoIdx = $presupuestoGen[0]->idx;
    
    // Insertar detalles del presupuesto
    foreach ($data['productos'] as $producto) {
        $db->consultaSegura(
            "INSERT INTO presupuesto_detail 
            (pres_idx, cantidad, precio, tiempo_entrega, product_code) 
            VALUES ($1, $2, $3, $4, $5)",
            [
                $presupuestoIdx,
                $producto['cantidad'],
                $producto['precio'],
                $producto['tiempo_entrega'],
                $producto['code']
            ]
        );
    }
    
    // Limpiar el carrito después de guardar
    $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    
    echo json_encode([
        'success' => true,
        'presupuesto_id' => $presupuestoIdx,
        'message' => 'Presupuesto guardado correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en guardarPresupuesto: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>