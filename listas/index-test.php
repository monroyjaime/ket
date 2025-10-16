<?php
session_start();
require_once("../php/dbcat.php");
$db = new DB();

$clientNum= 0;
$vendedorNum= 0;

$tipoPrecio = (isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0;  
$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;

/*if ( isset($_GET['role_num']) ) 
  $role = intval($_GET['role_num']); */

if( isset($_GET['prec']))
{
  $tipoPrecio = intval($_GET['prec']);
  $_SESSION["prec"] = $tipoPrecio;
}  

$otroPrecio = ($tipoPrecio==0)? 1 : 0;
$textPrecio = ($tipoPrecio == 0)? "ver Precios al Mayor" : "ver Precios Minorista";

$btnTipoPrecio ='';
$btnsPedido='';
if($numUsr > 0)
{
  $consult = $db->consultas("SELECT do_pedido FROM usuario WHERE num=".$numUsr);
  foreach ($consult as $value)
      $ableToPedido = $value->do_pedido;
      

  if($ableToPedido == 't')
  {
    $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 40px 1px;"><i class="bi bi-cart"></i> Ver carrito</button> ';
    $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalShowPedido" onClick="showPedidoClient()" style="margin: 1px 40px 1px;"><i class="bi bi-view-list"></i> Ver Pedidos</button> ';
  }
}

if($role >-1 && $role < 3)
{
    $btnTipoPrecio = '<button type="button" class="btn btn-warning btn-sm" onClick="backToSelf('.$role.','.$otroPrecio.')" style="margin: 10px 0px 10px;">'.$textPrecio.'</button>';
  // $btnTipoPrecio = ($tipoPrecio==0)? '<h5>Detal</h5>' : '<h5>Myor</h5>';
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
/*
if($role == -1)
{
  header("Location: ../");
  die();

}  
*/
$prodsCarrito = [];
if($numUsr > 0 && $ableToPedido == 't') //check if there is something in the carrito
{
  $consult = $db->consultas("SELECT product_code FROM pedido_carrito WHERE user_num=".$numUsr." ORDER BY product_code");
  foreach ($consult as $value){
    $prodsCarrito[] = $value->product_code;
  }
}


$stockColumn = ( ($role > -1 && $role < 2) || $role == 5 )? '<th data-field="current_stock" data-halign="center" data-align="right" >STOCK</th>' : '';
$pedidoCheckColumn = ($numUsr > 0 && $ableToPedido == 't')? '<th data-field="checked" data-checkbox="true"  data-formatter="checkFormater"></th>' : '';
//$pedidoCheckColumn = ($numUsr == 1)? '<th data-checkbox="true" data-formatter="checkFormater"></th>' : '';
$precioColumn = ($role == -1)? '' : '<th data-field="cost_max" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>';
$precio80Column = ($role == -1)? '' :'<th data-field="cost_max_80" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.-20%</th>';
$tituloLista = ($role == -1)? '<h2 style="background-color: #037C79; padding-botton: 14px; color: #FFF;">Listado general</h2>' : '<h2 style="background-color: #037C79; color: #FFF;">Listado general '.$titlePrec.' '.$btnTipoPrecio.'</h2>';
$dataUrl = "https://ketelectropartes.com/php/getListaPrecAll.php?prec=".$tipoPrecio;


$tags1  = '<div class="btn-group">';

$tags1 .=    '<a class="nav-link dropdown-toggle disable" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=      'listado Dptos.';
$tags1 .=    '</a>';
$tags1 .=    '<ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start" style="height: auto;max-height: 200px; overflow-x: hidden;">';
$tags1 .=        '<li>';
$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=1 AND show='t' ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .=        '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.','.$role.','.$tipoPrecio.')">'.$dpto_Name.'</a>';
}

$tags1 .= '<hr class="mt-2 mb-3"/>';

$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=2 AND show='t' ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .=        '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.','.$role.','.$tipoPrecio.')">'.$dpto_Name.'</a>';
}

$tags1 .=        '</li>';
$tags1 .=     '</ul>';


