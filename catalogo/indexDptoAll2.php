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


$linea_nombre = ($line == 1) ? 'automotriz' : 'ferretero';

if ($role == 1 || $role == 2) {
    // Usuarios con cambio de precio: el PDF depende del $tipoPrecio actual
    if ($tipoPrecio == 0) {
        // Minorista
        $ruta_pdf = "/pdfs/catalogo_{$linea_nombre}/conPrecio/catalogo_dptos_{$dptoId}.pdf";
        $label_pdf = "PDF con Precio Minorista";
    } else {
        // Mayorista
        $ruta_pdf = "/pdfs/catalogo_{$linea_nombre}/conPrecioMayor/catalogo_dptos_{$dptoId}.pdf";
        $label_pdf = "PDF con Precio Mayorista";
    }
} elseif ($role == 3) {
    // Usuario minorista (rol 3)
    $ruta_pdf = "/pdfs/catalogo_{$linea_nombre}/conPrecio/catalogo_dptos_{$dptoId}.pdf";
    $label_pdf = "PDF con Precio Minorista";
} elseif ($role == 4) {
    // Usuario mayorista (rol 4)
    $ruta_pdf = "/pdfs/catalogo_{$linea_nombre}/conPrecioMayor/catalogo_dptos_{$dptoId}.pdf";
    $label_pdf = "PDF con Precio Mayorista";
} else {
    // Visitante o sin sesión: PDF sin precio
    $ruta_pdf = "/pdfs/catalogo_{$linea_nombre}/catalogo_dptos_{$dptoId}.pdf";
    $label_pdf = "Ver catálogo PDF";
}



// Obtener nombre del departamento
$consult = $db->consultas("SELECT name FROM departamentos WHERE id=" . $dptoId);
$currCatName = '';
foreach ($consult as $value) { $currCatName = $value->name; }

// Función para generar SOLO el grid de productos
// Modificar la función generarGridProductos con el nuevo diseño

