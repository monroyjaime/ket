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
// Modificar la función generarGridProductos con el nuevo diseño

// Función generarGridProductos con foto GRANDE y protagonista
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
    
    $html = '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 mt-2" id="productos-grid">';
    
    foreach ($productos as $p) {
        $precio = floatval($p->precio);
        $imgUrl = $currCatImgRoute . $p->photo_url;
        
        $html .= '<div class="col">';
        $html .= '<div class="card h-100 shadow-sm" style="border-radius: 8px; overflow: hidden; transition: transform 0.2s;">';
        
        // Header con código (más compacto)
        $html .= '<div class="card-header text-center" style="background-color: #003272; padding: 5px;">';
        $html .= '<small style="color: white; font-weight: bold;">' . htmlspecialchars($p->code) . '</small>';
        $html .= '</div>';
        
        // Imagen - padding mínimo para maximizar espacio
        $html .= '<div style="background-color: white; padding: 10px; text-align: center; display: flex; align-items: center; justify-content: center;">';
        $html .= '<img src="' . $imgUrl . '" alt="' . htmlspecialchars($p->code) . '" style="width: 100%; max-width: 100%; height: auto; object-fit: contain;">';
        $html .= '</div>';
        
        // Footer horizontal (descripción | precio)
        $html .= '<div class="card-footer" style="background-color: #e8f4f4; border-top: 2px solid #037C79; padding: 8px 10px;">';
        
        if ($role > -1) {
            $html .= '<div class="d-flex justify-content-between align-items-center gap-2">';
            // Descripción (izquierda)
            $html .= '<div class="flex-grow-1">';
            $html .= '<p class="mb-0" style="font-size: 0.7rem; color: #333; line-height: 1.2;">' . htmlspecialchars($p->name) . '</p>';
            $html .= '<small class="text-muted" style="font-size: 0.6rem;">' . htmlspecialchars($p->unit) . '</small>';
            $html .= '</div>';
            // Precio (derecha)
            $html .= '<div class="text-end">';
            $html .= '<small style="color: #666; font-size: 0.6rem;">' . $labelTipoPrecio . '</small>';
            $html .= '<h6 class="mb-0" style="color: #003272; font-weight: bold; font-size: 0.85rem;">$' . number_format($precio, 3, ",", ".") . '</h6>';
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="text-center">';
            $html .= '<p class="mb-0" style="font-size: 0.7rem;">' . htmlspecialchars($p->name) . '</p>';
            $html .= '<small class="text-muted">Unidad: ' . htmlspecialchars($p->unit) . '</small>';
            $html .= '</div>';
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
            border-radius: 8px !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        }

        .card-header {
            padding: 5px !important;
        }

        .card-header small {
            font-size: 0.7rem;
        }

        /* Contenedor de imagen - padding reducido */
        .card > div[style*="background-color: white"] {
            background-color: white;
            padding: 8px !important;
            min-height: 120px;
        }

        .card img {
            width: 100% !important;
            height: auto !important;
            max-height: 160px;
            object-fit: contain;
        }

        .card-footer {
            padding: 8px 10px !important;
        }

        .card-footer p {
            font-size: 0.7rem;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .card-footer small {
            font-size: 0.6rem;
        }

        .card-footer h6 {
            font-size: 0.85rem;
            margin-bottom: 0;
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
            .card > div[style*="background-color: white"] {
                padding: 5px !important;
                min-height: 100px;
            }
            
            .card img {
                max-height: 120px;
            }
            
            .card-footer p {
                font-size: 0.65rem;
            }
            
            .card-footer small {
                font-size: 0.55rem;
            }
            
            .card-footer h6 {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .card > div[style*="background-color: white"] {
                padding: 4px !important;
                min-height: 80px;
            }
            
            .card img {
                max-height: 100px;
            }
            
            /* En móvil muy pequeño, el footer horizontal se mantiene pero más compacto */
            .card-footer .d-flex {
                gap: 5px !important;
            }
            
            .card-footer p {
                font-size: 0.6rem;
            }
            
            .card-footer h6 {
                font-size: 0.7rem;
            }
        }

        /* Para móviles muy pequeños (menos de 400px) */
        @media (max-width: 400px) {
            .card-footer .d-flex {
                flex-direction: column;
                text-align: center;
            }
            
            .card-footer .text-end {
                text-align: center !important;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- BARRA SUPERIOR - Versión corregida -->
    <div class="w-100" style="background-color: #CCC; padding: 12px 0;">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col text-start" style="padding-left: 20px;">
                    <?php echo $backCond; ?>
                </div>
                
                <?php if ($role == 1 || $role == 2): ?>
                <div class="col text-center">
                    <button id="btnCambiarPrecio" class="btn" 
                            style="background-color: #037C79; color: white; border-radius: 25px; padding: 8px 25px; font-weight: bold; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
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