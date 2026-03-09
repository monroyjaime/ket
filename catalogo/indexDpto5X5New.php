<?php
//require_once("app/php/db.php");
require_once("../php/dbcat.php");

$role = -1;                         
if ( isset($_GET['role_num']) ) 
    $role = intval($_GET['role_num']);

$dptoId =  (isset($_GET['dpto_id']))?  intval($_GET['dpto_id']) : 1;
$pageNum =  (isset($_GET['page_num']))?  intval($_GET['page_num']) : 1;

$db = new DB();

// Obtener información del departamento
$consult = $db->consultas("SELECT name, img_route FROM departamentos WHERE id=".$dptoId);
foreach ($consult as $value){
    $currCatName = $value->name;
    $currCatImgRoute = $value->img_route;
}

// Obtener productos del departamento
$query  = "SELECT id, code, name, photo_url, cost_max, unit, current_stock FROM productos WHERE show='t' AND dpto_id=";
$query .= $dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ORDER BY orden, code";

$consult1 = $db->consultas($query);
$productVals = array();
$numProducts = 0;

foreach ($consult1 as $value){
    $productVal = new stdClass();
    $productVal->id = $value->id;
    $productVal->code = $value->code;
    $productVal->desc = $value->name;
    $productVal->url = $value->photo_url;
    $productVal->unit = $value->unit;
    $productVal->current_stock = $value->current_stock;
    $productVal->cost = floatval($value->cost_max);
    $productVal->cost_80 = floatval($value->cost_max)*.8;

    $productVals[] = $productVal;
    $numProducts++;
}

// Calcular número de páginas
$productosPorPagina = 25;
$productosPrimeraPagina = 20;

if ($numProducts <= $productosPrimeraPagina) {
    $numPages = 1;
} else {
    $productosRestantes = $numProducts - $productosPrimeraPagina;
    $numPages = 1 + ceil($productosRestantes / $productosPorPagina);
}

$tags = '<div class="col text-center">';

if ($pageNum == 1) {
    // Título con márgenes superior e inferior para separarlo de los productos
    $tags .= '<h1 class="rounded-title" style="margin: 4.5rem 0;">'.$currCatName.'</h1>';
    
    $inicio = 0;
    $fin = min($productosPrimeraPagina, $numProducts) - 1;
} else {
    $inicio = $productosPrimeraPagina + (($pageNum - 2) * $productosPorPagina);
    $fin = min($inicio + $productosPorPagina - 1, $numProducts - 1);
}

$tags .= '</div>';
$tags .= '<div class="row row-cols-1 row-cols-sm-5 g-5 justify-content-center">';

for ($i = $inicio; $i <= $fin; $i++){
    $productVal_id = $productVals[$i]->id;
    $productVal_code = $productVals[$i]->code;
    $productVal_desc = $productVals[$i]->desc;
    $productVal_url = $productVals[$i]->url;
    $productVal_unit = $productVals[$i]->unit;
    $productVal_current_stock = $productVals[$i]->current_stock;
    $productVal_cost = $productVals[$i]->cost;
    $productVal_cost_80 = $productVals[$i]->cost_80;

    $currUrl = $currCatImgRoute.$productVal_url;
    
    $tags .= '<div class="col" style="background-color: #FFF;">';
    $tags .=    '<div class="card h-100 text-bg-light">';
    $tags .=        '<div class="card-header" style="background-color: #037C79;">';
    $tags .=            '<h3 style="color: #FFF;">'.$productVal_code.'</h3>';
    $tags .=        '</div>';
    $tags .=        '<img src="'.$currUrl.'" class="card-img-top" alt="'.$productVal_code.'">';
    $tags .=        '<div class="card-body" style="background-color: #0CC;">';
    $tags .=            '<h6 class="card-text">'.$productVal_desc.'</h6>';
    if($role > -1) {
        $tags .=        '<h5 class="card-text">Prec. : $'.number_format($productVal_cost, 3, ",").'</h5>';
        $tags .=        '<h5 class="card-text">-20% : $'.number_format($productVal_cost_80, 3, ",").'</h5>';
    }
    $tags .=        '</div>';
    $tags .=    '</div>';
    $tags .= '</div>';
}

$tags .= '</div>';
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<title>catalogo ket</title>
        <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">  
        <link rel="stylesheet" href="css/non-responsive.css">  

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Varela+Round&display=swap');

            .rounded-title {
                background-color: #003272;
                color: #FFF;
                border-radius: 30px;
                width: 70%;
                margin-left: auto;
                margin-right: auto;
                font-family: 'Varela Round', 'Arial Rounded MT Bold', 'Helvetica Rounded', Arial, sans-serif;
                font-weight: 400;
                padding: 1.1rem 0;  /* Padding interno se mantiene */
                letter-spacing: 3px;
                display: inline-block;
                box-sizing: border-box;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            }
            
            .icon-large {
                font-size: 25px;
            }
            
            .icon-dark-blue{
                color: #003272;
            }

            .row.justify-content-center {
                justify-content: center !important;
            }
            
            .row > .col {
                flex: 0 0 auto;
                width: 20%;
                max-width: 20%;
            }
            
            @media (max-width: 576px) {
                .row > .col {
                    width: 100%;
                    max-width: 100%;
                }
            }
            
            @media print {
                body { 
                    background-color: #FFF; 
                    margin: 0;
                    padding: 0;
                }
            }
        </style>
	</head>

	<body style="background-color: #FFF;">
    <div class="w-100 p-0" style="background-color: #FFF;">
        <div class="row align-items-start" style="max-height: 50px; background-color: #FFF;">
            <div class="col text-start" style="max-height: 40px; background-color: #FFF;" >
                <img src="../catalogo/images/logo.png" class="img-fluid" alt="logo" />
            </div>       
        </div>
        <div class="col text-end" style="max-height: 40px; background-color: #FFF;" >
            <p> pag. <?php echo $pageNum; ?> / <?php echo $numPages; ?></p>      
        </div>
    </div>

    <div class="w-100 p-3" style="background-color: #FFF;"> 
        <div id="productos" >
            <?php echo $tags; ?>
        </div>    
    </div>
   <script>
       function backHome(){      
        urlString =  "../index.php";
        window.location.href = urlString;
    }
  </script> 
</body>
</html>