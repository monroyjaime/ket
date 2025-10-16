<?php
require_once("../php/dbcat.php");
$db = new DB();

$preci_stock = '';

$role = -1;                         

if ( isset($_GET['role_num']) ) 
  $role = intval($_GET['role_num']);


if($role>-1)  
{
  $preci_stock .= '<th data-field="cost_max" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>';
  $preci_stock .= '<th data-field="cost_max_80" data-halign="center" data-align="right" data-formatter="precioFormater">PREC.-20%</th>';
  $preci_stock  .= '<th data-field="unit" data-halign="center" data-align="center">UNIDAD</th>';

}

$stockColumn = ($role > -1 && $role < 2)? '<th data-field="current_stock" data-halign="center" data-align="right" >EXISTENCIA</th>' : '';



$dptoId =  (isset($_GET['dpto_id']))?  intval($_GET['dpto_id']) : 6;  

$dataUrl = "https://ketelectropartes.com/php/getListaPrec.php?dpto=".$dptoId;
$query = "SELECT name,img_route FROM departamentos where id=".$dptoId;
$consult = $db->consultas($query);
foreach ($consult as $value)  
{
    $dptoName = $value->name;
    $imgRoute = $value->img_route."unique.jpg";

}

?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Catalogo lista</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css">
    <link href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css" rel="stylesheet">
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>


    <style>
      .icon-large {
        font-size: 25px;
      }
      .icon-dark-blue{
        color: #003272;
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
  
<div class="col text-center">
  <div class="col text-center" style="background-color: #DDD;">
  <h2>Catalogo de  <?php echo $dptoName; ?></h2>

    <div class="container text-center">
        <div class="row">
            <div class="col-md-4">
                <img src="<?php echo $imgRoute; ?>" class="img-fluid" alt="catalogo" />
            </div>
            <div class="col-md-8">       
                <table
                id="table"
                data-toggle="table"
                data-search="true"
                data-height="600"
                data-pagination="true"
                data-url="<?php echo $dataUrl; ?>"
                data-mobile-responsive="true"
                data-check-on-init="true"
                data-row-style="rowStyle">
                <thead>
                    <tr>
                    <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
                    <th data-field="name" data-halign="center" data-align="left">DESCRIPCION</th>

                    <?php echo $preci_stock;?>

                    </tr>
                </thead>
                </table>
            </div>    
        </div>
    </div>



  </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
<script>

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

    function backHome(){      
        urlString =  "../index.php";
        window.location.href = urlString;
    }

  </script>
</body>
</html>
