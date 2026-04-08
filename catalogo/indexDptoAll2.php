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

// Función para generar SOLO el grid de productos
function generarGridProductos($db, $dptoId, $tipoPrecio, $role) {
    $strTipoPrecio = ($tipoPrecio == 0) ? "cost_max" : "cost_mayor";
    $labelTipoPrecio = ($tipoPrecio == 0) ? "Precio" : "Precio Mayorista";
    
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

// Si es petición AJAX, solo devolvemos el grid
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['prec'])) {
    $tipoPrecioAjax = intval($_GET['prec']);
    echo generarGridProductos($db, $dptoId, $tipoPrecioAjax, $role);
    exit;
}

// Generar contenido inicial
$gridProductosHtml = generarGridProductos($db, $dptoId, $tipoPrecio, $role);

// Flecha de retroceso
$backCond = '<a href="#" onClick="backHome(' . $role . ',' . $line . ',' . $tipoPrecio . ',' . $comeFrom . ')" title="Volver"><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>';
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
        
        #btnCambiarPrecio {
            transition: all 0.3s ease;
            border: none;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        #btnCambiarPrecio:hover {
            transform: scale(1.05);
            background-color: #025a58 !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        /* Título en franja verde agua */
        .title-banner {
            background-color: #037c79;
            padding: 12px 0;
            text-align: center;
            margin-bottom: 20px;
        }
        .title-banner h1 {
            color: white;
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .title-banner h1 i {
            font-size: 2rem;
        }
        
        /* Media queries para botón responsive */
        @media (max-width: 768px) {
            #btnCambiarPrecio {
                font-size: 0.8rem;
                padding: 4px 12px !important;
                white-space: nowrap;
            }
            .title-banner h1 {
                font-size: 1.3rem;
            }
            .title-banner {
                padding: 8px 0;
            }
        }
        
        @media (max-width: 480px) {
            #btnCambiarPrecio {
                font-size: 0.7rem;
                padding: 3px 8px !important;
            }
            .title-banner h1 {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- BARRA SUPERIOR CON MÁS PADDING SUPERIOR -->
    <div class="w-100 p-0" style="background-color: #CCC; padding-top: 12px; padding-bottom: 4px;">
        <div class="row align-items-start" style="min-height: 50px;">
            <div class="col text-start" style="padding-left: 20px;">
                <?php echo $backCond; ?>
            </div>
            
            <?php if ($role == 1 || $role == 2): ?>
            <div class="col text-center">
                <button id="btnCambiarPrecio" class="btn btn-sm" 
                        style="background-color: #037C79; color: white; border-radius: 25px; padding: 6px 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"
                        data-current="<?php echo $tipoPrecio; ?>">
                    <i class="bi bi-arrow-repeat"></i> 
                    <?php echo ($tipoPrecio == 0) ? 'Ver Precio Mayorista' : 'Ver Precio Minorista'; ?>
                </button>
            </div>
            <?php else: ?>
            <div class="col text-center"></div>
            <?php endif; ?>
            
            <div class="col text-end" style="padding-right: 15px;">
                <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" style="max-height: 40px;" />
            </div>
        </div>
    </div>
    
    <!-- TÍTULO CENTRADO EN FRANJA VERDE AGUA -->
    <div class="title-banner">
        <h1>
            <i class="bi bi-grid-3x3-gap-fill"></i>
            Catálogo de <?php echo htmlspecialchars($currCatName); ?>
            <i class="bi bi-file-pdf-fill" style="color: #ff9999; cursor: pointer;" 
               onclick="window.open('<?php echo ($line == 1) ? "/pdfs/catalogo_automotriz/catalogo_dptos_{$dptoId}.pdf" : "/pdfs/catalogo_ferretero/catalogo_dptos_{$dptoId}.pdf"; ?>', '_blank')"></i>
        </h1>
    </div>
    
    <!-- CONTENIDO PRINCIPAL (grid de productos) -->
    <div class="w-100 p-3" style="background-color: #DDD;">
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
    let currentPrecio = <?php echo $tipoPrecio; ?>;
    const dptoId = <?php echo $dptoId; ?>;
    const role = <?php echo $role; ?>;
    const line = <?php echo $line; ?>;
    const comeFrom = <?php echo $comeFrom; ?>;

    // Función para ajustar el texto del botón según el ancho de pantalla
    function ajustarTextoBoton() {
        const btn = document.getElementById('btnCambiarPrecio');
        if (!btn) return;
        
        const esMovil = window.innerWidth <= 768;
        const esMovilPeq = window.innerWidth <= 480;
        const currentPrecioVal = parseInt(btn.getAttribute('data-current'));
        
        if (esMovilPeq) {
            // Texto ultra corto para móviles pequeños
            btn.innerHTML = currentPrecioVal == 0 ? 
                '<i class="bi bi-arrow-repeat"></i> Mayorista' : 
                '<i class="bi bi-arrow-repeat"></i> Minorista';
        } else if (esMovil) {
            // Texto corto para tablets/móviles
            btn.innerHTML = currentPrecioVal == 0 ? 
                '<i class="bi bi-arrow-repeat"></i> Ver Mayorista' : 
                '<i class="bi bi-arrow-repeat"></i> Ver Minorista';
        } else {
            // Texto completo en desktop
            btn.innerHTML = currentPrecioVal == 0 ? 
                '<i class="bi bi-arrow-repeat"></i> Ver Precio Mayorista' : 
                '<i class="bi bi-arrow-repeat"></i> Ver Precio Minorista';
        }
    }

    $('#btnCambiarPrecio').on('click', function() {
        const newPrecio = (currentPrecio == 0) ? 1 : 0;
        const btn = $(this);
        
        btn.html('<i class="bi bi-hourglass-split"></i> Cargando...');
        btn.prop('disabled', true);
        
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
                $('#productos-container').html(response);
                currentPrecio = newPrecio;
                $('#btnCambiarPrecio').attr('data-current', currentPrecio);
                ajustarTextoBoton();
                btn.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error);
                alert('Error al cambiar el precio. Recargue la página.');
                location.reload();
            }
        });
    });
    
    // Ejecutar ajuste de texto al cargar y al redimensionar
    $(document).ready(function() {
        ajustarTextoBoton();
        $(window).resize(function() {
            ajustarTextoBoton();
        });
    });
    <?php endif; ?>
    </script>
</body>
</html>