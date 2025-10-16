<?php
session_start();

require_once("../php/dbcat.php");
$db = new DB();

$linea = 1;
$tipoPrecio = 0;
$comeFrom = 0;  
$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;

$dptoId =  (isset($_GET['dpto']))?  intval($_GET['dpto']) : 6;  


/*if ( isset($_GET['role_num']) ) 
  $role = intval($_GET['role_num']);*/

if ( isset($_GET['line']) ) 
  $linea = intval($_GET['line']); 
 
if( isset($_GET['prec']))
  $tipoPrecio = intval($_GET['prec']);

  if( isset($_GET['from']))
    $comeFrom = intval($_GET['from']);

$otroPrecio = ($tipoPrecio==0)? 1 : 0;  

$textPrecio = ($tipoPrecio == 0)? "ver Precios al Mayor" : "ver Precios Minorista";
  
$btnTipoPrecio ='';
if($role >-1 && $role < 3)
{
 $btnTipoPrecio =  '<button type="button" class="btn btn-primary btn-sm" onClick="backToSelf('.$dptoId.','.$role.','.$linea.','.$otroPrecio.','.$comeFrom.')">'.$textPrecio.'</button>';
 // $btnTipoPrecio = ($tipoPrecio==0)? '<h5>Detal</h5>' : '<h5>Myor</h5>';
} 
    

$stockColumn = ($role > -1 && $role < 2)? '<th data-field="current_stock" data-halign="center" data-align="right"  data-force-hide="true" >STOCK</th>' : '';

$precioColumn = ($role == -1)? '' : '<th data-field="cost_max" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>' ;
$precio80Column = ($role == -1)? '' : '<th data-field="cost_max_80" data-halign="center" data-align="right" data-force-hide="true" data-formatter="precioFormater">PREC.-20%</th>';



$callBackLine = '<a href="#" onClick="backTo('.$role.','.$linea.','.$comeFrom.','.$tipoPrecio.')" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>';

$dataUrl = "https://ketelectropartes.com/php/getListaPrec.php?dpto=".$dptoId."&prec=".$tipoPrecio;
$query = "SELECT name FROM departamentos where id=".$dptoId;
$consult = $db->consultas($query);
foreach ($consult as $value)  
  $dptoName = $value->name;


switch($role)
{
    case 1:
      $titlePrec = ($tipoPrecio==0)? "(Precios minorista)" : "(Precios al mayor)";
    break;
    case 2:
      $titlePrec = ($tipoPrecio==0)? "(Precios minorista)" : "(Precios al mayor)";

    break;
    case 3:
      $titlePrec = "(Precios)";

    break;

}

$titlefull = $dptoName." ".$titlePrec;

$fotoFormater = ($tipoPrecio == 0)?  "fotoFormaterMin":"fotoFormaterMay" ;


?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Ket-listas de precio</title>

    

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css">
    <link href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css" rel="stylesheet">
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>

    <style>
      body {
        text-align: center;
      }
      span {
        display: inline-block;
        padding: 10px 20px;
      }
      .icon-green {
        color: green;
      }
      .icon-red {
        color: red;
      }
      .icon-yellow {
        color: yellow;
      }
      .icon-dark-blue{
        color: #003272;
      }
      .icon-large {
        font-size: 25px;
      }
      .vertical-center {
        margin: 0;
        position: absolute;
        top: 50%;
        -ms-transform: translateY(-50%);
        transform: translateY(-50%);
      }
    </style>

</head>

<body>

<div class="w-100 p-0" style="background-color: #CCC;">
    <div class="row align-items-start" style="max-height: 50px;">
        <div class="col text-start" style="max-height: 40px; padding-left: 20px;  " > 
        <?php echo $callBackLine; ?>
        </div>
        
        
        
        <div class="col text-end" style="max-height: 40px;" >
            <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
        </div>       

    </div>
</div>

<!--

<div class="w-100 p-3" style="background-color: #FFF;">
        <div class="row align-items-start">
            <div class="col text-start">
                <img src="../catalogo/images/logo.png" class="img-fluid" alt="logo" />
            </div>    
            <div class="col text-end">
                <img src="../catalogo/images/sublogo.png" class="img-fluid" alt="logo" />
            </div>

        </div>
    </div>

