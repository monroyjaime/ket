<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once("../../php/dbcat_async.php");

$db = new DBAsync();
$numUsr = isset($_SESSION['usr_num']) ? intval($_SESSION['usr_num']) : -1;

// Obtener el presupuesto_id de la URL o el último - CON VALOR POR DEFECTO
$presupuesto_id = $_GET['presupuesto_id'] ?? 0;
$presupuesto_id = intval($presupuesto_id); // Asegurar que sea entero

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
    
    if ($presupuesto_id > 0) {
        foreach ($presupuestos as $pres) {
            if ($pres->idx == $presupuesto_id) {
                $presupuesto_actual = $pres;
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
            text-align: center;
            padding: 0px;
            background-color: #f8f9fa;
        }
        .header-top {
            background-color: #CCC;
            padding: 0px;
        }
        .icon-dark-blue {
            color: #003272;
        }
        .icon-large {
            font-size: 25px;
        }
        .presupuesto-container {
            background: white;
            padding: 20px;
            margin: 0px;
        }
        .navigation-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }

        #uperbar{
            padding: 0px;
        }
        
        /* ESTILOS TOM SELECT MEJORADOS (igual al de clientes) */
        .tom-select-container {
            margin-bottom: 20px;
            padding: 0 0px;
        }
        .selector-label {
            text-align: left;
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #037C79;
        }
        .ts-control {
            text-align: left !important;
            border: 1px solid #037C79 !important;
            border-radius: 4px !important;
            padding: 8px 12px !important;
            background: white !important;
        }
        .ts-wrapper.single .ts-control {
            background: white !important;
        }
        .ts-dropdown {
            text-align: left !important;
            border: 1px solid #037C79 !important;
            border-top: none !important;
            border-radius: 0 0 4px 4px !important;
            background: white !important;
        }
        .ts-dropdown .option .highlight {
            background-color: #ffeb3b !important;
            color: #000 !important;
            font-weight: bold !important;
            padding: 2px 4px !important;
            border-radius: 3px !important;
        }
        .ts-dropdown .active {
            background-color: #037C79 !important;
            color: white !important;
        }
        .ts-dropdown .option:hover {
            background-color: #025a57 !important;
            color: white !important;
        }
        .ts-dropdown .ts-input {
            border-bottom: 1px solid #037C79 !important;
            padding: 8px 12px !important;
        }
        
        /* Título estilo index.php */
        .titulo-presupuestos {
            background-color: #037C79; 
            padding: 14px 0; 
            color: #FFF;
            margin: 0;
        }
        
        /* Contenedor principal sin padding */
        .container-sin-padding {
            padding: 0;
            margin: 0;
            max-width: 100%;
        }

        /* ESTILOS PARA EL CONTENIDO CARGADO POR AJAX - SOLO PANTALLA */
        @media screen {
            #presupuesto-content .container {
                padding-left: 0 !important;
                padding-right: 0 !important;
                max-width: 100% !important;
            }
            
            #presupuesto-content .presupuesto-header .row:first-child {
                justify-content: center !important;
            }
            
            #presupuesto-content .presupuesto-header .row.mt-2 {
                justify-content: center !important;
            }
            
            #presupuesto-content .presupuesto-header h4 {
                text-align: center !important;
            }
            
            #presupuesto-content .presupuesto-header .col-6 {
                text-align: center !important;
                width: 45% !important;
                flex: 0 0 auto !important;
            }
        }

        /* ESTILOS PARA IMPRESIÓN - OCULTAR ELEMENTOS NO NECESARIOS */
        @media print {
            #uperbar,
            .header-top,
            .titulo-presupuestos,
            .tom-select-container,
            .navigation-buttons {
                display: none !important;
            }
            
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .presupuesto-container {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER SUPERIOR (igual al index.php pero SIN BOTONES) -->
    <div id="uperbar" class="w-100 p-0" style="background-color: #CCC;">
        <div class="row align-items-start" style="max-height: 50px;">
            <div class="col text-start" style="max-height: 40px; padding-left: 20px;">
                <!-- FLECHA AZUL - Corregida para apuntar al index de presupuestos -->
                <a href="index.php" title="Pag. Prev.">
                    <i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i>
                </a>
            </div>  
            <div class="col text-center" style="max-height: 40px; padding-bottom: 14px; padding-top: 1px;">
                <!-- Espacio reservado para mantener el layout -->
            </div>
            <div class="col text-end" style="max-height: 40px;">
                <img src="../../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
            </div>       
        </div>
    </div>

    <!-- TÍTULO -->
    <div class="titulo-presupuestos">
        <h2>Presupuestos Guardados</h2>
    </div>

    <div class="container-sin-padding">
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
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando presupuesto...</span>
                        </div>
                        <p>Cargando presupuesto...</p>
                    </div>
                </div>
                
                <!-- Navegación DINÁMICA -->
                <div id="navigation-container">
                    <div class="navigation-buttons">
                        <button class="btn btn-outline-secondary" disabled>
                            <i class="bi bi-chevron-left"></i> Anterior
                        </button>
                        <span class="align-self-center px-3" id="contador-navegacion">
                            Cargando...
                        </span>
                        <button class="btn btn-outline-secondary" disabled>
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </button>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        // Variables globales
        let presupuestosTomSel;
        let listaPresupuestos = <?php echo json_encode($presupuestos); ?>;
        let presupuestoActualId = <?php echo $presupuesto_id > 0 ? $presupuesto_id : '0'; ?>;
        
        $(document).ready(function() {
            // Inicializar selector de presupuestos
            presupuestosTomSel = new TomSelect("#presupuestos-tom-sel", {
                sortField: { field: "text", direction: "desc" },
                searchField: ["text"],
                placeholder: "Buscar presupuesto...",
                onChange: function(value) {
                    if (value) {
                        presupuestoActualId = value;
                        cargarPresupuesto(value);
                    }
                }
            });
            
            // Cargar el presupuesto actual y actualizar navegación
            if (presupuestoActualId > 0) {
                cargarPresupuesto(presupuestoActualId);
                actualizarNavegacion();
            }
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
            
            // URL ABSOLUTA
            const url = `https://ketelectropartes.com/admin/php/verPresupuesto.php?presupuesto_id=${presupuestoId}`;
            
            $.get(url, function(data) {
                $('#presupuesto-content').html(data);
                presupuestoActualId = presupuestoId;
                actualizarNavegacion();
                
                // Actualizar URL sin recargar la página
                const nuevaUrl = `verPresupuestos.php?presupuesto_id=${presupuestoId}`;
                window.history.pushState({path: nuevaUrl}, '', nuevaUrl);
                
            }).fail(function(xhr, status, error) {
                console.error('Error cargando presupuesto:', error);
                $('#presupuesto-content').html(`
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-exclamation-triangle"></i>
                        Error al cargar el presupuesto.
                    </div>
                `);
            });
        }
        
        // Función para actualizar la navegación
        function actualizarNavegacion() {
            if (listaPresupuestos.length === 0) return;
            
            // Encontrar índice actual
            let indiceActual = -1;
            for (let i = 0; i < listaPresupuestos.length; i++) {
                if (listaPresupuestos[i].idx == presupuestoActualId) {
                    indiceActual = i;
                    break;
                }
            }
            
            if (indiceActual === -1) return;
            
            // Actualizar contador
            $('#contador-navegacion').text(`${indiceActual + 1} de ${listaPresupuestos.length}`);
            
            // Actualizar botones
            let htmlNavegacion = `
                <div class="navigation-buttons">
                    ${indiceActual > 0 ? 
                        `<button class="btn btn-outline-primary" onclick="navegarAnterior()">
                            <i class="bi bi-chevron-left"></i> Anterior
                        </button>` :
                        `<button class="btn btn-outline-secondary" disabled>
                            <i class="bi bi-chevron-left"></i> Anterior
                        </button>`
                    }
                    
                    <span class="align-self-center px-3">
                        ${indiceActual + 1} de ${listaPresupuestos.length}
                    </span>
                    
                    ${indiceActual < listaPresupuestos.length - 1 ? 
                        `<button class="btn btn-outline-primary" onclick="navegarSiguiente()">
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </button>` :
                        `<button class="btn btn-outline-secondary" disabled>
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </button>`
                    }
                </div>
            `;
            
            $('#navigation-container').html(htmlNavegacion);
        }
        
        // Funciones de navegación
        function navegarAnterior() {
            let indiceActual = -1;
            for (let i = 0; i < listaPresupuestos.length; i++) {
                if (listaPresupuestos[i].idx == presupuestoActualId) {
                    indiceActual = i;
                    break;
                }
            }
            
            if (indiceActual > 0) {
                const presupuestoAnterior = listaPresupuestos[indiceActual - 1];
                presupuestosTomSel.setValue(presupuestoAnterior.idx);
                cargarPresupuesto(presupuestoAnterior.idx);
            }
        }
        
        function navegarSiguiente() {
            let indiceActual = -1;
            for (let i = 0; i < listaPresupuestos.length; i++) {
                if (listaPresupuestos[i].idx == presupuestoActualId) {
                    indiceActual = i;
                    break;
                }
            }
            
            if (indiceActual < listaPresupuestos.length - 1) {
                const presupuestoSiguiente = listaPresupuestos[indiceActual + 1];
                presupuestosTomSel.setValue(presupuestoSiguiente.idx);
                cargarPresupuesto(presupuestoSiguiente.idx);
            }
        }
        
        // Navegación con teclado
        $(document).keydown(function(e) {
            if (e.key === 'ArrowLeft') {
                navegarAnterior();
            } else if (e.key === 'ArrowRight') {
                navegarSiguiente();
            }
        });
    </script>
</body>
</html>