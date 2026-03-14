<?php
require_once("../php/dbcat.php");

$role = isset($_GET['role_num']) ? intval($_GET['role_num']) : -1;
$dptoId = isset($_GET['dpto_id']) ? intval($_GET['dpto_id']) : 1;
$pageNum = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$fusionMode = isset($_GET['fusion']) && $_GET['fusion'] == 'true';
$firstProd = isset($_GET['first_prod']) ? intval($_GET['first_prod']) : 1;

$db = new DB();

// Obtener información del departamento actual
$consult = $db->consultas("SELECT name, img_route FROM departamentos WHERE id=".$dptoId);
$currCatName = '';
$currCatImgRoute = '';
foreach ($consult as $value){
    $currCatName = $value->name;
    $currCatImgRoute = $value->img_route;
}

// Obtener TOTAL de productos del departamento (para paginación)
$queryTotal = "SELECT COUNT(*) as total FROM productos WHERE show='t' AND dpto_id=".$dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0";
$consultTotal = $db->consultas($queryTotal);
$totalProductos = $consultTotal[0]->total;

// Calcular número TOTAL de páginas del departamento
$productosPorPagina = 25;
$productosPrimeraPagina = 20;

if ($totalProductos <= $productosPrimeraPagina) {
    $numPagesDpto = 1;
} else {
    $productosRestantes = $totalProductos - $productosPrimeraPagina;
    $numPagesDpto = 1 + ceil($productosRestantes / $productosPorPagina);
}

// Calcular offset y límite según la página y firstProd
if ($pageNum == 1 && $firstProd == 1) {
    // Página 1 normal: título + hasta 20 productos
    $limit = 20;
    $offset = 0;
} elseif ($pageNum == 1 && $firstProd > 1) {
    // Primera página sin título: hasta 25 productos desde firstProd
    $limit = 25;
    $offset = $firstProd - 1;
} else {
    // Páginas siguientes: 25 productos
    $limit = 25;
    $offset = ($firstProd == 1 ? 20 : 0) + (($pageNum - 2) * 25);
}

// Asegurar que no nos pasamos del total
if ($offset >= $totalProductos) {
    $productVals = array();
    $numProducts = 0;
} else {
    // Ajustar límite si nos pasamos
    if ($offset + $limit > $totalProductos) {
        $limit = $totalProductos - $offset;
    }
    
    $query = "SELECT id, code, name, photo_url, cost_max FROM productos ";
    $query .= "WHERE show='t' AND dpto_id=".$dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ";
    $query .= "ORDER BY orden, code ";
    $query .= "LIMIT ".$limit." OFFSET ".$offset;
    
    $consult1 = $db->consultas($query);
    $productVals = array();
    $numProducts = 0;
    
    foreach ($consult1 as $value){
        $productVal = new stdClass();
        $productVal->id = $value->id;
        $productVal->code = $value->code;
        $productVal->desc = $value->name;
        $productVal->url = $value->photo_url;
        $productVal->cost = floatval($value->cost_max);
        $productVal->cost_80 = floatval($value->cost_max)*.8;
        
        $productVals[] = $productVal;
        $numProducts++;
    }
}

// Determinar si debemos incluir título del siguiente departamento (solo para fusión)
$incluirTituloSiguiente = false;
$nombreSiguiente = '';
$idSiguiente = 0;

if ($fusionMode && $pageNum == $numPagesDpto) {
    // Estamos en la última página de este departamento
    $productosEnEstaPagina = $numProducts;
    
    if ($productosEnEstaPagina <= 15 && isset($_GET['next_dpto_id'])) {
        $idSiguiente = intval($_GET['next_dpto_id']);
        $consultNext = $db->consultas("SELECT name FROM departamentos WHERE id=".$idSiguiente);
        foreach ($consultNext as $nextValue){
            $nombreSiguiente = $nextValue->name;
            $incluirTituloSiguiente = true;
        }
    }
}

// Generar contenido HTML
$tags = '';

// Título del departamento actual (solo si es página 1 Y firstProd == 1)
if ($pageNum == 1 && $firstProd == 1) {
    $tags .= '<div class="row">';
    $tags .= '<div class="col text-center">';
    $tags .= '<h1 class="rounded-title" style="margin: 8rem 0; white-space: nowrap; width: 90%;">'.$currCatName.'</h1>';
    $tags .= '</div>';
    $tags .= '</div>';
}

// Productos (siempre en grid)
if ($numProducts > 0) {
    $tags .= '<div class="row row-cols-1 row-cols-sm-5 g-5 justify-content-center">';
    
    foreach ($productVals as $producto){
        $currUrl = $currCatImgRoute . $producto->url;
        
        $tags .= '<div class="col" style="background-color: #FFF;">';
        $tags .=    '<div class="card h-100 text-bg-light">';
        $tags .=        '<div class="card-header" style="background-color: #037C79;">';
        $tags .=            '<h3 style="color: #FFF;">'.$producto->code.'</h3>';
        $tags .=        '</div>';
        $tags .=        '<img src="'.$currUrl.'" class="card-img-top" alt="'.$producto->code.'">';
        $tags .=        '<div class="card-body" style="background-color: #0CC;">';
        $tags .=            '<h6 class="card-text">'.$producto->desc.'</h6>';
        if($role > -1) {
            $tags .=        '<h5 class="card-text">Prec. : $'.number_format($producto->cost, 3, ",").'</h5>';
            $tags .=        '<h5 class="card-text">-20% : $'.number_format($producto->cost_80, 3, ",").'</h5>';
        }
        $tags .=        '</div>';
        $tags .=    '</div>';
        $tags .= '</div>';
    }
    
    $tags .= '</div>';
}

// Si estamos en modo fusión, agregar título del siguiente departamento
if ($incluirTituloSiguiente) {
    $tags .= '<div style="border-top: 3px solid #003272; margin: 30px 0 20px 0;"></div>';
    $tags .= '<div class="col text-center">';
    $tags .= '<h1 class="rounded-title" style="margin: 2rem 0; white-space: nowrap; width: 90%;">'.$nombreSiguiente.'</h1>';
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
                width: 90%;
                max-width: 1200px;
                margin-left: auto;
                margin-right: auto;
                font-family: 'Varela Round', sans-serif;
                padding: 1.5rem 0;
                letter-spacing: 3px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
                padding: 2px;
            }
            
            @media print {
                body { 
                    margin: 0; 
                    padding: 0; 
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