// Función generarGridProductos con contenedor 4:3
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
        
        // Header con código
        $html .= '<div class="card-header text-center" style="background-color: #003272; padding: 8px;">';
        $html .= '<small style="color: white; font-weight: bold;">' . htmlspecialchars($p->code) . '</small>';
        $html .= '</div>';
        
        // Contenedor imagen con relación de aspecto 4:3 (500/375 = 1.33)
        $html .= '<div style="position: relative; width: 100%; padding-top: 75%; background-color: #f8f9fa; overflow: hidden;">';
        $html .= '<img src="' . $imgUrl . '" alt="' . htmlspecialchars($p->code) . '" ';
        $html .= 'style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; padding: 10px;">';
        $html .= '</div>';
        
        // Footer horizontal compacto
        $html .= '<div class="card-footer" style="background-color: #e8f4f4; border-top: 2px solid #037C79; padding: 8px;">';
        
        if ($role > -1) {
            $html .= '<div class="d-flex justify-content-between align-items-center gap-2">';
            // Descripción
            $html .= '<div class="flex-grow-1" style="min-width: 0;">';
            $html .= '<p class="mb-0" style="font-size: 0.75rem; color: #333; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">' . htmlspecialchars($p->name) . '</p>';
            $html .= '<small class="text-muted" style="font-size: 0.6rem;">' . htmlspecialchars($p->unit) . '</small>';
            $html .= '</div>';
            // Precio
            $html .= '<div class="text-end" style="flex-shrink: 0;">';
            $html .= '<small style="color: #666; font-size: 0.6rem;">' . $labelTipoPrecio . '</small>';
            $html .= '<h6 class="mb-0" style="color: #003272; font-weight: bold; font-size: 0.9rem;">$' . number_format($precio, 3, ",", ".") . '</h6>';
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="text-center">';
            $html .= '<p class="mb-0" style="font-size: 0.7rem; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">' . htmlspecialchars($p->name) . '</p>';
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
//$backCond = '<a href="#" onClick="backHome(' . $role . ',' . $line . ',' . $tipoPrecio . ',' . $comeFrom . ')" title="Volver"><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>';
$backCond = '<a href="indiceDptos.php"  title="Volver"><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <meta name="darkreader-lock" content="yes">
    <meta http-equiv="Color-Scheme" content="light">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <title>Catálogo KET - <?php echo htmlspecialchars($currCatName); ?></title>
    <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        /* RESET COMPLETO - Forzar tema claro independientemente del sistema */
        * {
            color-scheme: light !important;
            forced-color-adjust: none !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        body {
            background-color: #DDD !important;
            color: #333 !important;
            margin: 0;
            padding: 0;
        }
        
        /* Estilos generales */
        .icon-large {
            font-size: 25px;
        }
        
        .icon-dark-blue {
            color: #003272;
        }
        
        /* Barra superior */
        .top-bar, 
        div[style*="background-color: #CCC"] {
            background-color: #CCC !important;
        }
        
        /* Título en franja verde agua */
        .title-banner {
            background-color: #037c79 !important;
            padding: 12px 0;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .title-banner h1 {
            color: white !important;
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
        
        /* Botón de cambio de precio */
        #btnCambiarPrecio {
            background-color: #037C79 !important;
            color: white !important;
            border-radius: 25px;
            padding: 8px 25px;
            font-weight: bold;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        #btnCambiarPrecio:hover {
            transform: scale(1.05);
            background-color: #025a58 !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Cards de productos */
        .card {
            background-color: white !important;
            border: 1px solid #ddd !important;
            border-radius: 8px !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        
        /* Header de la card */
        .card-header {
            background-color: #003272 !important;
            padding: 8px !important;
            border-bottom: none !important;
        }
        
        .card-header small {
            color: white !important;
            font-weight: bold;
            font-size: 0.95rem;
        }
        
        /* Contenedor de imagen con relación 4:3 */
        .card-img-container {
            position: relative;
            width: 100%;
            padding-top: 75%;
            background-color: #f8f9fa !important;
            overflow: hidden;
        }
        
        .card-img-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            transition: transform 0.3s ease;
        }
        
        .card:hover .card-img-container img {
            transform: scale(1.05);
        }
        
        /* Footer de la card */
        .card-footer {
            background-color: #e8f4f4 !important;
            border-top: 2px solid #037C79 !important;
            padding: 8px !important;
        }
        
        .card-footer p {
            font-size: 0.85rem;
            color: #333 !important;
            line-height: 1.2;
            margin-bottom: 2px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .card-footer small {
            font-size: 0.7rem;
            color: #666 !important;
        }
        
        .card-footer h6 {
            font-size: 0.95rem;
            font-weight: bold;
            color: #003272 !important;
            margin-bottom: 0;
        }
        
        /* Contenedor principal */
        div[style*="background-color: #DDD"] {
            background-color: #DDD !important;
        }
        
        /* Grid responsivo */
        .row-cols-1, .row-cols-sm-2, .row-cols-md-3, .row-cols-lg-4 {
            margin: 0 -0.5rem;
        }
        
        [class*="row-cols-"] > .col {
            padding: 0 0.5rem;
            margin-bottom: 1rem;
        }

        /* Estilos del buscador */
        #buscadorProductos:focus {
            box-shadow: none;
            border-color: #037C79;
        }

        #buscadorProductos::placeholder {
            font-size: 0.85rem;
            color: #999;
        }

        .input-group-text {
            color: #037C79;
        }
        
        /* Media Queries para responsive */
        @media (max-width: 1200px) {
            .row-cols-lg-4 > .col {
                flex: 0 0 auto;
                width: 25%;
            }
        }
        
        @media (max-width: 992px) {
            .row-cols-md-3 > .col {
                flex: 0 0 auto;
                width: 33.333%;
            }
            
            .title-banner h1 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            #btnCambiarPrecio {
                padding: 6px 12px;
                font-size: 0.75rem;
                white-space: nowrap; /* Evita que el texto se divida en dos líneas */
            }

              #btnCambiarPrecio i {
                margin-right: 4px;
                font-size: 0.7rem;
            }
            
            .title-banner {
                padding: 8px 0;
                margin-bottom: 15px;
            }
            
            .title-banner h1 {
                font-size: 1.3rem;
            }
            
            .title-banner h1 i {
                font-size: 1.5rem;
            }
            
            .row-cols-sm-2 > .col {
                flex: 0 0 auto;
                width: 50%;
            }
            
            .card-img-container img {
                padding: 8px;
            }
            
            .card-footer p {
                font-size: 0.75rem;
            }
            
            .card-footer h6 {
                font-size: 0.8rem;
            }
            
            .card-footer small {
                font-size: 0.65rem;
            }
        }
        
        @media (max-width: 576px) {
            #btnCambiarPrecio {
                padding: 5px 10px;
                font-size: 0.7rem;
                white-space: nowrap;
            }
            
            .title-banner h1 {
                font-size: 1rem;
            }
            
            .title-banner h1 i {
                font-size: 1.2rem;
            }
            
            .row-cols-1 > .col {
                flex: 0 0 auto;
                width: 100%;
            }
            
            .card-img-container img {
                padding: 5px;
            }
            
            .card-footer p {
                font-size: 0.7rem;
            }

             .card-footer small {
                font-size: 0.6rem;
            }
            
            .card-footer h6 {
                font-size: 0.8rem;
            }
            
            /* Footer vertical en móvil muy pequeño */
            .card-footer .d-flex {
                flex-direction: column;
                text-align: center;
                gap: 5px;
            }
            
            .card-footer .text-end {
                text-align: center !important;
            }
        }
        
        @media (max-width: 400px) {
            .card-img-container {
                padding-top: 70%;
            }
            
            .card-header small {
                font-size: 0.75rem;
            }
            
            .card-footer p {
                font-size: 0.6rem;
                -webkit-line-clamp: 1;
            }
        }

        @media (prefers-color-scheme: dark) {
            html, body, :root {
                color-scheme: light !important;
                background-color: #DDD !important;
            }
            
            /* Evitar que el navegador invierta o modifique colores */
            img, video, canvas, iframe, 
            .card, .card-header, .card-footer, .card-body,
            .title-banner, .top-bar, .container-fluid,
            div[style*="background-color"], span, p, h1, h2, h3, h4, h5, h6 {
                filter: none !important;
                background-color: initial;
                color: initial;
            }
            
            /* Forzar colores específicos nuevamente */
            body, div[style*="background-color: #DDD"] {
                background-color: #DDD !important;
            }
            
            .card {
                background-color: white !important;
                border: 1px solid #ddd !important;
            }
            
            .card-header {
                background-color: #003272 !important;
            }
            
            .card-header small {
                color: white !important;
            }
            
            .card-footer {
                background-color: #e8f4f4 !important;
                border-top-color: #037C79 !important;
            }
            
            .title-banner {
                background-color: #037c79 !important;
            }
            
            .title-banner h1 {
                color: white !important;
            }
            
            #btnCambiarPrecio {
                background-color: #037C79 !important;
                color: white !important;
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
            <i class="bi bi-file-pdf-fill" style="color: #dc3545; cursor: pointer; font-size: 1.8rem;"
                onclick="window.open('<?php echo $ruta_pdf; ?>', '_blank')"
                title="<?php echo $label_pdf; ?>"></i>
        </h1>
    </div>

   <!-- Buscador de productos -->
    <div class="container-fluid px-3 mb-3">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="input-group shadow-sm" style="border-radius: 30px; overflow: hidden;">
                    <span class="input-group-text bg-white border-end-0" style="border-radius: 30px 0 0 30px;">
                        <i class="bi bi-search" style="color: #037C79;"></i>
                    </span>
                    <input type="text" id="buscadorProductos" class="form-control border-start-0" 
                        placeholder="Buscar por código o descripción..." 
                        style="border-left: none; border-right: none;">
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="limpiarBusqueda" 
                            style="border-radius: 0 30px 30px 0; background-color: white;">
                        <i class="bi bi-x-circle" style="color: #dc3545;"></i>
                    </button>
                </div>
            </div>
        </div>
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
const lineaNombre = '<?php echo ($line == 1) ? "automotriz" : "ferretero"; ?>';

// Función para ajustar el texto del botón según el ancho de pantalla
function ajustarTextoBoton() {
    const btn = document.getElementById('btnCambiarPrecio');
    if (!btn) return;
    
    const esMovil = window.innerWidth <= 768;
    const esMovilPeq = window.innerWidth <= 480;
    const currentPrecioVal = parseInt(btn.getAttribute('data-current'));
    
    if (esMovilPeq) {
        btn.innerHTML = currentPrecioVal == 0 ?
            '<i class="bi bi-arrow-repeat"></i> Mayorista' :
            '<i class="bi bi-arrow-repeat"></i> Minorista';
    } else if (esMovil) {
        btn.innerHTML = currentPrecioVal == 0 ?
            '<i class="bi bi-arrow-repeat"></i> Ver Mayorista' :
            '<i class="bi bi-arrow-repeat"></i> Ver Minorista';
    } else {
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
            
            // Actualizar el enlace del PDF
            var pdfIcon = $('.title-banner .bi-file-pdf-fill');
            var nuevaRutaPdf = '';
            
            if (currentPrecio == 0) {
                nuevaRutaPdf = '/pdfs/catalogo_' + lineaNombre + '/conPrecio/catalogo_dptos_' + dptoId + '.pdf';
                pdfIcon.attr('title', 'PDF con Precio Minorista');
            } else {
                nuevaRutaPdf = '/pdfs/catalogo_' + lineaNombre + '/conPrecioMayor/catalogo_dptos_' + dptoId + '.pdf';
                pdfIcon.attr('title', 'PDF con Precio Mayorista');
            }
            
            pdfIcon.off('click').on('click', function() {
                window.open(nuevaRutaPdf, '_blank');
            });
            
            btn.prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            alert('Error al cambiar el precio. Recargue la página.');
            location.reload();
        }
    });
});

