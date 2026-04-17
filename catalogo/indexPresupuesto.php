<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// 1. INCLUIR CONEXIÓN A BD
// ============================================
require_once("../php/dbcat.php");
$db = new DB();
$conn = $db->getLink();

// ============================================
// 2. OBTENER PARÁMETROS GET
// ============================================
$role = isset($_GET['role_num']) ? intval($_GET['role_num']) : -1;
$pageGlobal = isset($_GET['page_global']) ? intval($_GET['page_global']) : 1;
$totalPaginasGlobal = isset($_GET['total_paginas']) ? intval($_GET['total_paginas']) : 1;
$mostrarPrecio = isset($_GET['mostrar_precio']) ? intval($_GET['mostrar_precio']) : 0;

// El parámetro principal: presupuesto_num (el número que ve el usuario, ej: 292)
$presupuestoNum = isset($_GET['presupuesto_num']) ? intval($_GET['presupuesto_num']) : 0;

// Si recibimos presupuesto_id (por compatibilidad), lo tratamos igual
if ($presupuestoNum == 0 && isset($_GET['presupuesto_id'])) {
    $presupuestoNum = intval($_GET['presupuesto_id']);
}

$tags = '';
$numValery = 0;
$productos = [];

// ============================================
// 3. OBTENER DATOS DEL PRESUPUESTO DESDE BD
// ============================================
if ($presupuestoNum > 0) {
    // Obtener idx y num_valery a partir del presupuesto_num
    $queryPresupuesto = "
        SELECT idx, num_valery, presupuesto_num 
        FROM presupuesto_gen 
        WHERE presupuesto_num = $presupuestoNum
    ";
    $resultPresupuesto = pg_query($conn, $queryPresupuesto);
    
    if ($resultPresupuesto && $rowPresupuesto = pg_fetch_assoc($resultPresupuesto)) {
        $idx = $rowPresupuesto['idx'];
        $numValery = $rowPresupuesto['num_valery'];
        
        // Consultar productos del presupuesto usando idx
        $queryProductos = "
            SELECT 
                pd.product_code,
                pd.cantidad,
                pd.precio,
                p.name as descripcion,
                p.photo_url,
                d.img_route
            FROM presupuesto_detail pd
            JOIN productos p ON pd.product_code = p.code
            LEFT JOIN departamentos d ON p.dpto_id = d.id
            WHERE pd.pres_idx = $idx
              AND p.show = true
              AND p.cost_max > 0
            ORDER BY pd.orden ASC
        ";
        
        $resultProductos = pg_query($conn, $queryProductos);
        if ($resultProductos) {
            $productos = pg_fetch_all($resultProductos);
        }
    }
}

// ============================================
// 4. GENERAR ENCABEZADO
// ============================================
$tags .= '<div class="header">';
$tags .= '<div class="row align-items-center">';
$tags .= '<div class="col-6">';
$tags .= '<img src="../catalogo/images/logo.png" class="logo" alt="KET">';
$tags .= '</div>';
$tags .= '<div class="col-6 pagination-info">';
$tags .= 'Pág. ' . $pageGlobal . ' / ' . $totalPaginasGlobal;
$tags .= '</div>';
$tags .= '</div>';
$tags .= '</div>';

// ============================================
// 5. TÍTULO DEL PRESUPUESTO
// ============================================
if ($numValery > 0) {
    $tags .= '<div class="text-center">';
    $tags .= '<h1 class="rounded-title">Presupuesto N° ' . htmlspecialchars($numValery) . '</h1>';
    $tags .= '</div>';
}

