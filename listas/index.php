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

/*if ( isset($_GET['role_num']) ) 
  $role = intval($_GET['role_num']); */

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
  //$btnTipoPrecio = ($tipoPrecio==0)? '<h5>Detal</h5>' : '<h5>Myor</h5>';
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

//$stockColumn = ( ($role > -1 && $role < 2) || $role == 5 )? '<th data-field="'.$correct_stock.'" data-halign="center" data-align="right" >STOCK</th>' : '';
$pedidoCheckColumn = ($numUsr > 0 && $ableToPedido == 't')? '<th data-field="checked" data-checkbox="true"  data-formatter="checkFormater"></th>' : '';
$precioMinColumn = ($role == -1)? '' : '<th data-field="cost_min" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.MIN.</th>';
$precioMinPedColumn = ($role == -1)? '' : '<th data-field="prec_min" data-halign="center" data-align="right" data-formatter="precioFormater">Precio Min.</th>';
$precioMayColumn = ($role == -1 || $role > 2)? '' : '<th data-field="cost_may" data-halign="center" data-align="right" data-formatter="precioMayorFormater">PREC.MAY.</th>';
$precioMayPedColumn = ($role == -1 || $role > 2)? '' : '<th data-field="prec_may" data-halign="center" data-align="right" data-formatter="precioMayorFormater">Precio May.</th>';
$checkPreMayColumn = ($role == -1 || $role > 2)? '' : '<th data-field="check_prec" data-checkbox="true"  data-formatter="checkPrecMayFormater"></th>';

//$pedidoCheckColumn = ($numUsr == 1)? '<th data-checkbox="true" data-formatter="checkFormater"></th>' : '';
if($role == 3)
{
  $showPedidoPrecioNorm = '<th data-field="prec_min" data-halign="center" data-align="right" data-formatter="precioFormater">Precio</th>';
  $showPedidoPrecioMayor = '';
}
elseif($role>1 && $role<3)
{

}
$showPedidoPrecioNorm = ($role == 3)?  : '<th data-field="prec_min" data-halign="center" data-align="right" data-formatter="precioFormater">Precio Min.</th>';

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
$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=1 AND show='t' ORDER BY orden,name");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .=        '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.','.$role.','.$tipoPrecio.')">'.$dpto_Name.'</a>';
}

$tags1 .= '<hr class="mt-2 mb-3"/>';

$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=2 AND show='t' ORDER BY orden,name");
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
$consult = $db->consultas("SELECT id,name,img_route FROM departamentos WHERE num=1 AND img_route != 'no' ORDER BY orden,name");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .= '<a class="dropdown-item" href="#" onClick="javascript:getCatalogo('.$dpto_Id.','.$role.','.$tipoPrecio.')">'.$dpto_Name.'</a>';
}

$tags1 .= '<hr class="mt-2 mb-3"/>';

$consult = $db->consultas("SELECT id,name,img_route FROM departamentos WHERE num=2 AND img_route != 'no' ORDER BY orden,name");
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

