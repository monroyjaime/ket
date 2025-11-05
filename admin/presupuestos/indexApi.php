<?php
session_start();
require_once("../../php/dbcat.php");
$db = new DB();

// Inicializar variables con valores por defecto
$clientNum = 0;
$vendedorNum = 0;
$clientCode = "";
$clientName = "";
$vendedorCode = "";
$vendedorName = "";
$ableToPresupuesto = 'f';
$showAllPres = 'f';

$tipoPrecio = (isset($_SESSION['prec'])) ? intval($_SESSION['prec']) : 0;  
$numUsr = (isset($_SESSION['usr_num'])) ? intval($_SESSION['usr_num']) : -1;
$role = (isset($_SESSION['role'])) ? intval($_SESSION['role']) : -1;
$onlyStock = (isset($_SESSION['only_stock'])) ? intval($_SESSION['only_stock']) : 0; 

if (isset($_GET['prec'])) {
    $tipoPrecio = intval($_GET['prec']);
    $_SESSION["prec"] = $tipoPrecio;
}  

$otroPrecio = ($tipoPrecio == 0) ? 1 : 0;
$textPrecio = ($tipoPrecio == 0) ? "selec. Precios al Mayor" : "selec. Precios Minorista";

$btnTipoPrecio = '';
$btnsPedido = '';
$showAllPed = 'f';

