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

// LEER EL JSON DIRECTAMENTE DEL BODY
$json_input = file_get_contents('php://input');

if (empty($json_input)) {
    echo json_encode(['success' => false, 'error' => 'Datos del presupuesto vacíos o inválidos']);
    exit;
}

$data = json_decode($json_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Error decodificando JSON: ' . json_last_error_msg()]);
    exit;
}

if (empty($data)) {
    echo json_encode(['success' => false, 'error' => 'Datos del presupuesto vacíos después del decode']);
    exit;
}

try {
    $clienteInput = $data['cliente'];
    $clienteParaGuardar = '';
    
    // PROCESAR CLIENTE (numérico o texto)
    if (is_numeric($clienteInput)) {
        // Cliente existente - Buscar código y nombre
        $clienteNum = intval($clienteInput);
        $clienteData = $db->consultaSegura(
            "SELECT code, full_name FROM cliente WHERE num = $1", 
            [$clienteNum]
        );
        
        if (empty($clienteData)) {
            throw new Exception('Cliente no encontrado: ' . $clienteNum);
        }
        
        $clienteCode = $clienteData[0]->code;
        $clienteNombre = $clienteData[0]->full_name;
        $clienteParaGuardar = $clienteCode . " --- " . $clienteNombre;
        
    } else {
        // Cliente nuevo (texto) - Usar "000" como código y el texto como nombre
        $clienteNombre = trim($clienteInput);
        if (empty($clienteNombre)) {
            throw new Exception('Nombre de cliente vacío');
        }
        
        $clienteParaGuardar = "000 --- " . $clienteNombre;
    }
    
    // Generar número de presupuesto
    $presupuestoNum = $db->consultaSegura(
        "SELECT COALESCE(MAX(presupuesto_num), 0) + 1 as next_num FROM presupuesto_gen"
    );
    
    $numeroPresupuesto = $presupuestoNum[0]->next_num;
    
    // Preparar valores
    $descuentoTexto = $data['descuento_texto'] ?? '';
    $descuentoMonto = floatval($data['descuento_monto'] ?? 0);
    $recargoTexto = $data['recargo_texto'] ?? '';
    $recargoMonto = floatval($data['recargo_monto'] ?? 0);
    
    // NUEVO: Obtener valores de IVA
    $ivaPorcentaje = floatval($data['iva_porcentaje'] ?? 0);
    $ivaMonto = floatval($data['iva_monto'] ?? 0);
    
    // INSERTAR EN BD - Incluir campos de IVA
    $presupuestoGen = $db->consultaSegura(
        "INSERT INTO presupuesto_gen 
        (user_num, hora, archivado, presupuesto_num, status, fecha, num_valery, comentarios, cliente, 
         descuento_texto, descuento_monto, recargo_texto, recargo_monto, iva_porcentaje, iva_monto) 
        VALUES ($1, CURRENT_TIME, 0, $2, 1, CURRENT_DATE, $3, $4, $5, $6, $7, $8, $9, $10, $11) 
        RETURNING idx",
        [
            $data['usuario'],
            $numeroPresupuesto,
            $data['numero'],
            $data['comentario'] ?? '',
            $clienteParaGuardar,
            $descuentoTexto,
            $descuentoMonto,
            $recargoTexto,
            $recargoMonto,
            $ivaPorcentaje,  // NUEVO
            $ivaMonto        // NUEVO
        ]
    );
    
    if (empty($presupuestoGen)) {
        throw new Exception('Error al crear el presupuesto general');
    }
    
    $presupuestoIdx = $presupuestoGen[0]->idx;
    
    // Insertar productos
    foreach ($data['productos'] as $producto) {
        $db->consultaSegura(
            "INSERT INTO presupuesto_detail 
            (pres_idx, cantidad, precio, tiempo_entrega, product_code, orden) 
            VALUES ($1, $2, $3, $4, $5, $6)",
            [
                $presupuestoIdx,
                $producto['cantidad'],
                $producto['precio'],
                $producto['tiempo_entrega'],
                $producto['code'],
                $producto['orden']  
            ]
        );
    }
    
    // Limpiar carrito
    $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    
    echo json_encode([
        'success' => true,
        'presupuesto_id' => $presupuestoIdx,
        'presupuesto_num' => $numeroPresupuesto,
        'presupuesto_num_valery' => $data['numero'],
        'cliente_guardado' => $clienteParaGuardar,
        'iva_porcentaje' => $ivaPorcentaje,  // NUEVO
        'iva_monto' => $ivaMonto,            // NUEVO
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