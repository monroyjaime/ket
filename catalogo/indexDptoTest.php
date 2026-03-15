<?php
require_once("../php/dbcat.php");

// Parámetros de prueba
$dptoId = isset($_GET['dpto_id']) ? intval($_GET['dpto_id']) : 101;
$pageNum = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$cols = isset($_GET['cols']) ? intval($_GET['cols']) : 4;
$rows = isset($_GET['rows']) ? intval($_GET['rows']) : 10;
$layout = isset($_GET['layout']) ? $_GET['layout'] : 'derecha';
$sinTitulo = isset($_GET['sin_titulo']) ? true : false;

$db = new DB();

// Obtener información del departamento
$consult = $db->consultas("SELECT name, img_route FROM departamentos WHERE id=".$dptoId);
$currCatName = '';
$currCatImgRoute = '';
foreach ($consult as $value){
    $currCatName = $value->name;
    $currCatImgRoute = $value->img_route;
}

// Obtener productos
$query  = "SELECT code, name, photo_url FROM productos WHERE show='t' AND dpto_id=";
$query .= $dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ORDER BY orden, code LIMIT ".($cols * $rows);
$consult1 = $db->consultas($query);

// Calcular total de productos para paginación (simulado)
$totalProductos = count($consult1) * 3; // Simulamos más páginas
$totalPaginas = ceil($totalProductos / ($cols * $rows));

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Layout - <?php echo $cols.'x'.$rows; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #FFF; 
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif; 
        }
        
        /* Encabezado con logo */
        .header {
            background-color: #FFF;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .logo {
            max-height: 40px;
            width: auto;
        }
        
        .pagination-info {
            text-align: right;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Título del departamento */
        .rounded-title {
            background-color: #003272;
            color: #FFF;
            border-radius: 30px;
            padding: 1rem 2rem;
            margin: 1rem auto;
            display: inline-block;
            font-size: 1.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90%;
        }
        
        /* Grid de productos */
        .products-grid {
            padding: 0 15px;
        }
        
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: #037C79 !important;
            color: white !important;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            font-size: 0.85rem;
            border-bottom: none;
        }
        
        .layout-derecha .row {
            margin: 0;
            min-height: 120px;
        }
        
        .layout-derecha .col-5 {
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .layout-derecha .col-7 {
            padding: 10px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            line-height: 1.2;
            word-wrap: break-word;
            overflow-y: auto;
            max-height: 110px;
        }
        
        .layout-derecha img {
            max-height: 110px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }
        
        /* Debug info */
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
        
        .debug-info a {
            color: #0CC;
            text-decoration: none;
            margin: 0 3px;
        }
        
        @media print {
            .debug-info { display: none; }
            .card { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="debug-info">
        <strong>Layout Test</strong><br>
        Grid: <?php echo $cols.'x'.$rows; ?><br>
        Productos: <?php echo $cols * $rows; ?><br>
        Título: <?php echo $sinTitulo ? 'No' : 'Sí'; ?><br>
        <a href="?dpto_id=<?php echo $dptoId; ?>&cols=4&rows=10&layout=derecha">4x10 c/título</a><br>
        <a href="?dpto_id=<?php echo $dptoId; ?>&cols=4&rows=11&layout=derecha&sin_titulo=1">4x11 s/título</a><br>
        <a href="?dpto_id=<?php echo $dptoId; ?>&cols=4&rows=12&layout=derecha&sin_titulo=1">4x12 s/título</a>
    </div>

    <!-- Encabezado con logo -->
    <div class="header">
        <div class="row align-items-center">
            <div class="col-6">
                <img src="../catalogo/images/logo.png" class="logo" alt="KET">
            </div>
            <div class="col-6 pagination-info">
                Pág. <?php echo $pageNum; ?> / <?php echo $totalPaginas; ?>
            </div>
        </div>
    </div>

    <!-- Título del departamento (opcional) -->
    <?php if (!$sinTitulo): ?>
    <div class="text-center">
        <h1 class="rounded-title"><?php echo $currCatName; ?></h1>
    </div>
    <?php endif; ?>

    <!-- Grid de productos -->
    <div class="products-grid">
        <div class="row row-cols-1 row-cols-sm-<?php echo $cols; ?> g-3 justify-content-center">
            <?php foreach ($consult1 as $producto): 
                $imgUrl = $currCatImgRoute . $producto->photo_url;
            ?>
            <div class="col">
                <div class="card layout-<?php echo $layout; ?>">
                    <div class="card-header"><?php echo $producto->code; ?></div>
                    
                    <div class="row g-0">
                        <div class="col-5 text-center">
                            <img src="<?php echo $imgUrl; ?>" alt="<?php echo $producto->code; ?>">
                        </div>
                        <div class="col-7">
                            <?php echo $producto->name; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>