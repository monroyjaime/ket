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

// Solo mostrar botones si el usuario puede hacer presupuestos
if ($numUsr > 0) {
    $ableToPresupuesto = 't';
    $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 2px 1px;"><i class="bi bi-gear"></i> Def. Presup.</button> ';
    $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalShowPedido" onClick="showPedidoClient()" style="margin: 1px 2px 1px;"><i class="bi bi-file-earmark-ppt"></i> Ver Presup.</button> ';
    
    // Si necesitas consultar datos del usuario desde BD, hazlo aquí
    try {
        require_once("../../php/dbcat_async.php");
        $db = new DBAsync();
        $usuario = $db->consultaSegura("SELECT client, show_all_pres FROM usuario WHERE num = $1", [$numUsr]);
        
        if (!empty($usuario)) {
            $clientNum = intval($usuario[0]->client);
            $showAllPres = $usuario[0]->show_all_pres;
        }
    } catch (Exception $e) {
        error_log("Error consultando usuario: " . $e->getMessage());
    }
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
            data-toggle="table"
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
            data-row-style="rowStyle">
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
                    <th data-field="name" data-halign="center" data-align="left" data-width="500">. . . . . . DESCRIPCION . . . . . .</th>
                    <th data-field="photo_url" data-formatter="fotoFormater">FOTO</th>
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
                                <h6>Número de Presupuesto:</h6>
                                <input type="text" class="form-control" id="numero-presupuesto" placeholder="Número automático o manual">
                            </div>
                        </div>  
                    </div>
                </div> 

                <table
                    id="table-carrito"
                    class="bootstrap-table"
                    data-table-type="make-pedido"
                    data-toggle="table"  
                    data-height="400"
                    data-checkbox-header="false"
                    data-url="../../php/getCarritoCurrentData.php">
                    <thead>
                        <tr>
                            <th data-field="edo" data-formatter="edoFormater" data-width="40"></th>
                            <th data-field="code" data-halign="center" data-align="left" data-width="100">Código</th>
                            <th data-field="relacionado" data-halign="center" data-align="left" data-width="100" data-formatter="relacionadoFormater">Relacionado</th> <!-- NUEVA COLUMNA -->                            <th data-field="name" data-halign="center" data-align="left" data-width="300">Descripción</th>
                            <th data-field="stock" data-halign="center" data-align="center" data-width="80" data-formatter="stockFormater">Stock</th>
                            <th data-field="llegando" data-halign="center" data-align="center" data-width="90" data-formatter="llegandoFormater">Llegando</th>
                            <th data-field="precio_opciones" data-halign="center" data-align="center" data-width="200" data-formatter="precioOpcionesFormater">Precio</th>
                            <th data-field="precio_manual" data-halign="center" data-align="center" data-width="120" data-formatter="precioManualFormater">Precio Manual</th>
                            <th data-field="cantidad" data-halign="center" data-align="center" data-width="100" data-formatter="cantidadFormater">Cantidad</th>
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

<!-- Modal Mostrar Pedidos (SIMPLIFICADO) -->
<div class="modal fade" id="ModalShowPedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Mostrar Presupuestos</h4>
            </div>
            <div class="modal-body">
                <p>Modal para mostrar presupuestos - En desarrollo</p>
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
    var $tableMain = $('#table-main');
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
        if (codes_carrito.length > 0) {
            for (i = 0; i < codes_carrito.length; i++) {
                if (row.code === codes_carrito[i].code) {
                    return { checked: true };
                }
            }
        }
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
        alert('Función mostrar pedidos - En desarrollo');
    }

    // Event handlers para checkboxes de la tabla principal - CORREGIDOS
    $(function() {
        $tableMain.bootstrapTable({})
            .on('check.bs.table', function(e, row) {
                $.post("../../php/insDelOneProdCarrito.php", { 
                    action: 1, 
                    code: row.code 
                }, function(data) {
                    console.log('Producto agregado al carrito: ' + data);
                    // ACTUALIZAR la variable global codes_carrito
                    if (data == '1') {
                        // Agregar a codes_carrito
                        if (!codes_carrito.some(item => item.code === row.code)) {
                            codes_carrito.push({
                                code: row.code,
                                cantidad: 1,
                                precio: 0,
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
            });

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
</script>
</body>
</html>