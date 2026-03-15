<?php
require_once("../php/dbcat.php");

// ============================================
// PARÁMETROS DE PRUEBA
// ============================================
$dptoId = isset($_GET['dpto_id']) ? intval($_GET['dpto_id']) : 101;
$pageNum = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$cols = isset($_GET['cols']) ? intval($_GET['cols']) : 4;        // Columnas: 3,4,5,6
$rows = isset($_GET['rows']) ? intval($_GET['rows']) : 5;        // Filas: 4,5,6
$layout = isset($_GET['layout']) ? $_GET['layout'] : 'derecha';  // 'derecha' o 'abajo'

$db = new DB();

// Obtener información del departamento
$consult = $db->consultas("SELECT name, img_route FROM departamentos WHERE id=".$dptoId);
$currCatName = '';
$currCatImgRoute = '';
foreach ($consult as $value){
    $currCatName = $value->name;
    $currCatImgRoute = $value->img_route;
}

// Obtener productos del departamento
$query  = "SELECT id, code, name, photo_url, cost_max FROM productos WHERE show='t' AND dpto_id=";
$query .= $dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ORDER BY orden, code LIMIT ".($cols * $rows);

$consult1 = $db->consultas($query);
$productVals = array();

foreach ($consult1 as $value){
    $productVal = new stdClass();
    $productVal->code = $value->code;
    $productVal->desc = $value->name;
    $productVal->url = $value->photo_url;
    $productVal->cost = floatval($value->cost_max);
    $productVals[] = $productVal;
}

// Generar HTML
$tags = '<div class="container-fluid">';

// Título
$tags .= '<div class="row mb-5">';
$tags .= '<div class="col text-center">';
$tags .= '<h1 class="rounded-title" style="margin: 2rem 0; width: 90%;">'.$currCatName.'</h1>';
$tags .= '</div>';
$tags .= '</div>';

// Grid de productos
$tags .= '<div class="row row-cols-1 row-cols-sm-'.$cols.' g-4 justify-content-center">';

foreach ($productVals as $producto){
    $currUrl = $currCatImgRoute . $producto->url;
    
    $tags .= '<div class="col">';
    $tags .= '<div class="card h-100">';
    
    // Encabezado con código (siempre arriba a todo ancho)
    $tags .= '<div class="card-header text-center" style="background-color: #037C79; color: #FFF; font-weight: bold;">';
    $tags .= $producto->code;
    $tags .= '</div>';
    
    // Cuerpo de la card - layout variable
    if ($layout == 'derecha') {
        // Foto a la izquierda, descripción a la derecha
        $tags .= '<div class="row g-0 p-2">';
        $tags .= '<div class="col-6">';
        $tags .= '<img src="'.$currUrl.'" class="img-fluid" alt="'.$producto->code.'" style="max-height: 80px; object-fit: contain;">';
        $tags .= '</div>';
        $tags .= '<div class="col-6 d-flex align-items-center" style="background-color: #0CC;">';
        $tags .= '<div class="card-text small p-1">'.$producto->desc.'</div>';
        $tags .= '</div>';
        $tags .= '</div>';
    } else {
        // Layout original: foto arriba, descripción abajo
        $tags .= '<img src="'.$currUrl.'" class="card-img-top" alt="'.$producto->code.'" style="height: 80px; object-fit: contain;">';
        $tags .= '<div class="card-body" style="background-color: #0CC;">';
        $tags .= '<h6 class="card-text text-center">'.$producto->desc.'</h6>';
        $tags .= '</div>';
    }
    
    // Pie opcional
    $tags .= '<div class="card-footer text-center" style="background-color: #0CC; border-top: none; font-size: 0.8rem;">';
    $tags .= 'KET';  // Puede ser vacío o una franja de color
    $tags .= '</div>';
    
    $tags .= '</div>'; // fin card
    $tags .= '</div>'; // fin col
}

$tags .= '</div>'; // fin row
$tags .= '</div>'; // fin container

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <title>Test Layout - <?php echo $cols.'x'.$rows; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #FFF; padding: 20px; }
        .rounded-title {
            background-color: #003272;
            color: #FFF;
            border-radius: 30px;
            padding: 1rem 0;
            margin: 0 auto;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-header {
            background-color: #037C79 !important;
            color: #FFF !important;
            font-weight: bold;
        }
        .card-body, .card-footer {
            background-color: #0CC !important;
        }
        .debug-info {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="debug-info">
        <strong>Layout Test</strong><br>
        Columnas: <?php echo $cols; ?><br>
        Filas: <?php echo $rows; ?><br>
        Layout: <?php echo $layout; ?><br>
        Productos: <?php echo count($productVals); ?><br>
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=3&rows=5&layout=<?php echo $layout; ?>">3 cols</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=4&rows=5&layout=<?php echo $layout; ?>">4 cols</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=5&rows=5&layout=<?php echo $layout; ?>">5 cols</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=6&rows=5&layout=<?php echo $layout; ?>">6 cols</a><br>
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=<?php echo $cols; ?>&rows=4&layout=<?php echo $layout; ?>">4 rows</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=<?php echo $cols; ?>&rows=5&layout=<?php echo $layout; ?>">5 rows</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=<?php echo $cols; ?>&rows=6&layout=<?php echo $layout; ?>">6 rows</a><br>
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=<?php echo $cols; ?>&rows=<?php echo $rows; ?>&layout=derecha">Layout Derecha</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&page_num=<?php echo $pageNum; ?>&cols=<?php echo $cols; ?>&rows=<?php echo $rows; ?>&layout=abajo">Layout Abajo</a>
    </div>

    <?php echo $tags; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>