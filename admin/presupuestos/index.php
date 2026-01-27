<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Iniciar session solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// INICIALIZAR TODAS LAS VARIABLES PRIMERO
$numUsr = isset($_SESSION['usr_num']) ? intval($_SESSION['usr_num']) : -1;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;
$clientNum = 0; // INICIALIZAR
$showAllPres = 'f'; // INICIALIZAR
$ableToPresupuesto = 'f';
$usrName = "Usuario no identificado";



// CONSULTAR MÁRGENES DIRECTAMENTE DESDE PHP
$ganancia_min_glob = 1.2; // valor por defecto
$descuento_max_glob = 0.4; // valor por defecto

try {
    require_once("../../php/dbcat_async.php");
    $db = new DBAsync();
    
    // Consultar márgenes
    $margenes = $db->consultaSegura("SELECT ganancia_min_glob, descuento_max_glob FROM all_ket_values LIMIT 1");
    
    if (!empty($margenes)) {
        $ganancia_min_glob = floatval($margenes[0]->ganancia_min_glob);
        $descuento_max_glob = floatval($margenes[0]->descuento_max_glob);
    }
    
    // Solo mostrar botones si el usuario puede hacer presupuestos
    if ($numUsr > 0) {
        $ableToPresupuesto = 't';
        $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 2px 1px;"><i class="bi bi-gear"></i> Def. Presup.</button> ';
        $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" onClick="showPedidoClient()" style="margin: 1px 2px 1px;"><i class="bi bi-file-earmark-ppt"></i> Ver Presup.</button> ';
        $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" onClick="limpiarCarrito()" style="margin: 1px 2px 1px;"><i class="bi bi-recycle"></i>Vaciar Carrito</button> ';

        // Consultar datos del usuario
        $usuario = $db->consultaSegura("SELECT client, show_all_pres FROM usuario WHERE num = $1", [$numUsr]);
        
        if (!empty($usuario)) {
            $clientNum = intval($usuario[0]->client);
            $showAllPres = $usuario[0]->show_all_pres;
        }
    }
} catch (Exception $e) {
    error_log("Error en consultas: " . $e->getMessage());
}


// AHORA SÍ preparar opciones de clientes para Tom Select
$optionsClientes = '';

// Si el usuario tiene un cliente asignado, mostrarlo como opción por defecto
if ($clientNum > 0) {
    try {
        $cliente = $db->consultaSegura("SELECT code, full_name FROM cliente WHERE num = $1", [$clientNum]);
        
        if (!empty($cliente)) {
            $optionsClientes .= '<option value="'.$clientNum.'" selected>'.
                               htmlspecialchars($cliente[0]->code.' --- '.$cliente[0]->full_name).
                               '</option>';
        }
    } catch (Exception $e) {
        error_log("Error consultando cliente: " . $e->getMessage());
        $optionsClientes .= '<option value="0">Seleccione Cliente...</option>';
    }
} else {
    $optionsClientes .= '<option value="0">Seleccione Cliente...</option>';
}

// Si el usuario puede ver todos los presupuestos, cargar todos los clientes
if ($showAllPres == 't' && $numUsr > 0) {
    try {
        $clientes = $db->consultaSegura("SELECT num, code, full_name FROM cliente ORDER BY code");
        foreach ($clientes as $cliente) {
            if ($cliente->num != $clientNum) {
                $optionsClientes .= '<option value="'.$cliente->num.'">'.
                                   htmlspecialchars($cliente->code.' --- '.$cliente->full_name).
                                   '</option>';
            }
        }
    } catch (Exception $e) {
        error_log("Error consultando clientes: " . $e->getMessage());
    }
}

