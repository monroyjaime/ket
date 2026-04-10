<?php
require_once("../php/dbcat.php");
session_start();
$db = new DB();

// Verificar permisos específicos (NO usar rol)
$puedeOrdenarCatalogo = isset($_SESSION['do_orden_catalogo']) && $_SESSION['do_orden_catalogo'] == 1;

// Obtener todos los departamentos con catalogo_orden > 0
$query = "
    SELECT id, name, code, num, catalogo_orden 
    FROM departamentos 
    WHERE catalogo_orden > 0 
    ORDER BY num, catalogo_orden
";
$departamentos = $db->consultas($query);

// Separar por línea
$automotriz = [];
$ferretera = [];
foreach ($departamentos as $d) {
    if ($d->num == 1) {
        $automotriz[] = $d;
    } else {
        $ferretera[] = $d;
    }
}

$total_auto = count($automotriz);
$total_ferre = count($ferretera);
$total_general = $total_auto + $total_ferre;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <title>Catálogos Web KET - Índice</title>
    <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #DDD;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        /* Barra superior */
        .top-bar {
            background-color: #DDD;
            padding: 0px 5px;
        }
        
        .top-bar .row {
            align-items: center;
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
        
        /* Título centrado sobre franja verde agua */
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
        
        .title-banner h1 i {
            font-size: 2rem;
        }
        
        /* Estadísticas */
        .stats {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 0 20px 30px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stats p {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .stats strong {
            color: #003272;
        }
        
        /* Buscador */
        .search-container {
            margin: 0 20px 20px 20px;
        }
        
        .search-container .input-group {
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 30px;
            overflow: hidden;
        }
        
        .search-container .input-group-text {
            background-color: white;
            border-right: none;
            color: #037C79;
        }
        
        .search-container .form-control {
            border-left: none;
            border-right: none;
        }
        
        .search-container .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
        
        .search-container .btn-outline-secondary {
            border-left: none;
            color: #999;
        }
        
        .search-container .btn-outline-secondary:hover {
            background-color: transparent;
            color: #dc3545;
        }
        
        /* Secciones */
        .section-header {
            background-color: #e8f4f4;
            padding: 12px 20px;
            border-radius: 10px;
            margin: 20px 20px 15px 20px;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 1.5rem;
            display: inline-block;
        }
        
        .section-header .badge {
            background-color: #037C79;
            margin-left: 10px;
            font-size: 0.9rem;
            padding: 5px 12px;
        }
        
        .section-header.hidden,
        .grid.hidden {
            display: none;
        }
        
        /* Grid de departamentos */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 15px;
            margin: 0 20px 30px 20px;
        }
        
        .card-dpto {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            transition: all 0.2s;
        }
        
        .card-dpto:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .card-dpto h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: #003272;
        }
        
        .card-dpto .card-code {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .card-dpto .link {
            margin-top: 10px;
        }
        
        /* Botón Ver catálogo */
        .btn-ver-catalogo {
            background-color: #003272;
            color: white;
            border: 1px solid #003272;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 6px 15px;
            border-radius: 20px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-ver-catalogo:hover {
            background-color: #037C79;
            border-color: #037C79;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Botón Ordenar catálogo */
        .btn-ordenar-catalogo {
            background-color: #f39c12;
            color: white;
            border: 1px solid #f39c12;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 6px 15px;
            border-radius: 20px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-ordenar-catalogo:hover {
            background-color: #e67e22;
            border-color: #e67e22;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Pie de página */
        .footer {
            background-color: #99b9d7;
            padding: 20px;
            margin-top: 40px;
            text-align: center;
            color: #003272;
        }
        
        .footer a {
            color: #003272;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
                margin: 0 15px 20px 15px;
            }
            
            .section-header {
                margin: 15px 15px 10px 15px;
            }
            
            .stats {
                margin: 0 15px 20px 15px;
            }
            
            .search-container {
                margin: 0 15px 20px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Barra superior -->
    <div class="top-bar">
        <div class="row">
            <div class="col text-start">
                <a href="../index.php" class="back-icon" title="Volver al inicio">
                    <i class="bi bi-arrow-left-circle-fill"></i>
                </a>
            </div>
            <div class="col text-end">
                <img src="../catalogo/images/logoMini.png" class="logo-mini" alt="KET" />
            </div>
        </div>
    </div>
    
    <!-- Título centrado sobre franja verde agua -->
    <div class="title-banner">
        <h1>
            <i class="bi bi-file-image-fill"></i>
            Catálogos Web KET
            <i class="bi bi-file-image-fill"></i>
        </h1>
    </div>
    
    <!-- Estadísticas -->
    <div class="stats">
        <p><strong>📊 Total de departamentos:</strong> <?php echo $total_general; ?></p>
        <p><strong>🔧 Automotriz:</strong> <?php echo $total_auto; ?> departamentos &nbsp;|&nbsp; 
           <strong>🔩 Ferretera:</strong> <?php echo $total_ferre; ?> departamentos</p>
        <p><em>Seleccione un departamento para ver su catálogo web</em></p>
    </div>
    
    <!-- Buscador -->
    <div class="search-container">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="buscadorDptos" class="form-control" placeholder="Buscar departamento...">
            <button class="btn btn-outline-secondary" type="button" id="limpiarBusqueda">
                <i class="bi bi-x-circle"></i>
            </button>
        </div>
    </div>
    
    <!-- Línea Automotriz -->
    <div class="section-header">
        <h2>🔧 Línea Automotriz</h2>
        <span class="badge"><?php echo $total_auto; ?> departamentos</span>
    </div>
    <div class="grid">
        <?php foreach ($automotriz as $d): ?>
        <div class="card-dpto">
            <h3><?php echo htmlspecialchars($d->name); ?></h3>
            <div class="card-code">Código: <?php echo $d->code; ?> | ID: <?php echo $d->id; ?></div>
            <div class="link" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="../catalogo/indexDptoAll2.php?dpto_id=<?php echo $d->id; ?>&line=1&prec=0&from=1" class="btn-ver-catalogo">
                    <i class="bi bi-eye-fill"></i> Ver catálogo
                </a>
                <?php if ($puedeOrdenarCatalogo): ?>
                <a href="ordenar_catalogo.php?dpto_id=<?php echo $d->id; ?>&nombre=<?php echo urlencode($d->nae); ?>" class="btn-ordenar-catalogo">
                    <i class="bi bi-arrow-up-down"></i> Ordenar catálogo
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Línea Ferretera -->
    <div class="section-header">
        <h2>🔩 Línea Ferretera</h2>
        <span class="badge"><?php echo $total_ferre; ?> departamentos</span>
    </div>
    <div class="grid">
        <?php foreach ($ferretera as $d): ?>
        <div class="card-dpto">
            <h3><?php echo htmlspecialchars($d->name); ?></h3>
            <div class="card-code">Código: <?php echo $d->code; ?> | ID: <?php echo $d->id; ?></div>
            <div class="link" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="../catalogo/indexDptoAll2.php?dpto_id=<?php echo $d->id; ?>&line=1&prec=0&from=1" target="_blank" class="btn-ver-catalogo">
                    <i class="bi bi-eye-fill"></i> Ver catálogo
                </a>
                <?php if ($puedeOrdenarCatalogo): ?>
                <a href="ordenar_catalogo.php?dpto_id=<?php echo $d->id; ?>&nombre=<?php echo urlencode($d->name); ?>" class="btn-ordenar-catalogo">
                    <i class="bi bi-arrow-up-down"></i> Ordenar catálogo
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pie de página -->
    <div class="footer">
        <p>
            📁 <a href="../catalogo/actualizar_catalogos.php">Actualizar catálogos PDF</a> &nbsp;|&nbsp;
            📁 <a href="../listas/indiceDptos.php">Ver listas de precios</a>
        </p>
        <p>⚙️ Catálogos web KET</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buscador = document.getElementById('buscadorDptos');
            const limpiarBtn = document.getElementById('limpiarBusqueda');
            
            if (!buscador) return;
            
            function filtrarDepartamentos() {
                const termino = buscador.value.toLowerCase().trim();
                
                // Procesar cada grid
                const grids = document.querySelectorAll('.grid');
                const sectionHeaders = document.querySelectorAll('.section-header');
                
                grids.forEach((grid, index) => {
                    const cards = grid.querySelectorAll('.card-dpto');
                    let hayResultados = false;
                    
                    cards.forEach(card => {
                        const nombreElem = card.querySelector('h3');
                        const codigoElem = card.querySelector('.card-code');
                        
                        if (!nombreElem) return;
                        
                        const nombre = nombreElem.textContent.toLowerCase();
                        const codigo = codigoElem ? codigoElem.textContent.toLowerCase() : '';
                        
                        if (termino === '' || nombre.includes(termino) || codigo.includes(termino)) {
                            card.style.display = '';
                            hayResultados = true;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Mostrar/ocultar la sección correspondiente
                    if (sectionHeaders[index]) {
                        if (termino === '' || hayResultados) {
                            sectionHeaders[index].classList.remove('hidden');
                            grid.classList.remove('hidden');
                        } else {
                            sectionHeaders[index].classList.add('hidden');
                            grid.classList.add('hidden');
                        }
                    }
                });
                
                // Actualizar contadores
                actualizarContadores();
            }
            
            function actualizarContadores() {
                const grids = document.querySelectorAll('.grid');
                const sectionHeaders = document.querySelectorAll('.section-header');
                
                grids.forEach((grid, index) => {
                    const cards = grid.querySelectorAll('.card-dpto');
                    let visibles = 0;
                    
                    cards.forEach(card => {
                        if (card.style.display !== 'none') {
                            visibles++;
                        }
                    });
                    
                    if (sectionHeaders[index]) {
                        const badge = sectionHeaders[index].querySelector('.badge');
                        if (badge) {
                            badge.textContent = visibles + ' departamentos';
                            
                            // Cambiar color si hay filtro activo
                            if (buscador.value.trim() !== '' && visibles < cards.length) {
                                badge.style.backgroundColor = '#f39c12';
                            } else {
                                badge.style.backgroundColor = '#037C79';
                            }
                        }
                    }
                });
            }
            
            buscador.addEventListener('keyup', filtrarDepartamentos);
            
            if (limpiarBtn) {
                limpiarBtn.addEventListener('click', function() {
                    buscador.value = '';
                    filtrarDepartamentos();
                    buscador.focus();
                });
            }
            
            // Ejecutar al inicio
            filtrarDepartamentos();
        });
    </script>
</body>
</html>