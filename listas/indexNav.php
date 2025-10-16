<?php
require_once("../php/dbcat.php");
$db = new DB();

$dataUrl = "https://ketelectropartes.com/php/getListaPrecAll.php?dpto=";

$tags1  = '<div class="btn-group">';
$tags1 .= '<a class="nav-link dropdown-toggle disable" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Departamento</a>';

$tags1 .= '<ul class="dropdown-menu">';            
$tags1 .=  '<li class="nav-item dropend">';
$tags1 .=    '<a class="nav-link dropdown-toggle disable" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=      'Automotriz';
$tags1 .=    '</a>';
$tags1 .=    '<ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start" style="height: auto;max-height: 200px; overflow-x: hidden;">';
$tags1 .=        '<li>';
$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=1 ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .=        '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$dpto_Name.'</a>';
}
$tags1 .=        '</li>';
$tags1 .=     '</ul>';
$tags1 .=  '</li>';
$tags1 .=  '<li>';
$tags1 .=        '<hr class="dropdown-divider">';
$tags1 .=  '</li>';

$tags1 .=  '<li class="nav-item dropend">';
$tags1 .=    '<a class="nav-link dropdown-toggle disable" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=      'Ferreteria';
$tags1 .=    '</a>';
$tags1 .=    '<ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start" style="height: auto;max-height: 200px; overflow-x: hidden;">';
$tags1 .=        '<li>';
$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=2 ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .=        '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$dpto_Name.'</a>';
}
$tags1 .=        '</li>';
$tags1 .=     '</ul>';
$tags1 .=  '</li>';
$tags1 .=  '<li>';
$tags1 .=        '<hr class="dropdown-divider">';
$tags1 .=  '</li>';

$tags1 .=  '<li class="nav-item dropend">';
$tags1 .=    '<a class="nav-link dropdown-toggle disable" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=      'Otros';
$tags1 .=    '</a>';
$tags1 .=    '<ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start" style="height: auto;max-height: 200px; overflow-x: hidden;">';
$tags1 .=        '<li>';
$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=3 ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_Name = $value->name;
    $tags1 .=        '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$dpto_Name.'</a>';
}
$tags1 .=        '</li>';
$tags1 .=     '</ul>';
$tags1 .=  '</li>';

$tags1 .= '</ul>';
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
    </style>

</head>

<body>
<div class="w-100 p-0" style="background-color: #CCC;">
    <div class="row align-items-start" style="max-height: 50px;">
        <div class="col text-start" style="max-height: 40px; padding-left: 20px;  " > 
        <a href="#" onClick="backHome()" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>
        </div>    
   
        <div class="col text-center">
          <?php echo $tags1; ?>
        </div>

        <div class="col text-end" style="max-height: 40px;" >
            <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
        </div>       

    </div>
</div>


  
<div class="col text-center" >
  <div class="col text-center" style="background-color: #DDD;">
  <h2>Lista de precios general</h2>
<!--  <div id="toolbar" class="select">
    <select class="form-control">
    <option value="">Exportar Visible</option>
    <option value="all">Exportar Todo</option>

    </select>
  </div> -->
    <table
      id="table"
      data-toggle="table"

      data-show-export="false"
      data-click-to-select="true"
      data-show-toggle="true"
      data-show-columns="true"
      data-search="true"

      data-searchable="true"
      data-height="600"
      data-pagination="true"
      data-url="<?php echo $dataUrl; ?>"
      data-mobile-responsive="false"
      data-check-on-init="true"
      data-row-style="rowStyle">
      <thead>
        <tr>
          <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
          <th data-field="name" data-halign="center" data-align="left" data-width="500">. . . . . . DESCRIPCION . . . . . .</th>
          <th data-field="unit">UNIDAD</th>
          <th data-field="cost_max" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>
          <th data-field="cost_max_80" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.-20%</th>
          <th data-field="photo_url" data-formatter="fotoFormatter">FOTO</th>  
          <th data-field="div">DIVISION</th>
          <th data-field="dpto">DPTO.</th>
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
    <script type="text/javascript">

    var $table = $('#table')

    $(function() {
      $('#toolbar').find('select').change(function () {
        $table.bootstrapTable('destroy').bootstrapTable({
          exportDataType: $(this).val(),
          exportTypes: ['excel','csv']          
        })
      }).trigger('change')

      $('#myModal').on("hide.bs.modal", function () {
        // put your default event here
        console.log("cerrada Nodal");
        $(".modal-body").html("");
    }); 

    

    })

        function getLista(idDpto){
            console.log( "selected Depertamento: "+idDpto );
            urlString ="index1.php?dpto="+idDpto;
            window.location.href = urlString;
        }   

        function fotoFormatter(value, row) {
 
            var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
            if (value != 'empty.jpg')
                strReturn = '<a class="ver" data-bs-toggle="modal" data-bs-target="#myModal" href="#" onClick="verFoto(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>'
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
    
        function backHome(){      
            urlString =  "../index.php";
            window.location.href = urlString;
        }


    </script> 
</body>
</html>
