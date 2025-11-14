<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cambiar DB por DBAsync para consultas seguras
//require_once("https://ketelectropartes.com/php/dbcat_async.php");
require_once("../../php/dbcat_async.php");

$role = -1;
$presupuestoId = 0;
$pageNum = 1;
$productVals = [];
$numProducts = 0;
$tags = '';

try {
    // Validar y sanitizar parámetros GET
    $role = isset($_GET['role_num']) ? intval($_GET['role_num']) : -1;
    $presupuestoId = isset($_GET['pres_num']) ? intval($_GET['pres_num']) : 0;
    $pageNum = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
    
    // Validar parámetros requeridos
    if ($presupuestoId <= 0) {
        throw new Exception('ID de presupuesto inválido');
    }
    
    if ($pageNum <= 0) {
        throw new Exception('Número de página inválido');
    }

    // Usar DBAsync en lugar de DB
    $db = new DBAsync();
    
    // Consulta segura usando parámetros preparados
    $query = "SELECT a.product_code, CONCAT(b.img_route, c.photo_url) AS img_full_route 
              FROM presupuesto_detail a 
              INNER JOIN departamentos b ON b.id = c.dpto_id
              INNER JOIN productos c ON a.product_code = c.code
              WHERE b.img_route != 'no' 
                AND a.pres_idx = $1";
    
    // Ejecutar consulta preparada con DBAsync
    $consult = $db->consultaSegura($query, [$presupuestoId]);
    
    if (empty($consult)) {
        throw new Exception('No se encontraron productos para el presupuesto especificado');
    }

    // Procesar resultados
    foreach ($consult as $value) {
        $productVal = new stdClass();
        $productVal->code = htmlspecialchars($value->code ?? '', ENT_QUOTES, 'UTF-8');
        $productVal->url = htmlspecialchars($value->img_full_route ?? '', ENT_QUOTES, 'UTF-8');
        
        $productVals[] = $productVal;
        $numProducts++;
    }

    // Calcular paginación
    $numPages = ceil($numProducts / 25);
    
    // Validar que la página solicitada existe
    if ($pageNum > $numPages && $numPages > 0) {
        $pageNum = $numPages; // Ir a la última página disponible
    }
    
    $lastPageProdNum = ($pageNum == $numPages) ? 
        25 - (($numPages * 25 - $numProducts)) : 
        24;
    
    // Asegurar que no exceda el número de productos
    $lastPageProdNum = min($lastPageProdNum, 24);

    // Construir HTML
    $tags .= '<div class="col text-center">';
    $tags .= '<h2>Catálogo de Imágenes (Pag. ' . htmlspecialchars($pageNum) . ' / ' . htmlspecialchars($numPages) . ')</h2>';
    $tags .= '</div>';
    $tags .= '<div class="row row-cols-1 row-cols-sm-5 g-5">';

    // Calcular rangos para la paginación
    $currRangeFrom = ($pageNum - 1) * 25;
    $currRangeTo = min(($pageNum - 1) * 25 + $lastPageProdNum, $numProducts - 1);

    // Generar tarjetas de productos
    for ($i = $currRangeFrom; $i <= $currRangeTo; $i++) {
        if (!isset($productVals[$i])) {
            continue; // Saltar si no existe el índice
        }
        
        $productVal_code = $productVals[$i]->code;
        $productVal_url = $productVals[$i]->url;

        // Validar que la URL de la imagen no esté vacía
        $imageUrl = !empty($productVal_url) ? $productVal_url : '../catalogo/images/empty.jpg';
        
        $tags .= '<div class="col" style="background-color: #DDD;">';
        $tags .= '<div class="card h-100 text-bg-light">';
        $tags .= '<div class="card-header" style="background-color: #037C79;">';
        $tags .= '<h3 style="color: #FFF;">' . $productVal_code . '</h3>';
        $tags .= '</div>';
        $tags .= '<img src="' . $imageUrl . '" class="card-img-top" alt="' . $productVal_code . '" loading="lazy" style="height: 200px; object-fit: contain;">';
        $tags .= '<div class="card-body" style="background-color: #0CC;">';
        $tags .= '<h6 class="card-text">' . $productVal_code . '</h6>';
        $tags .= '</div>';
        $tags .= '</div>';
        $tags .= '</div>';
    }
    
    $tags .= '</div>';

} catch (Exception $e) {
    // Manejo de errores
    error_log("Error en presupuestoImages.php: " . $e->getMessage());
    
    $tags = '<div class="alert alert-danger text-center">';
    $tags .= '<h4>Error al cargar las imágenes</h4>';
    $tags .= '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    $tags .= '<p>Presupuesto ID: ' . htmlspecialchars($presupuestoId) . '</p>';
    $tags .= '</div>';
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <title>Catálogo KET - Presupuesto</title>
    <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">  
    <link rel="stylesheet" href="css/non-responsive.css">  

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

    <style>
        .icon-large {
            font-size: 25px;
        }
        .icon-dark-blue{
            color: #003272;
        }
        .card-img-top {
            height: 200px;
            object-fit: contain;
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>

<div class="w-100 p-0" style="background-color: #CCC;">
    <div class="row align-items-start" style="max-height: 50px;">
        <div class="col text-start" style="max-height: 40px; padding-left: 20px;">
            <a href="#" onClick="backHome()" title="Pag. Prev.">
                <i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i>
            </a>
        </div>    
        
        <div class="col text-center" style="max-height: 40px; padding-bottom: 14px; padding-top: 1px;">
            <!-- Espacio para botones si se necesitan -->
        </div>
        
        <div class="col text-end" style="max-height: 40px;">
            <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
        </div>       
    </div>
</div>

<div class="w-100 p-3" style="background-color: #DDD;"> 
    <div id="productos">
        <?php echo $tags; ?>
    </div>    
</div>

<script>
    function backHome() {      
        window.location.href = "../index.php";
    }
    
    // Navegación por teclado
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            // Navegar a página anterior si existe
            const currentPage = <?php echo $pageNum; ?>;
            if (currentPage > 1) {
                window.location.href = `?pres_num=<?php echo $presupuestoId; ?>&page_num=${currentPage - 1}`;
            }
        } else if (e.key === 'ArrowRight') {
            // Navegar a página siguiente si existe
            const currentPage = <?php echo $pageNum; ?>;
            const totalPages = <?php echo $numPages; ?>;
            if (currentPage < totalPages) {
                window.location.href = `?pres_num=<?php echo $presupuestoId; ?>&page_num=${currentPage + 1}`;
            }
        }
    });
</script> 

</body>
</html>