if ($numUsr > 0) {
    try {
        $consult = $db->consultas("SELECT do_presupuesto, show_all_pres FROM usuario WHERE num=" . $numUsr);
        if ($consult && count($consult) > 0) {
            foreach ($consult as $value) {
                $ableToPresupuesto = isset($value->do_presupuesto) ? $value->do_presupuesto : 'f';
                $showAllPres = isset($value->show_all_pres) ? $value->show_all_pres : 'f';
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta usuario: " . $e->getMessage());
        $ableToPresupuesto = 'f';
        $showAllPres = 'f';
    }

    if ($ableToPresupuesto == 't') {
        $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 2px 1px;"><i class="bi bi-gear"></i> Def. Presup.</button> ';
        $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalShowPedido" onClick="showPedidoClient()" style="margin: 1px 2px 1px;"><i class="bi bi-file-earmark-ppt"></i> Ver Presup.</button> ';
    }
}

$prodsCarrito = [];
if ($numUsr > 0 && $ableToPresupuesto == 't') {
    try {
        $consult = $db->consultas("SELECT product_code,tipo_precio FROM pedido_carrito WHERE user_num=" . $numUsr . " ORDER BY product_code");
        if ($consult) {
            foreach ($consult as $value) {
                $objRtn = new stdClass();
                $objRtn->code = $value->product_code;
                $objRtn->tipo_prec = intval($value->tipo_precio);
                $prodsCarrito[] = $objRtn;
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta carrito: " . $e->getMessage());
        $prodsCarrito = [];
    }
}

$pedidoCheckColumn = ($numUsr > 0 && $ableToPresupuesto == 't') ? '<th data-field="checked" data-checkbox="true"  data-formatter="checkFormater"></th>' : '';

$tituloLista = '<h2 style="background-color: #037C79; padding-bottom: 14px; color: #FFF;">Presupuestos</h2>';

// Consulta información del usuario
try {
    if ($numUsr > 0) {
        $consult = $db->consultas("SELECT full_name,client,vendedor FROM usuario WHERE num=" . $numUsr);
        if ($consult && count($consult) > 0) {
            foreach ($consult as $value) {
                $usrName = isset($value->full_name) ? $value->full_name : '';
                $clientNum = isset($value->client) ? intval($value->client) : 0;
                $vendedorNum = isset($value->vendedor) ? intval($value->vendedor) : 0;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error en consulta usuario info: " . $e->getMessage());
}

// Consulta información del cliente
if ($clientNum > 0) {
    try {
        $consult = $db->consultas("SELECT code,full_name FROM cliente WHERE num = " . $clientNum);
        if ($consult && count($consult) > 0) {
            foreach ($consult as $value) {
                $clientCode = isset($value->code) ? $value->code : '';
                $clientName = isset($value->full_name) ? $value->full_name : '';
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta cliente: " . $e->getMessage());
    }
}

// Consulta información del vendedor
if ($vendedorNum > 0) {
    try {
        $consult = $db->consultas("SELECT code,full_name FROM vendedor WHERE num = " . $vendedorNum);
        if ($consult && count($consult) > 0) {
            foreach ($consult as $value) {
                $vendedorCode = isset($value->code) ? $value->code : '';
                $vendedorName = isset($value->full_name) ? $value->full_name : '';
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta vendedor: " . $e->getMessage());
    }
}

$clientDefined = ($clientNum == 0) ? true : false;
$vendedorDefined = ($vendedorNum == 0) ? true : false;

$queUsuario = ($showAllPres == 't') ? "todos los usuarios" : (isset($usrName) ? $usrName : 'Usuario');

$usrNameTag = '<h4 style="background-color: #6c757d; padding-bottom: 14px; color: #FFF;">Lista de pedidos de ' . $queUsuario . '</h4>';

$optionText = ($clientNum == 0) ? "Seleccione Cliente..." : $clientCode . ' --- ' . $clientName;
$inputCliTomSel = '<option value="' . $clientNum . '">' . $optionText . '</option>';

// Consulta clientes
try {
    $queryClients = ($showAllPres == 't') ? 
        "SELECT num,code,full_name FROM cliente ORDER BY num" : 
        "SELECT num,code,full_name FROM cliente WHERE vendedor=(SELECT vendedor FROM usuario WHERE num=" . $numUsr . ") ORDER BY num";
    
    $consult = $db->consultas($queryClients);
    if ($consult) {
        foreach ($consult as $value) {
            $inputCliTomSel .= '<option value="' . $value->num . '">' . $value->code . ' --- ' . $value->full_name . '</option>';
        }
    }
} catch (Exception $e) {
    error_log("Error en consulta clientes: " . $e->getMessage());
}

$optionText = ($vendedorNum == 0) ? "Seleccione Vendedor..." : $vendedorCode . ' --- ' . $vendedorName;
$inputVenTomSel = '<option value="' . $vendedorNum . '">' . $optionText . '</option>';

// Consulta vendedores
try {
    $consult = $db->consultas("SELECT num,code,full_name FROM vendedor ORDER BY num");
    if ($consult) {
        foreach ($consult as $value) {
            $inputVenTomSel .= '<option value="' . $value->num . '">' . $value->code . ' --- ' . $value->full_name . '</option>';
        }
    }
} catch (Exception $e) {
    error_log("Error en consulta vendedores: " . $e->getMessage());
}

// Consulta valores globales
$ganan_glob = 0;
$desc_glob = 0;
try {
    $consult = $db->consultas("SELECT ganancia_min_global,descuento_max_global FROM all_ket_values");
    if ($consult && count($consult) > 0) {
        foreach ($consult as $value) {
            $ganan_glob = isset($value->ganancia_min_global) ? floatval($value->ganancia_min_global) : 0;
            $desc_glob = isset($value->descuento_max_global) ? floatval($value->descuento_max_global) : 0;
        }
    }
} catch (Exception $e) {
    error_log("Error en consulta valores globales: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Ket-Listas de Precios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/css/tom-select.css" rel="stylesheet">

    <script type="text/javascript">
        var roleNum = <?php echo $role; ?>;  
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

        .loading {
            opacity: 0.6;
            pointer-events: none;
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
            id="table"
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

<!-- Modales (mantener igual que antes) -->
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

<div class="modal fade" id="ModalMakePedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Definir Pedido</h4>
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
                                <h6>Vendedor:</h6>
                                <select id="vendedores-tom-sel" placeholder="Seleccione Vendedor..." autocomplete="off">
                                    <?php echo $inputVenTomSel; ?>
                                </select>  
                            </div>
                        </div>  
                    </div>
                </div> 

                <table
                    id="table-pedido"
                    data-toggle="table"  
                    data-height="300"
                    data-checkbox-header="false"
                    data-url="../../php/getCarritoCurrentData.php">
                    <thead>
                        <tr>
                            <th data-field="edo" data-formatter="edoFormater"></th>
                            <th data-field="code" data-halign="center" data-align="left">Código</th>
                            <th data-field="cantidad" data-halign="center" data-align="right" data-width="125" data-formatter="cantidadFormater">Cantidad</th>
                            <th data-field="unidad" data-halign="center" data-align="left">Unidad</th>
                            <th data-field="monto" data-halign="center" data-align="right" data-formatter="montoFormater">Monto</th>
                            <th data-field="comentario" data-halign="center" data-align="left" data-width="500" data-formatter="comentarioFormater">Descripcion</th>
                        </tr>
                    </thead>
                </table>
                
                <div style="text-align: right;">
                    <a class="updTot" href="javascript:void(0)" onClick="updateTotal()" title="update">
                        <i class="bi bi-arrow-clockwise"></i>
                        <h5 id="MontoTotal"></h5>
                    </a>
                </div>

                <div class="input-group">
                    <span class="input-group-text">Comentarios:</span>
                    <input type="text" aria-label="Last name" class="form-control" id="comentarioPedido">
                </div>

                <button type="button" class="btn btn-primary btn-sm" id="reg-pedido" onClick="registrarPedido()" style="margin: 10px 40px 1px;">
                    <i class="bi bi-cart-check"></i> Registrar Pedido
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal"><i class="bi bi-arrow-return-left"></i> Regresar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ModalShowPedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Mostrar Pedidos</h4>
            </div>
            <div class="modal-body">
                <div class="col text-center">
                    <?php echo $usrNameTag; ?>
                </div>  
                <div style="text-align: right;">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <label class="input-group-text" for="inputGroupPedidos">Pedidos</label>
                        </div>
                        <select class="custom-select form-control" id="inputGroupPedidos"></select>
                    </div>
                </div>

                <table 
                    id="table-pedidos-show"
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
                    data-row-style='lastRowStyle'>
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/mobile/bootstrap-table-mobile.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/tableExport.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF/jspdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF-AutoTable/jspdf.plugin.autotable.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/export/bootstrap-table-export.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/js/tom-select.complete.min.js"></script>
<script src="../../js/jquery.redirect.js" type="text/javascript"></script>
   
<script type="text/javascript">
    // Variables globales - con valores seguros
    var client_num = <?php echo $clientNum; ?>;
    var vend_num = <?php echo $vendedorNum; ?>;
    var client_code = '<?php echo addslashes($clientCode); ?>';
    var client_name = '<?php echo addslashes($clientName); ?>';
    var vend_code = '<?php echo addslashes($vendedorCode); ?>';
    var vend_name = '<?php echo addslashes($vendedorName); ?>';
    var codes_carrito = <?php echo json_encode($prodsCarrito); ?>;

    // ========== FUNCIONES UTILITARIAS ASYNC/AWAIT ==========
    async function apiCall(url, data = {}) {
        try {
            const response = await $.post(url, data);
            return response;
        } catch (error) {
            console.error('Error en API call:', error);
            throw error;
        }
    }

    function setLoading(element, isLoading) {
        if (isLoading) {
            element.addClass('loading');
            element.prop('disabled', true);
        } else {
            element.removeClass('loading');
            element.prop('disabled', false);
        }
    }

    function debounce(func, timeout = 1000) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    }

    // ========== INICIALIZACIÓN ==========
    $(document).ready(function() {
        initializeTables();
        initializeTomSelect();
        setupEventHandlers();
    });

    function initializeTables() {
        $('#table-pedidos-show').bootstrapTable({
            exportDataType: 'all',
            exportTypes: ['excel', 'pdf'],
            exportOptions: {
                fileName: 'default_filename'
            },
            jspdf: {
                orientation: 'p',
                margins: { left: 10, right: 10, top: 20, bottom: 20 },
                autotable: { widths: 'auto' }
            }
        });

        $('#table').bootstrapTable({
            checkboxHeader: false
        });
    }

    function initializeTomSelect() {
        // Configuración TomSelect para clientes
        window.ctrlClientSel = new TomSelect("#clients-tom-sel", {
            sortField: {
                field: "text",
                direction: "asc"
            },
            onChange: handleClientVendorChange,
            create: false
        });

        // Configuración TomSelect para vendedores
        window.ctrlVendedorSel = new TomSelect("#vendedores-tom-sel", {
            sortField: {
                field: "text",
                direction: "asc"
            },
            onChange: handleClientVendorChange,
            create: false
        });

        // Establecer valores iniciales
        if (client_num > 0) {
            ctrlClientSel.setValue(client_num.toString());
        }
        if (vend_num > 0) {
            ctrlVendedorSel.setValue(vend_num.toString());
        }

        // Verificar estado inicial del botón
        handleClientVendorChange();
    }

    function handleClientVendorChange() {
        const selectedClient = parseInt(ctrlClientSel.getValue()) || 0;
        const selectedVendedor = parseInt(ctrlVendedorSel.getValue()) || 0;
        
        $('#ModalMakePedido #reg-pedido').prop('disabled', selectedClient === 0 || selectedVendedor === 0);
    }

    function setupEventHandlers() {
        // Event handlers para la tabla principal
        $('#table').on('check.bs.table', async function(e, row) {
            await handleProductSelect(row, true);
        }).on('uncheck.bs.table', async function(e, row) {
            await handleProductSelect(row, false);
        });

        // Event handler para cambio de pedido
        $('#inputGroupPedidos').change(async function() {
            const selectedItem = $(this).val();
            await loadPedidoData(selectedItem);
        });

        // Configuración de búsqueda
        $('.search input').attr('placeholder', 'Buscar...');
        
        // Modal event handlers
        $('#myModal').on("hide.bs.modal", function() {
            $(".modal-body").html("");
        });
    }

    async function handleProductSelect(row, isSelected) {
        const action = isSelected ? 1 : 0;
        
        try {
            const result = await apiCall("../../php/insDelOneProdCarrito.php", {
                action: action,
                code: row.code
            });
            
            console.log(`${isSelected ? 'Added' : 'Removed'} product ${row.code}: ${result}`);
            
            if (!isSelected && result == 1) {
                const selections = $('#table').bootstrapTable('getSelections');
                if (selections.length === 0) {
                    backToSelfAlt();
                }
            }
            
        } catch (error) {
            console.error('Error handling product selection:', error);
        }
    }

    // ========== FUNCIONES PRINCIPALES ==========
    async function registrarPedido() {
        const btnRegistrar = $('#reg-pedido');
        
        try {
            setLoading(btnRegistrar, true);
            
            const selectedClientNum = parseInt(ctrlClientSel.getValue());
            const selectedVendedorNum = parseInt(ctrlVendedorSel.getValue());
            
            if (selectedClientNum === 0 || selectedVendedorNum === 0) {
                alert('Por favor, seleccione cliente y vendedor');
                return;
            }
            
            const pedidoData = await prepararDatosPedido(selectedClientNum);
            
            await apiCall("../../php/insertPedidoGeneral.php", {
                data: JSON.stringify(pedidoData)
            });
            
            await apiCall("../../php/insDelOneProdCarrito.php", { action: 2 });
            
            $('#ModalMakePedido').modal('hide');
            backToSelfAlt();
            
        } catch (error) {
            console.error('Error al registrar pedido:', error);
            alert('Error al registrar el pedido. Por favor, intente nuevamente.');
        } finally {
            setLoading(btnRegistrar, false);
        }
    }

    async function prepararDatosPedido(selectedClientNum) {
        const rows = $('#table-pedido').bootstrapTable('getData') || [];
        const productos = [];
        
        // Obtener comentarios
        const coments = [];
        $('#ModalMakePedido #Comentario').each(function(index, valor) {
            coments.push(valor.value);
        });
        
        // Preparar productos
        for (let i = 0; i < rows.length; i++) {
            const producto = {
                code: rows[i].code,
                amount: parseInt(rows[i].cantidad) || 0,
                precio: parseFloat(rows[i].prec_min) || 0,
                comentario: coments[i] || rows[i].name || '',
                tipo_prec: 0
            };
            productos.push(producto);
        }
        
        return {
            productos: productos,
            cliente: selectedClientNum,
            comentario: document.getElementById('comentarioPedido').value || ''
        };
    }

    async function showPedidoClient() {
        try {
            const pedidosHTML = await apiCall("../../php/getInputGroupPedidosClient.php");
            $('#ModalShowPedido #inputGroupPedidos').html(pedidosHTML);
            $('#ModalShowPedido').modal({ show: true });
        } catch (error) {
            console.error('Error al cargar pedidos:', error);
            alert('Error al cargar los pedidos del cliente.');
        }
    }

    async function loadPedidoData(selectedItem) {
        const newUrl = `../../php/getDataOnePedido.php?num=${selectedItem}`;
        $('#ModalShowPedido #table-pedidos-show').bootstrapTable('refresh', { url: newUrl });
    }

    // ========== FUNCIONES FORMATER Y UTILITARIAS ==========
    // (Mantener las mismas funciones formatter del código anterior)
    function fotoFormater(value, row) {
        var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
        if (value != 'empty.jpg')
            strReturn = '<a class="ver" data-bs-toggle="modal" data-bs-target="#myModal" href="#" onClick="verFoto(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>'
        return strReturn;
    }

    function checkFormater(value, row) {
        if (codes_carrito && codes_carrito.length > 0) {
            for (let i = 0; i < codes_carrito.length; i++) {
                if (row.code === codes_carrito[i].code) {
                    return { checked: true };
                }
            }
        }
        return { checked: false };
    }

    function precioFormatergen(value, row) {
        return '$' + (value ? value.toString().replace(/[.]/, ",") : '0');
    }

    function precioFormaterPresup(value, row) {
        if (!value) return '$0';
        const rowMonto = parseFloat(row.monto) || 0;
        const currentValue = parseFloat(value) || 0;
        if (rowMonto * 2 < currentValue)
            return '<i style="color: #720000ff; font-style: normal;font-weight: bold">$' + value.toString().replace(/[.]/, ",") + '</i>';
        else
            return '$' + value.toString().replace(/[.]/, ",");
    }

    function precioFormaterPed(value, row) {
        if (!value || parseFloat(value) == 0)
            return '<i style="color: #003272; font-style: normal;font-weight: bold">TOTAL:</i>';
        else
            return '$' + value.toString().replace(/[.]/, ",");
    }

    function montoFormater(value, row) {
        const cantidad = parseInt(row.cantidad) || 0;
        const precio = parseFloat(row.prec_min) || 0;
        const monto = cantidad * precio;
        return '$' + monto.toFixed(3).toString().replace(/[.]/, ",");
    }

    function montoFormaterPed(value, row) {
        if (value && parseFloat(value))
            return '$' + parseFloat(value).toFixed(3).toString().replace(/[.]/, ",");
        else {
            const cantidad = parseInt(row.cantidad) || 0;
            const precio = parseFloat(row.precio) || 0;
            const monto = cantidad * precio;
            return '$' + monto.toFixed(3).toString().replace(/[.]/, ",");
        }
    }

    function cantidadFormater(value, row) {
        return '<input class="form-control" id="Cantidad" type="number" min="0" value="' + (value || 0) + '" autofocus onfocus="this.select()" oninput="processCatidadCambia()"/>';
    }

    function comentarioFormater(value, row) {
        return '<input class="form-control" id="Comentario" type="text" value="' + (row.name || '') + '" autofocus onfocus="this.select()" />';
    }

    function edoFormater(value, row) {
        const cantidad = parseInt(row.cantidad) || 0;
        if (cantidad > 0)
            return '<i class="bi bi-check-circle-fill icon-green" title="normal"></i>';
        else
            return '<i class="bi bi-x-circle-fill icon-red" title="quitar de pedido"></i>';
    }

    function rowStyle(row, index) {
        if (index % 2 === 0) {
            return {
                css: {
                    color: 'white',
                    background: '#037C79'
                }
            }
        } else {
            return {
                css: {
                    color: 'black',
                    background: '#00CCCC'
                }
            }
        }
    }

    function lastRowStyle(row, index) {
        if (index % 2 === 0) {
            return {
                css: {
                    color: 'black',
                    background: '#EEEEEE'
                }
            }
        } else {
            return {
                css: {
                    color: 'black',
                    background: '#DDDDDD'
                }
            }
        }
    }

    function verFoto(val) {
        const urlString = "../../php/getOneProductPhoto.php?code=" + val;
        $('.modal-body').load(urlString, function() {
            $('#myModal').modal({ show: true });
        });
    }

    function backToSelfAlt() {
        location.reload();
    }

    function backHome() {
        window.location.href = "../../";
    }

    function getSelected() {
        $('#table-pedido').bootstrapTable('refresh');
        $('#ModalMakePedido #MontoTotal').html('Total: $');
        $('#ModalMakePedido').modal({ show: true });
    }

    function updateTotal() {
        const rows = $('#table-pedido').bootstrapTable('getData') || [];
        let total = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const cantidad = parseInt(rows[i].cantidad) || 0;
            const precio = parseFloat(rows[i].prec_min) || 0;
            total += cantidad * precio;
        }
        
        $('#ModalMakePedido #MontoTotal').html('Total: $' + total.toFixed(3).replace('.', ','));
    }

    const processCatidadCambia = debounce(() => {
        // Implementación simplificada para evitar complejidad
        updateTotal();
    });

    // Inicialización cuando la ventana carga
    $(window).on("load", function() {
        console.log("Página cargada correctamente");
        if (codes_carrito) {
            console.log("Productos en carrito:", codes_carrito.length);
        }
    });
</script>
</body>
</html>