$tags1 .=    '<a class="nav-link dropdown-toggle disable" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=      'Catálogo';
$tags1 .=    '</a>';
$tags1 .=    '<ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start" style="height: auto;max-height: 200px; overflow-x: hidden;">';
$tags1 .=        '<li>';
$consult = $db->consultas("SELECT id,name,img_route FROM departamentos WHERE num=1 AND img_route != 'no' ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .= '<a class="dropdown-item" href="#" onClick="javascript:getCatalogo('.$dpto_Id.','.$role.','.$tipoPrecio.')">'.$dpto_Name.'</a>';
}

$tags1 .= '<hr class="mt-2 mb-3"/>';

$consult = $db->consultas("SELECT id,name,img_route FROM departamentos WHERE num=2 AND img_route != 'no' ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .= '<a class="dropdown-item" href="#" onClick="javascript:getCatalogo('.$dpto_Id.','.$role.','.$tipoPrecio.')">'.$dpto_Name.'</a>';
}

$tags1 .=        '</li>';
$tags1 .=     '</ul>';
$tags1 .= '</div>';

$consult=$db->consultas("SELECT full_name,client,vendedor FROM usuario WHERE num=".$numUsr);
foreach ($consult as $value){
  $usrName= $value->full_name;
  $clientNum= intval($value->client);
  $vendedorNum= intval($value->vendedor);
  }

$clientName = "";
if($clientNum >0){
  $consult = $db->consultas("SELECT code,full_name FROM cliente where num = ".$clientNum);
  foreach($consult as $value)
    $clientCode = $value->code;
    $clientName = $value->full_name;
}

$vendedorName ="";  
if($vendedorNum >0){
  $consult = $db->consultas("SELECT code,full_name FROM vendedor where num = ".$vendedorNum);
  foreach($consult as $value)
    $vendedorCode = $value->code;

    $vendedorName = $value->full_name;
}

$clientDefined = ($clientNum==0)? true : false;
$vendedorDefined = ($vendedorNum==0)? true : false;


$usrNameTag = '<h4 style="background-color: #6c757d; padding-botton: 14px; color: #FFF;">Lista de pedidos de '.$usrName.'</h4>';

$optionText = ($clientNum==0)? "Seleccione Cliente..." : $clientName;

$inputClientes  ='<option selected value="'.$clientNum.'">'.$optionText.'</option>';
$arrClients = [];
$consult = $db->consultas("SELECT num,code,full_name FROM cliente ORDER BY num");
foreach ($consult as $value){
  $currObj =  new stdClass();
  $currObj->id = $value->num;
  $currObj->desc = $value->code.' --- '.$value->full_name;
  $arrClients[] = $currObj;

  $inputClientes .= '<option value="'.$value->num.'">'.$value->code.' --- '.$value->full_name.'</option>';
} 

$optionText = ($vendedorNum==0)? "Seleccione Vendedor..." : $vendedorName;

$inputVendedor = '<option selected value="'.$vendedorNum.'">'.$optionText.'</option>';
$consult = $db->consultas("SELECT num,code,full_name FROM vendedor ORDER BY num");
foreach ($consult as $value){
  $inputVendedor .= '<option value="'.$value->num.'">'.$value->code.' --- '.$value->full_name.'</option>';
}