-->  
<div class="col text-center">
  <div class="col text-center" style="background-color: #DDD;">
  <h2 style="background-color: #037C79; color: #FFF;"><?php echo $titlefull; ?></h2>

  <div class="col text-center" style="max-height: 40px; padding-botton: 14px; padding-top: 1px;" >
    <?php echo $btnTipoPrecio; ?>
  </div>

  <div id="toolbar" class="select" style="display: none;">

   
    <select class="form-control">
    <option value="all">all</option>

    </select>
  </div>

    <table 
      id="table"
      data-toggle="table"

      data-show-export="true"
      data-export-footer="true"
      data-click-to-select="true"
      data-toolbar="#toolbar"
      data-show-toggle="false"
      data-show-columns="false"
      data-search="true"

      data-search="true"
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
          <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
          <th data-field="name" data-halign="center" data-align="left" data-width="700">. . . . . . DESCRIPCION . . . . . .</th>
          <?php echo $stockColumn;?>
          <th data-field="unit" data-halign="center" data-align="center">UNIDAD</th>
          <?php echo $precioColumn;?>
          <?php echo $precio80Column;?>
          <th data-field="photo_url" data-force-hide="true" data-formatter="<?php echo $fotoFormater;?>">FOTO</th>   
                
        </tr>
      </thead>
      <tfoot>
            <tr>
                <th scope="row"><img src="../catalogo/images/logoMini.png"  alt="logo" /></th>                
                <td><?php echo $dptoName; ?></td>
                <td><?php echo $titlePrec; ?></td>
                <td>
                  <div id="current_date">
                    <script>
                        date = new Date();
                        year = date.getFullYear();
                        month = date.getMonth() + 1;
                        day = date.getDate();
                        document.getElementById("current_date").innerHTML =" "+ day + "/" + month + "/" + year;
                    </script>
                  </div>
                </td>
            </tr>
        </tfoot>
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



    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/mobile/bootstrap-table-mobile.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/tableExport.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF/jspdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF-AutoTable/jspdf.plugin.autotable.js"></script>
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/export/bootstrap-table-export.min.js"></script>

    <script type="text/javascript">

    var $table = $('#table')

    $(function() {
      
      $('#toolbar').find('select').change(function () {
        $table.bootstrapTable('destroy').bootstrapTable({
          exportDataType: $(this).val(),
          exportTypes: ['excel','pdf']          
        })
      }).trigger('change')

      $('.float-right.search.btn-group').find('input').attr('placeholder','....');
      $('.float-right.search.btn-group').find('input').wrap("<div class='input-group' id='awsearch'> </div>"); 
      $('#awsearch').prepend("<span class='input-group-addon'><i class='bi bi-search icon-dark-blue'></i>Buscar</span>")

     $('#myModal').on("hide.bs.modal", function () {
        // put your default event here
        console.log("cerrada Nodal");
        $(".modal-body").html("");
    }); 
    })

    function fotoFormatter(value,row,index) {
      var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
      if (value != 'empty.jpg')
          strReturn = '<a class="ver" data-bs-toggle="modal" data-bs-target="#myModal" href="#" onClick="verFoto(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>'
      
      return strReturn;
    }

    function fotoFormaterMin(value, row) {
      var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
      if (value != 'empty.jpg')
          strReturn = '<a class="ver" data-bs-toggle="modal" data-bs-target="#myModal" href="#" onClick="verFotoMin(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>'
      return strReturn;
    }

      function fotoFormaterMay(value, row) {

      var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
      if (value != 'empty.jpg')
          strReturn = '<a class="ver" data-bs-toggle="modal" data-bs-target="#myModal" href="#" onClick="verFotoMay(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>'
      return strReturn;
    }


    function precioFormater(value,row) {
      return '$'+value;
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
      //alert('code clickeado: '+val)
      urlString ="../php/getOneProductPhoto.php?code="+val;
      $('.modal-body').load(urlString,function(){
          $('#myModal').modal({show:true});
      });

           // window.location.href = urlString;
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

    function backTo(rol,line,from,prec){ 
      if(from == 0)
      {
        switch(line)
        {
          case 1:
            urlString =  "indexL1.php?prec="+prec;

          break;
          case 2:
            urlString =  "indexL2.php?prec="+prec;

          break;
        }
      }
      else
        urlString =  "index.php?prec="+prec;
  
        window.location.href = urlString;
    }

    function backToSelf(dpto,rol,line,prec,from){
      urlString ="index1.php?dpto="+dpto+"&line="+line+"&prec="+prec+"&from="+from;
      window.location.href = urlString;
    }

  </script>
</body>
</html>
