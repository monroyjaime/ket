<?php
session_start();
require_once("../php/dbcat.php");
$db = new DB();

$tipoPrecio = 0;  

$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;

/*if ( isset($_GET['role_num']) ) 
  $role = intval($_GET['role_num']); */

if( isset($_GET['prec']))
  $tipoPrecio = intval($_GET['prec']);

$otroPrecio = ($tipoPrecio==0)? 1 : 0;

$textPrecio = ($tipoPrecio == 0)? "ver Precios al Mayor" : "ver Precios Minorista";
  
$btnTipoPrecio ='';
if($role >-1 && $role < 3)
{
 $btnTipoPrecio =  '<button type="button" class="btn btn-primary btn-sm" onClick="backToSelf('.$role.','.$otroPrecio.')">'.$textPrecio.'</button>';
 // $btnTipoPrecio = ($tipoPrecio==0)? '<h5>Detal</h5>' : '<h5>Myor</h5>';
} 

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

    case 4:
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

$stockColumn = ($role > -1 && $role < 2)? '<th data-field="current_stock" data-halign="center" data-align="right" >STOCK</th>' : '';
$precioColumn = ($role == -1)? '' : '<th data-field="cost_max" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>';
$precio80Column = ($role == -1)? '' :'<th data-field="cost_max_80" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.-20%</th>';
$tituloLista = ($role == -1)? '<h2 style="background-color: #037C79; color: #FFF;">Listado general</h2>' : '<h2 style="background-color: #037C79; color: #FFF;">Listado general '.$titlePrec.'</h2>';
$dataUrl = "https://ketelectropartes.com/php/getListaPrecAll.php?prec=".$tipoPrecio;

$fotoFormater = ($tipoPrecio == 0)?  "fotoFormaterMin":"fotoFormaterMay" ;




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
 

        <div class="col text-end" style="max-height: 40px;" >
            <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
        </div>       

    </div>
</div>


  
<div class="col text-center" >
  <div class="col text-center" style="background-color: #DDD;">

  <?php echo $tituloLista; ?>

  <div class="col text-center" style="max-height: 40px; padding-botton: 14px; padding-top: 1px;" >
    <?php echo $btnTipoPrecio; ?>
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
      data-click-to-select="true"
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
          <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
          <th data-field="name" data-halign="center" data-align="left" data-width="500">. . . . . . DESCRIPCION . . . . . .</th>
          <?php echo $stockColumn;?>
          <th data-field="unit">UNIDAD</th>
          
          <?php echo $precioColumn;?>
          <?php echo $precio80Column;?>

          <th data-field="photo_url" data-formatter="<?php echo $fotoFormater;?>">FOTO</th>  
<!--      <th data-field="div">DIVISION</th>
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

    

    })

        function getLista(idDpto,rol){
            console.log( "selected Depertamento: "+idDpto );
            urlString ="index1.php?dpto="+idDpto;
            window.location.href = urlString;
        }   

        function fotoFormater(value, row) {
 
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
        
        function backHome(){      
          urlString =  "../";
          window.location.href = urlString;
        }



    </script> 
</body>
</html>
