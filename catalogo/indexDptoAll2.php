<?php
session_start();
require_once("../php/dbcat.php");

$db = new DB();
$line = 1;
$role = (isset($_SESSION['role'])) ? intval($_SESSION['role']) : -1;
$comeFrom = 0;

// --- 1. LÓGICA DE PRECIO INICIAL (basada en el rol) ---
// Rol 3 (minorista) -> precio 0, Rol 4 (mayorista) -> precio 1
if ($role == 3) {
    $tipoPrecio = 0; // Minorista
} elseif ($role == 4) {
    $tipoPrecio = 1; // Mayorista
} else {
    // Roles 1, 2 o indefinido (-1): por defecto mostrar minorista (0)
    // El usuario con rol 1/2 podrá cambiar con el botón.
    $tipoPrecio = 0;
}

// Obtener parámetros GET (dpto_id, line, from)
if (isset($_GET['line'])) $line = intval($_GET['line']);
if (isset($_GET['from'])) $comeFrom = intval($_GET['from']);

$dptoId = (isset($_GET['dpto_id'])) ? intval($_GET['dpto_id']) : 1;

// --- 2. FUNCIÓN para generar el HTML de los productos (la reutilizaremos para AJAX) ---
function generarGridProductos($db, $dptoId, $tipoPrecio, $role) {
    $strTipoPrecio = ($tipoPrecio == 0) ? "cost_max" : "cost_mayor";
    $labelTipoPrecio = ($tipoPrecio == 0) ? "Precio" : "Precio Mayorista";
    
    // Obtener datos del departamento para la ruta de imágenes
    $consult = $db->consultas("SELECT name, img_route FROM departamentos WHERE id=" . $dptoId);
    $currCatImgRoute = '';
    foreach ($consult as $value) {
        $currCatImgRoute = $value->img_route;
    }
    
    $query = "SELECT id, code, name, photo_url, " . $strTipoPrecio . " AS precio, unit, current_stock 
              FROM productos 
              WHERE show='t' AND dpto_id = " . $dptoId . " 
              AND photo_url != 'empty.jpg' AND " . $strTipoPrecio . " > 0 
              ORDER BY orden, code";
    
    $productos = $db->consultas($query);
    $html = '<div class="row row-cols-1 row-cols-sm-4 g-4 mt-2">';
    
    foreach ($productos as $p) {
        $productVal_cost = floatval($p->precio);
        $currUrl = $currCatImgRoute . $p->photo_url;
        
        $html .= '<div class="col" style="background-color: #DDD;">';
        $html .= '<div class="card h-100 text-bg-light">';
        $html .= '<div class="card-header" style="background-color: #037C79;">';
        $html .= '<h3 style="color: #FFF;">' . htmlspecialchars($p->code) . '</h3>';
        $html .= '</div>';
        $html .= '<img src="' . $currUrl . '" class="card-img-top" alt="' . htmlspecialchars($p->code) . '">';
        $html .= '<div class="card-body" style="background-color: #0CC;">';
        $html .= '<h6 class="card-text">' . htmlspecialchars($p->name) . '</h6>';
        
        if ($role > -1) {
            $html .= '<h5 class="card-text precio-label" data-tipo="' . $tipoPrecio . '">' . $labelTipoPrecio . ': $' . number_format($productVal_cost, 3, ",", ".") . '</h5>';
            $html .= '<h6 class="card-text">Unidad: ' . htmlspecialchars($p->unit) . '</h6>';
        }
        $html .= '</div></div></div>';
    }
    $html .= '</div>';
    return $html;
}

// --- 3. GENERAR EL CONTENIDO INICIAL ---
$gridProductosHtml = generarGridProductos($db, $dptoId, $tipoPrecio, $role);

// Obtener nombre del departamento para el título
$consult = $db->consultas("SELECT name FROM departamentos WHERE id=" . $dptoId);
$currCatName = '';
foreach ($consult as $value) { $currCatName = $value->name; }

// Preparar botón de "Cambiar Precio" (solo para roles 1 y 2)
$botonCambioPrecio = '';
if ($role == 1 || $role == 2) {
    $botonCambioPrecio = '<button id="btnCambiarPrecio" class="btn btn-sm btn-outline-light ms-3" data-current="0">
                            <i class="bi bi-arrow-repeat"></i> Cambiar a Mayorista
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
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
        <!-- Encabezado con título y botón de cambio -->
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <h2 class="mb-0">Catálogo de <?php echo htmlspecialchars($currCatName); ?></h2>
                <!-- Icono PDF -->
                <?php 
                $ruta_pdf = ($line == 1) ? "/pdfs/catalogo_automotriz/catalogo_dptos_{$dptoId}.pdf" : "/pdfs/catalogo_ferretero/catalogo_dptos_{$dptoId}.pdf";
                ?>
                <a href="<?php echo $ruta_pdf; ?>" target="_blank" title="Ver catálogo en PDF">
                    <i class="bi bi-file-pdf-fill" style="font-size: 1.8rem; color: #dc3545;"></i>
                </a>
            </div>
            <?php echo $botonCambioPrecio; ?>
        </div>
        
        <!-- Contenedor de la cuadrícula de productos -->
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
    // Lógica para cambiar el precio vía AJAX (solo para roles 1 y 2)
    let currentPrecio = <?php echo $tipoPrecio; ?>; // 0 o 1
    const dptoId = <?php echo $dptoId; ?>;
    const role = <?php echo $role; ?>;

    document.getElementById('btnCambiarPrecio').addEventListener('click', function() {
        // Cambiar el estado (0 -> 1, 1 -> 0)
        const newPrecio = (currentPrecio == 0) ? 1 : 0;
        
        // Mostrar indicador de carga (opcional)
        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Cargando...';
        this.disabled = true;
        
        // Llamada AJAX al mismo script pero con parámetro ajax=1 y el nuevo precio
        fetch(window.location.pathname + '?dpto_id=' + dptoId + '&line=<?php echo $line; ?>&from=<?php echo $comeFrom; ?>&ajax=1&prec=' + newPrecio)
            .then(response => response.text())
            .then(html => {
                // Actualizar el contenedor con el nuevo HTML de productos
                document.getElementById('productos-container').innerHTML = html;
                // Actualizar el botón
                currentPrecio = newPrecio;
                const btn = document.getElementById('btnCambiarPrecio');
                if (currentPrecio == 0) {
                    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Cambiar a Mayorista';
                    btn.setAttribute('data-current', '0');
                } else {
                    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Cambiar a Minorista';
                    btn.setAttribute('data-current', '1');
                }
                btn.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cambiar el precio. Recargue la página.');
                location.reload();
            });
    });
    <?php endif; ?>
    </script>
</body>
</html>

<?php
// --- 4. MANEJAR LA PETICIÓN AJAX (al final del archivo) ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['prec'])) {
    // Esta parte solo se ejecuta cuando se llama vía AJAX
    // Devolvemos SOLO el HTML de la cuadrícula, no la página completa.
    $tipoPrecioAjax = intval($_GET['prec']);
    echo generarGridProductos($db, $dptoId, $tipoPrecioAjax, $role);
    exit; // Importante: terminar la ejecución para no enviar el HTML completo.
}
?>