$tituloLista = '<h2 style="background-color: #037C79; padding-bottom: 14px; color: #FFF;">Presupuestos</h2>';
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Ket-Listas de Precios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/css/tom-select.css" rel="stylesheet">

    <script type="text/javascript">
        var roleNum = <?php echo $role; ?>;  
        var numUsr = <?php echo $numUsr; ?>;
        var codes_carrito = [];
        var ableToPresupuesto = '<?php echo $ableToPresupuesto; ?>';

        // MÁRGENES CARGADOS DIRECTAMENTE DESDE PHP
        var ganancia_min_glob = <?php echo $ganancia_min_glob; ?>;
        var descuento_max_glob = <?php echo $descuento_max_glob; ?>;
        
        console.log('✅ Márgenes cargados desde PHP:', ganancia_min_glob, descuento_max_glob);
    </script>

    <style>
        body {
            text-align: center;
            padding: 0px 0px;
        }
        .nav-link {
            color: #003272;
        }
        .icon-green {
            color: green;
        }
        .icon-yellow {
            color: yellow;
        }
        .icon-red {
            color: red;
        }
        .icon-large {
            font-size: 25px;
        }
        .icon-dark-blue {
            color: #003272;
        }
        .form-check {
            margin-bottom: 2px;
        }
        .form-check-label {
            font-size: 0.8rem;
        }
        .badge {
            font-size: 0.75rem;
        }
        .precio-manual-input {
            width: 100px;
        }
        .cantidad-input {
            width: 80px;
        }
        .tiempo-select {
            width: 110px;
        }

        /* NUEVOS ESTILOS PARA TOM SELECT MEJORADO */
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
        
        /* Resaltado de coincidencias - MUY VISIBLE */
        .ts-dropdown .option .highlight {
            background-color: #ffeb3b !important;
            color: #000 !important;
            font-weight: bold !important;
            padding: 2px 4px !important;
            border-radius: 3px !important;
        }
        
        /* Opción seleccionada y hover */
        .ts-dropdown .active {
            background-color: #037C79 !important;
            color: white !important;
        }
        
        .ts-dropdown .option:hover {
            background-color: #025a57 !important;
            color: white !important;
        }
        
        /* Input de búsqueda dentro del dropdown */
        .ts-dropdown .ts-input {
            border-bottom: 1px solid #037C79 !important;
            padding: 8px 12px !important;
        }
        
        /* Contenedor del Tom Select alineado a la izquierda */
        .tom-select-container {
            text-align: left;
        }
        
        /* Etiqueta del selector */
        .selector-label {
            text-align: left;
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #037C79;
        }

        .badge-margen {
            font-size: 0.6rem;
            padding: 2px 4px;
        }

        .form-check-label .badge {
            margin-left: 4px;
        }

        /* Estilos para la columna de precio combinada */
        .precio-combinado-container {
            padding: 4px 0;
        }

        .precio-opciones {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 6px;
        }

        .precio-manual-container {
            padding-top: 6px;
        }

        .precio-info {
            font-size: 0.7rem;
            line-height: 1.2;
        }

        /* Mejorar los radio buttons inline */
        .form-check-inline {
            margin-right: 8px;
            margin-bottom: 2px;
        }

        .form-check-inline .form-check-input {
            margin-right: 4px;
        }

        .form-check-inline .form-check-label {
            font-size: 0.75rem;
        }

        /* Input group más compacto */
        .precio-manual-container .input-group-sm {
            height: 28px;
        }

        .precio-manual-container .input-group-text {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .precio-manual-container .form-control {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            height: 28px;
        }

        /* Badges más compactos */
        .badge-margen {
            font-size: 0.75rem;
            padding: 3px;
            margin-left: 2px;
        }

        /* Ajustar altura de filas de la tabla */
        #table-carrito .bootstrap-table .table tbody tr td {
            padding: 4px 8px;
            vertical-align: top;
        }

        /* Estilo para texto relacionado (en lugar de badge) - COMPRESIBLE */
        .relacionado-text {
            font-size: 0.75rem;
            background-color: #0dcaf0;
            color: #000;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: help;
            line-height: 1.2;
        }

        /* Para que la columna "Relacionado" se pueda comprimir más */
        #table-carrito th:nth-child(3), 
        #table-carrito td:nth-child(3) {
            max-width: 150px;
            min-width: 80px;
            width: 120px; /* Ancho preferido */
        }

        /* Estilos para ocultar el check maestro */
        .bootstrap-table .fixed-table-header th input[type="checkbox"] {
            display: none !important;
        }
        .bootstrap-table .fixed-table-container thead th .th-inner.checkbox {
            display: none !important;
        }
        .bootstrap-table th:first-child .th-inner.check-all {
            display: none !important;
        }

    </style>
</head>

