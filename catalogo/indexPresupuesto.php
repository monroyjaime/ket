<?php
// Archivo: /var/www/html/catalogo/indexPresupuesto.php
// Recibe una lista de códigos de producto (separados por coma) y muestra las cards

$role = isset($_GET['role_num']) ? intval($_GET['role_num']) : -1;
$pageGlobal = isset($_GET['page_global']) ? intval($_GET['page_global']) : 1;
$totalPaginasGlobal = isset($_GET['total_paginas']) ? intval($_GET['total_paginas']) : 1;
$numValery = isset($_GET['num_valery']) ? intval($_GET['num_valery']) : 0;
$codigosStr = isset($_GET['codigos']) ? $_GET['codigos'] : '';

require_once("../php/dbcat.php");
$db = new DB();

$tags = '';

// ============================================
// ENCABEZADO CON LOGO Y NUMERACIÓN
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
// TÍTULO DEL PRESUPUESTO
// ============================================
if ($numValery > 0) {
    $tags .= '<div class="text-center">';
    $tags .= '<h1 class="rounded-title">Presupuesto N° '.$numValery.'</h1>';
    $tags .= '</div>';
}

// ============================================
// PROCESAR CÓDIGOS DE PRODUCTO
// ============================================
if (empty($codigosStr)) {
    $tags .= '<p>No se especificaron productos</p>';
} else {
    $codigos = explode(',', $codigosStr);
    
    // Escapar valores manualmente
    $codigos_escapados = array();
    foreach ($codigos as $codigo) {
        $codigos_escapados[] = "'" . pg_escape_string($db->getLink(), trim($codigo)) . "'";
    }
    $codigos_lista = implode(',', $codigos_escapados);
    
    $query = "SELECT p.code, p.name, p.photo_url, d.img_route 
              FROM productos p
              JOIN departamentos d ON p.dpto_id = d.id
              WHERE p.code IN ($codigos_lista)
                AND p.show = true
                AND p.photo_url != 'empty.jpg'
                AND p.cost_max > 0
              ORDER BY p.code";
    
    $consult1 = $db->consultas($query);
    
    if (empty($consult1)) {
        $tags .= '<p>No se encontraron productos con los códigos especificados.</p>';
        $tags .= '<p>Códigos recibidos: ' . htmlspecialchars($codigosStr) . '</p>';
    } else {
        $tags .= '<div class="products-grid">';
        $tags .= '<div class="row row-cols-1 row-cols-sm-3 g-4 justify-content-center">';
        
        foreach ($consult1 as $producto) {
            $imgUrl = $producto->img_route . $producto->photo_url;
            
            $tags .= '<div class="col">';
            $tags .= '<div class="card h-100">';
            
            // Encabezado con código
            $tags .= '<div class="card-header text-center" style="background-color: #037C79; color: white; font-weight: bold;">';
            $tags .= htmlspecialchars($producto->code);
            $tags .= '</div>';
            
            // Cuerpo: foto y descripción 50/50
            $tags .= '<div class="row g-0">';
            $tags .= '<div class="col-6 text-center img-container">';
            $tags .= '<img src="'.$imgUrl.'" alt="'.htmlspecialchars($producto->code).'">';
            $tags .= '</div>';
            $tags .= '<div class="col-6 texto">';
            $tags .= htmlspecialchars($producto->name);
            $tags .= '</div>';
            $tags .= '</div>';
            
            $tags .= '</div>';
            $tags .= '</div>';
        }
        
        $tags .= '</div>';
        $tags .= '</div>';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presupuesto KET</title>
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
            font-size: 20pt;
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
            padding: 6px;
            font-size: 10pt;
            border-bottom: none;
        }
        
        .row.g-0 {
            margin: 0;
            min-height: 100px;
        }
        
        .col-6.img-container {
            width: 50%;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
        }
        
        .col-6.img-container img {
            width: 100%;
            height: auto;
            max-height: 90px;
            object-fit: contain;
        }
        
        .col-6.texto {
            width: 50%;
            padding: 6px;
            display: flex;
            align-items: center;
            font-size: 8pt;
            line-height: 1.2;
            background-color: #f8f9fa;
            word-wrap: break-word;
            overflow-y: auto;
            max-height: 100px;
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