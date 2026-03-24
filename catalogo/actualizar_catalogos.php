<?php
session_start();
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

// Separar por linea
$automotriz = [];
$ferretera = [];
foreach ($departamentos as $d) {
    if ($d->num == 1) {
        $automotriz[] = $d;
    } else {
        $ferretera[] = $d;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <title>Actualizar Catálogos KET</title>
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
        
        /* Barra superior estilo página principal */
        .top-bar {
            background-color: #DDD;
            padding: 10px 10px;
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
            padding: 10px 0;
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
        
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
            border-radius: 8px;
        }
        
        .card-header {
            background-color: #003272;
            color: white;
            font-weight: bold;
            border-radius: 8px 8px 0 0 !important;
        }
        
        .card-header.ferretero {
            background-color: #037C79;
        }
        
        .dpto-checkbox {
            margin: 8px 0;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .dpto-checkbox label {
            margin-left: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .dpto-checkbox small {
            color: #666;
        }
        
        .log-area {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            height: 400px;
            overflow-y: auto;
            padding: 10px;
            border-radius: 5px;
        }
        
        .log-line {
            font-family: monospace;
            margin: 0;
            padding: 2px 0;
            border-bottom: 1px solid #333;
        }
        
        .log-line.info { color: #9cdcfe; }
        .log-line.ok { color: #6a9955; }
        .log-line.error { color: #f48771; }
        .log-line.proc { color: #ce9178; }
        
        .selected-count {
            font-size: 0.9em;
            margin-left: 10px;
        }
        
        .badge-automotriz {
            background-color: #003272;
        }
        
        .badge-ferretero {
            background-color: #037C79;
        }
        
        /* Boton flotante de resultado */
        .result-banner {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        
        .result-banner a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            background-color: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 30px;
            transition: all 0.2s;
        }
        
        .result-banner a:hover {
            background-color: rgba(255,255,255,0.3);
            color: white;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .btn-close-result {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0 5px;
        }
        
        .btn-close-result:hover {
            color: #ddd;
        }
        
        .btn-primary {
            background-color: #003272;
            border: none;
            padding: 10px;
            font-weight: bold;
        }
        
        .btn-primary:hover {
            background-color: #037C79;
        }
        
        .form-select:focus {
            border-color: #037C79;
            box-shadow: 0 0 0 0.2rem rgba(3, 124, 121, 0.25);
        }
    </style>
</head>
<body>
    <!-- Barra superior estilo página principal -->
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
            <i class="bi bi-file-pdf-fill"></i>
            Actualizar Catálogos KET
            <i class="bi bi-file-pdf-fill"></i>
        </h1>
    </div>

    <div class="container-fluid px-4">
        <div class="row">
            <!-- Panel de selección -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-check2-square"></i> Selección de Departamentos
                        <button type="button" class="btn btn-sm btn-light float-end" onclick="toggleAll(true)">Seleccionar Todos</button>
                        <button type="button" class="btn btn-sm btn-light float-end me-2" onclick="toggleAll(false)">Deseleccionar Todos</button>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <h5 class="text-primary">🔧 Automotriz <span class="badge badge-automotriz"><?php echo count($automotriz); ?></span></h5>
                        <div id="lista-automotriz">
                            <?php foreach ($automotriz as $d): ?>
                            <div class="dpto-checkbox">
                                <input type="checkbox" class="dpto-check" value="<?php echo $d->id; ?>" data-linea="A" data-nombre="<?php echo htmlspecialchars($d->name); ?>">
                                <label><?php echo htmlspecialchars($d->name); ?></label>
                                <small class="text-muted">(<?php echo $d->code; ?>)</small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <h5 class="text-success">🔩 Ferretera <span class="badge badge-ferretero"><?php echo count($ferretera); ?></span></h5>
                        <div id="lista-ferretera">
                            <?php foreach ($ferretera as $d): ?>
                            <div class="dpto-checkbox">
                                <input type="checkbox" class="dpto-check" value="<?php echo $d->id; ?>" data-linea="F" data-nombre="<?php echo htmlspecialchars($d->name); ?>">
                                <label><?php echo htmlspecialchars($d->name); ?></label>
                                <small class="text-muted">(<?php echo $d->code; ?>)</small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-sliders2"></i> Opciones
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Calidad del PDF</label>
                            <select id="calidad" class="form-select">
                                <option value="web">Web (comprimido, para visualización)</option>
                                <option value="impresion">Impresión (máxima calidad)</option>
                            </select>
                        </div>
                        <button type="button" id="btn-actualizar" class="btn btn-primary w-100" onclick="ejecutarActualizacion()">
                            <i class="bi bi-play-fill"></i> Actualizar Seleccionados
                        </button>
                        <div class="mt-2 text-center">
                            <span id="selected-count" class="selected-count">0 departamentos seleccionados</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Área de log -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-terminal"></i> Log de Actualización
                        <button type="button" class="btn btn-sm btn-secondary float-end" onclick="limpiarLog()">
                            <i class="bi bi-trash"></i> Limpiar
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="log-area" class="log-area">
                            <div class="log-line info">[SISTEMA] Esperando acción...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Banner flotante de resultados -->
    <div id="result-banner" class="result-banner">
        <i class="bi bi-check-circle-fill" style="font-size: 24px;"></i>
        <span id="result-text">0 PDFs generados</span>
        <a id="result-link" href="#" target="_blank">Ver PDFs</a>
        <button class="btn-close-result" onclick="cerrarBanner()">&times;</button>
    </div>

    <script>
        let procesando = false;
        let pdfsGenerados = [];
        let calidadActual = 'web';
        
        function actualizarContador() {
            var seleccionados = document.querySelectorAll('.dpto-check:checked').length;
            document.getElementById('selected-count').innerText = seleccionados + ' departamento(s) seleccionado(s)';
        }
        
        function toggleAll(seleccionar) {
            var checkboxes = document.querySelectorAll('.dpto-check');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = seleccionar;
            }
            actualizarContador();
        }
        
        function limpiarLog() {
            document.getElementById('log-area').innerHTML = '<div class="log-line info">[SISTEMA] Log limpiado.</div>';
            pdfsGenerados = [];
            cerrarBanner();
        }
        
        function cerrarBanner() {
            document.getElementById('result-banner').style.display = 'none';
        }
        
        function mostrarBanner() {
            var banner = document.getElementById('result-banner');
            var resultText = document.getElementById('result-text');
            var resultLink = document.getElementById('result-link');
            var calidad = document.getElementById('calidad').value;
            
            var count = pdfsGenerados.length;
            
            if (count === 0) {
                cerrarBanner();
                return;
            }
            
            resultText.innerText = count + ' PDF' + (count > 1 ? 's' : '') + ' generado' + (count > 1 ? 's' : '');
            
            if (count === 1) {
                resultLink.href = pdfsGenerados[0];
                resultLink.innerText = 'Ver PDF';
            } else {
                resultLink.href = '/pdfs/index.html';
                resultLink.innerText = 'Ver todos (' + count + ')';
            }
            
            banner.style.display = 'flex';
            
            setTimeout(function() {
                if (banner.style.display === 'flex') {
                    banner.style.display = 'none';
                }
            }, 10000);
        }
        
        function agregarLog(mensaje, tipo) {
            if (tipo === undefined) tipo = 'info';
            var logArea = document.getElementById('log-area');
            var timestamp = new Date().toLocaleTimeString();
            var div = document.createElement('div');
            div.className = 'log-line ' + tipo;
            div.innerHTML = '[' + timestamp + '] ' + mensaje;
            logArea.appendChild(div);
            div.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }
        
        function obtenerUrlPDF(dptoId, linea, calidad) {
            var carpeta = (linea === 'A') ? 'catalogo_automotriz' : 'catalogo_ferretero';
            var subcarpeta = (calidad === 'impresion') ? 'print/' : '';
            return '/pdfs/' + carpeta + '/' + subcarpeta + 'catalogo_dptos_' + dptoId + '.pdf';
        }
        
        async function ejecutarActualizacion() {
            if (procesando) {
                agregarLog('Ya hay un proceso en ejecución. Espere...', 'error');
                return;
            }
            
            var checkboxes = document.querySelectorAll('.dpto-check:checked');
            var seleccionados = [];
            for (var i = 0; i < checkboxes.length; i++) {
                seleccionados.push({
                    id: checkboxes[i].value,
                    linea: checkboxes[i].dataset.linea,
                    nombre: checkboxes[i].dataset.nombre
                });
            }
            
            if (seleccionados.length === 0) {
                agregarLog('No hay departamentos seleccionados.', 'error');
                return;
            }
            
            var calidad = document.getElementById('calidad').value;
            calidadActual = calidad;
            pdfsGenerados = [];
            procesando = true;
            var btn = document.getElementById('btn-actualizar');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
            
            agregarLog('========================================', 'info');
            agregarLog('INICIANDO ACTUALIZACIÓN DE ' + seleccionados.length + ' DEPARTAMENTOS', 'info');
            agregarLog('Calidad: ' + (calidad === 'web' ? 'Web (comprimido)' : 'Impresión (máxima calidad)'), 'info');
            
            for (var i = 0; i < seleccionados.length; i++) {
                var dpto = seleccionados[i];
                agregarLog('[' + (i+1) + '/' + seleccionados.length + '] Procesando: ' + dpto.nombre + ' (ID: ' + dpto.id + ')...', 'proc');
                
                try {
                    var formData = new URLSearchParams();
                    formData.append('dpto_id', dpto.id);
                    formData.append('calidad', calidad);
                    
                    var response = await fetch('actualizar_catalogo_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    });
                    
                    var data = await response.json();
                    
                    if (data.success) {
                        agregarLog('  ✅ ' + dpto.nombre + ' - PDF generado correctamente (' + (data.tamano || '?') + ' KB)', 'ok');
                        var urlPdf = obtenerUrlPDF(dpto.id, dpto.linea, calidad);
                        pdfsGenerados.push(urlPdf);
                    } else {
                        agregarLog('  ❌ ' + dpto.nombre + ' - Error: ' + (data.error || 'Desconocido'), 'error');
                    }
                } catch (error) {
                    agregarLog('  ❌ ' + dpto.nombre + ' - Error de conexión: ' + error.message, 'error');
                }
                
                await new Promise(function(r) { setTimeout(r, 500); });
            }
            
            agregarLog('========================================', 'info');
            agregarLog('PROCESO COMPLETADO - ' + seleccionados.length + ' departamentos procesados', 'ok');
            agregarLog('PDFs generados: ' + pdfsGenerados.length, 'ok');
            
            mostrarBanner();
            
            procesando = false;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-fill"></i> Actualizar Seleccionados';
        }
        
        var checkboxes = document.querySelectorAll('.dpto-check');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].addEventListener('change', actualizarContador);
        }
        
        actualizarContador();
    </script>
</body>
</html>