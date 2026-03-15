<?php
require_once("../php/dbcat.php");

// Parámetros de prueba
$dptoId = isset($_GET['dpto_id']) ? intval($_GET['dpto_id']) : 101;
$pageNum = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$cols = isset($_GET['cols']) ? intval($_GET['cols']) : 4;
$rows = isset($_GET['rows']) ? intval($_GET['rows']) : 6;
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
            padding: 20px; 
            font-family: 'Segoe UI', Arial, sans-serif; 
        }
        
        .rounded-title {
            background-color: #003272;
            color: #FFF;
            border-radius: 30px;
            padding: 1rem 2rem;
            margin: 0 auto 2rem auto;
            display: inline-block;
            font-size: 2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90%;
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
            padding: 8px;
            font-size: 0.9rem;
            border-bottom: none;
        }
        
        .layout-derecha .row {
            margin: 0;
            min-height: 120px;  /* Aumentado para foto más grande */
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
            font-size: 0.8rem;
            line-height: 1.3;
            word-wrap: break-word;
            overflow: hidden;
        }
        
        .layout-derecha img {
            max-height: 110px;  /* Aumentado de 90px a 110px */
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }
        
        /* Garantizar que el texto nunca se desborde */
        .layout-derecha .col-7 {
            max-height: 110px;
            overflow-y: auto;  /* Scroll solo si es necesario */
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
        
        .debug-info a {
            color: #0CC;
            text-decoration: none;
            margin: 0 3px;
        }
        
        /* Para impresión */
        @media print {
            body { padding: 0; }
            .debug-info { display: none; }
            .card { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="debug-info">
        <strong>Layout Optimizado</strong><br>
        Columnas: <?php echo $cols; ?><br>
        Filas: <?php echo $rows; ?><br>
        Productos/página: <?php echo $cols * $rows; ?><br>
        <a href="?dpto_id=<?php echo $dptoId; ?>&cols=4&rows=6&layout=derecha">4x6</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&cols=5&rows=5&layout=derecha">5x5</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&cols=4&rows=5&layout=derecha">4x5</a> |
        <a href="?dpto_id=<?php echo $dptoId; ?>&cols=3&rows=7&layout=derecha">3x7</a>
    </div>

    <?php if (!$sinTitulo): ?>
    <div class="text-center">
        <h1 class="rounded-title"><?php echo $currCatName; ?></h1>
    </div>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row row-cols-1 row-cols-sm-<?php echo $cols; ?> g-3 justify-content-center">
            <?php foreach ($consult1 as $producto): 
                $imgUrl = $currCatImgRoute . $producto->photo_url;
            ?>
            <div class="col">
                <div class="card layout-<?php echo $layout; ?>">
                    <div class="card-header"><?php echo $producto->code; ?></div>
                    
                    <?php if ($layout == 'derecha'): ?>
                    <div class="row g-0">
                        <div class="col-5 text-center">
                            <img src="<?php echo $imgUrl; ?>" alt="<?php echo $producto->code; ?>">
                        </div>
                        <div class="col-7">
                            <?php echo $producto->name; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <img src="<?php echo $imgUrl; ?>" class="card-img-top" alt="<?php echo $producto->code; ?>" style="height: 110px; object-fit: contain; padding: 5px;">
                    <div class="card-body text-center" style="background-color: #f8f9fa;">
                        <?php echo $producto->name; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>