<body>
<div class="w-100 p-0" style="background-color: #CCC;">
    <div class="row align-items-start" style="max-height: 50px;">
        <div class="col text-start" style="max-height: 40px; padding-left: 20px;">
            <a href="#" onClick="backHome()" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>
        </div>  
        <div class="col text-center" style="max-height: 40px; padding-bottom: 14px; padding-top: 1px;">
            <?php echo $btnsPedido ?? ''; ?>
        </div>
        <div class="col text-end" style="max-height: 40px;">
            <img src="../../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
        </div>       
    </div>
</div>

<div class="col text-center">
    <div class="col text-center" style="background-color: #DDD;">
        <?php echo $tituloLista; ?>
        
        <div id="toolbar" class="select">
            <select class="form-control"></select> 
        </div>

        <table
            id="table-main"
            class="bootstrap-table"
            data-show-export="false"
            data-click-to-select="true"
            data-maintain-meta-data="true"
            data-show-columns="false"
            data-search="true"
            data-searchable="true"
            data-height="600"
            data-pagination="true"
            data-page-size="100" 
            data-page-list="[100]"
            data-url="../../php/getListaProdAllPresup.php"
            data-mobile-responsive="false"
            data-check-on-init="true"
            data-row-style="rowStyle"
            data-checkbox-header="false">
            <thead>
                <tr>
                    <?php 
                    // Mostrar columna de check solo si el usuario puede hacer presupuestos
                    if ($numUsr > 0 && $ableToPresupuesto == 't') {
                        echo '<th data-field="checked" data-checkbox="true" data-formatter="checkFormater"></th>';
                    }
                    ?>
                    <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
                    <th data-field="relacionado" data-halign="center" data-align="left">RELACIONADO</th>
                    <th data-field="stock" data-halign="center" data-align="left">STOCK</th>
                    <th data-field="llegando" data-halign="center" data-align="left">LLEGANDO</th> 
                    <th data-field="prec_min" data-formatter="precioFormaterPresup" data-halign="center" data-align="left">PREC 1</th>
                    <th data-field="prec_may" data-formatter="precioFormaterPresup" data-halign="center" data-align="left">PREC 2</th>
                    <th data-field="costo" data-formatter="precioFormatergen" data-halign="center" data-align="left">COSTO</th>
                    <th data-field="unit">UNIDAD</th>
                    <th data-field="name" data-halign="center" data-align="left" data-width="500" data-formatter="descripcionFormater">. . . . . . DESCRIPCION . . . . . .</th>                    <th data-field="photo_url" data-formatter="fotoFormater">FOTO</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal Detalle Producto -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detalle de Producto</h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal"><i class="bi bi-x-circle-fill"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Definir Presupuesto (COMPLETO) -->
