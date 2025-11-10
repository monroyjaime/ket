<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TEMPORAL: No usar JSON header para ver debug
// header('Content-Type: application/json');

session_start();

echo "<pre>";
echo "=== INICIO GUARDAR PRESUPUESTO ===\n";

try {
    // DEBUG: Verificar si el archivo existe
    $db_path = "../../php/dbcat_async.php";
    echo "Buscando archivo: $db_path\n";
    
    if (!file_exists($db_path)) {
        throw new Exception("Archivo dbcat_async.php no encontrado en: $db_path");
    }
    
    // Incluir el archivo
    require_once($db_path);
    echo "✅ dbcat_async.php incluido correctamente\n";
    
    // Crear instancia de DB
    $db = new DBAsync();
    echo "✅ Instancia DB creada\n";
    
    // Leer input
    $input = file_get_contents('php://input');
    echo "Input recibido: " . strlen($input) . " caracteres\n";
    
    if (empty($input)) {
        throw new Exception('Input vacío');
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error JSON: ' . json_last_error_msg());
    }
    
    if (empty($data)) {
        throw new Exception('Data vacía después de decode');
    }
    
    echo "✅ JSON decodificado correctamente\n";
    echo "Número de productos: " . count($data['productos'] ?? []) . "\n";
    echo "Descuento texto: '" . ($data['descuento_texto'] ?? 'NO') . "'\n";
    echo "Descuento monto: " . ($data['descuento_monto'] ?? 'NO') . "\n";
    
    // Validar usuario
    $numUsr = $_SESSION['usr_num'] ?? -1;
    echo "Usuario en sesión: $numUsr\n";
    
    if ($numUsr <= 0) {
        throw new Exception('Usuario no autenticado');
    }
    
    // Validar cliente
    if (empty($data['cliente'])) {
        throw new Exception('Cliente no especificado');
    }
    
    echo "Consultando cliente: " . $data['cliente'] . "\n";
    
    $clienteData = $db->consultaSegura(
        "SELECT code, full_name FROM cliente WHERE num = $1", 
        [$data['cliente']]
    );
    
    if (empty($clienteData)) {
        throw new Exception('Cliente no encontrado: ' . $data['cliente']);
    }
    
    $clienteCode = $clienteData[0]->code;
    echo "Cliente encontrado: $clienteCode\n";
    
    // Generar número
    $presupuestoNum = $db->consultaSegura(
        "SELECT COALESCE(MAX(presupuesto_num), 0) + 1 as next_num FROM presupuesto_gen"
    );
    $numeroPresupuesto = $presupuestoNum[0]->next_num;
    echo "Número de presupuesto: $numeroPresupuesto\n";
    
    // Preparar valores descuento/recargo
    $descuentoTexto = $data['descuento_texto'] ?? '';
    $descuentoMonto = floatval($data['descuento_monto'] ?? 0);
    $recargoTexto = $data['recargo_texto'] ?? '';
    $recargoMonto = floatval($data['recargo_monto'] ?? 0);
    
    echo "Insertando en BD con:\n";
    echo " - descuento_texto: '$descuentoTexto'\n";
    echo " - descuento_monto: $descuentoMonto\n";
    echo " - recargo_texto: '$recargoTexto'\n";
    echo " - recargo_monto: $recargoMonto\n";
    
    // Insertar
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
        throw new Exception('Error al crear presupuesto');
    }
    
    $presupuestoIdx = $presupuestoGen[0]->idx;
    echo "✅ Presupuesto creado con ID: $presupuestoIdx\n";
    
    // Insertar detalles
    $productosCount = 0;
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
        $productosCount++;
    }
    echo "✅ $productosCount productos insertados\n";
    
    // Limpiar carrito
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
        'message' => 'Presupuesto guardado correctamente con descuentos/recargos'
    ]);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

echo "=== FIN ===\n";
echo "</pre>";
?>