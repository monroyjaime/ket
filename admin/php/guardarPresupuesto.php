<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TEMPORALMENTE comentar esta línea para ver el debug
// header('Content-Type: application/json');

session_start();

// DEBUG INICIAL - Esto debe aparecer SIEMPRE
echo "=== DEBUG INICIAL - ARCHIVO NUEVO ===<br>";

require_once("../../php/dbcat_async.php");

$db = new DBAsync();
$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

echo "Usuario: $numUsr<br>";

if ($numUsr <= 0) {
    echo "ERROR: Usuario no autenticado<br>";
    exit;
}

// LEER EL JSON DIRECTAMENTE DEL BODY
$json_input = file_get_contents('php://input');
echo "Longitud del JSON: " . strlen($json_input) . " caracteres<br>";

if (empty($json_input)) {
    echo "ERROR: JSON vacío<br>";
    exit;
}

$data = json_decode($json_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR decodificando JSON: " . json_last_error_msg() . "<br>";
    exit;
}

if (empty($data)) {
    echo "ERROR: Datos vacíos después del decode<br>";
    exit;
}

echo "✅ JSON decodificado correctamente<br>";
echo "Cliente: '" . ($data['cliente'] ?? 'NO') . "'<br>";
echo "Productos: " . count($data['productos'] ?? []) . "<br>";
echo "Usuario: " . ($data['usuario'] ?? 'NO') . "<br>";

try {
    $clienteInput = $data['cliente'];
    
    // PROCESAR CLIENTE (numérico o texto)
    if (is_numeric($clienteInput)) {
        // Cliente existente
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
        echo "Cliente existente: $clienteNombre ($clienteCode)<br>";
        
    } else {
        // Cliente nuevo (texto)
        $clienteNombre = trim($clienteInput);
        if (empty($clienteNombre)) {
            throw new Exception('Nombre de cliente vacío');
        }
        
        $clienteCode = 'TEMP_' . date('YmdHis') . '_' . substr(md5($clienteNombre), 0, 8);
        echo "Cliente nuevo: '$clienteNombre' -> $clienteCode<br>";
    }
    
    // Generar número de presupuesto
    $presupuestoNum = $db->consultaSegura(
        "SELECT COALESCE(MAX(presupuesto_num), 0) + 1 as next_num FROM presupuesto_gen"
    );
    
    $numeroPresupuesto = $presupuestoNum[0]->next_num;
    echo "Número presupuesto: $numeroPresupuesto<br>";
    
    // Preparar valores
    $descuentoTexto = $data['descuento_texto'] ?? '';
    $descuentoMonto = floatval($data['descuento_monto'] ?? 0);
    $recargoTexto = $data['recargo_texto'] ?? '';
    $recargoMonto = floatval($data['recargo_monto'] ?? 0);
    
    echo "Descuento: $descuentoMonto, Recargo: $recargoMonto<br>";
    
    // INSERTAR EN BD
    echo "Insertando en presupuesto_gen...<br>";
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
    echo "✅ Presupuesto creado ID: $presupuestoIdx<br>";
    
    // Insertar productos
    $contador = 0;
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
        $contador++;
    }
    echo "✅ $contador productos insertados<br>";
    
    // Limpiar carrito
    $db->consultaSegura(
        "DELETE FROM presupuesto_carrito WHERE user_num = $1",
        [$numUsr]
    );
    echo "✅ Carrito limpiado<br>";
    
    echo "🎉 PRESUPUESTO GUARDADO EXITOSAMENTE<br>";
    
    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'presupuesto_id' => $presupuestoIdx,
        'presupuesto_num' => $numeroPresupuesto,
        'presupuesto_num_valery' => $data['numero'],
        'cliente_nombre' => $clienteNombre,
        'message' => 'Presupuesto guardado correctamente'
    ]);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

echo "=== FIN ===<br>";
?>