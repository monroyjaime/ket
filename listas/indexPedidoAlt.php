<?php
session_start();
require_once("../php/dbcat.php");
$db = new DB();

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
    $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 40px 1px;">Hacer Pedido</button> ';
    $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" onClick="getSelected()" style="margin: 1px 40px 1px;">Ver Pedidos</button> ';
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

$stockColumn = ( ($role > -1 && $role < 2) || $role == 5 )? '<th data-field="current_stock" data-halign="center" data-align="right" >STOCK</th>' : '';
$pedidoCheckColumn = ($numUsr > 0 && $ableToPedido == 't')? '<th data-field="checked" data-checkbox="true"></th>' : '';
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

      

      .fixed-table-toolbar .search {
            width: 100%;
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


<div id="toolbar" class="select">
    <select class="form-control">
        <option value="">Exportar Visible</option>
        <option value="all">Exportar Todo</option>
    </select> 
</div>

 -->

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
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
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

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
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

    
    var $table = $('#table')

    function getRowSelections() {
      return $.map($table.bootstrapTable('getSelections'), function(row) {
        return row;
      })
    }


    $(function() {

      $('.float-right.search.btn-group').find('input').attr('placeholder','....');
      $('.float-right.search.btn-group').find('input').wrap("<div class='input-group' id='awsearch'> </div>"); 
      $('#awsearch').prepend("<span class='input-group-addon'><i class='bi bi-search icon-dark-blue'></i>Buscar</span>")
    /*  $('#toolbar').find('select').change(function () {
        $table.bootstrapTable('destroy').bootstrapTable({
          exportDataType: $(this).val(),
          exportTypes: ['excel','csv']          
        })
      }).trigger('change')
*/
      $('#myModal').on("hide.bs.modal", function () {
        // put your default event here
        console.log("cerrada Nodal");
        $(".modal-body").html("");
      }); 

      $('#ModalMakePedido').on("hide.bs.modal", function () {
        // put your default event here
        console.log("cerrada Nodal Hacer Pedido");
        $(".modal-body").html("");
      }); 


    

    })

    var $table1 = $('#tableSelected')

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

        function pedidoStsFormater(value, row){
          var strReturn = '<i class="bi bi-check-circle-fill icon-yellow" title="normal"></i>';
          if(row.cantidad == 0)
            strReturn = '<i class="bi bi-x-circle-fill icon-red" title="quitar de pedido"></i>';
          
          return strReturn;

        }

        function pedidoCantidadFormater(value, row){
          var strReturn = '<input id = "Cantidad" type="number" min="0" class="form-control" value="$ '+value+'" oninput="catidadCambia()" disabled/>'
          return strReturn;

        }

        function pedidoCostoFormater(value, row){
          var costVal= value*row.cantidad
          return '$'+costVal;

        }


        function precioFormater(value,row) {
          return '$'+value;
        }

        function checkFormater(value,row){
          if(parseInt(row.current_stock) <= 0)
          {
            return {
              disabled: true
            }
          }
          return value
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
          var selectedRows = getRowSelections();
          var precios = []
          var countRow=1;
          var strRows='';
          var dataSelected = [];
          
          tags  = '<table';
          tags += 'id="tableSelected"';
          tags += 'data-toggle="table"';

          tags += 'data-show-export="false"';
          tags += 'data-click-to-select="false"';
          tags += 'data-maintain-meta-data="true"';
          tags += 'data-show-columns="false"';
          tags += 'data-search="false"';

          tags += 'data-searchable="false"';
          tags += 'data-height="600"';
          tags += 'data-pagination="true"';

          tags += 'data-page-size="5"'; 
          tags += 'data-page-list="[100]"';

          //data-url="<?php echo $dataUrl; ?>"
          tags += 'data-mobile-responsive="false"';
          tags += 'data-check-on-init="true">'
          tags += '<thead>';
          tags +=   '<tr>';
          tags +=     '<th data-field="status" data-formatter="pedidoStsFormater"></th>';
          tags +=     '<th data-field="code" data-halign="center" data-align="left">Código</th>';
          tags +=     '<th data-field="name" data-halign="center" data-align="left" data-width="500">Descripción</th>';
          tags +=     '<th data-field="cantidad" data-formatter="pedidoCantidadFormater" data-events="pedidoCantidadEvents">Cantidad</th>';
          tags +=     '<th data-field="unit">Unidad</th>'
          tags +=     '<th data-field="precio" data-formatter="precioFormater">Precio</th>'
          tags +=     '<th data-field="costo" data-formatter="pedidoCostoFormater">Costo</th>'
          tags +=  '</tr>';
          tags += '</thead>';
          tags +='<table>';
          
          $.each(selectedRows, function(index, value) {
            //selectedItems += value.name + '\n';
            strRows += '"row'+countRow+'": {';
            strRows += '"code": "'+value.code+'",'
            strRows += '"name": "'+value.name+'",'
            strRows += '"cantidad": 1,'
            strRows += '"unit": "'+value.unit+'",'
            strRows += '"precio": '+value.cost_max+','
            strRows += '"costo": '+value.cost_max+'}'



            precios.push(parseFloat(value.cost_max.replace(',', '.')))
            dataSelected.push(strRows);




          });
          tags +='  <div style="text-align: right;">';
          startTot = (Math.round(precios.reduce((a, b) => a + b, 0)*1000)/1000).toFixed(3).toString().replace('.', ',') 
          //currTot = (Math.round(montos.reduce((a, b) => a + b, 0)*1000)/1000).toFixed(3).toString().replace('.', ',')

          tags +='  <h5 id="MontoTotal">Total: $'+startTot+'</h5>';
          tags +='  </div>';
          tags += '<button type="button" class="btn btn-primary btn-sm"  onClick="enableCantidad()" style="margin: 1px 40px 1px;">Definir Cantidad(es)</button> '
          tags += '<button type="button" class="btn btn-primary btn-sm"  onClick="registrarPedido()" style="margin: 1px 40px 1px;">Registrar Pedido</button> '

          $table1.bootstrapTable({data: dataSelected})

          //alert('The following products are selected: ' + selectedItems);
          $('.modal-body').html(tags);
          $('#ModalMakePedido').modal({show:true});
          //console.log('The following products are selected: ' + selectedItems);

        }

        function registrarPedido(){

          var rows = document.getElementById('tableSelected').rows, 
          codes= [];
          precios=[];
          for (let i = 1; i < rows.length; i++) {

                codes.push(rows[i].cells[0].innerHTML)
                precios.push(rows[i].cells[4].innerHTML)
                console.log('row'+(i)+' cell 4:'+rows[i].cells[4].innerHTML);

                //codes.push( rows[i].cells[0].innerHTML );
              
          }
          /*    
          $.each($('#ModalMakePedido #tableSelected'),function(index,valor){
            console.log(document.getElmentById("Cantidad").value)
          });  */
          productos=[];
          $.each($('#ModalMakePedido #Cantidad'),function(index,valor){
            const producto = {} 
            producto.code = codes[index]
            producto.amount = valor.value;
            productos.push(producto)
            //console.log(valor.value+' index:'+index)
          });

          for (let i = 0; i < productos.length; i++) {
            console.log(productos[i].code+'--'+productos[i].amount); 
          }

          $('.modal-body').html('');
          $('#ModalMakePedido').modal('hide');
          backToSelfAlt();
          //console.log('Aqui registraremos pedido Cantidades:');
        }

        function enableCantidad(){
          $('#ModalMakePedido #Cantidad').prop('disabled',false)
          /*
          $.each($('#ModalMakePedido #Cantidad'),function(index,valor){
            console.log(valor)
            valor.enable
              //this.prop('disabled',false);
          })*/
        }
         
        function catidadCambia(){
          //console.log('algo cambia')
          var rows = document.getElementById('tableSelected').rows, 
          precios=[];
          montos=[];
          for (let i = 1; i < rows.length; i++) {
                precios.push(parseFloat(rows[i].cells[4].innerHTML.replace(',', '.').replace('$','')))
                montos.push(parseFloat(rows[i].cells[5].innerHTML.replace(',', '.').replace('$','')))
          }
          $.each($('#ModalMakePedido #Cantidad'),function(index,valor){
     
            currMonto = Math.round(parseInt(valor.value)*precios[index]*1000)/1000;
            if(currMonto != montos){  //update this row
              montos[index] = currMonto
              

              //valor.value =currMonto
              /*
              $('#tableSelected').bootstrapTable('updateCell',{
                index: index,
                field: 'Monto',
                value: currMonto
              }) */
            }
            //console.log('cantidad: '+valor.value+' precio:'+precios[index]+' monto: '+currMonto+' leido:'+montos[index])
            currTot = (Math.round(montos.reduce((a, b) => a + b, 0)*1000)/1000).toFixed(3).toString().replace('.', ',')
            console.log('Total: $'+currTot)
            $('#ModalMakePedido #MontoTotal').html('Total: $'+currTot)
          });
          $.each($('#ModalMakePedido #Monto'),function(index,valor){  
            console.log('Monto: '+montos[index])

            valor.value = '$'+montos[index].toString().replace('.', ',')
          });
          
        }

    </script> 
</body>
</html>
