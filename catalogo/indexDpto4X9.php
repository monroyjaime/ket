<?php
require_once("../php/dbcat.php");

$role = isset($_GET['role_num']) ? intval($_GET['role_num']) : -1;
$dptoId = isset($_GET['dpto_id']) ? intval($_GET['dpto_id']) : 1;
$pageNum = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$firstProd = isset($_GET['first_prod']) ? intval($_GET['first_prod']) : 1;

$db = new DB();

// Obtener información del departamento
$consult = $db->consultas("SELECT name, img_route FROM departamentos WHERE id=".$dptoId);
$currCatName = '';
$currCatImgRoute = '';
foreach ($consult as $value){
    $currCatName = $value->name;
    $currCatImgRoute = $value->img_route;
}

// ============================================
// CONFIGURACIÓN FIJA DEL NUEVO FORMATO
// ============================================
$cols = 4;
$rowsConTitulo = 7;
$rowsSinTitulo = 8;
$productosPorPagina = ($pageNum == 1 && $firstProd == 1) ? $cols * $rowsConTitulo : $cols * $rowsSinTitulo;

// Obtener TOTAL de productos
$queryTotal = "SELECT COUNT(*) as total FROM productos WHERE show='t' AND dpto_id=".$dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0";
$consultTotal = $db->consultas($queryTotal);
$totalProductos = $consultTotal[0]->total;

// Calcular número de páginas
if ($totalProductos <= $cols * $rowsConTitulo) {
    $numPages = 1;
} else {
    $productosRestantes = $totalProductos - ($cols * $rowsConTitulo);
    $numPages = 1 + ceil($productosRestantes / ($cols * $rowsSinTitulo));
}

// Calcular offset para esta página
$offset = ($firstProd - 1) + (($pageNum - 1) * $productosPorPagina);

// Obtener productos para esta página
$query = "SELECT code, name, photo_url, cost_max FROM productos ";
$query .= "WHERE show='t' AND dpto_id=".$dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ";
$query .= "ORDER BY orden, code ";
$query .= "LIMIT ".$productosPorPagina." OFFSET ".$offset;

$consult1 = $db->consultas($query);

// Generar HTML
$tags = '';

// ============================================
// ENCABEZADO CON LOGO
// ============================================
$tags .= '<div class="header">';
$tags .= '<div class="row align-items-center">';
$tags .= '<div class="col-6">';
$tags .= '<img src="../catalogo/images/logo.png" class="logo" alt="KET">';
$tags .= '</div>';
$tags .= '<div class="col-6 pagination-info">';
$tags .= 'Pág. '.$pageNum.' / '.$numPages;
$tags .= '</div>';
$tags .= '</div>';
$tags .= '</div>';

// ============================================
// TÍTULO DEL DEPARTAMENTO
// ============================================
if ($pageNum == 1 && $firstProd == 1) {
    $tags .= '<div class="text-center">';
    $tags .= '<h1 class="rounded-title">'.$currCatName.'</h1>';
    $tags .= '</div>';
}

// ============================================
// PRODUCTOS - CON FILAS AGRUPADAS
// ============================================
$tags .= '<div class="products-grid">';

$fila_actual = 0;
$index = 0;

foreach ($consult1 as $producto) {
    // Inicio de nueva fila cada 4 productos
    if ($index % $cols == 0) {
        if ($index > 0) {
            $tags .= '</div>';
        }
        $fila_actual++;
        $tags .= '<div class="row g-2" data-group="fila_'.$fila_actual.'" style="page-break-inside: avoid; margin-bottom: 5px;">';
    }
    
    // Card del producto
    $imgUrl = $currCatImgRoute . $producto->photo_url;
    
    $tags .= '<div class="col">';
    $tags .= '<div class="card">';
    $tags .= '<div class="card-header">'.$producto->code.'</div>';
    $tags .= '<div class="row g-0">';
    $tags .= '<div class="col-5 text-center">';
    $tags .= '<img src="'.$imgUrl.'" alt="'.$producto->code.'">';
    $tags .= '</div>';
    $tags .= '<div class="col-7">';
    $tags .= $producto->name;
    $tags .= '</div>';
    $tags .= '</div>';
    $tags .= '</div>';
    $tags .= '</div>';
    
    $index++;
}

// Cerrar última fila si hay productos
if ($index > 0) {
    $tags .= '</div>';
}

$tags .= '</div>';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo KET 4x9</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #FFF; 
            margin: 0;
            padding: 5.5mm 10mm;
            font-family: 'Segoe UI', Arial, sans-serif; 
        }
        
        .header {
            margin-bottom: 5px;
        }
        
        .logo {
            max-height: 60px;
            width: auto;
        }
        
        .pagination-info {
            text-align: right;
            font-size: 11pt;
            color: #333;
        }
        
        .rounded-title {
            background-color: #003272;
            color: #FFF;
            border-radius: 30px;
            padding: 0.8rem 2rem;
            margin: 0.5rem auto 1rem auto;
            display: inline-block;
            font-size: 24pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90%;
        }
        
        .products-grid {
            margin-top: 5px;
        }
        
        .row {
            margin-bottom: 5px;
        }
        
        .card {
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: #037C79 !important;
            color: white !important;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            font-size: 10pt;
            border-bottom: none;
        }
        
        .row.g-0 {
            margin: 0;
            min-height: 95px;
        }
        
        .col-5 {
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .col-7 {
            padding: 6px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            font-size: 8.5pt;
            line-height: 1.2;
            word-wrap: break-word;
            overflow-y: auto;
            max-height: 95px;
        }
        
        img {
            max-height: 85px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }
        
        @media print {
            body {
                margin: 5.5mm 10mm;
                padding: 0;
            }
            .card {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <?php echo $tags; ?>
</body>
</html>