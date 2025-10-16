<?php
//require_once("app/php/db.php");
require_once("../php/dbcat.php");
$codigo =  (isset($_GET['code']))?  $_GET['code'] : 'GA000-007';

$db = new DB();


$tags = '<div class="col text-center">';

$tags .=    '<h2>Detalle Producto</h2>';
$tags .= '</div>';

//$tags .=    '<div class="myclass">';
$tags .=    '<div class="row row-cols-1 row-cols-md-1 g-1 ">';


$query  = "select a.code,a.name,b.name as dpto, a.unit,a.cost_max,concat(b.img_route,a.photo_url) as foto from productos a, ";
$query .= "departamentos b where a.dpto_id=b.id and a.code='".$codigo."'";
//echo "query: ".$query."\n";

$consult1 = $db->consultas($query);
foreach ($consult1 as $value1){
    $productVal_dpto =$value1->dpto;
    $productVal_code = $value1->code;
    $productVal_desc = $value1->name;
    $productVal_unit = $value1->unit;
    $productVal_prec = $value1->cost_max;

    $currUrl = $value1->foto;

    $tags .=    '<div class="col" style="background-color: #DDD;">';
    $tags .=        '<div class="card h-100 text-bg-light">';
    $tags .=            '<div class="card-header" style="background-color: #eee;">';
    $tags .=                '<h5>Dpto: '.$productVal_dpto.'</h5>'; 
    $tags .=                '<h3>Código: '.$productVal_code.'</h3>';
    $tags .=            '</div>';
    $tags .=            '<img src="'.$currUrl.'" class="card-img-top" alt="'.$productVal_code.'">';
    $tags .=            '<div class="card-body" style="background-color: #eee;">';
    $tags .=                '<h4 class="card-text">'.$productVal_desc.'</h4>';
    $tags .=                '<h4 class="card-text">Unidad: '.$productVal_unit.'</h4>';
    $tags .=                '<h4 class="card-text">Precio: $'.$productVal_prec.'</h4>';
    $tags .=            '</div>';
    $tags .=        '</div>';
    $tags .=    '</div>';
}

$tags .=    '</div>'; 

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
 <!--       <meta name="viewport" content="initial-scale=1, maximum-scale=1"> -->
  <!--         <meta name="viewport" content="initial-scale=0.1"> -->
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">


		<title>catalogo ket</title>
        <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">  
        <link rel="stylesheet" href="css/non-responsive.css">  

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

        <style>
            .myclass{
            display:table;
            text-align:center;
            margin:0 auto;
            min-width:400px;
            }
            iframe{
            width:100%;
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
    <div style="background-color: #DDD;">    
        <?php echo $tags; ?>
    </div>
    

</body>

</html>	