<div class="modal fade" id="ModalMakePedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 95%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Definir Presupuesto</h4>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="row">
                        <div class="col">
                            <div class="p-4">
                                <div class="tom-select-container">
                                    <label class="selector-label">Cliente:</label>
                                    <select id="clients-tom-sel" placeholder="Seleccione Cliente..." autocomplete="off">
                                        <?php echo $optionsClientes; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col">
                            <div class="p-4">
                                <h6 id="label-numero-presupuesto">Número de Presupuesto:</h6>
                                <input type="text" class="form-control" id="numero-presupuesto" placeholder="Generando número automático...">
                                <small class="text-muted">Puede usar este número o ingresar uno manual</small>
                            </div>
                        </div>   
                    </div>
                </div> 

                <table
                    id="table-carrito"
                    class="bootstrap-table"
                    data-table-type="make-pedido"
                    data-toggle="table"  
                    data-height="600"
                    data-checkbox-header="false"
                    data-url="../../php/getCarritoCurrentData.php">
                    <thead>
                        <tr>
                            <th data-field="edo" data-formatter="edoFormater" data-width="40"></th>
                            <th data-field="code" data-halign="center" data-align="left" data-width="100">Código</th>
                            <th data-field="relacionado" data-halign="center" data-align="left" data-width="120" data-formatter="relacionadoFormater">Relacionado</th>
                            <th data-field="stock" data-halign="center" data-align="center" data-width="80" data-formatter="stockFormater">Stock</th>
                            <th data-field="llegando" data-halign="center" data-align="center" data-width="90" data-formatter="llegandoFormater">Llegando</th>
                            <!--<th data-field="precio_opciones" data-halign="center" data-align="center" data-width="200" data-formatter="precioOpcionesFormater">Precio</th>
                            <th data-field="precio_manual" data-halign="center" data-align="center" data-width="120" data-formatter="precioManualFormater">Precio Manual</th> -->
                            <!-- NUEVA COLUMNA COMBINADA - SOLO EN ESTA TABLA -->
                            <th data-field="precio_combinado" data-halign="center" data-align="center" data-width="280" data-formatter="precioCombinadoFormater">Precio</th>                            <th data-field="cantidad" data-halign="center" data-align="center" data-width="100" data-formatter="cantidadFormater">Cantidad</th>
                            <th data-field="unidad" data-halign="center" data-align="center" data-width="80">Unidad</th>
                            <th data-field="tiempo_entrega" data-halign="center" data-align="center" data-width="120" data-formatter="tiempoEntregaFormater">Tiempo Entrega</th>
                    <th data-field="monto" data-halign="center" data-align="right" data-width="100" data-formatter="montoFormater">Monto</th>
                </tr>
            </thead>
        </table>
        
        <div style="text-align: right; margin-top: 20px;">
            <a class="updTot" href="javascript:void(0)" onClick="updateTotal()" title="update">
                <i class="bi bi-arrow-clockwise"></i>
                <h4 id="MontoTotal" style="color: #037C79; font-weight: bold;"></h4>
            </a>
        </div>

        <div class="input-group mt-3">
            <span class="input-group-text">Comentarios del Presupuesto:</span>
            <textarea class="form-control" id="comentarioPresupuesto" rows="2"></textarea>
        </div>

        <!-- NUEVA SECCIÓN: Descuentos y Recargos -->
        <div class="container mt-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0">Descuento</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label">Concepto de Descuento:</label>
                                <input type="text" class="form-control" id="descuento_texto" placeholder="Ej: Descuento por volumen">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Porcentaje de Descuento (%):</label>
                                <input type="number" class="form-control" id="descuento_porcentaje" step="0.1" min="0" max="100" placeholder="0.0" value="0" onchange="calcularDescuentoDesdePorcentaje()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Monto Calculado ($):</label>
                                <input type="number" class="form-control" id="descuento_monto" step="0.001" min="0" placeholder="0.000" value="0" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Recargo</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label">Concepto de Recargo:</label>
                                <input type="text" class="form-control" id="recargo_texto" placeholder="Ej: Recargo por urgencia">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Monto de Recargo ($):</label>
                                <input type="number" class="form-control" id="recargo_monto" step="0.001" min="0" placeholder="0.000" value="0">
                            </div>
                             <!-- NUEVO CAMPO IVA -->
                            <div class="mb-2">
                                <label class="form-label">IVA (%):</label>
                                <input type="number" class="form-control" id="iva_porcentaje" step="0.1" min="0" max="100" placeholder="0.0" value="0" onchange="calcularIVA()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Monto de IVA ($):</label>
                                <input type="number" class="form-control" id="iva_monto" step="0.001" min="0" placeholder="0.000" value="0" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                
            </div>



        </div>

        <button type="button" class="btn btn-success btn-lg" id="reg-presupuesto" onClick="guardarPresupuesto()" style="margin: 20px 40px 10px;">
            <i class="bi bi-save"></i> Guardar Presupuesto
        </button>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><i class="bi bi-arrow-return-left"></i> Regresar</button>
    </div>
</div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/mobile/bootstrap-table-mobile.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/tableExport.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF/jspdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF-AutoTable/jspdf.plugin.autotable.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/export/bootstrap-table-export.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/js/tom-select.complete.min.js"></script>
<script src="../../js/jquery.redirect.js" type="text/javascript"></script>

<!-- JavaScript específico de presupuestos -->
<script src="../../js/presupuesto.js" type="text/javascript"></script>

