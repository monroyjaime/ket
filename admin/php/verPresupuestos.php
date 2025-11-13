<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once("../../php/dbcat_async.php");

$db = new DBAsync();
$numUsr = isset($_SESSION['usr_num']) ? intval($_SESSION['usr_num']) : -1;

// Obtener el presupuesto_id de la URL o el último
$presupuesto_id = $_GET['presupuesto_id'] ?? 0;

try {
    // Obtener lista de presupuestos no archivados
    $presupuestos = $db->consultaSegura(
        "SELECT pg.idx, pg.presupuesto_num, pg.fecha, pg.cliente, pg.num_valery, u.full_name as usuario_nombre
         FROM presupuesto_gen pg
         LEFT JOIN usuario u ON pg.user_num = u.num
         WHERE pg.archivado = 0
         ORDER BY pg.fecha DESC, pg.hora DESC"
    );
    
    // Si no hay presupuesto_id específico, tomar el más reciente
    if ($presupuesto_id == 0 && !empty($presupuestos)) {
        $presupuesto_id = $presupuestos[0]->idx;
    }
    
    // Obtener datos del presupuesto actual
    $presupuesto_actual = null;
    $indice_actual = -1;
    
    if ($presupuesto_id > 0) {
        for ($i = 0; $i < count($presupuestos); $i++) {
            if ($presupuestos[$i]->idx == $presupuesto_id) {
                $presupuesto_actual = $presupuestos[$i];
                $indice_actual = $i;
                break;
            }
        }
    }
    
} catch (Exception $e) {
    die("Error al cargar los presupuestos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostrar Presupuestos - KET Electropartes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/css/tom-select.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .header-nav {
            background-color: #037C79;
            color: white;
            padding: 10px 0;
            margin-bottom: 20px;
        }
        .presupuesto-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .navigation-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .tom-select-container {
            margin-bottom: 20px;
        }
        .selector-label {
            font-weight: bold;
            color: #037C79;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header-nav">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <a href="../index.php" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Volver al Catálogo
                    </a>
                </div>
                <div class="col text-center">
                    <h4 class="mb-0">📋 Presupuestos Guardados</h4>
                </div>
                <div class="col text-end">
                    <img src="../../catalogo/images/logoMini.png" alt="KET" height="40">
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Selector de Presupuestos -->
        <div class="tom-select-container">
            <label class="selector-label">Seleccionar Presupuesto:</label>
            <select id="presupuestos-tom-sel" placeholder="Buscar presupuesto...">
                <option value="">Seleccione un presupuesto...</option>
                <?php foreach ($presupuestos as $pres): ?>
                <option value="<?php echo $pres->idx; ?>" 
                        <?php echo ($pres->idx == $presupuesto_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(
                        date('d/m/Y', strtotime($pres->fecha)) . ' - ' . 
                        '#' . $pres->presupuesto_num . ' - ' .
                        $pres->cliente . ' - ' .
                        'Por: ' . ($pres->usuario_nombre ?? 'Sistema')
                    ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Contenedor del Presupuesto -->
        <div class="presupuesto-container">
            <?php if ($presupuesto_actual): ?>
                <div id="presupuesto-content">
                    <!-- El contenido del presupuesto se cargará aquí via JavaScript -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando presupuesto...</span>
                        </div>
                        <p>Cargando presupuesto...</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-x display-1 text-muted"></i>
                    <h3 class="text-muted">No hay presupuestos disponibles</h3>
                    <p class="text-muted">No se encontraron presupuestos para mostrar.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navegación -->
        <?php if ($presupuesto_actual && count($presupuestos) > 1): ?>
        <div class="navigation-buttons">
            <!-- Anterior -->
            <?php if ($indice_actual > 0): ?>
            <button class="btn btn-outline-primary" onclick="navegarPresupuesto(<?php echo $presupuestos[$indice_actual - 1]->idx; ?>)">
                <i class="bi bi-chevron-left"></i> Anterior
            </button>
            <?php else: ?>
            <button class="btn btn-outline-secondary" disabled>
                <i class="bi bi-chevron-left"></i> Anterior
            </button>
            <?php endif; ?>

            <!-- Contador -->
            <span class="align-self-center px-3">
                <?php echo ($indice_actual + 1) . ' de ' . count($presupuestos); ?>
            </span>

            <!-- Siguiente -->
            <?php if ($indice_actual < count($presupuestos) - 1): ?>
            <button class="btn btn-outline-primary" onclick="navegarPresupuesto(<?php echo $presupuestos[$indice_actual + 1]->idx; ?>)">
                Siguiente <i class="bi bi-chevron-right"></i>
            </button>
            <?php else: ?>
            <button class="btn btn-outline-secondary" disabled>
                Siguiente <i class="bi bi-chevron-right"></i>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        // Inicializar Tom Select
        let presupuestosTomSel;
        
        $(document).ready(function() {
            // Inicializar selector de presupuestos
            presupuestosTomSel = new TomSelect("#presupuestos-tom-sel", {
                sortField: { field: "text", direction: "desc" },
                searchField: ["text"],
                placeholder: "Buscar presupuesto...",
                onChange: function(value) {
                    if (value) {
                        cargarPresupuesto(value);
                    }
                }
            });
            
            // Cargar el presupuesto actual
            <?php if ($presupuesto_actual): ?>
            cargarPresupuesto(<?php echo $presupuesto_id; ?>);
            <?php endif; ?>
        });
        
        // Función para cargar presupuesto
        function cargarPresupuesto(presupuestoId) {
            $('#presupuesto-content').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando presupuesto...</span>
                    </div>
                    <p>Cargando presupuesto...</p>
                </div>
            `);
            
            $.get(`verPresupuesto.php?presupuesto_id=${presupuestoId}`, function(data) {
                $('#presupuesto-content').html(data);
                
                // Actualizar URL sin recargar la página
                const nuevaUrl = `verPresupuestos.php?presupuesto_id=${presupuestoId}`;
                window.history.pushState({path: nuevaUrl}, '', nuevaUrl);
                
            }).fail(function() {
                $('#presupuesto-content').html(`
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-exclamation-triangle"></i>
                        Error al cargar el presupuesto. Intente nuevamente.
                    </div>
                `);
            });
        }
        
        // Función para navegación
        function navegarPresupuesto(presupuestoId) {
            // Actualizar el selector
            presupuestosTomSel.setValue(presupuestoId);
            
            // Cargar el presupuesto
            cargarPresupuesto(presupuestoId);
        }
        
        // Navegación con teclado
        $(document).keydown(function(e) {
            <?php if ($presupuesto_actual && count($presupuestos) > 1): ?>
            if (e.key === 'ArrowLeft' && <?php echo $indice_actual > 0 ? 'true' : 'false'; ?>) {
                navegarPresupuesto(<?php echo $indice_actual > 0 ? $presupuestos[$indice_actual - 1]->idx : 'null'; ?>);
            } else if (e.key === 'ArrowRight' && <?php echo $indice_actual < count($presupuestos) - 1 ? 'true' : 'false'; ?>) {
                navegarPresupuesto(<?php echo $indice_actual < count($presupuestos) - 1 ? $presupuestos[$indice_actual + 1]->idx : 'null'; ?>);
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>