?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Ket-Listas de Precios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css">
    <link href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css" rel="stylesheet">
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

      }

      .nav-link{
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
      .icon-dark-blue{
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
      @media screen  (min-width: 769px) {
        .dropend:hover > .dropdown-menu {
          position: absolute;
          top: 0;
          left: 100%;
        }
        .dropend .dropdown-toggle {
          margin-left: 0.5em;
        }

      }

      a:link { 
        text-decoration: none; 
      } 
      a:visited { 
        text-decoration: none; 
      } 
      a:hover { 
        text-decoration: none; 
      } 
      a:active { 
        text-decoration: none; 
      }
      

      .fixed-table-toolbar .search {
            width: 100%;
      }

      /* Hide the spin buttons in WebKit browsers */
      input::-webkit-outer-spin-button,
      input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

      /* Hide spin buttons in Firefox */
        input[type="number"] {
            -moz-appearance: textfield;
        }

        #Cantidad {
          width: 75px;
        }

    </style>

</head>

<body>
<div class="w-100 p-0" style="background-color: #CCC;">
    <div class="row align-items-start" style="max-height: 50px;">
        <div class="col text-start" style="max-height: 40px; padding-left: 20px;  " > 
        <a href="#" onClick="backHome()" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>
        </div>  
        
        <div class="col text-left">
          <?php echo $tags1; ?>
        </div>
 

        <div class="col text-end" style="max-height: 40px;" >
            <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
        </div>       

    </div>
</div>


  
<div class="col text-center" >
  <div class="col text-center" style="background-color: #DDD;">
    <?php echo $tituloLista; ?>
  
  <div class="col text-center" style="max-height: 40px; padding-botton: 14px; padding-top: 1px;" >
    <?php echo $btnsPedido; ?>
  </div>

<!--  

<div class="input-group">
  <div class="form-outline" data-mdb-input-init>
    <input type="search" id="form1" class="form-control" />
    <label class="form-label" for="form1">Search</label>
  </div>
  <button type="button" class="btn btn-primary" data-mdb-ripple-init>
    <i class="fas fa-search"></i>
  </button>
</div>
 -->
<div id="toolbar" class="select">
    <select class="form-control">
    </select> 
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

      data-url="<?php echo $dataUrl; ?>"
      data-mobile-responsive="false"
      data-check-on-init="true"
      data-row-style="rowStyle">
      <thead>
        <tr>
          <?php echo $pedidoCheckColumn;?>
          <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
          <th data-field="name" data-halign="center" data-align="left" data-width="500">. . . . . . DESCRIPCION . . . . . .</th>
          <?php echo $stockColumn;?>
          <th data-field="unit">UNIDAD</th>
          
          <?php echo $precioColumn;?>
          <?php echo $precio80Column;?>
          <th data-field="photo_url" data-formatter="fotoFormater">FOTO</th>
<!--      <th data-field="photo_url" data-formatter="<?php echo $fotoFormater;?>">FOTO</th>  
          <th data-field="div">DIVISION</th>
          <th data-field="dpto">DPTO.</th> -->
        </tr>
      </thead>
    </table>
  </div>
</div>


<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
        <h4 class="modal-title">Detalle de Producto</h4>
      </div>
      <div class="modal-body">

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><i class="bi bi-x-circle-fill"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="ModalMakePedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="max-width: 90%;" role="document">
    <!-- Modal content-->
    <div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
        <h4 class="modal-title">Definir Pedido</h4>
      </div>
      <div class="modal-body">
        <select id="clients-tom-sel" multiple placeholder="Seleccione Cliente..."></select>
         <div class="input-group mb-3">
         <div class="input-group-prepend">
            <label class="input-group-text " for="inputGroupCliente">Cliente</label>
          </div> 
          <select class="custom-select form-control" id="inputGroupCliente"> 
        <!--  <select  id="inputGroupCliente" placeholder="Seleccione Cliente..." autocomplete="off">  -->
          <?php echo $inputClientes; ?>
          </select>
        </div>
   
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <label class="input-group-text " for="inputGroupVendedor">Vendedor</label>
          </div>
          <select class="custom-select form-control" id="inputGroupVendedor">
          <?php echo $inputVendedor; ?>
          </select>
        </div>

        <table
        id="table"
        data-toggle="table"  
        data-height="300"
        data-url="../php/getCarritoCurrentData.php">

        <thead>
          <tr>
            <th data-field="edo" data-formatter="edoFormater"></th>
            <th data-field="code" data-halign="center" data-align="left">Código</th>
            <th data-field="cantidad" data-halign="center" data-align="right" data-width="125" data-formatter="cantidadFormater">Cantidad</th>
            <th data-field="unidad" data-halign="center" data-align="left">Unidad</th>
            <th data-field="precio" data-halign="center" data-align="right" data-formatter="precioFormater">Precio</th>
            <th data-field="monto" data-halign="center" data-align="right" data-formatter="montoFormater">Monto</th>
            <th data-field="comentario" data-halign="center" data-align="left" data-width="500" data-formatter="comentarioFormater">Descripcion</th>

          </tr>
        </thead>
        </table>
        <div style="text-align: right;">
          <a class="updTot" href="javascript:void(0)"  onClick="catidadCambia()" title="update">
          <i class="bi bi-arrow-clockwise"></i>
          <h5 id="MontoTotal"></h5>
        </div>
        
        <button type="button" class="btn btn-primary btn-sm"  id="reg-pedido" onClick="registrarPedido()" style="margin: 1px 40px 1px;"><i class="bi bi-cart-check" ></i> Registrar Pedido</button>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><i class="bi bi-arrow-return-left"></i> Regresar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="ModalShowPedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="max-width: 90%;" role="document">

    <!-- Modal content-->
     
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
            <label class="input-group-text " for="inputGroupPedidos">Pedidos</label>
          </div>
          <select class="custom-select form-control" id="inputGroupPedidos">

          </select>
          </div>
        </div>

        <table 
          id="table"

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
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

    var client_code = <?php echo $clientCode; ?>;
    var client_name = <?php echo $clientName; ?>;

    var vend_code = <?php echo $vendedorCode; ?>;
    var vend_name = <?php echo $vendedorName; ?>;

    var clients_objs = <?php echo json_encode($arrClients); ?>;

    var codes_carrito = <?php echo json_encode($prodsCarrito); ?>;

    var control = new TomSelect('#clients-tom-sel',{
      maxItems: null,
      valueField: 'id',
      labelField: 'desc',
      searchField: 'desc',
      options: clients_objs,
      create: false
    });

    $( window ).on( "load", function() {
      console.log("on load Selected client num: "+$('#inputGroupCliente').val());
      console.log("from php client num: "+client_num+" vendedor num: "+vend_num);
      console.log("selected on load vendedor num: "+$('#inputGroupVendedor').val());
      /*console.log("productos en carrito: "+codes_carrito.length+"--"+codes_carrito[0]);*/
      for (i = 0; i < codes_carrito.length; i++)        
          console.log((i+1) + ": "+codes_carrito[i]) 
    

      if(client_num > 0){
        $('#inputGroupCliente').val(client_num);
        $('#inputGroupCliente').prop('disabled', true);


      }
      if(vend_num > 0){
        $('#inputGroupVendedor').val(vend_num);
        $('#inputGroupVendedor').prop('disabled', true);
        //console.log("new vendedor num: "+$('#inputGroupVendedor').val());


      }

      if(client_num == 0 || vend_num == 0){
        console.log("CLIENTE O VENDEDOR INDEFINIDO");
        $('#ModalMakePedido #reg-pedido').prop('disabled', true);

      }


    } );

    function debounce(func, timeout = 1000){
      let timer;
      return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
      };
    }

    
    var $table = $('#table')

    var $table1 = $('#ModalShowPedido #table')

    function getRowSelections() {
      return $.map($table.bootstrapTable('getSelections'), function(row) {
        return row;
      })
    }

    $(function() {

      $('#table').bootstrapTable({})
        .on('check.bs.table', function (e, row) {
          //console.log('Code: '+ row.code + " Selected");
          $.post("../php/insDelOneProdCarrito.php",{action: 1, code:row.code}, 
              function(data,status){
                  console.log('insert one prod. messg from Srv: '+data);
              });
          if( $('#inputGroupCliente').val() > 0 && $('#inputGroupVendedor').val() > 0)
          {
            console.log('and more than 0 prods. selected')
            $('#ModalMakePedido #reg-pedido').prop('disabled', false);
          }    
        })
        .on('uncheck.bs.table', function (e, row) {
          //console.log('Code: '+ row.code + " unSelected");
          $.post("../php/insDelOneProdCarrito.php",{action: 0, code:row.code}, 
            function(data,status){
              console.log('delete one prod. messg from Srv: '+data);
              if(data == 1)
              {
                if($table.bootstrapTable('getSelections').length == 0)
                  backToSelfAlt();
              }
            });
          });

      /*$('#ModalMakePedido #table').bootstrapTable({})   
        .on('click-cell.bs.table', function (field,value,row) {
          console.log('click cell: row'+JSON.stringify(row));
        });*/

        function dateFilename() {
        var d = new Date()
        return 'pedidoKet' + d.getFullYear() + 
          ('00' + (d.getMonth() + 1)).slice(-2) +
          ('00' + d.getDate()).slice(-2) + 
          ('00' + d.getHours()).slice(-2) + 
          ('00' + d.getMinutes()).slice(-2) +
          ('00' + d.getSeconds()).slice(-2)
      }


      
       $('#toolbar').find('select').change(function () {
        $table1.bootstrapTable('destroy').bootstrapTable({
          exportDataType: $(this).val(),
          exportTypes: ['excel','pdf'],
          exportOptions: {fileName: dateFilename}          
        })
      }).trigger('change')  
      
      $('.float-right.search.btn-group').find('input').attr('placeholder','....');
      $('.float-right.search.btn-group').find('input').wrap("<div class='input-group' id='awsearch'> </div>"); 
      $('#awsearch').prepend("<span class='input-group-addon'><i class='bi bi-search icon-dark-blue'></i> Buscar</span>")
      $('#myModal').on("hide.bs.modal", function () {
        // put your default event here
        console.log("cerrada Nodal");
        $(".modal-body").html("");
      }); 

      $('#ModalMakePedido').on("hide.bs.modal", function () {
        // put your default event here
        console.log("cerrada Nodal Hacer Pedido");



       // $(".modal-body").html("");
      }); 

      $('#inputGroupCliente').change(function () {
        var selectedClient = $('#inputGroupCliente').val();
        var selectedVendor = $('#inputGroupVendedor').val();

        console.log("Selected client num: "+selectedClient+" Selected vendedor num: "+selectedVendor);
        if (selectedClient ==0 || selectedVendor==0)
          $('#ModalMakePedido #reg-pedido').prop('disabled', true);
        else
          $('#ModalMakePedido #reg-pedido').prop('disabled', false);
    });

    $('#inputGroupVendedor').change(function () {
        var selectedClient = $('#inputGroupCliente').val();
        var selectedVendor = $('#inputGroupVendedor').val();

        console.log("Selected client num: "+selectedClient+" Selected vendedor num: "+selectedVendor);
        if (selectedClient ==0 || selectedVendor==0)
          $('#ModalMakePedido #reg-pedido').prop('disabled', true);
        else
          $('#ModalMakePedido #reg-pedido').prop('disabled', false);
    });

    $('#inputGroupPedidos').change(function(){
      var selectedItem = $('#inputGroupPedidos').val();
      newUrl = '../php/getDataOnePedido.php?num='+selectedItem
      console.log("Selected pedido num: "+newUrl);
      
      $('#ModalShowPedido #table').bootstrapTable('refresh',{url: newUrl});

    })


    })

        function getLista(idDpto,rol){
            console.log( "selected Depertamento: "+idDpto );
            urlString ="index1.php?dpto="+idDpto+"&from=1";
            window.location.href = urlString;
        }   

        function fotoFormater(value, row) {
 
            var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
            if (value != 'empty.jpg')
                strReturn = '<a class="ver" data-bs-toggle="modal" data-bs-target="#myModal" href="#" onClick="verFoto(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>'
            return strReturn;
        }

        function checkFormater(value, row){
          if(codes_carrito.length > 0)
          {
            for(i=0;i<codes_carrito.length;i++)
            {
              if(row.code === codes_carrito[i])
              {
                return {
                  checked: true
                  }
              }
            }
          } 
        }


        function precioFormater(value,row) {
          return '$'+value.replace(/[.]/, ",");
        }

        function precioFormaterPed(value,row) {
          if(parseFloat(value)==0)
            return '<i style="color: #003272; font-style: normal;font-weight: bold">TOTAL:</i>';
          else  
            return '$'+value.replace(/[.]/, ",");
        }
        function montoFormater(value,row){
          return '$' + ((parseInt(row.cantidad)*parseFloat(row.precio)).toFixed(3)).toString().replace(/[.]/, ",");
        }

        function montoFormaterPed(value,row){
          if(parseFloat(value))
            return '$' + (parseFloat(value).toFixed(3)).toString().replace(/[.]/, ",");
          else
            return '$'+((parseInt(row.cantidad)*parseFloat(row.precio)).toFixed(3)).toString().replace(/[.]/, ",");
        }

        function cantidadFormater(value,row){
         return '<input class="form-control" id = "Cantidad" type="number" min="0" value="'+value+'" autofocus onfocus="this.select()" oninput="processCatidadCambia()"/>';
        }

        function comentarioFormater(value,row){
          return '<input class="form-control" id = "Comentario" type="text" value="'+row.name+'" autofocus onfocus="this.select()" />';

        }

        function edoFormater(value,row){
          if(row.cantidad >0)
            returnString = '<i class="bi bi-check-circle-fill icon-green" title="normal"></i>';
          else
            returnString = '<i class="bi bi-x-circle-fill icon-red" title="quitar de pedido"></i>';
          
          return returnString;
        }



        function rowStyle(row, index) {
          if(index % 2 === 0){
            return{
              css: {
                color: 'white',
                background: '#037C79'
              }
            }
          }
          else{
            return{
              css: {
                  color: 'black',
                  background: '#00CCCC'
              }
            }
          }    
        }

        function lastRowStyle(row,index){
          if(index % 2 === 0){
            return{
              css:{
                color: 'black',
                background:'#EEEEEE'
              }
            } 
          }
          else{
            return{
              css:{
                color: 'black',
                background:'#DDDDDD'
              }
            }
          }
        }
        
        function verFoto(val){
          //alert('clickeado '+val)
          urlString ="../php/getOneProductPhoto.php?code="+val;
          $('.modal-body').load(urlString,function(){
              $('#myModal').modal({show:true});
          });
          //window.location.href = urlString;
        }

        function verFotoMin(val){
          //alert('clickeado '+val)
          urlString ="../php/getOneProductPhotoPrecMin.php?code="+val;
          $('.modal-body').load(urlString,function(){
              $('#myModal').modal({show:true});
          });
          //window.location.href = urlString;
        }

        function verFotoMay(val){
          //alert('clickeado '+val)
          urlString ="../php/getOneProductPhotoPrecMay.php?code="+val;
          $('.modal-body').load(urlString,function(){
              $('#myModal').modal({show:true});
          });
          //window.location.href = urlString;
        }
