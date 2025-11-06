<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar session solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../php/dbcat_async.php"); // Cambiar a dbcat_async.php
$db = new DBAsync(); // Usar DBAsync en lugar de DB

$clientNum = 0;
$usrName = "Usuario no identificado";
$clientName = "";
$clientCode = "";
$ganan_glob = 0;
$desc_glob = 0;

$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;
$role = filter_var($_SESSION['role'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

$ableToPresupuesto = 'f';
$showAllPres = 'f';

// Consulta de datos de usuario con validación - USANDO DBAsync
if ($numUsr > 0) {
    try {
        // Usar consultaSegura de DBAsync
        $consult = $db->consultaSegura("SELECT do_presupuesto, show_all_pres, full_name, client FROM usuario WHERE num = $1", [$numUsr]);
        
        if (!empty($consult)) {
            foreach ($consult as $value) {
                $ableToPresupuesto = $value->do_presupuesto;
                $showAllPres = $value->show_all_pres;
                $usrName = $value->full_name;
                $clientNum = intval($value->client);
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta usuario: " . $e->getMessage());
    }

    if ($ableToPresupuesto == 't') {
        $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 2px 1px;"><i class="bi bi-gear"></i> Def. Presup.</button> ';
        $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalShowPedido" onClick="showPedidoClient()" style="margin: 1px 2px 1px;"><i class="bi bi-file-earmark-ppt"></i> Ver Presup.</button> ';
    }
}

// Consulta de productos en carrito
$prodsCarrito = [];
if ($numUsr > 0 && $ableToPresupuesto == 't') {
    try {
        $consult = $db->consultaSegura("SELECT product_code,cantidad,precio,tiempo_entrega FROM presupuesto_carrito WHERE user_num = $1 ORDER BY product_code", [$numUsr]);
        
        foreach ($consult as $value) {
            $objRtn = new stdClass();
            $objRtn->code = $value->product_code;
            $objRtn->cantidad = intval($value->cantidad);
            $objRtn->precio = floatval($value->precio);
            $objRtn->tiempo_entrega =intval($value->tiempo_entrega);
            $prodsCarrito[] = $objRtn;
        }
    } catch (Exception $e) {
        error_log("Error en consulta carrito: " . $e->getMessage());
    }
}

$pedidoCheckColumn = ($numUsr > 0 && $ableToPresupuesto == 't') ? '<th data-field="checked" data-checkbox="true"  data-formatter="checkFormater"></th>' : '';

// Consulta de datos de cliente
if ($clientNum > 0) {
    try {
        $consult = $db->consultaSegura("SELECT code, full_name FROM cliente WHERE num = $1", [$clientNum]);
        
        if (!empty($consult)) {
            foreach ($consult as $value) {
                $clientCode = $value->code;
                $clientName = $value->full_name;
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta cliente: " . $e->getMessage());
    }
}

// Variables booleanas corregidas
$clientDefined = ($clientNum > 0);

$queUsuario = ($showAllPres == 't') ? "todos los usuarios" : htmlspecialchars($usrName);
$usrNameTag = '<h4 style="background-color: #6c757d; padding-bottom: 14px; color: #FFF;">Lista de presupuestos de '.$queUsuario.'</h4>';

// Preparar opciones de clientes
$optionText = ($clientNum == 0) ? "Seleccione Cliente..." : htmlspecialchars($clientCode.' --- '.$clientName);
$inputCliTomSel = '<option value="'.$clientNum.'">'.$optionText.'</option>';

$queryClients = ($showAllPres == 't') ? 
    "SELECT num, code, full_name FROM cliente ORDER BY num" : 
    "SELECT num, code, full_name FROM cliente WHERE vendedor = (SELECT vendedor FROM usuario WHERE num = $1) ORDER BY num";

try {
    if ($showAllPres == 't') {
        $consult = $db->consultaSegura("SELECT num, code, full_name FROM cliente ORDER BY num");
    } else {
        $consult = $db->consultaSegura($queryClients, [$numUsr]);
    }
    
    foreach ($consult as $value) {
        $inputCliTomSel .= '<option value="'.$value->num.'">'.htmlspecialchars($value->code.' --- '.$value->full_name).'</option>';
    }
} catch (Exception $e) {
    error_log("Error en consulta clientes: " . $e->getMessage());
}

// Consulta de valores globales
try {
    $consult = $db->consultaSegura("SELECT ganancia_min_glob, descuento_max_glob FROM all_ket_values");
    
    if (!empty($consult)) {
        foreach ($consult as $value) {
            $ganan_glob = floatval($value->ganancia_min_glob);
            $desc_glob = floatval($value->descuento_max_glob);
        }
    }
} catch (Exception $e) {
    error_log("Error en consulta all_ket_values: " . $e->getMessage());
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
        var client_num = <?php echo $clientNum; ?>;
        var client_code = <?php echo json_encode($clientCode); ?>;
        var client_name = <?php echo json_encode($clientName); ?>;
        var codes_carrito = <?php echo json_encode($prodsCarrito); ?>;
        var numUsr = <?php echo $numUsr; ?>;
    </script>

    <style>
        body {
            text-align: center;
            padding: 0px 0px;
        }
        .nav-link {
            color: #003272;
        }
        span {
            display: inline-block;
            padding: 10px 20px;
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
        .dropend .dropdown-toggle {
            color: #003272;
            margin-left: 1em;
        }
        .dropdown-item:hover {
            background-color: #003272;
            color: #fff;
        }
        .dropdown .dropdown-menu {
            display: none;
        }
        .dropdown:hover > .dropdown-menu,
        .dropend:hover > .dropdown-menu {
            display: block;
            margin-top: 0.125em;
            margin-left: 0.125em;
        }
        @media screen and (min-width: 769px) {
            .dropend:hover > .dropdown-menu {
                position: absolute;
                top: 0;
                left: 100%;
            }
            .dropend .dropdown-toggle {
                margin-left: 0.5em;
            }
        }
        a:link, a:visited, a:hover, a:active { 
            text-decoration: none; 
        }
        .fixed-table-toolbar .search {
            width: 100%;
        }
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
        #Cantidad {
            width: 75px;
        }
        .precio-input, .tiempo-input {
            width: 100px;
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
    </style>
</head>

<body>
<div class="w-100 p-0" style="background-color: #CCC;">
    <div class="row align-items-start" style="max-height: 50px;">
        <div class="col text-start" style="max-height: 40px; padding-left: 20px;">
            <a href="#" onClick="backHome()" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>
        </div>  
        <div class="col text-center" style="max-height: 40px; padding-bottom: 14px; padding-top: 1px;">
            <?php echo $btnsPedido; ?>
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
            data-table-type="main"
            data-toggle="table"
            data-show-export="false"
            data-click-to-select="false"
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
                    <?php echo $pedidoCheckColumn; ?>
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

<!-- Modal Definir Presupuesto -->
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
                                <h6>Cliente:</h6>
                                <select id="clients-tom-sel" placeholder="Seleccione Cliente..." autocomplete="off">
                                    <?php echo $inputCliTomSel; ?>
                                </select> 
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
                            <th data-field="name" data-halign="center" data-align="left" data-width="300">Descripción</th>
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

<!-- Modal Mostrar Pedidos -->
<div class="modal fade" id="ModalShowPedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Mostrar Presupuestos</h4>
            </div>
            <div class="modal-body">
                <div class="col text-center">
                    <?php echo $usrNameTag; ?>
                </div>  
                <div style="text-align: right;">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <label class="input-group-text" for="inputGroupPedidos">Presupuestos</label>
                        </div>
                        <select class="custom-select form-control" id="inputGroupPedidos"></select>
                    </div>
                </div>

                <table 
                    id="table-pedidos"
                    class="bootstrap-table"
                    data-table-type="show-pedido"
                    data-show-export="true"
                    data-click-to-select="true"
                    data-toolbar="#toolbar"
                    data-show-toggle="false"
                    data-show-columns="false"
                    data-search="false"
                    data-searchable="false"
                    data-height="300"
                    data-check-on-init="true"
                    data-url="../../php/getDataOnePedido.php?num=0"
                    data-row-style="lastRowStyle">
                    <thead>
                        <tr>
                            <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
                            <th data-field="cantidad" data-halign="center" data-align="right" data-width="125">CANTIDAD</th>
                            <th data-field="unidad" data-halign="center" data-align="left" data-width="150">UNIDAD</th>
                            <th data-field="precio" data-halign="center" data-align="right" data-width="125" data-formatter="precioFormaterPed">PRECIO</th>
                            <th data-field="monto" data-halign="center" data-align="right" data-width="125" data-formatter="montoFormaterPed">MONTO</th>
                            <th data-field="comentario" data-halign="center" data-align="left" data-width="500">COMENTARIO</th>
                            <th data-field="tipo_prec" data-halign="center" data-align="right" data-width="125">TIPO PREC.</th>
                        </tr>
                    </thead>
                </table>  
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

<script type="text/javascript">
    // Variables globales para las tablas
    var $tableMain = $('#table-main');
    var $tableShowPedido = $('#table-pedidos');
    var $tableMakePedido = $('#table-carrito');
    
    var ctrlClientSel;

    // Función para generar número de presupuesto automático
    function generarNumeroPresupuesto() {
        const timestamp = new Date().getTime();
        const random = Math.floor(Math.random() * 1000);
        return `PRES-${timestamp}-${random}`;
    }

    // Inicialización de Tom Select
    $(document).ready(function() {
        ctrlClientSel = new TomSelect("#clients-tom-sel", {
            sortField: { field: "text", direction: "asc" },
            onChange: function() {
                var selectedClient = parseInt(ctrlClientSel.getValue()) || 0;
                $('#reg-presupuesto').prop('disabled', selectedClient <= 0);
            },
            create: true,
            createOnBlur: true
        });

        // Generar número de presupuesto automáticamente
        $('#numero-presupuesto').val(generarNumeroPresupuesto());

        // Inicializar tabla de mostrar pedidos
        $tableShowPedido.bootstrapTable({
            exportDataType: 'all',
            exportTypes: ['excel', 'pdf'],
            exportOptions: { fileName: 'default_filename' },
            jspdf: {
                orientation: 'p',
                margins: { left:10, right:10, top:20, bottom:20 },
                autotable: { widths: 'auto' }
            }
        });
    });

    $(window).on("load", function() {
        console.log("on load Tom Select client num: " + ctrlClientSel.getValue());
        console.log("from php client num: " + client_num);

        // Mostrar productos en carrito
        for (i = 0; i < codes_carrito.length; i++) {
            console.log((i+1) + ": " + codes_carrito[i].code + " cantidad:" + codes_carrito[i].cantidad);
        }

        // Configurar valor por defecto y deshabilitar si es necesario
        if (client_num > 0) {
            ctrlClientSel.setValue(client_num);
            ctrlClientSel.disable();
        }

        // Verificar si el cliente está definido
        var selectedClient = parseInt(ctrlClientSel.getValue()) || 0;
        $('#reg-presupuesto').prop('disabled', selectedClient <= 0);
    });

    // Función debounce optimizada
    function debounce(func, timeout = 1000) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    }

    // Función para refrescar tabla con Promise
    function refreshCarritoTable() {
        return new Promise((resolve) => {
            $tableMakePedido.bootstrapTable('refresh');
            $tableMakePedido.one('load-success.bs.table', function() {
                resolve();
            });
        });
    }

    // Funciones de formateo para tabla principal
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

    function precioFormaterPed(value, row) {
        if (parseFloat(value) == 0) {
            return '<i style="color: #003272; font-style: normal;font-weight: bold">TOTAL:</i>';
        }
        return '$'+value.replace(/[.]/, ",");
    }

    function montoFormaterPed(value, row) {
        if (parseFloat(value)) {
            return '$' + (parseFloat(value).toFixed(3)).toString().replace(/[.]/, ",");
        }
        return '$'+((parseInt(row.cantidad)*parseFloat(row.precio)).toFixed(3)).toString().replace(/[.]/, ",");
    }

    function rowStyle(row, index) {
        if (index % 2 === 0) {
            return { css: { color: 'white', background: '#037C79' } };
        }
        return { css: { color: 'black', background: '#00CCCC' } };
    }

    function lastRowStyle(row, index) {
        if (index % 2 === 0) {
            return { css: { color: 'black', background: '#EEEEEE' } };
        }
        return { css: { color: 'black', background: '#DDDDDD' } };
    }

    // NUEVOS FORMATEADORES PARA EL MODAL DE PRESUPUESTO
    function stockFormater(value, row) {
        const stock = parseInt(value) || 0;
        if (stock > 0) {
            return '<span class="badge bg-success">' + stock + '</span>';
        } else {
            return '<span class="badge bg-danger">' + stock + '</span>';
        }
    }

    function llegandoFormater(value, row) {
        const llegando = parseInt(value) || 0;
        if (llegando > 0) {
            return '<span class="badge bg-warning text-dark">' + llegando + '</span>';
        } else {
            return '<span class="badge bg-secondary">0</span>';
        }
    }

    function precioOpcionesFormater(value, row) {
        const precMin = parseFloat(row.prec_min) || 0;
        const precMay = parseFloat(row.prec_may) || 0;
        const precioActual = parseFloat(row.precio) || 0;
        
        let selectedMin = '';
        let selectedMay = '';
        
        if (precioActual === precMin) {
            selectedMin = 'checked';
        } else if (precioActual === precMay) {
            selectedMay = 'checked';
        }
        
        return `
            <div class="form-check">
                <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                       value="${precMin}" ${selectedMin} onchange="seleccionarPrecio(this, '${row.code}')">
                <label class="form-check-label small">Precio 1: $${precMin.toFixed(3).replace('.', ',')}</label>
            </div>
            <div class="form-check">
                <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                       value="${precMay}" ${selectedMay} onchange="seleccionarPrecio(this, '${row.code}')">
                <label class="form-check-label small">Precio 2: $${precMay.toFixed(3).replace('.', ',')}</label>
            </div>
        `;
    }

    function precioManualFormater(value, row) {
        const precioActual = parseFloat(row.precio) || 0;
        const precMin = parseFloat(row.prec_min) || 0;
        const precMay = parseFloat(row.prec_may) || 0;
        
        // Si el precio actual no coincide con ninguno de los precios fijos, mostrar en manual
        const mostrarManual = (precioActual !== precMin && precioActual !== precMay && precioActual > 0);
        
        return `
            <input class="form-control precio-manual-input" type="number" step="0.001" min="0" 
                   value="${mostrarManual ? precioActual : ''}" 
                   data-code="${row.code}" 
                   placeholder="Manual..."
                   onfocus="this.select()" 
                   oninput="actualizarPrecioManual(this)"/>
        `;
    }

    function cantidadFormater(value, row) {
        return `
            <input class="form-control cantidad-input" type="number" min="0" 
                   value="${value}" 
                   data-code="${row.code}" 
                   onfocus="this.select()" 
                   oninput="actualizarCantidad(this)"/>
        `;
    }

    function tiempoEntregaFormater(value, row) {
        const stock = parseInt(row.stock) || 0;
        const llegando = parseInt(row.llegando) || 0;
        const cantidad = parseInt(row.cantidad) || 0;
        const tiempoActual = parseInt(value) || 0;
        
        // Calcular tiempo de entrega automático basado en stock
        let tiempoSugerido = 0;
        if (cantidad > 0) {
            if (stock >= cantidad) {
                tiempoSugerido = 0; // Inmediato
            } else if (llegando >= cantidad) {
                tiempoSugerido = 7; // 7 días si está llegando
            } else {
                tiempoSugerido = 30; // 30 días si no hay stock
            }
        }
        
        // Usar el tiempo actual si existe, sino el sugerido
        const tiempoMostrar = tiempoActual > 0 ? tiempoActual : tiempoSugerido;
        
        return `
            <select class="form-control tiempo-select" data-code="${row.code}" onchange="actualizarTiempoEntrega(this)">
                <option value="0" ${tiempoMostrar === 0 ? 'selected' : ''}>Inmediato</option>
                <option value="7" ${tiempoMostrar === 7 ? 'selected' : ''}>7 días</option>
                <option value="15" ${tiempoMostrar === 15 ? 'selected' : ''}>15 días</option>
                <option value="30" ${tiempoMostrar === 30 ? 'selected' : ''}>30 días</option>
                <option value="45" ${tiempoMostrar === 45 ? 'selected' : ''}>45 días</option>
                <option value="60" ${tiempoMostrar === 60 ? 'selected' : ''}>60 días</option>
                <option value="90" ${tiempoMostrar === 90 ? 'selected' : ''}>90 días</option>
            </select>
        `;
    }

    function montoFormater(value, row) {
        const cantidad = parseInt(row.cantidad) || 0;
        const precio = parseFloat(row.precio) || 0;
        const monto = cantidad * precio;
        
        if (monto > 0) {
            return `<strong>$${monto.toFixed(3).replace('.', ',')}</strong>`;
        } else {
            return '$0,000';
        }
    }

    function edoFormater(value, row) {
        const cantidad = parseInt(row.cantidad) || 0;
        if (cantidad > 0) {
            return '<i class="bi bi-check-circle-fill icon-green" title="En presupuesto"></i>';
        }
        return '<i class="bi bi-dash-circle icon-secondary" title="Sin cantidad"></i>';
    }

    // Funciones para manejar las interacciones del modal
    function seleccionarPrecio(radio, code) {
        const precio = parseFloat(radio.value) || 0;
        
        // Limpiar campo manual cuando se selecciona un radio
        $(`.precio-manual-input[data-code="${code}"]`).val('');
        
        debounce(() => {
            $.post("../../php/updPrecioOneProdCarrito.php", {
                code: code,
                precio: precio
            }, function(data) {
                if (data == '1') {
                    updateFilaCarrito(code);
                }
            });
        })();
    }

    function actualizarPrecioManual(input) {
        const code = input.getAttribute('data-code');
        const precio = parseFloat(input.value) || 0;
        
        // Desmarcar radios cuando se escribe manualmente
        $(`.precio-radio[name="precio_${code}"]`).prop('checked', false);
        
        debounce(() => {
            $.post("../../php/updPrecioOneProdCarrito.php", {
                code: code,
                precio: precio
            }, function(data) {
                if (data == '1') {
                    updateFilaCarrito(code);
                }
            });
        })();
    }

    function actualizarCantidad(input) {
        const code = input.getAttribute('data-code');
        const cantidad = parseInt(input.value) || 0;
        
        debounce(() => {
            $.post("../../php/updCantOneProdCarrito.php", {
                code: code,
                cantidad: cantidad
            }, function(data) {
                if (data == '1') {
                    updateFilaCarrito(code);
                }
            });
        })();
    }

    function actualizarTiempoEntrega(select) {
        const code = select.getAttribute('data-code');
        const tiempo = parseInt(select.value) || 0;
        
        debounce(() => {
            $.post("../../php/updTiempoOneProdCarrito.php", {
                code: code,
                tiempo_entrega: tiempo
            }, function(data) {
                console.log('Tiempo actualizado: ' + data);
            });
        })();
    }

    // Función para actualizar una fila específica del carrito
    function updateFilaCarrito(code) {
        refreshCarritoTable().then(() => {
            updateTotal();
        });
    }

    // Función para inicializar tiempos de entrega automáticamente
    function inicializarTiemposEntrega() {
        var rows = $tableMakePedido.bootstrapTable('getData');
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const stock = parseInt(row.stock) || 0;
            const llegando = parseInt(row.llegando) || 0;
            const cantidad = parseInt(row.cantidad) || 0;
            const tiempoActual = parseInt(row.tiempo_entrega) || 0;
            
            if (cantidad > 0 && tiempoActual === 0) {
                let tiempoSugerido = 0;
                
                if (stock >= cantidad) {
                    tiempoSugerido = 0; // Inmediato
                } else if (llegando >= cantidad) {
                    tiempoSugerido = 7; // 7 días si está llegando
                } else {
                    tiempoSugerido = 30; // 30 días si no hay stock
                }
                
                // Actualizar tiempo si es diferente al actual
                if (tiempoSugerido !== tiempoActual) {
                    $.post("../../php/updTiempoOneProdCarrito.php", {
                        code: row.code,
                        tiempo_entrega: tiempoSugerido
                    }, function(data) {
                        console.log('Tiempo inicializado para ' + row.code + ': ' + data);
                    });
                }
            }
        }
    }

    // Funciones de navegación
    function verFoto(val) {
        urlString = "../../php/getOneProductPhoto.php?code=" + val;
        $('.modal-body').load(urlString, function() {
            $('#myModal').modal({show:true});
        });
    }

    function backHome() {      
        urlString = "../../";
        window.location.href = urlString;
    }

    function backToSelfAlt() {
        window.location.reload();
    }

    // Funciones de presupuestos
    function getSelected() {
        refreshCarritoTable().then(() => {
            updateTotal();
            // Inicializar tiempos de entrega automáticamente después de cargar
            setTimeout(inicializarTiemposEntrega, 500);
        });
        $('#ModalMakePedido').modal({show:true});
    }

    function guardarPresupuesto() {
        const selectedClientNum = parseInt(ctrlClientSel.getValue()) || 0;
        const numeroPresupuesto = $('#numero-presupuesto').val();
        const comentarioPresupuesto = $('#comentarioPresupuesto').val();

        if (selectedClientNum === 0) {
            alert('Por favor seleccione un cliente');
            return;
        }

        if (!numeroPresupuesto) {
            alert('Por favor ingrese un número de presupuesto');
            return;
        }

        // Obtener todos los datos del carrito
        var rows = $tableMakePedido.bootstrapTable('getData');
        const productos = [];

        for (let i = 0; i < rows.length; i++) {
            if (parseInt(rows[i].cantidad) > 0) {
                const producto = {
                    code: rows[i].code,
                    name: rows[i].name,
                    cantidad: parseInt(rows[i].cantidad),
                    precio: parseFloat(rows[i].precio) || 0,
                    tiempo_entrega: parseInt(rows[i].tiempo_entrega) || 0,
                    unidad: rows[i].unidad,
                    stock: parseInt(rows[i].stock) || 0,
                    llegando: parseInt(rows[i].llegando) || 0,
                    prec_min: parseFloat(rows[i].prec_min) || 0,
                    prec_may: parseFloat(rows[i].prec_may) || 0
                };
                productos.push(producto);
            }
        }

        if (productos.length === 0) {
            alert('No hay productos en el presupuesto');
            return;
        }

        const presupuesto = {
            numero: numeroPresupuesto,
            cliente: selectedClientNum,
            productos: productos,
            comentario: comentarioPresupuesto,
            usuario: numUsr,
            total: calcularTotalPresupuesto()
        };

        const paramJSON = JSON.stringify(presupuesto);
        
        $.post("../../php/guardarPresupuesto.php", {
            data: paramJSON
        }, function(data, status) {
            console.log('guardarPresupuesto data recibed from Srv: ' + data + ' status: ' + status);
            if (status === 'success' && data == '1') {
                alert('Presupuesto guardado correctamente');
                $('#ModalMakePedido').modal('hide');
                // Limpiar carrito después de guardar
                $.post("../../php/limpiarCarritoPresupuesto.php", function() {
                    backToSelfAlt();
                });
            } else {
                alert('Error al guardar el presupuesto: ' + data);
            }
        }).fail(function() {
            alert('Error de conexión');
        });
    }

    function calcularTotalPresupuesto() {
        var rows = $tableMakePedido.bootstrapTable('getData');
        let total = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const cantidad = parseInt(rows[i].cantidad) || 0;
            const precio = parseFloat(rows[i].precio) || 0;
            total += cantidad * precio;
        }
        
        return total;
    }

    function updateTotal() {
        var rows = $tableMakePedido.bootstrapTable('getData');
        let total = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const cantidad = parseInt(rows[i].cantidad) || 0;
            const precio = parseFloat(rows[i].precio) || 0;
            total += cantidad * precio;
        }
        
        const totalFormateado = total.toFixed(3).replace('.', ',');
        $('#MontoTotal').html('Total Presupuesto: $' + totalFormateado);
    }

    function showPedidoClient() {
        $.post("../../php/getInputGroupPedidosClient.php", {}, 
            function(data, status) {
                $('#ModalShowPedido #inputGroupPedidos').html(data);
                $('#ModalShowPedido').modal({show:true});
            });

        $.post("../../php/getMaxNumStsPedido.php", {}, 
            function(data, status) {
                if (status === 'success') {
                    const obj = JSON.parse(data);
                    numPedido = obj.num_pedido;
                    pedSts = obj.ped_sts;
                    $tableShowPedido.bootstrapTable('refreshOptions', {
                        exportOptions: {
                            fileName: function() {
                                return 'ket' + numPedido;
                            }
                        }  
                    });
                }
            });
    }

    // Event handlers de las tablas
    $(function() {
        $tableMain.bootstrapTable({})
            .on('check.bs.table', function(e, row) {
                $.post("../../php/insDelOneProdCarrito.php", { action: 1, code: row.code }, 
                    function(data, status) {
                        console.log('insert one prod. messg from Srv: ' + data);
                    });
            })
            .on('uncheck.bs.table', function(e, row) {
                $.post("../../php/insDelOneProdCarrito.php", { action: 0, code: row.code }, 
                    function(data, status) {
                        console.log('delete one prod. messg from Srv: ' + data);
                        if (data == 1) {
                            if ($tableMain.bootstrapTable('getSelections').length == 0) {
                                backToSelfAlt();
                            }
                        }
                    });
            });

        $('#inputGroupPedidos').change(function() {
            var selectedItem = $('#inputGroupPedidos').val();
            var newUrl = '../../php/getDataOnePedido.php?num=' + selectedItem;
            $tableShowPedido.bootstrapTable('refresh', { url: newUrl });

            $.post("../../php/getNumStsPedido.php", { num: selectedItem }, 
                function(data, status) {
                    if (status === 'success') {
                        const obj = JSON.parse(data);
                        numPedido = obj.num_pedido;
                        pedSts = obj.ped_sts;
                        $tableShowPedido.bootstrapTable('refreshOptions', {
                            exportOptions: {
                                fileName: function() {
                                    return 'ket' + numPedido;
                                }
                            }  
                        });
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
</script>
</body>
</html>