<?php
require_once("../php/dbcat.php");
$db = new DB();

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
    <title>Listas de Precios KET - Índice</title>
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
            display: inline-block;
            margin-top: 10px;
        }
        
        .card-dpto .link a {
            text-decoration: none;
            font-size: 0.9rem;
            padding: 6px 15px;
            border-radius: 20px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #003272;
            color: white;
            border: 1px solid #003272;
        }
        
        .card-dpto .link a:hover {
            background-color: #037C79;
            border-color: #037C79;
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
        }
    </style>
</head>
<body>
    <!-- Barra superior -->
    <div class="top-bar">
        <div class="row">
            <div class="col text-start">
                <a href="index.php" class="back-icon" title="Volver al inicio">
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
            <i class="bi bi-table"></i>
            Listas de Precios KET
            <i class="bi bi-table"></i>
        </h1>
    </div>
    
    <!-- Estadísticas -->
    <div class="stats">
        <p><strong>📊 Total de departamentos:</strong> <?php echo $total_general; ?></p>
        <p><strong>🔧 Automotriz:</strong> <?php echo $total_auto; ?> departamentos &nbsp;|&nbsp; 
           <strong>🔩 Ferretera:</strong> <?php echo $total_ferre; ?> departamentos</p>
        <p><em>Seleccione un departamento para ver su lista de precios</em></p>
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
            <div class="link">
                <a href="index1.php?dpto=<?php echo $d->id; ?>&from=1" target="_blank">
                    <i class="bi bi-eye-fill"></i> Ver lista
                </a>
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
            <div class="link">
                <a href="index1.php?dpto=<?php echo $d->id; ?>&from=1" target="_blank">
                    <i class="bi bi-eye-fill"></i> Ver lista
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pie de página -->
    <div class="footer">
        <p>
            📁 <a href="../catalogo/actualizar_catalogos.php">Actualizar catálogos PDF</a> &nbsp;|&nbsp;
            📁 <a href="../catalogos/indiceDptos.php">Ver catálogos web</a>
        </p>
        <p>⚙️ Listas de precios KET</p>
    </div>
</body>
</html>