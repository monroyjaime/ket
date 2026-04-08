<?php
session_start();
require_once("../php/dbcat.php");

$db = new DB();
$line = 1;
$role = (isset($_SESSION['role'])) ? intval($_SESSION['role']) : -1;
$comeFrom = 0;

// Determinar tipo de precio inicial según el rol
if ($role == 3) {
    $tipoPrecio = 0; // Minorista
} elseif ($role == 4) {
    $tipoPrecio = 1; // Mayorista
} else {
    // Roles 1, 2 o -1: empezamos con minorista (0)
    $tipoPrecio = 0;
}

// Obtener parámetros GET
if (isset($_GET['line'])) $line = intval($_GET['line']);
if (isset($_GET['from'])) $comeFrom = intval($_GET['from']);
$dptoId = (isset($_GET['dpto_id'])) ? intval($_GET['dpto_id']) : 1;

// Obtener nombre del departamento
$consult = $db->consultas("SELECT name FROM departamentos WHERE id=" . $dptoId);
$currCatName = '';
foreach ($consult as $value) { $currCatName = $value->name; }

// --- FUNCIÓN para generar SOLO el grid de productos (sin encabezados) ---
function generarGridProductos($db, $dptoId, $tipoPrecio, $role) {
    $strTipoPrecio = ($tipoPrecio == 0) ? "cost_max" : "cost_mayor";
    $labelTipoPrecio = ($tipoPrecio == 0) ? "Precio" : "Precio Mayorista";
    
    // Ruta de imágenes
    $consult = $db->consultas("SELECT img_route FROM departamentos WHERE id=" . $dptoId);
    $currCatImgRoute = '';
    foreach ($consult as $value) { $currCatImgRoute = $value->img_route; }
    
    $query = "SELECT code, name, photo_url, " . $strTipoPrecio . " AS precio, unit 
              FROM productos 
              WHERE show='t' AND dpto_id = " . $dptoId . " 
              AND photo_url != 'empty.jpg' AND " . $strTipoPrecio . " > 0 
              ORDER BY orden, code";
    
    $productos = $db->consultas($query);
    
    $html = '<div class="row row-cols-1 row-cols-sm-4 g-4 mt-2" id="productos-grid">';
    
    foreach ($productos as $p) {
        $precio = floatval($p->precio);
        $imgUrl = $currCatImgRoute . $p->photo_url;
        
        $html .= '<div class="col" style="background-color: #DDD;">';
        $html .= '<div class="card h-100 text-bg-light">';
        $html .= '<div class="card-header" style="background-color: #037C79;">';
        $html .= '<h3 style="color: #FFF;">' . htmlspecialchars($p->code) . '</h3>';
        $html .= '</div>';
        $html .= '<img src="' . $imgUrl . '" class="card-img-top" alt="' . htmlspecialchars($p->code) . '">';
        $html .= '<div class="card-body" style="background-color: #0CC;">';
        $html .= '<h6 class="card-text">' . htmlspecialchars($p->name) . '</h6>';
        
        if ($role > -1) {
            $html .= '<h5 class="card-text precio-label">' . $labelTipoPrecio . ': $' . number_format($precio, 3, ",", ".") . '</h5>';
            $html .= '<h6 class="card-text">Unidad: ' . htmlspecialchars($p->unit) . '</h6>';
        }
        $html .= '</div></div></div>';
    }
    $html .= '</div>';
    return $html;
}

// --- SI ES PETICIÓN AJAX, SOLO DEVOLVEMOS EL GRID ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['prec'])) {
    $tipoPrecioAjax = intval($_GET['prec']);
    echo generarGridProductos($db, $dptoId, $tipoPrecioAjax, $role);
    exit;
}

// --- GENERAR CONTENIDO INICIAL COMPLETO ---
$gridProductosHtml = generarGridProductos($db, $dptoId, $tipoPrecio, $role);

// Botón de cambio (solo para roles 1 y 2)
$botonCambioPrecio = '';
if ($role == 1 || $role == 2) {
    $botonTexto = ($tipoPrecio == 0) ? 'Cambiar a Mayorista' : 'Cambiar a Minorista';
    $botonCambioPrecio = '<button id="btnCambiarPrecio" class="btn btn-sm btn-outline-light ms-3" data-current="' . $tipoPrecio . '">
                            <i class="bi bi-arrow-repeat"></i> ' . $botonTexto . '
                          </button>';
}

