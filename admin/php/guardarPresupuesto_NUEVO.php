<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// TEMPORAL: Deshabilitar JSON para ver debug
// header('Content-Type: application/json');

session_start();
require_once("../../php/dbcat_async.php");

$db = new DBAsync();
$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

echo "<pre>";
echo "=== DEBUG GUARDAR PRESUPUESTO NUEVO ===\n";
echo "Usuario: $numUsr\n";

if ($numUsr <= 0) {
    echo "ERROR: Usuario no autenticado\n";
    exit;
}

// LEER EL JSON DIRECTAMENTE DEL BODY
$json_input = file_get_contents('php://input');

if (empty($json_input)) {
    echo "ERROR: No se recibió JSON en el body\n";
    echo "Esto significa que el AJAX no está enviando los datos correctamente\n";
    exit;
}

echo "JSON recibido (primeros 500 caracteres):\n" . substr($json_input, 0, 500) . "\n\n";

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
echo "Campos de descuento/recargo:\n";
echo "descuento_texto: '" . ($data['descuento_texto'] ?? 'NO EXISTE') . "'\n";
echo "descuento_monto: " . ($data['descuento_monto'] ?? 'NO EXISTE') . "\n";
echo "recargo_texto: '" . ($data['recargo_texto'] ?? 'NO EXISTE') . "'\n";
echo "recargo_monto: " . ($data['recargo_monto'] ?? 'NO EXISTE') . "\n\n";

// Obtener información del cliente
$clienteData = $db->consultaSegura(
    "SELECT code, full_name FROM cliente WHERE num = $1", 
    [$data['cliente']]
);

if (empty($clienteData)) {
    echo "ERROR: Cliente no encontrado\n";
    exit;
}

$clienteCode = $clienteData[0]->code;

// Generar número de presupuesto
$presupuestoNum = $db->consultaSegura(
    "SELECT COALESCE(MAX(presupuesto_num), 0) + 1 as next_num FROM presupuesto_gen"
);

$numeroPresupuesto = $presupuestoNum[0]->next_num;

// Preparar valores para descuento/recargo
$descuentoTexto = $data['descuento_texto'] ?? '';
$descuentoMonto = floatval($data['descuento_monto'] ?? 0);
$recargoTexto = $data['recargo_texto'] ?? '';
$recargoMonto = floatval($data['recargo_monto'] ?? 0);

echo "Guardando en BD con valores:\n";
echo "descuento_texto: '$descuentoTexto'\n";
echo "descuento_monto: $descuentoMonto\n";
echo "recargo_texto: '$recargoTexto'\n";
echo "recargo_monto: $recargoMonto\n\n";

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
    echo "ERROR: No se pudo crear el presupuesto general\n";
    exit;
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

echo "✅ PRESUPUESTO GUARDADO EXITOSAMENTE\n";
echo "ID: $presupuestoIdx\n";
echo "Número: $numeroPresupuesto\n";

// Ahora enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'presupuesto_id' => $presupuestoIdx,
    'presupuesto_num' => $numeroPresupuesto,
    'presupuesto_num_valery' => $data['numero'],
    'message' => 'Presupuesto guardado correctamente'
]);

echo "\n=== FIN DEBUG ===\n";
echo "</pre>";
?>