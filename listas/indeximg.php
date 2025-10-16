<?php
require_once("../php/dbcat.php");
$db = new DB();

$dataUrl = "https://ketelectropartes.com/php/getListaPrecAll.php?dpto=";

$tags1  =   '<div class="col text-end" >';  
$tags1 .=   '<div class="btn-group" style="padding: 2px;">';

$tags1 .=     '<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=       'Automotriz';
$tags1 .=     '</button>';
$tags1 .=     '<div class="dropdown-menu">';


$consult = $db->consultas("SELECT id,name,img_route FROM departamentos WHERE num=1 ORDER BY id");
foreach ($consult as $value){
    $objAux = new stdClass();
    $dpto_Id = $value->id;
    $objAux->dptoName = $value->name;
    $objAux->imgRoute = $value->img_route;
    $arrCategories[] =$objAux;
    $tags1 .= '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$objAux->dptoName.'</a>';
}
$tags1 .=   '</div>';
$tags1 .= '</div>';

$tags1 .=   '<div class="btn-group" style="padding: 2px;">';
$tags1 .=     '<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=       'Ferreteria';
$tags1 .=     '</button>';
$tags1 .=     '<div class="dropdown-menu">';


$consult = $db->consultas("SELECT id,name,img_route FROM departamentos WHERE num>=2 ORDER BY id");
foreach ($consult as $value){
    $objAux = new stdClass();
    $dpto_Id = $value->id;
    $objAux->dptoName = $value->name;
    $objAux->imgRoute = $value->img_route;
    $arrCategories[] =$objAux;
    $tags1 .= '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$objAux->dptoName.'</a>';
}
$tags1 .=   '</div>';

$tags1 .= '</div>';
/*
$tags1 .=   '<div class="btn-group" style="padding: 2px;">';

$tags1 .=     '<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=       'Otros';
$tags1 .=     '</button>';
$tags1 .=     '<div class="dropdown-menu">';


$consult = $db->consultas("SELECT id,name,img_route FROM departamentos WHERE num=3 ORDER BY id");
foreach ($consult as $value){
    $objAux = new stdClass();
    $dpto_Id = $value->id;
    $objAux->dptoName = $value->name;
    $objAux->imgRoute = $value->img_route;
    $arrCategories[] =$objAux;
    $tags1 .= '<a class="dropdown-item" href="#" onClick="javascript:getLista('.$dpto_Id.')">'.$objAux->dptoName.'</a>';
}
$tags1 .=   '</div>';
$tags1 .= '</div>';

*/
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
    </style>

</head>

<body>
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

    <div class="w-100 p-3" style="background-color: #DDD;">    
        <?php echo $tags1; ?>
       
    </div>
  
<div class="col text-center">
  <div class="col text-center" style="background-color: #DDD;">
  <h2>Lista de precios general</h2>
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
          <th data-field="photo_url" data-formatter="fotoFormatter">FOTO</th>  
          <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
          <th data-field="name" data-halign="center" data-align="left">DESC.</th>
          <th data-field="cost_max" data-halign="center" data-align="right" data-formatter="precioFormater">PRECIO</th>
          <th data-field="unit" data-halign="center" data-align="left">UNIDAD</th>
          <th data-field="div">DIVISION</th>
          <th data-field="dpto">DPTO.</th>
        </tr>
      </thead>
    </table>
  </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/mobile/bootstrap-table-mobile.min.js"></script>

    <script type="text/javascript">
        function getLista(idDpto){
            console.log( "selected Depertamento: "+idDpto );
            urlString ="index1.php?dpto="+idDpto;
            window.location.href = urlString;
        }   

        function fotoFormatter(value, row) {
 
            var strReturn = '<i class="bi bi-x-circle-fill icon-red" title="no disponible"></i>';
            if (value != 'empty.jpg')
                strReturn = '<a class="ver" href="#" onClick="verFoto(\''+row.code+'\')" title="click para ver"><i class="bi bi-check-circle-fill icon-yellow"></i></a>'
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
            urlString ="oneProductPhoto.php?code="+val;
            window.location.href = urlString;
        }
    
        


    </script> 
</body>
</html>
