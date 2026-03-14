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

// ============================================
// IMPORTANTE: Usamos la misma lógica de consulta que indexDpto5X5New.php
// pero adaptada para first_prod si es necesario
// ============================================

// Obtener TODOS los productos (sin paginar aún) para calcular totales
$queryBase = "SELECT id, code, name, photo_url, cost_max, unit, current_stock FROM productos WHERE show='t' AND dpto_id=".$dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ORDER BY orden, code";
$consultTotal = $db->consultas($queryBase);
$todosLosProductos = array();
foreach ($consultTotal as $value){
    $producto = new stdClass();
    $producto->id = $value->id;
    $producto->code = $value->code;
    $producto->desc = $value->name;
    $producto->url = $value->photo_url;
    $producto->unit = $value->unit;
    $producto->current_stock = $value->current_stock;
    $producto->cost = floatval($value->cost_max);
    $producto->cost_80 = floatval($value->cost_max)*.8;
    $todosLosProductos[] = $producto;
}
$totalProductos = count($todosLosProductos);

// Calcular número de páginas (igual que en New.php)
$productosPorPagina = 25;
$productosPrimeraPagina = 20;

if ($totalProductos <= $productosPrimeraPagina) {
    $numPages = 1;
} else {
    $productosRestantes = $totalProductos - $productosPrimeraPagina;
    $numPages = 1 + ceil($productosRestantes / $productosPorPagina);
}

// Determinar el rango de productos para esta página según pageNum Y firstProd
if ($pageNum == 1) {
    if ($firstProd == 1) {
        // Página 1 normal: primeros 20 productos
        $inicio = 0;
        $fin = min($productosPrimeraPagina, $totalProductos) - 1;
    } else {
        // Primera página sin título (viene de fusión): mostrar desde firstProd
        $inicio = $firstProd - 1;
        $fin = min($inicio + $productosPorPagina - 1, $totalProductos - 1);
    }
} else {
    // Páginas siguientes
    if ($firstProd == 1) {
        // Caso normal: después de página 1 con título
        $inicio = $productosPrimeraPagina + (($pageNum - 2) * $productosPorPagina);
    } else {
        // Caso con firstProd > 1: empezar desde firstProd
        $inicio = ($firstProd - 1) + (($pageNum - 1) * $productosPorPagina);
    }
    $fin = min($inicio + $productosPorPagina - 1, $totalProductos - 1);
}

// Construir el HTML exactamente igual que en New.php
$tags = '<div class="col text-center">';

// Título solo si es página 1 Y firstProd == 1
if ($pageNum == 1 && $firstProd == 1) {
    $tags .= '<h1 class="rounded-title" style="margin: 10rem 0; width: 95%;">'.$currCatName.'</h1>';
}

$tags .= '</div>';
$tags .= '<div class="row row-cols-1 row-cols-sm-5 g-5 justify-content-center">';

// Mostrar productos en el rango calculado
for ($i = $inicio; $i <= $fin; $i++){
    if (!isset($todosLosProductos[$i])) continue;
    
    $producto = $todosLosProductos[$i];
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

// Si estamos en modo fusión y es última página con espacio, agregar título del siguiente
if ($fusionMode && $pageNum == $numPages && isset($_GET['next_dpto_id'])) {
    $espacioLibre = 25 - ($fin - $inicio + 1);
    if ($espacioLibre >= 5) { // Al menos una fila de espacio
        $idSiguiente = intval($_GET['next_dpto_id']);
        $consultNext = $db->consultas("SELECT name FROM departamentos WHERE id=".$idSiguiente);
        foreach ($consultNext as $nextValue){
            $nombreSiguiente = $nextValue->name;
            $tags .= '<div style="border-top: 3px solid #003272; margin: 30px 0 20px 0;"></div>';
            $tags .= '<div class="col text-center">';
            $tags .= '<h1 class="rounded-title" style="margin: 2rem 0; width: 95%;">'.$nombreSiguiente.'</h1>';
            $tags .= '</div>';
        }
    }
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
                width: 95%;
                max-width: 1200px;
                margin-left: auto;
                margin-right: auto;
                font-family: 'Varela Round', 'Arial Rounded MT Bold', 'Helvetica Rounded', Arial, sans-serif;
                font-weight: 400;
                padding: 1.1rem 0;
                letter-spacing: 3px;
                display: inline-block;
                box-sizing: border-box;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
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
            
            @media print {
                body { 
                    background-color: #FFF; 
                    margin: 0;
                    padding: 0;
                }
                .rounded-title {
                    white-space: nowrap;
                    width: 95%;
                    font-size: 24pt;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
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
                <p> pag. <?php echo $pageNum; ?> / <?php echo $numPages; ?></p>      
            </div>
        </div>

        <div class="w-100 p-3" style="background-color: #FFF;"> 
            <div id="productos">
                <?php echo $tags; ?>
            </div>    
        </div>
    </body>
</html>