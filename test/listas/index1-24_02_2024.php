<?php
require_once("../php/dbcat.php");
$db = new DB();

$role = -1;                         

if ( isset($_GET['role_num']) ) 
  $role = intval($_GET['role_num']);

$stockColumn = ($role > -1 && $role < 2)? '<th data-field="current_stock" data-halign="center" data-align="right" >EXISTENCIA</th>' : '';

$callBackLine = '<a href="#" onClick="backTo('.$role.')" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>';
$dptoId =  (isset($_GET['dpto']))?  intval($_GET['dpto']) : 6;  

$dataUrl = "https://ketelectropartes.com/php/getListaPrec.php?dpto=".$dptoId;
$query = "SELECT name FROM departamentos where id=".$dptoId;
$consult = $db->consultas($query);
foreach ($consult as $value)  
  $dptoName = $value->name;

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
        
        <div class="col text-center" style="max-height: 40px; padding-top: 5px;" >
            <h5>Precios de</h5>          
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
  <h2 style="background-color: #037C79; color: #FFF;"><?php echo $dptoName; ?></h2>

  <div id="toolbar" class="select" style="display: none;">
    <select class="form-control">
    <option value="all">all</option>

    </select>
  </div>

    <table
      id="table"
      data-toggle="table"

      data-show-export="true"
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
          <th data-field="name" data-halign="center" data-align="left" data-width="500">. . . . . . DESCRIPCION . . . . . .</th>
          <?php echo $stockColumn;?>
          <th data-field="unit" data-halign="center" data-align="center">UNIDAD</th>
          <th data-field="cost_max" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>
          <th data-field="cost_max_80" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.-20%</th>

          <th data-field="photo_url" data-formatter="fotoFormatter">FOTO</th>         
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
          exportTypes: ['excel','pdf']          
        })
      }).trigger('change')

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

    function backTo(rol){      
        urlString =  "index.php?role_num="+rol;
        window.location.href = urlString;
    }

  </script>
</body>
</html>