// Flecha de retroceso
$backCond = '<a href="#" onClick="backHome(' . $role . ',' . $line . ',' . $tipoPrecio . ',' . $comeFrom . ')" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <title>Catálogo KET - <?php echo htmlspecialchars($currCatName); ?></title>
    <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .icon-large { font-size: 25px; }
        .icon-dark-blue { color: #003272; }
        .d-flex { display: flex; }
        .justify-content-center { justify-content: center; }
        .align-items-center { align-items: center; }
        .gap-3 { gap: 1rem; }
        .mb-0 { margin-bottom: 0; }
        .mt-2 { margin-top: 0.5rem; }
        #btnCambiarPrecio { transition: all 0.2s; }
        #btnCambiarPrecio:hover { transform: scale(1.02); background-color: #037C79; border-color: white; }
    </style>
</head>
<body>
    <div class="w-100 p-0" style="background-color: #CCC;">
        <div class="row align-items-start" style="max-height: 50px;">
            <div class="col text-start" style="max-height: 40px; padding-left: 20px;">
                <?php echo $backCond; ?>
            </div>
            <div class="col text-end" style="max-height: 40px;">
                <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
            </div>
        </div>
    </div>
    
    <div class="w-100 p-3" style="background-color: #DDD;">
        <!-- Encabezado con título y botón -->
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <h2 class="mb-0">Catálogo de <?php echo htmlspecialchars($currCatName); ?></h2>
                <?php 
                $ruta_pdf = ($line == 1) ? "/pdfs/catalogo_automotriz/catalogo_dptos_{$dptoId}.pdf" : "/pdfs/catalogo_ferretero/catalogo_dptos_{$dptoId}.pdf";
                ?>
                <a href="<?php echo $ruta_pdf; ?>" target="_blank" title="Ver catálogo en PDF">
                    <i class="bi bi-file-pdf-fill" style="font-size: 1.8rem; color: #dc3545;"></i>
                </a>
            </div>
            <?php echo $botonCambioPrecio; ?>
        </div>
        
        <!-- Contenedor de productos (se reemplazará vía AJAX) -->
        <div id="productos-container">
            <?php echo $gridProductosHtml; ?>
        </div>
    </div>

    <script>
    function backHome(rol, line, prec, from) {
        let urlString = "";
        if (from == 0) {
            if (line == 1) urlString = "../listas/indexL1.php?prec=" + prec;
            else urlString = "../listas/indexL2.php?prec=" + prec;
        } else {
            urlString = "../listas/index.php?prec=" + prec;
        }
        window.location.href = urlString;
    }

    <?php if ($role == 1 || $role == 2): ?>
    // Lógica AJAX para cambiar precio
    let currentPrecio = <?php echo $tipoPrecio; ?>;
    const dptoId = <?php echo $dptoId; ?>;
    const role = <?php echo $role; ?>;
    const line = <?php echo $line; ?>;
    const comeFrom = <?php echo $comeFrom; ?>;

    $('#btnCambiarPrecio').on('click', function() {
        const newPrecio = (currentPrecio == 0) ? 1 : 0;
        const btn = $(this);
        const originalHtml = btn.html();
        
        // Mostrar loading
        btn.html('<i class="bi bi-hourglass-split"></i> Cargando...');
        btn.prop('disabled', true);
        
        // Llamada AJAX
        $.ajax({
            url: window.location.pathname,
            method: 'GET',
            data: {
                dpto_id: dptoId,
                line: line,
                from: comeFrom,
                ajax: 1,
                prec: newPrecio
            },
            success: function(response) {
                // Reemplazar SOLO el contenido del contenedor
                $('#productos-container').html(response);
                currentPrecio = newPrecio;
                
                // Actualizar texto del botón
                if (currentPrecio == 0) {
                    btn.html('<i class="bi bi-arrow-repeat"></i> Cambiar a Mayorista');
                } else {
                    btn.html('<i class="bi bi-arrow-repeat"></i> Cambiar a Minorista');
                }
                btn.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error);
                alert('Error al cambiar el precio. Recargue la página.');
                location.reload();
            }
        });
    });
    <?php endif; ?>
    </script>
</body>
</html>