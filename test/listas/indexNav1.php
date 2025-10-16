
<?php
//require_once("app/php/db.php");
require_once("../php/dbcat.php");

$db = new DB();

$dataUrl = "https://ketelectropartes.com/php/getListaPrecAll.php?dpto=";


$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=1 ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_name = $value->name;

    $tags1 .= '<li>';
    $tags1 .= '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$dpto_name.'</a>';
    $tags1 .= '</li>';
}
//echo "tags1: ".$tags1."\n";
 
$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=2 ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_name = $value->name;

    $tags2 .= '<li>';
    $tags2 .= '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$dpto_name.'</a>';
    $tags2 .= '</li>';
}
//echo "tags2: ".$tags2."\n";
$consult = $db->consultas("SELECT id,name FROM departamentos WHERE num=3 ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_name = $value->name;

    $tags3 .= '<li>';
    $tags3 .= '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$dpto_name.'</a>';
    $tags3 .= '</li>';
}
?>

<!DOCTYPE html>

<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<title>Ket Home</title>
        <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">   
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

        <script type="text/javascript">

        </script> 
        <style>

          .navbar-nav .nav-link {
            color: #000;
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
	<body >
 

    <nav class="navbar navbar-expand-sm navbar-light" style="background-color: #CCC;">
    <div class="container-fluid">
     <!-- Brand -->
     <a class="navbar-brand me-2 mb-1 d-flex align-items-center" href="#">
          <img
            src="../catalogo/images/logo.png"
            height="40"
            alt="KET Logo"
            loading="lazy"
            style="margin-top: 2px;"
          />
        </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
 
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Departamentos
            </a>
          <ul class="dropdown-menu">
            
            <li class="nav-item dropend">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Automotriz
              </a>
              <ul class="dropdown-menu" style="height: auto;max-height: 200px; overflow-x: hidden;">
                <?php echo $tags1; ?>
              </ul>
            </li>
            <li>
                  <hr class="dropdown-divider">
            </li>

            <li class="nav-item dropend">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Ferreteria
              </a>
              <ul class="dropdown-menu" style="height: auto;max-height: 200px; overflow-x: hidden;">
                <?php echo $tags2; ?>
              </ul>
            </li>
            <li>
                  <hr class="dropdown-divider">
            </li>
            <li class="nav-item dropend">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Otros
              </a>
              <ul class="dropdown-menu" style="height: auto;max-height: 200px; overflow-x: hidden;">
                <?php echo $tags3; ?>
              </ul>
            </li>
            
          </ul>
        </li>

      </ul>
      <img src="../catalogo/images/sublogo.png" 
        height="40"
        alt="KET sub Logo"
        loading="lazy"
        style="margin-top: 2px;">
    </div>
  </div>
</nav>

<div class="w-100 p-0" style="background-color: #FFF;">
    <div class="col text-center">
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
    
        


    </script> 

  </body>
  </html>