/*
        function backHome(){  
          console.log("usr_num ="+usrNum+" ses_num ="+sesionNum+" ses_id="+sesionId);    
          pagetoCall = "../index.php";
          $.redirect(pagetoCall,{"usr_num": usrNum,
                                 "ses_num": sesionNum,
                                 "ses_id": sesionId });
        }
*/

        function backToSelf(rol,prec){
          urlString ="index.php?prec="+prec;
          window.location.href = urlString;
        } 

        function backToSelfAlt(){
          urlString ="index.php";
          window.location.href = urlString;
        }
        
        function backHome(){      
          urlString =  "../";
          window.location.href = urlString;
        }
        
        function getCatalogo(idDpto,role,prec){   //,checkNum){   
            urlString =  "../catalogo/indexDptoAll2.php?dpto_id="+idDpto+"&line=1&prec="+prec+"&from=1";;
            window.location.href = urlString;
        }

        function getSelected(){
          $('#ModalMakePedido #table').bootstrapTable('refreshOptions',{
            url : '../php/getCarritoCurrentData.php'
          });
          /*var rows = []
          
          rows = $('#ModalMakePedido #table').bootstrapTable('getData');

          
          precios=[];
          montos=[];
          for (let i = 0; i < rows.length; i++){
            precios.push(parseFloat(rows[i].precio));
          }

          $.each($('#ModalMakePedido #table #Cantidad'),function(index,valor){
             montos.push(Math.round(parseInt(valor.value)*precios[index]*1000)/1000);
          })

          currTot = (Math.round(montos.reduce((a, b) => a + b, 0)*1000)/1000).toFixed(3).toString().replace('.', ',')
          $('#ModalMakePedido #MontoTotal').html('Total: $'+currTot)*/
          $('#ModalMakePedido #MontoTotal').html('Total: $');
          //catidadCambia(); 


          $('#ModalMakePedido').modal({show:true});
          //console.log('The following products are selected: ' + selectedItems);

        }

        function registrarPedido(){

          var selectedClientNum = $('#inputGroupCliente').val();
          var selectedVendedorNum = $('#inputGroupVendedor').val();

          var rows = $('#ModalMakePedido #table').bootstrapTable('getData');

          //console.log("row: "+rows+" longitud: "+rows.length)
          const pedido = {};
          productos=[];
          coments=[];
          $.each($('#ModalMakePedido #table #Comentario'),function(index,valor){
            //console.log("comentario"+index+": "+valor.value)
            coments.push(valor.value);
          })

          for (let i = 0; i < rows.length; i++) {

            const producto = {} 
            producto.code = rows[i].code;
            producto.amount = parseInt(rows[i].cantidad);
            producto.precio = parseFloat(rows[i].precio);
            producto.comentario = coments[i]; //rows[i].comentario;

            productos.push(producto)
          } 

          pedido.productos = productos;

          pedido.cliente = selectedClientNum;

          pedido.vendedor = selectedVendedorNum;

          var paramJSON = JSON.stringify(pedido);
          /*
          for (let i = 0; i < productos.length; i++) {
            console.log(productos[i].code+'--'+productos[i].amount+'--'+productos[i].precio); 
          }
          */
          $.post("../php/insertPedidoGeneral.php",
            {data: paramJSON}, 
            function(data,status){
                console.log('insertPedidoGeneral data recibed from Srv: '+data+' status: '+status);
            });
  

          //$('.modal-body').html('');
          $('#ModalMakePedido').modal('hide');
          $.post("../php/insDelOneProdCarrito.php",
            {action: 2}, 
            function(data,status){
                console.log('delete all products/client messg from Srv: '+data+' status: '+status);
            }); 
          backToSelfAlt();
          //console.log('Aqui registraremos pedido Cantidades:');
        }

        function enableCantidad(){
          $('#ModalMakePedido #Cantidad').prop('disabled',false)
          $('#ModalMakePedido #Status').html('<i class="bi bi-check-circle-fill icon-green" title="normal"></i>')

        }        
         
        function catidadCambia(){
          var rows = $('#ModalMakePedido #table').bootstrapTable('getData');

          //console.log("row: "+rows+" longitud: "+rows.length)
          precios=[];
          montos=[];
          cantidades=[];
          codes=[];
          for (let i = 0; i < rows.length; i++) {
                console.log("rows.precio: "+rows[i].precio+" rows.monto: "+rows[i].monto);
                precios.push(parseFloat(rows[i].precio));
                montos.push(parseFloat(rows[i].monto));
                cantidades.push(parseInt(rows[i].cantidad));
                codes.push(rows[i].code);
              }
          $.each($('#ModalMakePedido #table #Cantidad'),function(index,valor){
            console.log('catidad actual: '+cantidades[index]+' cantidad nueva: '+valor.value)
            if(cantidades[index] != parseInt(valor.value))
            {
              $.post("../php/updCantOneProdCarrito.php",
              {cantidad:valor.value, 
               code: codes[index]}, 
              function(data,status){
                  console.log('update cantidad messg from Srv: '+data);
                  if(data == 1)
                    $('#ModalMakePedido #table').bootstrapTable('refresh');
              });
            }
            currMonto = Math.round(parseInt(valor.value)*precios[index]*1000)/1000;
            console.log('cantidad: '+valor.value+' precio:'+precios[index]+' monto: '+currMonto+' leido:'+montos[index])

            if(currMonto != montos[index]){  //update this row
              montos[index] = currMonto
              

              valor.value =currMonto
              
              $('#ModalMakePedido #table').bootstrapTable('updateCell',{
                index: index,
                field: 'monto',
                value: currMonto
              }) 
            }
            currTot = (Math.round(montos.reduce((a, b) => a + b, 0)*1000)/1000).toFixed(3).toString().replace('.', ',')
            //console.log('Total: $'+currTot)
            $('#ModalMakePedido #MontoTotal').html('Total: $'+currTot)
          });
          $.each($('#ModalMakePedido #Monto'),function(index,valor){  
            console.log('Monto: '+montos[index])

            valor.value = '$'+montos[index].toString().replace('.', ',')
          });

          $.each($('#ModalMakePedido #Status'),function(index,valor){  
           // console.log('Monto Sts: '+montos[index]+' valor: '+valor.innerHTML)

            if(montos[index] == 0)
              valor.innerHTML = '<i class="bi bi-x-circle-fill icon-red" title="quitar de pedido"></i>'
            else
              valor.innerHTML = '<i class="bi bi-check-circle-fill icon-green" title="normal"></i>'
          });
          
        }

        const processCatidadCambia = debounce(() => catidadCambia());

        function showPedidoClient(){
          $.post("../php/getInputGroupPedidosClient.php",
            {}, 
            function(data,status){
                console.log('data recibed from Show Pedido');
                //tags = data;
                //$('#ModalShowPedido .modal-body').html(data);
                $('#ModalShowPedido #inputGroupPedidos').html(data);
                $('#ModalShowPedido').modal({show:true});

            });
        }

    </script> 
</body>
</html>
