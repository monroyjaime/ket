<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
//require_once("dbcat_async.php");
require_once("../../php/dbcat_async.php");

$presupuesto_id = $_GET['presupuesto_id'] ?? 0;
$db = new DBAsync();

try {
    // Obtener datos generales del presupuesto
    $presupuestoGen = $db->consultaSegura(
        "SELECT pg.*, u.full_name as usuario_nombre, c.full_name as cliente_nombre
         FROM presupuesto_gen pg
         LEFT JOIN usuario u ON pg.user_num = u.num
         LEFT JOIN cliente c ON pg.cliente = c.code
         WHERE pg.idx = $1",
        [$presupuesto_id]
    );
    
    if (empty($presupuestoGen)) {
        die('Presupuesto no encontrado');
    }
    
    $presupuesto = $presupuestoGen[0];
    
    // Obtener detalles del presupuesto
    $detalles = $db->consultaSegura(
        "SELECT pd.*, p.name as producto_nombre, p.unit as unidad
         FROM presupuesto_detail pd
         LEFT JOIN productos p ON pd.product_code = p.code
         WHERE pd.pres_idx = $1
         ORDER BY pd.product_code",
        [$presupuesto_id]
    );
    
    // Calcular total
    $total = 0;
    foreach ($detalles as $detalle) {
        $total += $detalle->cantidad * $detalle->precio;
    }
    
} catch (Exception $e) {
    die("Error al cargar el presupuesto: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto #<?php echo $presupuesto->presupuesto_num; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; }
        .presupuesto-header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .presupuesto-footer { border-top: 2px solid #333; padding-top: 20px; margin-top: 20px; }
        .table-presupuesto { width: 100%; border-collapse: collapse; }
        .table-presupuesto th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; }
        .table-presupuesto td { border: 1px solid #dee2e6; padding: 8px; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #e9ecef; }
        @media print {
            .no-print { display: none; }
            body { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Encabezado -->
        <div class="presupuesto-header">
            <div class="row">
                <div class="col-6">
                    <h2>KET ELECTROPARTES C.A.</h2>
                    <p>RIF: J-12345678-9<br>
                    Dirección: Av. Principal, Centro<br>
                    Teléfono: (123) 456-7890<br>
                    Email: info@ketelectropartes.com</p>
                </div>
                <div class="col-6 text-end">
                    <h3>PRESUPUESTO</h3>
                    <p><strong>N°:</strong> <?php echo htmlspecialchars($presupuesto->presupuesto_num); ?><br>
                    <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($presupuesto->fecha)); ?><br>
                    <strong>Hora:</strong> <?php echo date('H:i', strtotime($presupuesto->hora)); ?></p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-6">
                    <strong>Cliente:</strong><br>
                    <?php echo htmlspecialchars($presupuesto->cliente_nombre ?? $presupuesto->cliente); ?>
                </div>
                <div class="col-6">
                    <strong>Atendido por:</strong><br>
                    <?php echo htmlspecialchars($presupuesto->usuario_nombre ?? 'Sistema'); ?>
                </div>
            </div>
        </div>

        <!-- Detalles del presupuesto -->
        <table class="table-presupuesto">
            <thead>
                <tr>
                    <th width="10%">Código</th>
                    <th width="40%">Descripción</th>
                    <th width="10%" class="text-right">Cantidad</th>
                    <th width="10%">Unidad</th>
                    <th width="15%" class="text-right">Precio Unit.</th>
                    <th width="15%" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle->product_code); ?></td>
                    <td><?php echo htmlspecialchars($detalle->producto_nombre); ?></td>
                    <td class="text-right"><?php echo number_format($detalle->cantidad, 0); ?></td>
                    <td><?php echo htmlspecialchars($detalle->unidad); ?></td>
                    <td class="text-right">$<?php echo number_format($detalle->precio, 3, ',', '.'); ?></td>
                    <td class="text-right">$<?php echo number_format($detalle->cantidad * $detalle->precio, 3, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>$<?php echo number_format($total, 3, ',', '.'); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Comentarios -->
        <?php if (!empty($presupuesto->comentarios)): ?>
        <div class="mt-4">
            <strong>Comentarios:</strong><br>
            <?php echo nl2br(htmlspecialchars($presupuesto->comentarios)); ?>
        </div>
        <?php endif; ?>

        <!-- Tiempos de entrega -->
        <div class="mt-4">
            <strong>Tiempos de Entrega:</strong><br>
            <?php 
            $tiempos = [];
            foreach ($detalles as $detalle) {
                if ($detalle->tiempo_entrega > 0) {
                    $tiempos[$detalle->tiempo_entrega][] = $detalle->product_code;
                }
            }
            
            if (!empty($tiempos)) {
                foreach ($tiempos as $dias => $productos) {
                    echo "• " . $dias . " días: " . implode(', ', $productos) . "<br>";
                }
            } else {
                echo "Entrega inmediata";
            }
            ?>
        </div>

        <!-- Pie de página -->
        <div class="presupuesto-footer text-center">
            <p>¡Gracias por su preferencia!<br>
            Este presupuesto es válido por 30 días</p>
        </div>

        <!-- Botones de acción -->
        <div class="mt-4 no-print">
            <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir PDF</button>
            <button class="btn btn-secondary" onclick="window.history.back()">← Volver</button>
            <a href="index.php" class="btn btn-success">🏠 Ir al Inicio</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>