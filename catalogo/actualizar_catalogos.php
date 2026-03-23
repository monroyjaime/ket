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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar Catálogos KET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #003272;
            color: white;
            font-weight: bold;
        }
        .card-header.ferretero {
            background-color: #037C79;
        }
        .dpto-checkbox {
            margin: 5px 0;
        }
        .dpto-checkbox label {
            margin-left: 5px;
            cursor: pointer;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="bi bi-file-pdf-fill text-danger"></i> 
                    Actualizar Catálogos KET
                </h1>
            </div>
        </div>

        <div class="row">
            <!-- Panel de selección -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-check2-square"></i> Selección de Departamentos
                        <button class="btn btn-sm btn-light float-end" onclick="toggleAll(true)">Seleccionar Todos</button>
                        <button class="btn btn-sm btn-light float-end me-2" onclick="toggleAll(false)">Deseleccionar Todos</button>
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
                        <button id="btn-actualizar" class="btn btn-primary w-100" onclick="ejecutarActualizacion()">
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
                        <button class="btn btn-sm btn-secondary float-end" onclick="limpiarLog()">
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

    <script>
        let procesando = false;
        
        // Actualizar contador de seleccionados
        function actualizarContador() {
            const seleccionados = document.querySelectorAll('.dpto-check:checked').length;
            document.getElementById('selected-count').innerText = seleccionados + ' departamento(s) seleccionado(s)';
        }
        
        // Seleccionar/deseleccionar todos
        function toggleAll(seleccionar) {
            document.querySelectorAll('.dpto-check').forEach(cb => {
                cb.checked = seleccionar;
            });
            actualizarContador();
        }
        
        // Limpiar área de log
        function limpiarLog() {
            document.getElementById('log-area').innerHTML = '<div class="log-line info">[SISTEMA] Log limpiado.</div>';
        }
        
        // Agregar línea al log
        function agregarLog(mensaje, tipo = 'info') {
            const logArea = document.getElementById('log-area');
            const timestamp = new Date().toLocaleTimeString();
            const div = document.createElement('div');
            div.className = `log-line ${tipo}`;
            div.innerHTML = `[${timestamp}] ${mensaje}`;
            logArea.appendChild(div);
            div.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }
        
        // Ejecutar actualización
        async function ejecutarActualizacion() {
            if (procesando) {
                agregarLog('Ya hay un proceso en ejecución. Espere...', 'error');
                return;
            }
            
            const seleccionados = Array.from(document.querySelectorAll('.dpto-check:checked')).map(cb => cb.value);
            if (seleccionados.length === 0) {
                agregarLog('No hay departamentos seleccionados.', 'error');
                return;
            }
            
            const calidad = document.getElementById('calidad').value;
            procesando = true;
            const btn = document.getElementById('btn-actualizar');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
            
            agregarLog(`========================================`, 'info');
            agregarLog(`INICIANDO ACTUALIZACIÓN DE ${seleccionados.length} DEPARTAMENTOS`, 'info');
            agregarLog(`Calidad: ${calidad === 'web' ? 'Web (comprimido)' : 'Impresión (máxima calidad)'}`, 'info`);
            
            // Procesar uno por uno para mostrar progreso
            for (let i = 0; i < seleccionados.length; i++) {
                const dptoId = seleccionados[i];
                const dptoNombre = document.querySelector(`.dpto-check[value="${dptoId}"]`).closest('.dpto-checkbox').querySelector('label').innerText;
                
                agregarLog(`[${i+1}/${seleccionados.length}] Procesando: ${dptoNombre} (ID: ${dptoId})...`, 'proc');
                
                try {
                    const response = await fetch('actualizar_catalogo_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `dpto_id=${dptoId}&calidad=${calidad}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        agregarLog(`  ✅ ${dptoNombre} - PDF generado correctamente (${data.tamano || '?'} KB)`, 'ok');
                    } else {
                        agregarLog(`  ❌ ${dptoNombre} - Error: ${data.error}`, 'error');
                    }
                } catch (error) {
                    agregarLog(`  ❌ ${dptoNombre} - Error de conexión: ${error.message}`, 'error');
                }
                
                // Pequeña pausa entre solicitudes
                await new Promise(r => setTimeout(r, 500));
            }
            
            agregarLog(`========================================`, 'info');
            agregarLog(`PROCESO COMPLETADO - ${seleccionados.length} departamentos procesados`, 'ok');
            agregarLog(`Los PDFs están disponibles en: https://ketelectropartes.com/pdfs/index.html`, 'info');
            
            procesando = false;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-fill"></i> Actualizar Seleccionados';
        }
        
        // Event listeners
        document.querySelectorAll('.dpto-check').forEach(cb => {
            cb.addEventListener('change', actualizarContador);
        });
        
        actualizarContador();
    </script>
</body>
</html>