$(document).ready(function() {
    ajustarTextoBoton();
    $(window).resize(function() {
        ajustarTextoBoton();
    });
});
<?php endif; ?>

    // Forzar tema claro en Chrome Android
    (function() {
        // Prevenir que Chrome aplique tema oscuro automático
        document.documentElement.style.colorScheme = 'light';
        document.documentElement.setAttribute('data-color-scheme', 'light');
        
        // Forzar estilos en línea como respaldo
        var style = document.createElement('style');
        style.textContent = '*{color-scheme:light!important;}html,body{background-color:#DDD!important;}';
        document.head.appendChild(style);
    })();

    // Buscador de productos en tiempo real
    document.addEventListener('DOMContentLoaded', function() {
        const buscador = document.getElementById('buscadorProductos');
        const limpiarBtn = document.getElementById('limpiarBusqueda');
        
        if (!buscador) return;
        
        function filtrarProductos() {
            const termino = buscador.value.toLowerCase().trim();
            const productos = document.querySelectorAll('#productos-grid .col');
            let contador = 0;
            
            productos.forEach(producto => {
                const card = producto.querySelector('.card');
                if (!card) return;
                
                // Buscar en código (card-header) y en descripción (card-footer p)
                const codigo = card.querySelector('.card-header small');
                const descripcion = card.querySelector('.card-footer p');
                
                const textoCodigo = codigo ? codigo.textContent.toLowerCase() : '';
                const textoDescripcion = descripcion ? descripcion.textContent.toLowerCase() : '';
                
                if (termino === '' || textoCodigo.includes(termino) || textoDescripcion.includes(termino)) {
                    producto.style.display = '';
                    contador++;
                } else {
                    producto.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            let mensajeNoResultados = document.getElementById('mensajeNoResultados');
            if (contador === 0 && termino !== '') {
                if (!mensajeNoResultados) {
                    mensajeNoResultados = document.createElement('div');
                    mensajeNoResultados.id = 'mensajeNoResultados';
                    mensajeNoResultados.className = 'alert alert-warning text-center mt-3';
                    mensajeNoResultados.innerHTML = '<i class="bi bi-exclamation-triangle"></i> No se encontraron productos que coincidan con "<strong>' + 
                                                    termino + '</strong>"';
                    document.getElementById('productos-grid').parentNode.appendChild(mensajeNoResultados);
                } else {
                    mensajeNoResultados.style.display = 'block';
                    mensajeNoResultados.innerHTML = '<i class="bi bi-exclamation-triangle"></i> No se encontraron productos que coincidan con "<strong>' + 
                                                    termino + '</strong>"';
                }
            } else if (mensajeNoResultados) {
                mensajeNoResultados.style.display = 'none';
            }
        }
        
        // Evento keyup para filtrar mientras escribe
        buscador.addEventListener('keyup', filtrarProductos);
        
        // Botón limpiar búsqueda
        if (limpiarBtn) {
            limpiarBtn.addEventListener('click', function() {
                buscador.value = '';
                filtrarProductos();
                buscador.focus();
            });
        }
    });

    </script>
</body>
</html>