$usrNameTag = '<h4 style="background-color: #6c757d; padding-botton: 14px; color: #FFF;">Lista de pedidos de '.$queUsuario.'</h4>';

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

      data-url="../php/getListaPrecAll2Prec.php"
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

          <?php echo $precioMinColumn;?>
          <?php echo $precioMayColumn;?>
          
          <th data-field="photo_url" data-formatter="fotoFormater">FOTO</th>
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
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="p-4" >
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
        id="table"
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
          <a class="updTot" href="javascript:void(0)"  onClick="updateTotal()" title="update">
          <i class="bi bi-arrow-clockwise"></i>
          <h5 id="MontoTotal"></h5>
        </div>

        <div class="input-group">
          <span class="input-group-text">Comentarios:</span>
          <input type="text" aria-label="Last name" class="form-control" id="comentarioPedido">
        </div>


        
        <button type="button" class="btn btn-primary btn-sm"  id="reg-pedido" onClick="registrarPedido()" style="margin: 10px 40px 1px;"><i class="bi bi-cart-check" ></i> Registrar Pedido</button>

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

    var client_code = <?php echo "'".$clientCode."'"; ?>;
    var client_name = <?php echo "'".$clientName."'"; ?>;

    var vend_code = <?php echo "'".$vendedorCode."'"; ?>;
    var vend_name = <?php echo "'".$vendedorName."'"; ?>;

    var codes_carrito = <?php echo json_encode($prodsCarrito); ?>;

    $(document).ready(function() {
        // Initialize the table
        $('#ModalShowPedido #table').bootstrapTable({
            exportDataType: $(this).val(),

            exportTypes: ['excel','pdf'],

            exportOptions: {
                fileName: 'default_filename' // Default file name
            },

            jspdf: {orientation: 'p',
              margins: {left:10, right:10, top:20, bottom:20},
              autotable: {widths : 'auto'}
            }
        });

      });

    var eventHandCliVend = function(){
      return function(){
        var selectedClient = parseInt(ctrlClientSel.getValue())
        selectedClient = (isNaN(selectedClient))? 0 : selectedClient
        var selectedVendedor = parseInt(ctrlVendedorSel.getValue())
        selectedVendedor =(isNaN(selectedVendedor))? 0 : selectedVendedor
        console.log("Selected client num: "+selectedClient)
        console.log("Selected vendedor num: "+selectedVendedor)
        if (selectedClient ==0 || selectedVendedor==0)
          $('#ModalMakePedido #reg-pedido').prop('disabled', true);
        else
          $('#ModalMakePedido #reg-pedido').prop('disabled', false);



      }

    }


    var ctrlClientSel = new TomSelect("#clients-tom-sel",{
      sortField: {
        field: "text",
        direction: "asc"
      },
      onChange: eventHandCliVend()
    });

    


    var ctrlVendedorSel = new TomSelect("#vendedores-tom-sel",{
      sortField: {
        field: "text",
        direction: "asc"
      },
      onChange: eventHandCliVend()
    });

    $('#table').bootstrapTable({
      checkboxHeader: false, // Disable the "check all" option
      });
   

    $( window ).on( "load", function() {

      

      console.log("on load Tom Select client num: "+ctrlClientSel.getValue());
      console.log("on load Tom Select vendedor num: "+ctrlVendedorSel.getValue());


      console.log("from php client num: "+client_num+" vendedor num: "+vend_num);
      console.log("selected on load vendedor num: "+$('#inputGroupVendedor').val());
      /*console.log("productos en carrito: "+codes_carrito.length+"--"+codes_carrito[0]);*/
      for (i = 0; i < codes_carrito.length; i++)        
          console.log((i+1) + ": "+codes_carrito[i].code+" tipo prec:"+codes_carrito[i].tipo_prec) 
    

      if(client_num > 0){
        ctrlClientSel.setValue(client_num)
        ctrlClientSel.disable()

      }
      if(vend_num > 0){
        ctrlVendedorSel.setValue(vend_num)
        ctrlVendedorSel.disable()
      }

      if(client_num == 0 || vend_num == 0){
        console.log("CLIENTE O VENDEDOR INDEFINIDO");
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

    
    var $table = $('#table')

    var $table1 = $('#ModalShowPedido #table')

    var $table2 = $('#ModalMakePedido #table')


    function getRowSelections() {
      return $.map($table.bootstrapTable('getSelections'), function(row) {
        return row;
      })
    }

    function getRowCheckedPrecio() {
      return $.map($table2.bootstrapTable('getSelections'), function(row) {
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
          //if( $('#inputGroupCliente').val() > 0 && $('#inputGroupVendedor').val() > 0)
          if(parseInt(ctrlClientSel.getValue()) >0 && parseInt(ctrlVendedorSel.getValue()) > 0)    
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

      $('#ModalMakePedido #table').bootstrapTable({})
      .on('check.bs.table', function (e, row,$element) {
        var index = $element.data('index');
        //console.log("index: "+index)
        $.post("../php/updTipoPrecProdCarrito.php",{value: 1, code:row.code}, 
        function(data,status){
          if(data == 1)
          {
            $('#ModalMakePedido #table').bootstrapTable('refresh')
            .on('load-success.bs.table', function(data){

            newMonto = row.prec_may*row.cantidad
            console.log('Code: '+ row.code + " Selected "+status+" new monto: "+newMonto);
            $('#ModalMakePedido #table').bootstrapTable('updateCell',{
                index: index,
                field: 'monto',
                value: newMonto
              })
              
            updateTotal()

            })
          }
        })
      })      
      .on('uncheck.bs.table', function (e, row,$element) {
        var index = $element.data('index');
        //console.log("index: "+index)
        $.post("../php/updTipoPrecProdCarrito.php",{value: 0, code:row.code}, 
        function(data,status){
          if(data == 1)
          {
            $('#ModalMakePedido #table').bootstrapTable('refresh')
            .on('load-success.bs.table', function(data){
              newMonto = row.prec_min*row.cantidad
              console.log('Code: '+ row.code + " unSelected "+status+" new monto: "+newMonto);
              $('#ModalMakePedido #table').bootstrapTable('updateCell',{
                  index: index,
                  field: 'monto',
                  value: newMonto
                })
                
              updateTotal()  

            })

           
          }
        })
      })

      /*$('#ModalMakePedido #table').bootstrapTable({})   
        .on('click-cell.bs.table', function (field,value,row) {
          console.log('click cell: row'+JSON.stringify(row));
        });*/

      /*  function dateFilename() {
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
      }).trigger('change')  */
      
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

/*
      ctrlClientSel.on('change',function{
      console.log("Selected client num: "+ctrlClientSel.getValue())
    })

    ctrlVendedorSel.on('change',function{
      console.log("Selected Vendedor num: "+ctrlVendedorSel.getValue())
    }) 



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

    */

    $('#inputGroupPedidos').change(function(){
      var selectedItem = $('#inputGroupPedidos').val();
      newUrl = '../php/getDataOnePedido.php?num='+selectedItem
      console.log("Selected pedido num: "+newUrl);

    $.post("../php/getNumStsPedido.php",
    {num:selectedItem}, 
    function(data,status){
    //console.log('data recibed from getNumStsPedido');
    if(status === 'success')
    {
        const obj = JSON.parse(data)
        numPedido=obj.num_pedido
        pedSts=obj.ped_sts
        console.log("num pedido  "+numPedido+" ped sts: "+pedSts)
        //console.log("num valery already defined: "+numValery)
        $('#ModalShowPedido #table').bootstrapTable('refreshOptions',{
          exportOptions: {
            fileName: function() {
                return 'ket'+numPedido;
            }
          }  
        })
      }
    });
      
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
              if(row.code === codes_carrito[i].code)
              {
                return {
                  checked: true
                  }
              }
            }
          }
          else
          {
            return {
                  checked: false
                  } 
          }
            
        }

        function checkPrecMayFormater(value,row){
          if(row.prec_may == 0)
            return {
              disabled: true,
              checked: false
          }
          else
          {
            if(row.check_prec == 0)
            {
              return {
                checked: false
                }
            }
            else 
            {
              return {
                checked: true
                }
            }
          }
        }

        function selecPrecFormater(value,row){
            if(row.cost_may == 0)
              return ''
            else
              //return '<div class="form-check form-switch" style="text-align: center"><input class="form-check-input" type="checkbox" role="switch" id="selectPrec"></div>';
              return '<input class="form-check-input" type="checkbox" role="switch" id="selectPrec">'
            
            }

        function precioFormater(value,row) {
          return '$'+value .replace(/[.]/, ",");
        }

        function precioMayorFormater(value,row){
          if(value == 0)
            return '---'
          else
            return '$'+value.replace(/[.]/, ",");
        }

        function precioFormaterPed(value,row) {
          if(parseFloat(value)==0)
            return '<i style="color: #003272; font-style: normal;font-weight: bold">TOTAL:</i>';
          else  
            return '$'+value.replace(/[.]/, ",");
        }
        function montoFormater(value,row){
          currPrec = (row.check_prec == 0)? row.prec_min : row.prec_may
          return '$' + ((parseInt(row.cantidad)*parseFloat(currPrec)).toFixed(3)).toString().replace(/[.]/, ",");
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
          window.location.href = window.location.href;
          location.reload(true);
/*          urlString ="https://ketelectropartes.com/listas/index.php";
          window.location.href = urlString;*/
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
          
          var selectedClientNum = parseInt(ctrlClientSel.getValue())
          var selectedVendedorNum = parseInt(ctrlVendedorSel.getValue())

          selectedPrecMay = getRowCheckedPrecio()
          if(selectedPrecMay.length ==0)
            console.log("ningun precio mayor selecctionado")

  

          var rows = $('#ModalMakePedido #table').bootstrapTable('getData');

          //console.log("row: "+rows+" longitud: "+rows.length)
          console.log("GET DATA: "+JSON.stringify($table2.bootstrapTable('getData')))
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
            producto.precio = (rows[i].check_prec)? parseFloat(rows[i].prec_may) : parseFloat(rows[i].prec_min);
            producto.comentario = coments[i]; //rows[i].comentario;
            producto.tipo_prec = (rows[i].check_prec)? 1 : 0
            productos.push(producto)
          } 

          pedido.productos = productos;

          pedido.cliente = selectedClientNum;

          pedido.vendedor = selectedVendedorNum;

          pedido.comentario = document.getElementById('comentarioPedido').value

          var paramJSON = JSON.stringify(pedido);
          
          for (let i = 0; i < productos.length; i++) {
            console.log(productos[i].code+'--'+productos[i].amount+'--'+productos[i].precio+'--'+productos[i].tipo_prec); 
          }
          
         
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
                if(status === 'success')
                  backToSelfAlt(); 
            });
          //backToSelfAlt(); 
          //console.log('Aqui registraremos pedido Cantidades:');
        }

        function enableCantidad(){
          $('#ModalMakePedido #Cantidad').prop('disabled',false)
          $('#ModalMakePedido #Status').html('<i class="bi bi-check-circle-fill icon-green" title="normal"></i>')

        }        
         
        function catidadCambia(){
          
          var rows = $('#ModalMakePedido #table').bootstrapTable('getData');

          //console.log("rows: "+JSON.stringify(rows)+" longitud: "+rows.length)
          precios=[];
          montos=[];
          cantidades=[];
          codes=[];
          for (let i = 0; i < rows.length; i++) {
            currPrecio = (rows.check_prec == 0)? rows[i].prec_min : rows[i].prec_may;
            //console.log("rows.precio min: "+rows[i].prec_min+" row.check_prec:"+ rows[i].check_prec+" rows.monto: "+rows[i].monto);
            precios.push(parseFloat(currPrecio));
            montos.push(parseFloat(rows[i].monto));
            cantidades.push(parseInt(rows[i].cantidad));
            codes.push(rows[i].code);
          }
          $.each($('#ModalMakePedido #table #Cantidad'),function(index,valor){
            //console.log('catidad actual: '+cantidades[index]+' cantidad nueva: '+valor.value)
            if(cantidades[index] != parseInt(valor.value))
            {
              $.post("../php/updCantOneProdCarrito.php",
              {cantidad:valor.value, 
               code: codes[index]}, 
              function(data,status){
                  //console.log('update cantidad messg from Srv: '+data);
                  if(data == 1)
                    $('#ModalMakePedido #table').bootstrapTable('refresh');
              });
            }
            currMonto = Math.round(parseInt(valor.value)*precios[index]*1000)/1000;
            console.log('cantidad: '+valor.value+' precio:'+precios[index]+' monto: '+currMonto+' leido:'+montos[index])
            console.log("index: "+index+" currentMonto: "+currMonto+'montos[index]: '+montos[index])
            if(currMonto != montos[index]){  //update this row
              console.log("cambio de monto detectado...")
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

        function updateTotal(){
          var rows = $('#ModalMakePedido #table').bootstrapTable('getData');
          console.log("updateTota:: rows.length: "+rows.length)
          montos=[];
          for (let i = 0; i < rows.length; i++) {
            currPrecio = (rows[i].check_prec == 0) ? rows[i].prec_min : rows[i].prec_may
            currMonto = parseFloat(currPrecio)*parseInt(rows[i].cantidad)
            montos.push(currMonto);
          }
          currTot = (Math.round(montos.reduce((a, b) => a + b, 0)*1000)/1000).toFixed(3).toString().replace('.', ',')
          $('#ModalMakePedido #MontoTotal').html('Total: $'+currTot)

        }

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

          $.post("../php/getMaxNumStsPedido.php",
            {}, 
            function(data,status){
            //console.log('data recibed from getNumStsPedido');
            if(status === 'success')
            {
                const obj = JSON.parse(data)
                numPedido=obj.num_pedido
                pedSts=obj.ped_sts
                console.log("num pedido  "+numPedido+" ped sts: "+pedSts)
                //console.log("num valery already defined: "+numValery)
                $('#ModalShowPedido #table').bootstrapTable('refreshOptions',{
                  exportOptions: {
                    fileName: function() {
                        return 'ket'+numPedido;
                    }
                  }  
                })
              }
            });
            
            
        }

        function refreshMakePedido(){
          $('#ModalMakePedido #table').bootstrapTable('refreshOptions',{
            url : '../php/getCarritoCurrentData.php'})
        }

        const processRefreshMakePedido = debounce(() => refreshMakePedido());

    </script> 
</body>
</html>
