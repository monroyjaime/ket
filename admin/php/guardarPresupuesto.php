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
    // Obtener datos del POST
    if (isset($_POST['data'])) {
        $data = json_decode($_POST['data'], true);
    } else {
        throw new Exception('Datos del presupuesto vacíos o inválidos');
    }
    
    if (empty($data)) {
        throw new Exception('Datos del presupuesto vacíos después del decode');
    }
    
    // DEBUG: Log para ver qué datos llegan
    error_log("Datos recibidos en guardarPresupuesto: " . print_r($data, true));
    
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
    
    // ✅ OPCIÓN A: Generar un número de presupuesto numérico único
    $presupuestoNum = $db->consultaSegura(
        "SELECT COALESCE(MAX(presupuesto_num), 0) + 1 as next_num FROM presupuesto_gen"
    );
    
    $numeroPresupuesto = $presupuestoNum[0]->next_num;
    
    // DEBUG: Verificar campos de descuento/recargo
    error_log("Campos descuento/recargo:");
    error_log("descuento_texto: " . ($data['descuento_texto'] ?? 'NO ENVIADO'));
    error_log("descuento_monto: " . ($data['descuento_monto'] ?? 'NO ENVIADO'));
    error_log("recargo_texto: " . ($data['recargo_texto'] ?? 'NO ENVIADO'));
    error_log("recargo_monto: " . ($data['recargo_monto'] ?? 'NO ENVIADO'));
    
    // NUEVO: Insertar con campos de descuento y recargo - CORREGIDOS LOS NOMBRES
    $presupuestoGen = $db->consultaSegura(
        "INSERT INTO presupuesto_gen 
        (user_num, hora, archivado, presupuesto_num, status, fecha, num_valery, comentarios, cliente, 
         descuento_texto, descuento_monto, recargo_texto, recargo_monto) 
        VALUES ($1, CURRENT_TIME, 0, $2, 1, CURRENT_DATE, $3, $4, $5, $6, $7, $8, $9) 
        RETURNING idx",
        [
            $data['usuario'],
            $numeroPresupuesto,
            $data['numero'],
            $data['comentario'] ?? '',
            $clienteCode,
            // NUEVO: Campos de descuento y recargo - USAR VALORES POR DEFECTO SI NO EXISTEN
            $data['descuento_texto'] ?? '',
            floatval($data['descuento_monto'] ?? 0),
            $data['recargo_texto'] ?? '',
            floatval($data['recargo_monto'] ?? 0)
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
        'presupuesto_num' => $numeroPresupuesto,
        'presupuesto_num_valery' => $data['numero'],
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