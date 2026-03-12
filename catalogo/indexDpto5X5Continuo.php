<?php
require_once("../php/dbcat.php");

$role = isset($_GET['role_num']) ? intval($_GET['role_num']) : -1;
$dptoId = isset($_GET['dpto_id']) ? intval($_GET['dpto_id']) : 1;
$pageNum = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$fusionMode = isset($_GET['fusion']) && $_GET['fusion'] == 'true';

$db = new DB();

// Obtener información del departamento actual
$consult = $db->consultas("SELECT name, img_route FROM departamentos WHERE id=".$dptoId);
$currCatName = '';
$currCatImgRoute = '';
foreach ($consult as $value){
    $currCatName = $value->name;
    $currCatImgRoute = $value->img_route;
}

// Obtener productos del departamento actual
$query  = "SELECT id, code, name, photo_url, cost_max, unit, current_stock FROM productos ";
$query .= "WHERE show='t' AND dpto_id=".$dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ";
$query .= "ORDER BY orden, code";

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

// Calcular número de páginas del departamento actual
$productosPorPagina = 25;
$productosPrimeraPagina = 20;

if ($numProducts <= $productosPrimeraPagina) {
    $numPagesDpto = 1;
} else {
    $productosRestantes = $numProducts - $productosPrimeraPagina;
    $numPagesDpto = 1 + ceil($productosRestantes / $productosPorPagina);
}

// Determinar si debemos incluir título del siguiente departamento
$incluirTituloSiguiente = false;
$nombreSiguiente = '';
$idSiguiente = 0;

if ($fusionMode && $pageNum == $numPagesDpto) {
    // Estamos en la última página de este departamento
    // Verificar cuántos productos tiene esta página
    $productosEnEstaPagina = 0;
    if ($pageNum == 1) {
        $productosEnEstaPagina = min($productosPrimeraPagina, $numProducts);
    } else {
        $inicio = $productosPrimeraPagina + (($pageNum - 2) * $productosPorPagina);
        $fin = min($inicio + $productosPorPagina - 1, $numProducts - 1);
        $productosEnEstaPagina = ($fin - $inicio + 1);
    }
    
    // Si hay espacio para al menos 2 filas (10 productos), podemos agregar título del siguiente
    if ($productosEnEstaPagina <= 15) { // 3 filas o menos
        // Obtener el siguiente departamento (hardcodeado o de una tabla)
        // Por ahora, lo pasaremos como parámetro desde Python
        if (isset($_GET['next_dpto_id'])) {
            $idSiguiente = intval($_GET['next_dpto_id']);
            $consultNext = $db->consultas("SELECT name FROM departamentos WHERE id=".$idSiguiente);
            foreach ($consultNext as $nextValue){
                $nombreSiguiente = $nextValue->name;
                $incluirTituloSiguiente = true;
            }
        }
    }
}

// Generar contenido
$tags = '<div class="col text-center">';

if ($pageNum == 1) {
    $tags .= '<div class="col text-center" style="display: flex; justify-content: center; align-items: center; min-height: 150px;">';
    $tags .= '<h1 class="rounded-title" style="margin: 2rem auto; width: 90%;">'.$currCatName.'</h1>';
    $tags .= '</div>';
    // ...
} else {
    $inicio = $productosPrimeraPagina + (($pageNum - 2) * $productosPorPagina);
    $fin = min($inicio + $productosPorPagina - 1, $numProducts - 1);
}

$tags .= '</div>';
$tags .= '<div class="row row-cols-1 row-cols-sm-5 g-5 justify-content-center">';

for ($i = $inicio; $i <= $fin; $i++){
    $productVal_code = $productVals[$i]->code;
    $productVal_desc = $productVals[$i]->desc;
    $productVal_url = $productVals[$i]->url;
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

// Si estamos en modo fusión y hay que incluir título del siguiente
if ($incluirTituloSiguiente) {
    $tags .= '<div style="border-top: 3px solid #003272; margin: 30px 0 20px 0;"></div>';
    $tags .= '<div class="col text-center" style="display: flex; justify-content: center; align-items: center; min-height: 120px;">';
    $tags .= '<h1 class="rounded-title" style="margin: 1rem auto; width: 90%;">'.$nombreSiguiente.'</h1>';
    $tags .= '</div>';
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">
        <title>Catálogo Continuo</title>
        <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">  
        <link rel="stylesheet" href="css/non-responsive.css">  
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Varela+Round&display=swap');
            
            .rounded-title {
                background-color: #003272;
                color: #FFF;
                border-radius: 30px;
                width: 90%;  /* Aumentado de 70% a 90% */
                max-width: 1200px;  /* Límite para pantallas muy grandes */
                margin-left: auto;
                margin-right: auto;
                font-family: 'Varela Round', sans-serif;
                padding: 1.5rem 0;  /* Aumentado ligeramente */
                letter-spacing: 3px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                /* FORZAR UNA SOLA LÍNEA */
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                /* CENTRADO VERTICAL ADICIONAL */
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1.2;
            }
            
            /* Para asegurar que el contenedor del título también centre bien */
            .col.text-center {
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .card-header {
                background-color: #037C79 !important;
            }
            
            .card-header h3 {
                color: #FFF;
                margin: 0;
                font-size: 1.2rem;
            }
            
            .card-body {
                background-color: #0CC !important;
            }
            
            .row.justify-content-center {
                justify-content: center !important;
            }
            
            .row > .col {
                flex: 0 0 auto;
                width: 20%;
                max-width: 20%;
            }
            
            img.card-img-top {
                height: 100px;
                object-fit: contain;
                background-color: white;
            }
            
            @media print {
                body { 
                    margin: 0; 
                    padding: 0; 
                }
                .rounded-title {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    white-space: nowrap;  /* Reforzar en impresión */
                }
            }
        </style>
    </head>
    <body style="background-color: #FFF;">
        <div class="w-100 p-0" style="background-color: #FFF;">
            <div class="row align-items-start" style="max-height: 50px;">
                <div class="col text-start">
                    <img src="../catalogo/images/logo.png" class="img-fluid" alt="logo" />
                </div>       
            </div>
            <div class="col text-end">
                <p>pag. <?php echo $pageNum; ?> / <?php echo $numPagesDpto; ?></p>      
            </div>
        </div>
        <div class="w-100 p-3" style="background-color: #FFF;"> 
            <div id="productos">
                <?php echo $tags; ?>
            </div>    
        </div>
    </body>
</html>