// ============================================
// 6. GENERAR GRID DE PRODUCTOS CON PAGINACIÓN
// ============================================
if (empty($productos)) {
    $tags .= '<p>No se encontraron productos para este presupuesto.</p>';
} else {
    // Calcular productos por página
    $productos_por_pagina = ($mostrarPrecio == 1) ? 18 : 21;
    $inicio = ($pageGlobal - 1) * $productos_por_pagina;
    $productos_pagina = array_slice($productos, $inicio, $productos_por_pagina);
    
    $tags .= '<div class="products-grid">';
    $tags .= '<div class="row row-cols-1 row-cols-sm-3 g-4 justify-content-center">';
    
    foreach ($productos_pagina as $producto) {
        $code = $producto['product_code'];
        $precio = floatval($producto['precio']);
        $descripcion = $producto['descripcion'];
        $photoUrl = $producto['photo_url'];
        
        // Manejar imagen
        if (empty($photoUrl) || $photoUrl == 'empty.jpg') {
            $imgUrl = '../catalogo/images/empty.jpg';
        } else {
            $imgUrl = $producto['img_route'] . $photoUrl;
        }
        
        $tags .= '<div class="col">';
        $tags .= '<div class="card h-100">';
        $tags .= '<div class="card-header text-center" style="background-color: #037C79; color: white; font-weight: bold;">';
        $tags .= htmlspecialchars($code);
        $tags .= '</div>';
        $tags .= '<div class="row g-0">';
        $tags .= '<div class="col-6 text-center img-container">';
        $tags .= '<img src="'.$imgUrl.'" alt="'.htmlspecialchars($code).'" style="max-height:90px; width:auto; max-width:100%; object-fit:contain;">';
        $tags .= '</div>';
        $tags .= '<div class="col-6 texto">';
        $tags .= htmlspecialchars($descripcion);
        $tags .= '</div>';
        $tags .= '</div>';
        
        if ($mostrarPrecio == 1 && $precio > 0) {
            $precioFormateado = number_format($precio, 3, ',', '.');
            $tags .= '<div class="card-footer text-center" style="background-color: #f0f0f0; padding: 6px;">';
            $tags .= '<strong>Precio: $' . $precioFormateado . '</strong>';
            $tags .= '</div>';
        }
        
        $tags .= '</div>';
        $tags .= '</div>';
    }
    
    $tags .= '</div>';
    $tags .= '</div>';
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
        body { background-color: #FFF; margin: 0; padding: 5.5mm 10mm; font-family: 'Segoe UI', Arial, sans-serif; }
        .header { margin-bottom: 5px; }
        .logo { max-height: 60px; width: auto; }
        .pagination-info { text-align: right; font-size: 11pt; color: #333; }
        .rounded-title { background-color: #003272; color: #FFF; border-radius: 30px; padding: 0.5rem 1rem; margin: 0.5rem auto 1rem auto; display: inline-block; font-size: 20pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; width: auto; min-width: 300px; line-height: 1.2; }
        .products-grid { margin-top: 5px; }
        .row > .col { flex: 0 0 auto; width: 33.333%; max-width: 33.333%; }
        .row { margin-bottom: 5px; }
        .card { border: 1px solid #ddd; border-radius: 6px; overflow: hidden; height: 100%; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card-header { background-color: #037C79 !important; color: white !important; font-weight: bold; text-align: center; padding: 6px; font-size: 10pt; border-bottom: none; }
        .row.g-0 { margin: 0; min-height: 100px; }
        .col-6.img-container { width: 50%; padding: 4px; display: flex; align-items: center; justify-content: center; background-color: #fff; }
        .col-6.img-container img { width: 100%; height: auto; max-height: 90px; object-fit: contain; }
        .col-6.texto { width: 50%; padding: 6px; display: flex; align-items: center; font-size: 8pt; line-height: 1.2; background-color: #f8f9fa; word-wrap: break-word; overflow-y: auto; max-height: 100px; }
        .card-footer { font-size: 9pt; background-color: #f8f9fa; }
        @media print { body { margin: 5.5mm 10mm; padding: 0; } .card { break-inside: avoid; } }
    </style>
</head>
<body>
    <?php echo $tags; ?>
</body>
</html>