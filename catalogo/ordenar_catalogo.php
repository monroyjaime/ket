<?php
// ordenar_catalogo.php - Interfaz para ordenar productos por arrastrar/soltar
session_start();
require_once("../php/dbcat.php");

// Verificar autenticación de administrador
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

if ($role != 1 || $isAdmin != 1) {
    header('Location: ../index.php');
    exit;
}

$dpto_id = isset($_GET['dpto_id']) ? (int)$_GET['dpto_id'] : 0;
$nombre_dpto = isset($_GET['nombre']) ? urldecode($_GET['nombre']) : '';

if ($dpto_id <= 0) {
    header('Location: indiceDptos.php');
    exit;
}

$db = new DB();

// Obtener img_route del departamento
$deptoQuery = "SELECT img_route FROM departamentos WHERE id = $dpto_id";
$deptoResult = $db->consultas($deptoQuery);
$imgRoute = !empty($deptoResult) ? $deptoResult[0]->img_route : '';

// Limpiar img_route
$imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
$imgRoute = ltrim($imgRoute, '/');
if ($imgRoute && substr($imgRoute, -1) !== '/') {
    $imgRoute .= '/';
}

// Obtener productos del departamento
$query = "SELECT id, code, name, photo_url, orden 
          FROM productos 
          WHERE dpto_id = $dpto_id 
          ORDER BY orden ASC, code ASC";
$productos = $db->consultas($query);

$pageTitle = "Ordenar Catálogo - " . htmlspecialchars($nombre_dpto);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <title><?php echo $pageTitle; ?> - KET</title>
    <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- SortableJS para drag & drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #DDD;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        .top-bar {
            background-color: #DDD;
            padding: 0px 5px;
        }
        
        .top-bar .back-icon {
            font-size: 25px;
            color: #003272;
            text-decoration: none;
        }
        
        .top-bar .logo-mini {
            max-height: 40px;
            width: auto;
        }
        
        .title-banner {
            background-color: #037c79;
            padding: 7px 0;
            text-align: center;
            margin-bottom: 30px;
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
        
        .card {
            margin: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: #003272;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .product-item {
            background: white;
            border-radius: 10px;
            padding: 10px;
            cursor: grab;
            transition: all 0.2s;
            border: 2px solid #ddd;
            text-align: center;
        }
        
        .product-item:active {
            cursor: grabbing;
        }
        
        .product-item.dragging {
            opacity: 0.5;
            cursor: grabbing;
        }
        
        .product-item:hover {
            border-color: #037C79;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 150px;
            object-fit: contain;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .product-code {
            font-weight: bold;
            color: #003272;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .product-name {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 5px;
            height: 40px;
            overflow: hidden;
        }
        
        .product-order {
            font-size: 0.7rem;
            color: #999;
            background: #f0f0f0;
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
        }
        
        .actions {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .btn-guardar {
            background-color: #037C79;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .btn-guardar:hover {
            background-color: #003272;
            transform: translateY(-2px);
        }
        
        .btn-cancelar {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .btn-cancelar:hover {
            background-color: #5a6268;
        }
        
        .sortable-ghost {
            opacity: 0.4;
            background-color: #e8f4f8;
            border: 2px dashed #037C79;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #037C79;
            margin: 15px 20px;
            padding: 12px 15px;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .grid-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
                padding: 15px;
            }
            
            .product-image {
                height: 100px;
            }
        }
        .product-image {
            width: 100%;
            height: 150px;
            object-fit: contain;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="row">
            <div class="col text-start">
                <a href="indiceDptos.php" class="back-icon" title="Volver">
                    <i class="bi bi-arrow-left-circle-fill"></i>
                </a>
            </div>
            <div class="col text-end">
                <img src="../catalogo/images/logoMini.png" class="logo-mini" alt="KET" />
            </div>
        </div>
    </div>
    
    <div class="title-banner">
        <h1>
            <i class="bi bi-arrow-up-down"></i>
            Ordenar Catálogo
            <i class="bi bi-grid-3x3-gap-fill"></i>
        </h1>
    </div>
    
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-building"></i> <?php echo htmlspecialchars($nombre_dpto); ?>
                <span class="float-end"><?php echo count($productos); ?> productos</span>
            </div>
            <div class="info-box">
                <i class="bi bi-arrows-move"></i>
                <strong>Instrucciones:</strong> Arrastra y suelta los productos para cambiar su orden de aparición en el catálogo.
                El orden superior (izquierda a derecha, arriba a abajo) determina la posición. Haz clic en "Guardar Orden" cuando termines.
            </div>
            <div id="productos-container" class="grid-container">
                <?php foreach ($productos as $index => $p): 
                    // Construir ruta de la imagen
                    $fotoNombre = $p->photo_url;
                    if (empty($fotoNombre) || $fotoNombre == 'empty.jpg' || $fotoNombre == 'none') {
                        $imgPath = '../catalogo/images/empty.jpg';
                    } else {
                        $imgPath = '../' . $imgRoute . $fotoNombre;
                    }
                ?>
                <div class="product-item" data-id="<?php echo $p->id; ?>" data-code="<?php echo htmlspecialchars($p->code); ?>">
                    <img src="<?php echo $imgPath; ?>" class="product-image" alt="<?php echo htmlspecialchars($p->code); ?>"
                        onerror="this.src='../catalogo/images/empty.jpg'">
                    <div class="product-code"><?php echo htmlspecialchars($p->code); ?></div>
                    <div class="product-name"><?php echo htmlspecialchars(substr($p->name, 0, 60)) . (strlen($p->name) > 60 ? '...' : ''); ?></div>
                    <div class="product-order">Orden: <?php echo $p->orden; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="actions">
                <button type="button" class="btn-cancelar" onclick="window.location.href='indiceDptos.php'">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="button" class="btn-guardar" onclick="guardarOrden()">
                    <i class="bi bi-save"></i> Guardar Orden
                </button>
            </div>
        </div>
    </div>

    <script>
        let sortable;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Sortable
            const container = document.getElementById('productos-container');
            sortable = new Sortable(container, {
                animation: 300,
                ghostClass: 'sortable-ghost',
                handle: '.product-item',
                draggable: '.product-item',
                onEnd: function() {
                    // Actualizar números de orden visualmente
                    actualizarNumerosOrden();
                }
            });
            
            // Actualizar números de orden visuales
            actualizarNumerosOrden();
        });
        
        function actualizarNumerosOrden() {
            const items = document.querySelectorAll('.product-item');
            items.forEach((item, index) => {
                const orderSpan = item.querySelector('.product-order');
                if (orderSpan) {
                    orderSpan.textContent = 'Orden: ' + (index + 1);
                }
            });
        }
        
        function guardarOrden() {
            const items = document.querySelectorAll('.product-item');
            const ordenes = [];
            
            items.forEach((item, index) => {
                ordenes.push({
                    id: parseInt(item.dataset.id),
                    orden: index + 1
                });
            });
            
            // Mostrar loading
            Swal.fire({
                title: 'Guardando...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar al servidor
            fetch('guardar_orden_catalogo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    dpto_id: <?php echo $dpto_id; ?>,
                    ordenes: ordenes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Orden guardado!',
                        text: 'El nuevo orden se ha guardado correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'indiceDptos.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al guardar el orden'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión: ' + error.message
                });
            });
        }
    </script>
</body>
</html>