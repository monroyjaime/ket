<?php
require_once("../php/dbcat.php");

$role = isset($_GET['role_num']) ? intval($_GET['role_num']) : -1;
$dptoId = isset($_GET['dpto_id']) ? intval($_GET['dpto_id']) : 1;
$pageNum = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$firstProd = isset($_GET['first_prod']) ? intval($_GET['first_prod']) : 1;
$pageGlobal = isset($_GET['page_global']) ? intval($_GET['page_global']) : 1;
$totalPaginasGlobal = isset($_GET['total_paginas']) ? intval($_GET['total_paginas']) : 1;

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
// CONFIGURACIÓN - 3 COLUMNAS, FOTO GRANDE
// ============================================
$cols = 3;
$rowsConTitulo = 7;   // 3x7 = 21 productos con título
$rowsSinTitulo = 8;    // 3x8 = 24 productos sin título

// Calcular productos por página SEGÚN el caso
if ($pageNum == 1 && $firstProd == 1) {
    $productosPorPagina = $cols * $rowsConTitulo;  // 21 productos
} else {
    $productosPorPagina = $cols * $rowsSinTitulo;  // 24 productos
}

// Obtener TOTAL de productos (para cálculos de páginas, aunque no se usa directamente aquí)
$queryTotal = "SELECT COUNT(*) as total FROM productos WHERE show='t' AND dpto_id=".$dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0";
$consultTotal = $db->consultas($queryTotal);
$totalProductos = $consultTotal[0]->total;

// Calcular offset para esta página
$offset = ($firstProd - 1) + (($pageNum - 1) * $productosPorPagina);

// Obtener productos para esta página
$query = "SELECT code, name, photo_url, cost_max FROM productos ";
$query .= "WHERE show='t' AND dpto_id=".$dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ";
$query .= "ORDER BY orden, code ";
$query .= "LIMIT ".$productosPorPagina." OFFSET ".$offset;

$consult1 = $db->consultas($query);
$numProducts = count($consult1);  // ✅ AHORA SÍ, $consult1 ya está definida

// Función para formatear descripción
function formatearDescripcion($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = ucfirst($texto);
    return $texto;
}

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
$tags .= 'Pág. '.$pageGlobal.' / '.$totalPaginasGlobal;
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
if ($numProducts > 0) {
    $tags .= '<div class="products-grid">';
    
    $fila_actual = 0;
    $index = 0;
    
    foreach ($consult1 as $producto) {
        // Inicio de nueva fila cada 3 productos
        if ($index % $cols == 0) {
            if ($index > 0) {
                $tags .= '</div>';
            }
            $fila_actual++;
            $tags .= '<div class="row g-2 justify-content-center" data-group="fila_'.$fila_actual.'" style="page-break-inside: avoid; margin-bottom: 5px;">';
        }
        
        // Card del producto
        $imgUrl = $currCatImgRoute . $producto->photo_url;
        $descripcion = formatearDescripcion($producto->name);
        
        $tags .= '<div class="col">';
        $tags .= '<div class="card">';
        $tags .= '<div class="card-header">'.$producto->code.'</div>';
        $tags .= '<div class="row g-0">';
        // Foto: 50% ancho
        $tags .= '<div class="col-6 text-center img-container">';
        $tags .= '<img src="'.$imgUrl.'" alt="'.$producto->code.'">';
        $tags .= '</div>';
        // Descripción: 50% ancho
        $tags .= '<div class="col-6 texto">';
        $tags .= $descripcion;
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
} else {
    // Esto no debería pasar, pero por si acaso
    $tags .= '<p>No hay productos en esta página</p>';
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo KET 3x7 Foto Grande</title>
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
            padding: 0.5rem 1rem;
            margin: 0.5rem auto 1rem auto;
            display: inline-block;
            font-size: 16pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            width: auto;
            min-width: 300px;
            line-height: 1.2;
        }
        
        .products-grid {
            margin-top: 5px;
        }
        
        /* Grid de 3 columnas */
        .row > .col {
            flex: 0 0 auto;
            width: 33.333%;
            max-width: 33.333%;
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
            font-size: 9pt;
            border-bottom: none;
        }
        
        .row.g-0 {
            margin: 0;
            min-height: 110px;
        }
        
        /* Foto: 50% ancho */
        .col-6.img-container {
            width: 50%;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
        }
        
        .col-6.img-container img {
            width: 100%;
            height: auto;
            max-height: 105px;
            object-fit: contain;
        }
        
        /* Texto: 50% ancho */
        .col-6.texto {
            width: 50%;
            padding: 4px;
            display: flex;
            align-items: center;
            font-size: 7.5pt;
            line-height: 1.2;
            background-color: #f8f9fa;
            word-wrap: break-word;
            overflow-y: auto;
            max-height: 110px;
        }
        
        .col-6.texto::first-letter {
            text-transform: uppercase;
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