<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TEMPORAL: Mostrar debug siempre
echo "<pre>";
echo "=== DEBUG GUARDAR PRESUPUESTO ===\n";

session_start();
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
echo "Raw input recibido:\n";
echo $json_input . "\n\n";

if (!empty($json_input)) {
    $data = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "ERROR decodificando JSON: " . json_last_error_msg() . "\n";
        exit;
    }
    
    echo "Datos JSON decodificados:\n";
    print_r($data);
    
    echo "\nCampos específicos:\n";
    echo "descuento_texto: '" . ($data['descuento_texto'] ?? 'NO EXISTE') . "'\n";
    echo "descuento_monto: " . ($data['descuento_monto'] ?? 'NO EXISTE') . "\n";
    echo "recargo_texto: '" . ($data['recargo_texto'] ?? 'NO EXISTE') . "'\n";
    echo "recargo_monto: " . ($data['recargo_monto'] ?? 'NO EXISTE') . "\n";
    
    // CONTINUAR CON EL GUARDADO NORMAL
    echo "\n=== CONTINUANDO CON GUARDADO NORMAL ===\n";
    
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
    
    // Generar número de presupuesto
    $presupuestoNum = $db->consultaSegura(
        "SELECT COALESCE(MAX(presupuesto_num), 0) + 1 as next_num FROM presupuesto_gen"
    );
    
    $numeroPresupuesto = $presupuestoNum[0]->next_num;
    
    // Preparar valores para la inserción
    $descuentoTexto = $data['descuento_texto'] ?? '';
    $descuentoMonto = floatval($data['descuento_monto'] ?? 0);
    $recargoTexto = $data['recargo_texto'] ?? '';
    $recargoMonto = floatval($data['recargo_monto'] ?? 0);
    
    echo "Valores a insertar en BD:\n";
    echo "descuento_texto: '$descuentoTexto'\n";
    echo "descuento_monto: $descuentoMonto\n";
    echo "recargo_texto: '$recargoTexto'\n";
    echo "recargo_monto: $recargoMonto\n";
    
    // Insertar en presupuesto_gen
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
    
    echo "=== PRESUPUESTO GUARDADO EXITOSAMENTE ===\n";
    echo "ID: $presupuestoIdx\n";
    echo "Número: $numeroPresupuesto\n";
    
    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'presupuesto_id' => $presupuestoIdx,
        'presupuesto_num' => $numeroPresupuesto,
        'presupuesto_num_valery' => $data['numero'],
        'message' => 'Presupuesto guardado correctamente'
    ]);
    
} else {
    echo "ERROR: No se recibió JSON en el body\n";
    echo "Contenido de POST:\n";
    print_r($_POST);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Datos del presupuesto vacíos o inválidos'
    ]);
}

echo "=== FIN DEBUG ===\n";
echo "</pre>";
?>