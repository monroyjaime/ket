<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TEMPORAL: Deshabilitar JSON para ver debug completo
// header('Content-Type: application/json');

session_start();

echo "<pre>";
echo "=== DEBUG GUARDAR PRESUPUESTO ===\n";

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
echo "JSON recibido:\n";
echo $json_input . "\n\n";

if (empty($json_input)) {
    echo "ERROR: JSON vacío\n";
    exit;
}

$data = json_decode($json_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR decodificando JSON: " . json_last_error_msg() . "\n";
    exit;
}

if (empty($data)) {
    echo "ERROR: Datos vacíos después del decode\n";
    exit;
}

echo "✅ JSON decodificado correctamente\n";
echo "Cliente recibido: '" . ($data['cliente'] ?? 'NO_RECIBIDO') . "'\n";
echo "Tipo de cliente: " . gettype($data['cliente'] ?? 'NULL') . "\n";
echo "Número de productos: " . count($data['productos'] ?? []) . "\n";

// CONTINUAR CON EL PROCESO NORMAL...
try {
    $clienteCode = '';
    $clienteNombre = '';
    $clienteInput = $data['cliente'];
    
    echo "Procesando cliente: '$clienteInput'\n";
    
    // VERIFICAR SI ES NUMÉRICO (cliente existente) O TEXTO (cliente nuevo)
    if (is_numeric($clienteInput)) {
        echo "Cliente numérico (existente)\n";
        $clienteNum = intval($clienteInput);
        $clienteData = $db->consultaSegura(
            "SELECT code, full_name FROM cliente WHERE num = $1", 
            [$clienteNum]
        );
        
        if (empty($clienteData)) {
            throw new Exception('Cliente no encontrado');
        }
        
        $clienteCode = $clienteData[0]->code;
        $clienteNombre = $clienteData[0]->full_name;
        
    } else {
        // CLIENTE NUEVO (texto libre)
        echo "Cliente texto (nuevo)\n";
        $clienteNombre = trim($clienteInput);
        if (empty($clienteNombre)) {
            throw new Exception('Nombre de cliente vacío');
        }
        
        // Generar código temporal único
        $clienteCode = 'TEMP_' . date('YmdHis') . '_' . substr(md5($clienteNombre), 0, 8);
        echo "Código temporal generado: $clienteCode\n";
    }
    
    // Generar número de presupuesto
    $presupuestoNum = $db->consultaSegura(
        "SELECT COALESCE(MAX(presupuesto_num), 0) + 1 as next_num FROM presupuesto_gen"
    );
    
    $numeroPresupuesto = $presupuestoNum[0]->next_num;
    echo "Número de presupuesto: $numeroPresupuesto\n";
    
    // Preparar valores para descuento/recargo
    $descuentoTexto = $data['descuento_texto'] ?? '';
    $descuentoMonto = floatval($data['descuento_monto'] ?? 0);
    $recargoTexto = $data['recargo_texto'] ?? '';
    $recargoMonto = floatval($data['recargo_monto'] ?? 0);
    
    echo "Descuento: $descuentoMonto, Recargo: $recargoMonto\n";
    
    // Insertar en presupuesto_gen
    echo "Insertando en base de datos...\n";
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
            $descuentoTexto,
            $descuentoMonto,
            $recargoTexto,
            $recargoMonto
        ]
    );
    
    if (empty($presupuestoGen)) {
        throw new Exception('Error al crear el presupuesto general');
    }
    
    $presupuestoIdx = $presupuestoGen[0]->idx;
    echo "✅ Presupuesto creado con ID: $presupuestoIdx\n";
    
    // Insertar detalles del presupuesto
    $contadorProductos = 0;
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
        $contadorProductos++;
    }
    echo "✅ $contadorProductos productos insertados\n";
    
    // Limpiar el carrito después de guardar
    $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    echo "✅ Carrito limpiado\n";
    
    echo "✅ PRESUPUESTO GUARDADO EXITOSAMENTE\n";
    
    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'presupuesto_id' => $presupuestoIdx,
        'presupuesto_num' => $numeroPresupuesto,
        'presupuesto_num_valery' => $data['numero'],
        'cliente_nombre' => $clienteNombre,
        'cliente_tipo' => is_numeric($clienteInput) ? 'existente' : 'nuevo',
        'message' => 'Presupuesto guardado correctamente'
    ]);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

echo "=== FIN DEBUG ===\n";
echo "</pre>";
?>