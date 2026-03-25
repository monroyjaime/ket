<?php
session_start();
require_once("../php/dbcat.php");
$db = new DB();

$clientNum= 0;
$vendedorNum= 0;

$tipoPrecio = (isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0;  
$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;
$onlyStock = (isset($_SESSION['only_stock']))? intval($_SESSION['only_stock']) : 0; 

if( isset($_GET['prec']))
{
  $tipoPrecio = intval($_GET['prec']);
  $_SESSION["prec"] = $tipoPrecio;
}  

$otroPrecio = ($tipoPrecio==0)? 1 : 0;
$textPrecio = ($tipoPrecio == 0)? "selec. Precios al Mayor" : "selec. Precios Minorista";

$btnTipoPrecio ='';
$btnsPedido='';
$showAllPed='f';
if($numUsr > 0)
{
  $consult = $db->consultas("SELECT do_pedido, show_all_ped FROM usuario WHERE num=".$numUsr);
  foreach ($consult as $value){
    $ableToPedido = $value->do_pedido;
    $showAllPed = $value->show_all_ped;
  }
      

  if($ableToPedido == 't')
  {
    $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 2px 1px;"><i class="bi bi-cart"></i> Ver carrito</button> ';
    $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalShowPedido" onClick="showPedidoClient()" style="margin: 1px 2px 1px;"><i class="bi bi-view-list"></i> Ver Pedidos</button> ';
  }
}

if($role == -1)
{
  $precioMinColumn = '';
  $precioMayColumn = '';
}
elseif($role == 3)
{
  $precioMinColumn = '<th data-field="cost_min" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>';
  $precioMayColumn = '';
  $precioMinPedColumn = '<th data-field="prec_min" data-halign="center" data-align="right" data-formatter="precioFormater">Precio</th>';
  $precioMayPedColumn = '';
}
elseif($role >-1 && $role < 3)
{
  $btnTipoPrecio = '<button type="button" class="btn btn-warning btn-sm" onClick="backToSelf('.$role.','.$otroPrecio.')" style="margin: 10px 0px 10px;">'.$textPrecio.'</button>';

  $precioMinColumn = '<th data-field="cost_min" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.MIN.</th>';
  $precioMayColumn = '<th data-field="cost_may" data-halign="center" data-align="right" data-formatter="precioMayorFormater">PREC.MAY.</th>';

  $precioMinPedColumn = '<th data-field="prec_min" data-halign="center" data-align="right" data-formatter="precioFormater">Precio Min.</th>';
  $precioMayPedColumn = '<th data-field="prec_may" data-halign="center" data-align="right" data-formatter="precioMayorFormater">Precio May.</th>';
}

switch($role)
{
    case 1:
      $titlePrec = ($tipoPrecio==0)? "(Precios)" : "(Precios al mayor)";
    break;
    case 2:
      $titlePrec = ($tipoPrecio==0)? "(Precios)" : "(Precios al mayor)";
    break;
    case 3:
      $titlePrec = "(Precios)";
    break;
    case 4:
      $titlePrec = "(Precios al Mayor)";
      $tipoPrecio=1;
    break;
    case 5:
      $titlePrec = "(Precios al Mayor)";
      $tipoPrecio=1;
    break;
}

$prodsCarrito = [];
if($numUsr > 0 && $ableToPedido == 't')
{
  $consult = $db->consultas("SELECT product_code,tipo_precio FROM pedido_carrito WHERE user_num=".$numUsr." ORDER BY product_code");
  foreach ($consult as $value){
    $objRtn = new stdClass();
    $objRtn->code = $value->product_code;
    $objRtn->tipo_prec = intval($value->tipo_precio);
    $prodsCarrito[] = $objRtn;
  }
}

$stockColumn='';
if ($role > -1 && $role < 2)
  $stockColumn =  '<th data-field="current_stock" data-halign="center" data-align="right" >STOCK</th>';
elseif($role == 5)
   $stockColumn = '<th data-field="stock_tot" data-halign="center" data-align="right" >STOCK</th>';

$pedidoCheckColumn = ($numUsr > 0 && $ableToPedido == 't')? '<th data-field="checked" data-checkbox="true"  data-formatter="checkFormater"></th>' : '';
$precioMinColumn = ($role == -1)? '' : '<th data-field="cost_min" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.MIN.</th>';
$precioMinPedColumn = ($role == -1)? '' : '<th data-field="prec_min" data-halign="center" data-align="right" data-formatter="precioFormater">Precio Min.</th>';
$precioMayColumn = ($role == -1 || $role > 2)? '' : '<th data-field="cost_may" data-halign="center" data-align="right" data-formatter="precioMayorFormater">PREC.MAY.</th>';
$precioMayPedColumn = ($role == -1 || $role > 2)? '' : '<th data-field="prec_may" data-halign="center" data-align="right" data-formatter="precioMayorFormater">Precio May.</th>';
$checkPreMayColumn = ($role == -1 || $role > 2)? '' : '<th data-field="check_prec" data-checkbox="true"  data-formatter="checkPrecMayFormater"></th>';

$precioColumn = ($role == -1)? '' : '<th data-field="cost_max" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>';
$precio80Column = ($role == -1)? '' :'<th data-field="cost_max_80" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.-20%</th>';
$tituloLista = ($role == -1)? '<h2 style="background-color: #037C79; padding-bottom: 14px; color: #FFF;">Listado general</h2>' : '<h2 style="background-color: #037C79; color: #FFF;">Listado general '.$titlePrec.' '.$btnTipoPrecio.'</h2>';
$dataUrl = "https://ketelectropartes.com/php/getListaPrecAll.php?prec=".$tipoPrecio;

$consult=$db->consultas("SELECT full_name,client,vendedor FROM usuario WHERE num=".$numUsr);
foreach ($consult as $value){
  $usrName= $value->full_name;
  $clientNum= intval($value->client);
  $vendedorNum= intval($value->vendedor);
  }

$clientName = "";
$clientcode = "";

if($clientNum >0){
  $consult = $db->consultas("SELECT code,full_name FROM cliente where num = ".$clientNum);
  foreach($consult as $value)
    $clientCode = $value->code;
    $clientName = $value->full_name;
}

$vendedorName ="";  
$vendedorCode="";
if($vendedorNum >0){
  $consult = $db->consultas("SELECT code,full_name FROM vendedor where num = ".$vendedorNum);
  foreach($consult as $value)
    $vendedorCode = $value->code;
    $vendedorName = $value->full_name;
}

$clientDefined = ($clientNum==0)? true : false;
$vendedorDefined = ($vendedorNum==0)? true : false;

$queUsuario=($showAllPed == 't')? "todos los usuarios" : $usrName;

$usrNameTag = '<h4 style="background-color: #6c757d; padding-bottom: 14px; color: #FFF;">Lista de pedidos de '.$queUsuario.'</h4>';

$optionText = ($clientNum==0)? "Seleccione Cliente..." : $clientCode.' --- '.$clientName;

$inputCliTomSel ='<option value="'.$clientNum.'">'.$optionText.'</option>';
$queryClients = ($showAllPed == 't')?  "SELECT num,code,full_name FROM cliente ORDER BY num" : "SELECT num,code,full_name FROM cliente WHERE vendedor=(select vendedor from usuario where num=".$numUsr.") ORDER BY num";

$consult = $db->consultas($queryClients);
foreach ($consult as $value)
  $inputCliTomSel .= '<option value="'.$value->num.'">'.$value->code.' --- '.$value->full_name.'</option>';


$optionText = ($vendedorNum==0)? "Seleccione Vendedor..." : $vendedorCode.' --- '.$vendedorName;

$inputVenTomSel ='<option value="'.$vendedorNum.'">'.$optionText.'</option>';

$consult = $db->consultas("SELECT num,code,full_name FROM vendedor ORDER BY num");
foreach ($consult as $value)
  $inputVenTomSel .= '<option value="'.$value->num.'">'.$value->code.' --- '.$value->full_name.'</option>';

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
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/js/tom-select.complete.min.js"></script>

    <script type="text/javascript">
        var roleNum = <?php echo $role;?>;
    </script>

    <style>
        body {
            text-align: center;
            padding: 0px 0px;
            background-color: #DDD;
        }
        
        /* Barra superior */
        .top-bar {
            background-color: #DDD;
            padding: 0px 5px;
        }
        
        .top-bar .row {
            align-items: center;
        }
        
        .top-bar .back-icon {
            font-size: 25px;
            color: #003272;
            text-decoration: none;
        }
        
        .top-bar .logo-mini {
            max-height: 40px;
            width: auto;
        }

             
        .title-banner {
            background-color: #037c79;
            padding: 7px 0;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .title-banner h1 {
            color: white;
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .nav-link{
            color: #003272;
        }
        
        span {
            display: inline-block;
            padding: 10px 20px;
        }
        
        .icon-green { color: green; }
        .icon-yellow { color: yellow; }
        .icon-red { color: red; }
        .icon-large { font-size: 25px; }
        .icon-dark-blue{ color: #003272; }
        
        a:link, a:visited, a:hover, a:active { text-decoration: none; }
        
        .fixed-table-toolbar .search { width: 100%; }
        
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type="number"] { -moz-appearance: textfield; }
        #Cantidad { width: 75px; }
    </style>
</head>
<body>

<!-- Barra superior -->
<!-- Barra superior -->
<div class="top-bar" style="background-color: #DDD; padding: 8px 10px;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
        <!-- Flecha izquierda -->
        <div style="flex-shrink: 0;">
            <a href="../index.php" style="color: #003272; font-size: 24px; text-decoration: none;">
                <i class="bi bi-arrow-left-circle-fill"></i>
            </a>
        </div>
        
        <!-- Botones centrados (ocupan el espacio disponible) -->
        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; flex: 1;">
            <a href="indiceDptos.php" style="
                background-color: #003272;
                color: white;
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                white-space: nowrap;
            ">
                <i class="bi bi-list-ul"></i> Listas
            </a>
            <a href="../catalogo/indiceDptos.php" style="
                background-color: #037C79;
                color: white;
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                white-space: nowrap;
            ">
                <i class="bi bi-images"></i> Catálogos
            </a>
        </div>
        
        <!-- Logo derecho -->
        <div style="flex-shrink: 0;">
            <img src="../catalogo/images/logoMini.png" style="max-height: 35px; width: auto;" alt="KET" />
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
            data-url="../php/getListaPrecAll2Prec.php"
            data-mobile-responsive="false"
            data-check-on-init="true"
            data-row-style="rowStyle">
            <thead>
                <tr>
                    <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
                    <th data-field="name" data-halign="center" data-align="left" data-width="500">DESCRIPCION</th>
                    <?php echo $stockColumn;?>
                    <th data-field="unit">UNIDAD</th>
                    <?php echo $precioMinColumn;?>
                    <?php echo $precioMayColumn;?>
                    <th data-field="photo_url" data-formatter="fotoFormater">FOTO</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modales (mantenemos los originales) -->
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
                    id="table-carrito"
                    data-toggle="table"  
                    data-height="300"
                    data-checkbox-header="false"
                    data-url="../php/getCarritoCurrentData.php">
                    <thead>
                        <tr>
                            <th data-field="edo" data-formatter="edoFormater"></th>
                            <th data-field="code" data-halign="center" data-align="left">Código</th>
                            <th data-field="cantidad" data-halign="center" data-align="right" data-width="125" data-formatter="cantidadFormater">Cantidad</th>
                            <th data-field="unidad" data-halign="center" data-align="left">Unidad</th>
                            <?php echo $precioMinPedColumn;?>
                            <?php echo $precioMayPedColumn;?>
                            <?php echo $checkPreMayColumn;?>
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
                    <input type="text" class="form-control" id="comentarioPedido">
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="reg-pedido" onClick="registrarPedido()" style="margin: 10px 40px 1px;"><i class="bi bi-cart-check"></i> Registrar Pedido</button>
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
                <div class="modal-body">
                    <div style="text-align: right;">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <label class="input-group-text" for="inputGroupPedidos">Pedidos</label>
                            </div>
                            <select class="custom-select form-control" id="inputGroupPedidos"></select>
                        </div>
                    </div>
                    <table 
                        id="table-pedidos"
                        data-show-export="true"
                        data-click-to-select="true"
                        data-toolbar="#toolbar"
                        data-show-toggle="false"
                        data-show-columns="false"
                        data-search="false"
                        data-searchable="false"
                        data-height="300"
                        data-check-on-init="true"
                        data-url="../php/getDataOnePedido.php?num=0"
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
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/mobile/bootstrap-table-mobile.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/tableExport.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF/jspdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF-AutoTable/jspdf.plugin.autotable.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/export/bootstrap-table-export.min.js"></script>
<script src="../js/jquery.redirect.js" type="text/javascript"></script>

<script type="text/javascript">
    var client_num = <?php echo $clientNum; ?>;
    var vend_num = <?php echo $vendedorNum; ?>;
    var client_code = <?php echo "'".$clientCode."'"; ?>;
    var client_name = <?php echo "'".$clientName."'"; ?>;
    var vend_code = <?php echo "'".$vendedorCode."'"; ?>;
    var vend_name = <?php echo "'".$vendedorName."'"; ?>;
    var codes_carrito = <?php echo json_encode($prodsCarrito); ?>;

    $(document).ready(function() {
        $('#ModalShowPedido #table-pedidos').bootstrapTable({
            exportDataType: $(this).val(),
            exportTypes: ['excel','pdf'],
            exportOptions: { fileName: 'default_filename' },
            jspdf: {orientation: 'p', margins: {left:10, right:10, top:20, bottom:20}, autotable: {widths : 'auto'}}
        });
    });

    var eventHandCliVend = function(){
        return function(){
            var selectedClient = parseInt(ctrlClientSel.getValue());
            selectedClient = (isNaN(selectedClient))? 0 : selectedClient;
            var selectedVendedor = parseInt(ctrlVendedorSel.getValue());
            selectedVendedor =(isNaN(selectedVendedor))? 0 : selectedVendedor;
            if (selectedClient ==0 || selectedVendedor==0)
                $('#ModalMakePedido #reg-pedido').prop('disabled', true);
            else
                $('#ModalMakePedido #reg-pedido').prop('disabled', false);
        }
    }

    var ctrlClientSel = new TomSelect("#clients-tom-sel",{
        sortField: { field: "text", direction: "asc" },
        onChange: eventHandCliVend()
    });

    var ctrlVendedorSel = new TomSelect("#vendedores-tom-sel",{
        sortField: { field: "text", direction: "asc" },
        onChange: eventHandCliVend()
    });

    $('#table').bootstrapTable({ checkboxHeader: false });

    $(window).on("load", function() {
        if(client_num > 0){
            ctrlClientSel.setValue(client_num);
            ctrlClientSel.disable();
        }
        if(vend_num > 0){
            ctrlVendedorSel.setValue(vend_num);
            ctrlVendedorSel.disable();
        }
        if(client_num == 0 || vend_num == 0){
            $('#ModalMakePedido #reg-pedido').prop('disabled', true);
        }
    });

    function debounce(func, timeout = 1000){
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    }

    var $table = $('#table');
    var $table2 = $('#table-carrito');

    function getRowSelections() {
        return $.map($table.bootstrapTable('getSelections'), function(row) { return row; });
    }

    function getRowCheckedPrecio() {
        return $.map($table2.bootstrapTable('getSelections'), function(row) { return row; });
    }

    $(function() {
        $('#table').bootstrapTable({})
            .on('check.bs.table', function (e, row) {
                $.post("../php/insDelOneProdCarrito.php",{action: 1, code:row.code}, function(data,status){
                    console.log('insert one prod. messg from Srv: '+data);
                });
                if(parseInt(ctrlClientSel.getValue()) >0 && parseInt(ctrlVendedorSel.getValue()) > 0)    
                {
                    $('#ModalMakePedido #reg-pedido').prop('disabled', false);
                }    
            })
            .on('uncheck.bs.table', function (e, row) {
                $.post("../php/insDelOneProdCarrito.php",{action: 0, code:row.code}, function(data,status){
                    console.log('delete one prod. messg from Srv: '+data);
                    if(data == 1)
                    {
                        if($table.bootstrapTable('getSelections').length == 0)
                            backToSelfAlt();
                    }
                });
            });

        $('#table-carrito').bootstrapTable({})
            .on('check.bs.table', function (e, row,$element) {
                var index = $element.data('index');
                $.post("../php/updTipoPrecProdCarrito.php",{value: 1, code:row.code}, function(data,status){
                    if(data == 1)
                    {
                        $('#table-carrito').bootstrapTable('refresh')
                            .on('load-success.bs.table', function(data){
                                newMonto = row.prec_may*row.cantidad;
                                $('#table-carrito').bootstrapTable('updateCell',{
                                    index: index, field: 'monto', value: newMonto
                                });
                                updateTotal();
                            });
                    }
                });
            })      
            .on('uncheck.bs.table', function (e, row,$element) {
                var index = $element.data('index');
                $.post("../php/updTipoPrecProdCarrito.php",{value: 0, code:row.code}, function(data,status){
                    if(data == 1)
                    {
                        $('#table-carrito').bootstrapTable('refresh')
                            .on('load-success.bs.table', function(data){
                                newMonto = row.prec_min*row.cantidad;
                                $('#table-carrito').bootstrapTable('updateCell',{
                                    index: index, field: 'monto', value: newMonto
                                });
                                updateTotal();
                            });
                    }
                });
            });

        $('.float-right.search.btn-group').find('input').attr('placeholder','....');
        $('.float-right.search.btn-group').find('input').wrap("<div class='input-group' id='awsearch'> </div>"); 
        $('#awsearch').prepend("<span class='input-group-addon'><i class='bi bi-search icon-dark-blue'></i> Buscar</span>");
        
        $('#myModal').on("hide.bs.modal", function () { $(".modal-body").html(""); });
        $('#ModalMakePedido').on("hide.bs.modal", function () { });
        $('#inputGroupPedidos').change(function(){
            var selectedItem = $('#inputGroupPedidos').val();
            newUrl = '../php/getDataOnePedido.php?num='+selectedItem;
            $.post("../php/getNumStsPedido.php", {num:selectedItem}, function(data,status){
                if(status === 'success')
                {
                    const obj = JSON.parse(data);
                    numPedido=obj.num_pedido;
                    $('#ModalShowPedido #table-pedidos').bootstrapTable('refreshOptions',{
                        exportOptions: { fileName: function() { return 'ket'+numPedido; } }
                    });
                }
            });
            $('#ModalShowPedido #table-pedidos').bootstrapTable('refresh',{url: newUrl});
        });
    });

    function getLista(idDpto,rol){
        urlString ="index1.php?dpto="+idDpto+"&from=1";
        window.location.href = urlString;
    }   

    function fotoFormater(value, row) {
        var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
        if (value != 'empty.jpg')
            strReturn = '<a class="ver" data-bs-toggle="modal" data-bs-target="#myModal" href="#" onClick="verFoto(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>';
        return strReturn;
    }

    function checkFormater(value, row){
        if(codes_carrito.length > 0)
        {
            for(i=0;i<codes_carrito.length;i++)
            {
                if(row.code === codes_carrito[i].code)
                    return { checked: true };
            }
        }
        return { checked: false };
    }

    function checkPrecMayFormater(value,row){
        if(row.prec_may == 0)
            return { disabled: true, checked: false };
        else
            return { checked: (row.check_prec == 0) ? false : true };
    }

    function precioFormater(value,row) { return '$'+value .replace(/[.]/, ","); }
    function precioMayorFormater(value,row){ return (value == 0) ? '---' : '$'+value.replace(/[.]/, ","); }
    function precioFormaterPed(value,row) { return (parseFloat(value)==0) ? '<i style="color: #003272; font-style: normal;font-weight: bold">TOTAL:</i>' : '$'+value.replace(/[.]/, ","); }
    
    function montoFormater(value,row){
        currPrec = (row.check_prec == 0)? row.prec_min : row.prec_may;
        return '$' + ((parseInt(row.cantidad)*parseFloat(currPrec)).toFixed(3)).toString().replace(/[.]/, ",");
    }
    
    function montoFormaterPed(value,row){
        if(parseFloat(value))
            return '$' + (parseFloat(value).toFixed(3)).toString().replace(/[.]/, ",");
        else
            return '$'+((parseInt(row.cantidad)*parseFloat(row.precio)).toFixed(3)).toString().replace(/[.]/, ",");
    }
    
    function cantidadFormater(value,row){
        return '<input class="form-control" id="Cantidad" type="number" min="0" value="'+value+'" autofocus onfocus="this.select()" oninput="processCatidadCambia()"/>';
    }
    
    function comentarioFormater(value,row){
        return '<input class="form-control" id="Comentario" type="text" value="'+row.name+'" autofocus onfocus="this.select()" />';
    }
    
    function edoFormater(value,row){
        return (row.cantidad >0) ? '<i class="bi bi-check-circle-fill icon-green" title="normal"></i>' : '<i class="bi bi-x-circle-fill icon-red" title="quitar de pedido"></i>';
    }
    
    function rowStyle(row, index) {
        return { css: (index % 2 === 0) ? { color: 'white', background: '#037C79' } : { color: 'black', background: '#00CCCC' } };
    }
    
    function lastRowStyle(row,index){
        return { css: (index % 2 === 0) ? { color: 'black', background:'#EEEEEE' } : { color: 'black', background:'#DDDDDD' } };
    }
    
    function verFoto(val){
        $('.modal-body').load("../php/getOneProductPhoto.php?code="+val, function(){ $('#myModal').modal({show:true}); });
    }
    
    function backToSelf(rol,prec){ window.location.href = "index.php?prec="+prec; }
    function backToSelfAlt(){ window.location.href = window.location.href; location.reload(true); }
    function backHome(){ window.location.href = "../"; }
    
    function getCatalogo(idDpto,role,prec){
        window.location.href = "../catalogo/indexDptoAll2.php?dpto_id="+idDpto+"&line=1&prec="+prec+"&from=1";
    }
    
    function getSelected(){
        $('#table-carrito').bootstrapTable('refreshOptions',{ url : '../php/getCarritoCurrentData.php' });
        $('#ModalMakePedido').modal({show:true});
    }
    
    function registrarPedido(){
        var selectedClientNum = parseInt(ctrlClientSel.getValue());
        var selectedVendedorNum = parseInt(ctrlVendedorSel.getValue());
        var rows = $('#table-carrito').bootstrapTable('getData');
        const pedido = {};
        productos=[];
        coments=[];
        $.each($('#table-carrito #Comentario'), function(index,valor){ coments.push(valor.value); });
        for (let i = 0; i < rows.length; i++) {
            const producto = {}; 
            producto.code = rows[i].code;
            producto.amount = parseInt(rows[i].cantidad);
            producto.precio = (rows[i].check_prec)? parseFloat(rows[i].prec_may) : parseFloat(rows[i].prec_min);
            producto.comentario = coments[i];
            producto.tipo_prec = (rows[i].check_prec)? 1 : 0;
            productos.push(producto);
        } 
        pedido.productos = productos;
        pedido.cliente = selectedClientNum;
        pedido.vendedor = selectedVendedorNum;
        pedido.comentario = document.getElementById('comentarioPedido').value;
        var paramJSON = JSON.stringify(pedido);
        $.post("../php/insertPedidoGeneral.php", {data: paramJSON}, function(data,status){ console.log('insertPedidoGeneral data recibed from Srv: '+data+' status: '+status); });
        $('#ModalMakePedido').modal('hide');
        $.post("../php/insDelOneProdCarrito.php", {action: 2}, function(data,status){
            if(status === 'success') backToSelfAlt();
        });
    }
    
    function updateTotal(){
        var rows = $('#table-carrito').bootstrapTable('getData');
        montos=[];
        for (let i = 0; i < rows.length; i++) {
            currPrecio = (rows[i].check_prec == 0) ? rows[i].prec_min : rows[i].prec_may;
            currMonto = parseFloat(currPrecio)*parseInt(rows[i].cantidad);
            montos.push(currMonto);
        }
        currTot = (Math.round(montos.reduce((a, b) => a + b, 0)*1000)/1000).toFixed(3).toString().replace('.', ',');
        $('#ModalMakePedido #MontoTotal').html('Total: $'+currTot);
    }
    
    function showPedidoClient(){
        $.post("../php/getInputGroupPedidosClient.php", {}, function(data,status){
            $('#ModalShowPedido #inputGroupPedidos').html(data);
            $('#ModalShowPedido').modal({show:true});
        });
        $.post("../php/getMaxNumStsPedido.php", {}, function(data,status){
            if(status === 'success')
            {
                const obj = JSON.parse(data);
                numPedido=obj.num_pedido;
                $('#ModalShowPedido #table-pedidos').bootstrapTable('refreshOptions',{
                    exportOptions: { fileName: function() { return 'ket'+numPedido; } }
                });
            }
        });
    }
    
    const processCatidadCambia = debounce(() => catidadCambia());
    
    function catidadCambia(){
        var rows = $('#table-carrito').bootstrapTable('getData');
        precios=[]; montos=[]; cantidades=[]; codes=[];
        for (let i = 0; i < rows.length; i++) {
            currPrecio = (rows[i].check_prec == 0)? rows[i].prec_min : rows[i].prec_may;
            precios.push(parseFloat(currPrecio));
            montos.push(parseFloat(rows[i].monto));
            cantidades.push(parseInt(rows[i].cantidad));
            codes.push(rows[i].code);
        }
        $.each($('#table-carrito #Cantidad'), function(index,valor){
            if(cantidades[index] != parseInt(valor.value))
            {
                $.post("../php/updCantOneProdCarrito.php", {cantidad:valor.value, code: codes[index]}, function(data,status){
                    if(data == 1) $('#table-carrito').bootstrapTable('refresh');
                });
            }
            currMonto = Math.round(parseInt(valor.value)*precios[index]*1000)/1000;
            if(currMonto != montos[index]){
                montos[index] = currMonto;
                $('#table-carrito').bootstrapTable('updateCell',{ index: index, field: 'monto', value: currMonto });
            }
            currTot = (Math.round(montos.reduce((a, b) => a + b, 0)*1000)/1000).toFixed(3).toString().replace('.', ',');
            $('#ModalMakePedido #MontoTotal').html('Total: $'+currTot);
        });
        $.each($('#table-carrito #Status'), function(index,valor){
            if(montos[index] == 0)
                valor.innerHTML = '<i class="bi bi-x-circle-fill icon-red" title="quitar de pedido"></i>';
            else
                valor.innerHTML = '<i class="bi bi-check-circle-fill icon-green" title="normal"></i>';
        });
    }
</script> 
</body>
</html>