<script type="text/javascript">
    // Variables globales
    var $tableMain = $('#table-main');;
    var $tableShowPedido = $('#table-pedidos');

    // Formateadores para la tabla principal
    function fotoFormater(value, row) {
        var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
        if (value != 'empty.jpg') {
            strReturn = '<a class="ver" data-bs-toggle="modal" data-bs-target="#myModal" href="#" onClick="verFoto(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>';
        }
        return strReturn;
    }

    function checkFormater(value, row) {
        //console.log('Check formater para:', row.code, 'Carrito:', codes_carrito);
        
        if (codes_carrito.length > 0) {
            for (i = 0; i < codes_carrito.length; i++) {
                if (row.code === codes_carrito[i].code) {
                    console.log('Producto encontrado en carrito, marcando check');
                    return { checked: true };
                }
            }
        }
        //console.log('Producto NO encontrado en carrito');
        return { checked: false };
    }

    function precioFormatergen(value, row) {
        return '$' + value.replace(/[.]/, ",");
    }

    function precioMayorFormater(value, row) {
        if (value == 0) return '---';
        return '$' + value.replace(/[.]/, ",");
    }

    function precioFormaterPresup(value, row) {
        if (parseFloat(row.monto) * 2 < parseFloat(value)) {
            return '<i style="color: #720000ff; font-style: normal;font-weight: bold">$'+value.replace(/[.]/, ",")+'</i>';
        }
        return '$'+value.replace(/[.]/, ",");
    }


    // Formateador para la descripción
    function descripcionFormater(value, row) {
        // Si no_code es true, mostrar input + botón
        if (row.no_code == 't') {
            return `
                <div class="descripcion-editable-container">
                    <div class="input-group input-group-sm">
                        <input type="text" 
                            class="form-control descripcion-input" 
                            value="${value || ''}" 
                            data-code="${row.code}"
                            placeholder="Ingrese descripción personalizada"
                            style="font-size: 0.9rem;">
                        <button class="btn btn-outline-primary btn-sm guardar-descripcion" 
                                type="button"
                                data-code="${row.code}"
                                title="Guardar descripción">
                            <i class="bi bi-save"></i>
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                        Producto sin código - Puede personalizar la descripción
                    </small>
                </div>
            `;
        }
        
        // Si no_code es false, mostrar la descripción normal
        return `<span class="descripcion-normal">${value || ''}</span>`;
    }

    // Función para guardar la descripción personalizada
    function guardarDescripcionPersonalizada(code) {
        const input = $(`.descripcion-input[data-code="${code}"]`);
        const descripcion = input.val().trim();
        const boton = $(`.guardar-descripcion[data-code="${code}"]`);
        
        if (!descripcion) {
            mostrarNotificacion('Por favor ingrese una descripción', 'warning');
            input.focus();
            return;
        }
        
        // Mostrar indicador de carga en el botón
        const iconoOriginal = boton.html();
        boton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        boton.prop('disabled', true);
        
        // Enviar al servidor
        $.post("../../php/guardarDescripcionPersonalizada.php", {
            code: code,
            descripcion: descripcion,
            usuario: numUsr
        }, function(data) {
            // Restaurar botón
            boton.html(iconoOriginal);
            boton.prop('disabled', false);
            
            try {
                const respuesta = JSON.parse(data);
                if (respuesta.success) {
                    mostrarNotificacion('Descripción guardada correctamente', 'success');
                    
                    // Actualizar el dato en la fila de la tabla
                    const filaIndex = $tableMain.bootstrapTable('getRowIndexByUniqueId', code);
                    if (filaIndex !== -1) {
                        // Actualizar el valor en la tabla
                        $tableMain.bootstrapTable('updateRow', {
                            index: filaIndex,
                            row: {
                                name: descripcion
                            }
                        });
                    }
                } else {
                    mostrarNotificacion(respuesta.error || 'Error al guardar', 'danger');
                }
            } catch (e) {
                console.error('Error parseando respuesta:', e);
                mostrarNotificacion('Error en la respuesta del servidor', 'danger');
            }
        }).fail(function() {
            // Restaurar botón en caso de error
            boton.html(iconoOriginal);
            boton.prop('disabled', false);
            mostrarNotificacion('Error de conexión con el servidor', 'danger');
        });
    }

    // Event delegation para los botones de guardar descripción
    $(document).on('click', '.guardar-descripcion', function() {
        const code = $(this).data('code');
        guardarDescripcionPersonalizada(code);
    });

    // Permitir guardar con Enter en el input
    $(document).on('keypress', '.descripcion-input', function(e) {
        if (e.which === 13) { // Enter key
            const code = $(this).data('code');
            guardarDescripcionPersonalizada(code);
            e.preventDefault();
        }
    });



    function rowStyle(row, index) {
        if (index % 2 === 0) {
            return { css: { color: 'white', background: '#037C79' } };
        }
        return { css: { color: 'black', background: '#00CCCC' } };
    }

    // Función para ver foto
    function verFoto(val) {
        urlString = "../../php/getOneProductPhoto.php?code=" + val;
        $('.modal-body').load(urlString, function() {
            $('#myModal').modal({show:true});
        });
    }

    // Funciones básicas
    function backHome() {      
        window.location.href = "../../";
    }

    function backToSelfAlt() {
        window.location.reload();
    }

    function showPedidoClient() {
        // Abrir la página de ver presupuestos
         window.location.href = 'verPresupuestos.php';
    }

    // Función para cargar el carrito al iniciar la página
    function cargarCarritoInicial() {
        console.log('Cargando carrito inicial...');
        $.get("../../php/getCarritoCurrentData.php", function(data) {
            try {
                const carritoData = JSON.parse(data);
                codes_carrito = carritoData.map(item => ({
                    code: item.code,
                    cantidad: item.cantidad,
                    precio: item.precio,
                    tiempo_entrega: item.tiempo_entrega
                }));
                console.log('Carrito inicial cargado:', codes_carrito.length, 'productos');
                
                // Forzar actualización de los checks en la tabla principal
                if ($tableMain.length > 0) {
                    setTimeout(() => {
                        $tableMain.bootstrapTable('refresh');
                    }, 500);
                }
            } catch (e) {
                console.error('Error cargando carrito inicial:', e);
            }
        }).fail(function() {
            console.error('Error al cargar carrito inicial');
        });
    }

    // SISTEMA UNIFICADO DE NOTIFICACIONES TOAST
    function showToast(type, title, message) {
        // Configurar colores e iconos según tipo
        const config = {
            success: {
                bg: 'bg-success',
                icon: 'bi-check-circle',
                iconColor: 'text-white'
            },
            danger: {
                bg: 'bg-danger',
                icon: 'bi-exclamation-circle',
                iconColor: 'text-white'
            },
            warning: {
                bg: 'bg-warning',
                icon: 'bi-exclamation-triangle',
                iconColor: 'text-dark'
            },
            info: {
                bg: 'bg-info',
                icon: 'bi-info-circle',
                iconColor: 'text-white'
            },
            primary: {
                bg: 'bg-primary',
                icon: 'bi-info-circle',
                iconColor: 'text-white'
            }
        };
        
        const toastConfig = config[type] || config.info;
        
        // Crear ID único
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        // Crear HTML del toast
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center ${toastConfig.bg} text-white border-0 position-fixed bottom-0 end-0 m-3" 
                 role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body p-3">
                        <div class="d-flex align-items-center">
                            <i class="bi ${toastConfig.icon} ${toastConfig.iconColor} fs-4 me-3"></i>
                            <div>
                                ${title ? `<strong class="me-2">${title}</strong><br>` : ''}
                                <span class="small">${message}</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-3 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        // Agregar al body
        $('body').append(toastHtml);
        
        // Inicializar y mostrar
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Limpiar del DOM después de ocultar
        toastElement.addEventListener('hidden.bs.toast', function() {
            $(this).remove();
        });
        
        return toastId;
    }

    // Función rápida para notificaciones comunes
    function mostrarNotificacion(mensaje, tipo = 'success') {
        const titulos = {
            success: 'Éxito',
            danger: 'Error',
            warning: 'Advertencia',
            info: 'Información',
            primary: 'Información'
        };
        
        return showToast(tipo, titulos[tipo] || 'Información', mensaje);
    }

    // Función para limpiar el carrito
    function limpiarCarrito() {
        if (!confirm('¿Está seguro que desea limpiar todo el carrito?\n\n⚠️ Esta acción eliminará todos los productos seleccionados y no se puede deshacer.')) {
            return;
        }
        
        console.log('🔄 Iniciando limpieza de carrito...');
        console.log('Usuario actual:', numUsr);
        
        // Mostrar indicador de carga en el botón
        const btnLimpiar = event.target.closest('button') || event.target;
        const originalHtml = btnLimpiar.innerHTML;
        btnLimpiar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Limpiando...';
        btnLimpiar.disabled = true;
        
        // Mostrar toast de "procesando"
        const loadingToastId = showToast('info', 'Limpiando carrito', 'Por favor espere...');
        
        // CORREGIR LA RUTA: Desde admin/presupuestos/index.php necesitamos subir 2 niveles
        const url = '../../../php/limpiarCarrito.php';
        console.log('🌐 URL de petición:', url);
        
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: {
                usuario: numUsr
            },
            success: function(data) {
                console.log('📥 Respuesta del servidor:', data);
                
                // Restaurar botón
                btnLimpiar.innerHTML = originalHtml;
                btnLimpiar.disabled = false;
                
                if (data && data.success) {
                    // Limpiar la variable global
                    codes_carrito = [];
                    
                    // Desmarcar todos los checkboxes en la tabla principal
                    if ($tableMain && $tableMain.length > 0) {
                        $tableMain.bootstrapTable('uncheckAll');
                    }
                    
                    // Refrescar la tabla
                    $tableMain.bootstrapTable('refresh');
                    
                    // Mostrar notificación de éxito
                    const mensaje = data.productos_eliminados > 0 
                        ? `✅ Se eliminaron ${data.productos_eliminados} productos del carrito`
                        : '✅ El carrito ya estaba vacío';
                    
                    mostrarNotificacion(mensaje, 'success');
                    
                    // Si el modal está abierto, cerrarlo
                    if ($('#ModalMakePedido').is(':visible')) {
                        $('#ModalMakePedido').modal('hide');
                    }
                } else {
                    const errorMsg = data && data.error ? data.error : 'Error desconocido';
                    console.error('❌ Error del servidor:', errorMsg);
                    mostrarNotificacion('Error: ' + errorMsg, 'danger');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('❌ Error AJAX:', {
                    status: jqXHR.status,
                    textStatus: textStatus,
                    errorThrown: errorThrown,
                    responseText: jqXHR.responseText
                });
                
                // Restaurar botón
                btnLimpiar.innerHTML = originalHtml;
                btnLimpiar.disabled = false;
                
                let errorMsg = 'Error de conexión con el servidor';
                if (textStatus === 'timeout') {
                    errorMsg = 'Tiempo de espera agotado';
                } else if (jqXHR.status === 404) {
                    errorMsg = 'Archivo no encontrado: limpiarCarrito.php en ' + url;
                } else if (jqXHR.responseText) {
                    // Intentar parsear la respuesta aunque no sea JSON
                    try {
                        const response = JSON.parse(jqXHR.responseText);
                        errorMsg = response.error || 'Error del servidor';
                    } catch (e) {
                        errorMsg = 'Respuesta inválida del servidor: ' + jqXHR.responseText.substring(0, 100);
                    }
                }
                
                mostrarNotificacion(errorMsg, 'danger');
            }
        });
    }

    // Función para asegurar que el check maestro no aparece
    function asegurarSinCheckMaestro() {
        // Eliminar cualquier checkbox en el header
        $('.bootstrap-table th:first-child input[type="checkbox"]').remove();
        
        // También eliminar cualquier elemento de check-all
        $('.bootstrap-table .check-all').remove();
        
        // Prevenir clicks en el header de la primera columna
        $('.bootstrap-table thead th:first-child').css({
            'pointer-events': 'none',
            'cursor': 'default'
        });
    }

    // Event handlers para checkboxes de la tabla principal
    $(function() {
        // Cargar carrito al iniciar
        cargarCarritoInicial();
        
        // Verificar si la tabla existe y no está ya inicializada
        if ($tableMain.length > 0) {
            $tableMain.bootstrapTable({
                checkboxHeader: false  // Deshabilitar check maestro
            })
            .on('check.bs.table', function(e, row) {
                // PRIMERO agregar al carrito (sin precio)
                $.post("../../php/insDelOneProdCarrito.php", { 
                    action: 1, 
                    code: row.code 
                }, function(data) {
                    console.log('Producto agregado al carrito: ' + data);
                    if (data == '1') {
                        // LUEGO establecer precio por defecto si es necesario
                        const precMin = parseFloat(row.prec_min) || 0;
                        const precMay = parseFloat(row.prec_may) || 0;
                        const prec3 = parseFloat(row.prec_3) || 0;
                        
                        let precioPorDefecto = 0;
                        if (precMin > 0) {
                            precioPorDefecto = precMin;
                        } else if (precMay > 0) {
                            precioPorDefecto = precMay;
                        } else if (prec3 > 0) {
                            precioPorDefecto = prec3;
                        }
                        
                        // Solo actualizar precio si hay uno por defecto
                        if (precioPorDefecto > 0) {
                            setTimeout(() => {
                                $.post("../../php/updPrecioOneProdCarrito.php", {
                                    code: row.code,
                                    precio: precioPorDefecto
                                }, function(updateData) {
                                    console.log('Precio por defecto establecido: ' + updateData);
                                    
                                    // ACTUALIZAR EL TOTAL DEL PRESUPUESTO
                                    if (typeof updateTotal === 'function') {
                                        // Pequeño delay para asegurar que la BD se actualizó
                                        setTimeout(() => {
                                            updateTotal();
                                            console.log('✅ Total actualizado después de precio por defecto');
                                        }, 300);
                                    }
                                });
                            }, 100);
                        }
                        
                        // Actualizar codes_carrito
                        if (!codes_carrito.some(item => item.code === row.code)) {
                            codes_carrito.push({
                                code: row.code,
                                cantidad: 1,
                                precio: precioPorDefecto,
                                tiempo_entrega: 0
                            });
                        }
                        console.log('Carrito actualizado:', codes_carrito);
                    }
                });
            })
            .on('uncheck.bs.table', function(e, row) {
                $.post("../../php/insDelOneProdCarrito.php", { 
                    action: 0, 
                    code: row.code 
                }, function(data) {
                    console.log('Producto eliminado del carrito: ' + data);
                    // ACTUALIZAR la variable global codes_carrito
                    if (data == '1') {
                        codes_carrito = codes_carrito.filter(item => item.code !== row.code);
                        console.log('Carrito actualizado:', codes_carrito);
                        
                        if ($tableMain.bootstrapTable('getSelections').length == 0) {
                            backToSelfAlt();
                        }
                    }
                });
            })
            // Ejecutar cuando la tabla cargue
            .on('load-success.bs.table', asegurarSinCheckMaestro)
            .on('post-body.bs.table', asegurarSinCheckMaestro);
        }
        
        // También llamar en ready después de un tiempo
        setTimeout(asegurarSinCheckMaestro, 1000);
        
        // Mejorar la barra de búsqueda
        $('.float-right.search.btn-group').find('input').attr('placeholder', '....');
        $('.float-right.search.btn-group').find('input').wrap("<div class='input-group' id='awsearch'> </div>"); 
        $('#awsearch').prepend("<span class='input-group-addon'><i class='bi bi-search icon-dark-blue'></i> Buscar</span>");

        // Limpiar modales al cerrar
        $('#myModal').on("hide.bs.modal", function() {
            $(".modal-body").html("");
        });
    });

    console.log('Página cargada correctamente');

    // Función para abrir modal automáticamente cuando viene con parámetro
    function verificarAbrirModal() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('abrir_modal') === '1') {
            console.log('Abriendo modal automáticamente...');
            
            // Pequeño delay para asegurar que todo esté cargado
            setTimeout(() => {
                // Forzar actualización del carrito primero
                forzarActualizacionCarrito().then(() => {
                    console.log('Carrito actualizado, abriendo modal...');
                    
                    // Mostrar el modal
                    $('#ModalMakePedido').modal('show');
                    
                    // Limpiar el parámetro de la URL sin recargar
                    const nuevaUrl = window.location.pathname;
                    window.history.replaceState({}, '', nuevaUrl);
                    
                    // Mostrar mensaje informativo usando el sistema unificado
                    setTimeout(() => {
                        mostrarNotificacion(
                            'Los productos del presupuesto anterior se han cargado en el carrito. Recuerde seleccionar cliente y número de presupuesto.',
                            'success'
                        );
                    }, 1000);
                });
            }, 500);
        }
    }

    // Llamar la función cuando el documento esté listo
    $(document).ready(function() {
        verificarAbrirModal();
    });


</script>
</body>
</html>