<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("../../php/dbcat_async.php");

$presupuesto_id = $_GET['presupuesto_id'] ?? 0;
$db = new DBAsync();

try {
    // Obtener datos generales del presupuesto
    $presupuestoGen = $db->consultaSegura(
        "SELECT pg.*, u.full_name as usuario_nombre
         FROM presupuesto_gen pg
         LEFT JOIN usuario u ON pg.user_num = u.num
         WHERE pg.idx = $1",
        [$presupuesto_id]
    );
    
    if (empty($presupuestoGen)) {
        die('Presupuesto no encontrado. ID: ' . $presupuesto_id);
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
    
    if (empty($detalles)) {
        die('No se encontraron detalles para el presupuesto ID: ' . $presupuesto_id);
    }
    
    // Calcular subtotal
    $subtotal = 0;
    foreach ($detalles as $detalle) {
        $subtotal += $detalle->cantidad * $detalle->precio;
    }
    
    // Usar descuentos, recargos e IVA de la base de datos
    $descuento = floatval($presupuesto->descuento_monto) ?? 0;
    $recargo = floatval($presupuesto->recargo_monto) ?? 0;
    $iva = floatval($presupuesto->iva_monto) ?? 0;
    $iva_porcentaje = floatval($presupuesto->iva_porcentaje) ?? 0;
    $total = $subtotal - $descuento + $recargo + $iva;
    $descPercent = (1-($subtotal/($subtotal - $descuento))) *100;
    $descLabel = ($descPercent ===0)? "" : "Descuento (".$descPercent."%): ";
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
        body { 
            font-family: Arial, sans-serif; 
            margin: 10px;
            padding: 0;
        }
        .container {
            max-width: 100%;
            padding: 0 15px;
        }
        .presupuesto-header { 
            border-bottom: 2px solid #333; 
            padding-bottom: 15px; 
            margin-bottom: 15px; 
            text-align: center;  /* AGREGAR esta línea */
        }
        .presupuesto-footer { 
            border-top: 2px solid #333; 
            padding-top: 15px; 
            margin-top: 15px; 
            position: relative;
        }
        .table-presupuesto { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
            font-size: 0.9em;
            table-layout: fixed;
            word-wrap: break-word;
        }
        .table-presupuesto th:nth-child(1), .table-presupuesto td:nth-child(1) { width: 5%; } /* Ítem */
        .table-presupuesto th:nth-child(2), .table-presupuesto td:nth-child(2) { width: 12%; } /* Código */
        .table-presupuesto th:nth-child(3), .table-presupuesto td:nth-child(3) { width: 32%; } /* Descripción */
        .table-presupuesto th:nth-child(4), .table-presupuesto td:nth-child(4) { width: 7%; } /* Cantidad */
        .table-presupuesto th:nth-child(5), .table-presupuesto td:nth-child(5) { width: 7%; } /* Unidad */
        .table-presupuesto th:nth-child(6), .table-presupuesto td:nth-child(6) { width: 12%; } /* Precio Unit. */
        .table-presupuesto th:nth-child(7), .table-presupuesto td:nth-child(7) { width: 10%; } /* Tiempo Entrega */
        .table-presupuesto th:nth-child(8), .table-presupuesto td:nth-child(8) { width: 12%; } /* Subtotal */
        
        .table-presupuesto th { 
            background-color: #f8f9fa; 
            border: 1px solid #dee2e6; 
            padding: 8px 6px;
            text-align: left; 
            font-size: 0.85em;
        }
        .table-presupuesto td { 
            border: 1px solid #dee2e6; 
            padding: 8px 6px;
            font-size: 0.85em;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #e9ecef; }
        .totales-section {
            margin-top: 40px;
        }
        .totales-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .totales-table { 
            width: 300px;
            border-collapse: collapse; 
            font-size: 0.95em;
        }
        .totales-table td { 
            padding: 8px 12px;
            border: 1px solid #dee2e6; 
        }
        .totales-table .label { 
            font-weight: bold; 
            background-color: #f8f9fa; 
        }
        .no-print { margin-top: 15px; }
        .logo { 
            max-width: 50%; 
            height: auto; 
            width: auto;
        }
        .info-empresa {
            font-size: 0.85em;
            margin-top: 5px;
        }
        .comentarios-section {
            flex: 1;
            margin-right: 20px;
        }
        .numero-interno {
            font-size: 0.8em;
            color: #6c757d;
        }
        @media print {
            .no-print { display: none; }
            body { 
                font-size: 12px !important;
                margin: 10mm !important;
                padding: 0 !important;
                line-height: 1.3;
            }
            .container { 
                max-width: 95% !important;
                padding: 0 !important;
                margin: 0 auto !important;
            }
            .presupuesto-header { 
                padding-bottom: 12px !important; 
                margin-bottom: 12px !important; 
            }
            .table-presupuesto { 
                margin: 12px 0 !important;
                font-size: 0.85em !important;
                width: 100% !important;
            }
            .table-presupuesto th,
            .table-presupuesto td { 
                padding: 6px 4px !important;
            }
            .logo {
                max-width: 35% !important;
            }
            .info-empresa {
                font-size: 0.8em !important;
            }
            h4 {
                font-size: 1.3em !important;
                margin-bottom: 8px !important;
            }
            .totales-section {
                margin-top: 60px !important;
            }
            .totales-table {
                font-size: 0.95em !important;
                width: 300px !important;
            }
            .comentarios-section {
                margin-top: 25px !important;
            }
            .presupuesto-footer {
                position: relative !important;
                margin-top: 80px !important;
                border-top: 2px solid #333 !important;
                padding-top: 15px !important;
                text-align: center !important;
                font-size: 0.9em !important;
            }
            .container {
                page-break-after: auto !important;
                page-break-inside: avoid;
            }
            .numero-interno {
                font-size: 0.75em !important;
                color: #6c757d !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="presupuesto-header">
            <div class="row">
                <div class="col-6 text-start">
                    <img src="https://ketelectropartes.com/catalogo/images/logoSlogan.png" alt="KET ELECTROPARTES C.A." class="logo">
                    <div class="info-empresa">
                        RIF: J-303726445<br>
                        Dirección: Crta. Petare Santa Lucia, Km. 1 Local Centro Industria Viana<br> 
                        Nro. Galpon 1-A Sector Altos de Valencia Filas de Mariche Miranda<br>
                        Zona Postal 1030<br>
                        Teléfono: (0414) 316-1207<br>
                    </div>
                </div>
                <div class="col-6 text-end">
                    <h4>PRESUPUESTO</h4>
                    <div style="font-size: 0.9em;">
                        <!-- CAMBIO 1: No. Cliente primero -->
                        <?php if (!empty($presupuesto->num_valery)): ?>
                        <strong>No. <?php echo htmlspecialchars($presupuesto->num_valery); ?></strong><br>
                        <?php endif; ?>
                        
                        <!-- CAMBIO 2: No. Interno en gris y más pequeño -->
                        <span class="numero-interno">
                            <strong>No. Interno:</strong> <?php echo htmlspecialchars($presupuesto->presupuesto_num); ?>
                        </span><br>
                        
                        <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($presupuesto->fecha)); ?><br>
                        <strong>Hora:</strong> <?php echo date('H:i', strtotime($presupuesto->hora)); ?>
                    </div>
                </div>
            </div>
            
            <div class="row mt-2">  <!-- Removido justify-content-center -->
                <div class="col-md-6 text-start" style="font-size: 0.9em;">                    
                    <strong>Cliente:</strong><br>
                    <!-- CAMBIO 3: Mostrar formato "CODIGO --- NOMBRE" completo -->
                    <?php echo htmlspecialchars($presupuesto->cliente); ?>
                </div>
                <div class="col-md-6 text-end" style="font-size: 0.9em;">
                    <strong>Elaborado por:</strong><br>
                    <?php echo htmlspecialchars($presupuesto->usuario_nombre ?? 'Sistema'); ?>
                </div>
            </div>
        </div>

        <!-- Detalles del presupuesto -->
        <table class="table-presupuesto">
            <thead>
                <tr>
                    <th class="text-center">Ítem</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Unidad</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-center">Tiempo Entrega</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $contador_item = 1;
                foreach ($detalles as $detalle): 
                ?>
                <tr>
                    <td class="text-center"><?php echo $contador_item; ?></td>
                    <td><?php echo htmlspecialchars($detalle->product_code); ?></td>
                    <td><?php echo htmlspecialchars($detalle->producto_nombre); ?></td>
                    <td class="text-center"><?php echo number_format($detalle->cantidad, 0); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($detalle->unidad); ?></td>
                    <td class="text-right">$<?php echo number_format($detalle->precio, 3, ',', '.'); ?></td>
                    <td class="text-center">
                        <?php 
                        if ($detalle->tiempo_entrega == 0) {
                            echo 'Inmediato';
                        } else {
                            echo $detalle->tiempo_entrega . ' días';
                        }
                        ?>
                    </td>
                    <td class="text-right">$<?php echo number_format($detalle->cantidad * $detalle->precio, 2, ',', '.'); ?></td>
                </tr>
                <?php 
                $contador_item++;
                endforeach; 
                ?>
            </tbody>
        </table>

        <!-- Sección de totales al final -->
        <div class="totales-section">
            <div class="totales-container">
                <div class="comentarios-section">
                    <?php if (!empty($presupuesto->comentarios)): ?>
                    <div style="font-size: 0.9em;">
                        <strong>Comentarios:</strong><br>
                        <?php echo nl2br(htmlspecialchars($presupuesto->comentarios)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Mostrar descuentos y recargos si existen -->
                    <?php if ($descuento > 0 && !empty($presupuesto->descuento_texto)): ?>
                    <div style="font-size: 0.9em; margin-top: 10px;">
                        <strong>Descuento:</strong><br>
                        <?php echo htmlspecialchars($presupuesto->descuento_texto); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($recargo > 0 && !empty($presupuesto->recargo_texto)): ?>
                    <div style="font-size: 0.9em; margin-top: 10px;">
                        <strong>Recargo:</strong><br>
                        <?php echo htmlspecialchars($presupuesto->recargo_texto); ?>
                    </div>
                    <?php endif; ?>

                    <!-- CAMBIO 4: ELIMINADO mostrar IVA aquí (solo aparece en la tabla) -->
                </div>
                
                <table class="totales-table">
                    <tr>
                        <td class="label">Sub-Total:</td>
                        <td class="text-right">$<?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                    </tr>
                    <?php if ($descuento > 0): ?>
                    <tr>
                        <td class="label">$<?php echo $descLabel; ?></td>
                        <td class="text-right">-$<?php echo number_format($descuento, 2, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($recargo > 0): ?>
                    <tr>
                        <td class="label">Recargo:</td>
                        <td class="text-right">+$<?php echo number_format($recargo, 2, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($iva > 0): ?>
                    <tr>
                        <td class="label">IVA (<?php echo $iva_porcentaje; ?>%):</td>
                        <td class="text-right">+$<?php echo number_format($iva, 2, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td class="label">TOTAL:</td>
                        <td class="text-right"><strong>$<?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="presupuesto-footer text-center" style="font-size: 0.9em;">
            <p>¡Gracias por su preferencia!<br>
            Este presupuesto es válido por 3 días</p>
        </div>

        <!-- Botones de acción -->
        <div class="no-print text-center">
            <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir PDF</button>
            <button class="btn btn-secondary" onclick="window.history.back()">← Volver</button>
<!-- NUEVO BOTÓN PARA PRECARGAR EN CARRITO -->
    <button class="btn btn-warning" onclick="precargarEnCarrito(<?php echo $presupuesto->idx; ?>)"><i class="bi bi-cart-plus"></i> Usar para Nuevo Presupuesto</button>
            <a href="../presupuestos/index.php" class="btn btn-success">🏠 Ir al Inicio</a>
            <a href="../presupuestos/presupuestoImages.php?pres_num=<?php echo $presupuesto->idx; ?>" class="btn btn-info">
                <i class="bi bi-images"></i> Ver Imágenes
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function precargarEnCarrito(presupuestoId) {
        // Preguntar qué hacer con los precios
        const opcionPrecios = confirm('¿Desea usar los precios actuales de los productos?\n\n' +
                                    '• OK = Usar precios ACTUALES\n' +
                                    '• Cancelar = Mantener precios HISTÓRICOS del presupuesto');
        if (!confirm('¿Precargar presupuesto en carrito?')) {
            return;
        }
        
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Cargando...';
        btn.disabled = true;
        
        const url = 'https://ketelectropartes.com/admin/php/precargarPresupuestoCarrito.php';
        const datos = {
            presupuesto_id: presupuestoId,
            usuario_id: <?php echo $numUsr ?? -1; ?>,
            usar_precios_actuales: opcionPrecios // true = actuales, false = históricos
        };
        
        // Usar fetch para ver la respuesta cruda
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.text())  // Leer como texto primero
        .then(text => {
            console.log('Respuesta CRUDA:', text);
            
            try {
                // Intentar parsear como JSON
                const data = JSON.parse(text);
                console.log('JSON parseado:', data);
                
                if (data.success) {
                    window.location.href = 'index.php?abrir_modal=1';
                } else {
                    alert('Error: ' + data.error);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (e) {
                console.error('No es JSON válido:', e);
                alert('El servidor devolvió un formato inválido. Ver consola para detalles.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error de red:', error);
            alert('Error de conexión: ' + error.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
    </script>
</body>
</html>