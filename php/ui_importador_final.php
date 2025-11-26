<?php
// ui_importador_final.php - VERSIÓN QUE SÍ FUNCIONA

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    // LIMPIAR TODO OUTPUT ANTES
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    if ($_POST['accion'] === 'estadisticas') {
        try {
            require_once("dbcat.php");
            $db = new DB();
            
            $result = $db->consultas("SELECT COUNT(*) as total FROM productos");
            $total_productos = $result[0]->total;
            
            $result = $db->consultas("SELECT COUNT(*) as con_stock FROM productos WHERE current_stock > 0");
            $con_stock = $result[0]->con_stock;
            
            $result = $db->consultas("SELECT MAX(updated_at) as ultima_actualizacion FROM productos");
            $ultima_actualizacion = $result[0]->ultima_actualizacion ?? 'Nunca';
            
            echo json_encode([
                'success' => true,
                'total_productos' => $total_productos,
                'con_stock' => $con_stock,
                'ultima_actualizacion' => $ultima_actualizacion
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($_POST['accion'] === 'ejecutar') {
        try {
            // EJECUTAR EN BACKGROUND Y CAPTURAR OUTPUT
            $scriptPath = '/var/www/html/php/importa_google_sheets.php';
            $outputFile = '/tmp/import_ui_output_' . time() . '.log';
            
            // Ejecutar en background con timeout
            $command = "timeout 120s php " . escapeshellarg($scriptPath) . " > " . escapeshellarg($outputFile) . " 2>&1 & echo $!";
            $pid = shell_exec($command);
            $pid = trim($pid);
            
            // Esperar un poco y verificar
            sleep(5);
            
            // Leer output
            $output = [];
            if (file_exists($outputFile)) {
                $output = file($outputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                // Tomar líneas relevantes
                $relevantOutput = [];
                foreach ($output as $line) {
                    if (strpos($line, '✅') !== false || 
                        strpos($line, '❌') !== false || 
                        strpos($line, '🚀') !== false ||
                        strpos($line, '🎉') !== false) {
                        $relevantOutput[] = $line;
                    }
                }
                $output = array_slice($relevantOutput, -15); // Últimas 15 líneas relevantes
                unlink($outputFile); // Limpiar
            }
            
            // Verificar si el proceso terminó
            $isRunning = false;
            if ($pid) {
                exec("ps -p " . escapeshellarg($pid) . " 2>&1", $processState);
                $isRunning = (count($processState) > 1);
            }
            
            $success = !$isRunning; // Si ya terminó, fue exitoso
            
            if (empty($output)) {
                if ($success) {
                    $output = ["✅ Proceso ejecutado en background exitosamente"];
                } else {
                    $output = ["⏳ Proceso aún en ejecución..."];
                }
            }
            
            echo json_encode([
                'success' => $success,
                'output' => $output,
                'pid' => $pid,
                'message' => $success ? 'Importación completada' : 'Importación en progreso'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// SI NO ES AJAX, MOSTRAR HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Productos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn:disabled { background: #6c757d; cursor: not-allowed; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .loading { display: none; text-align: center; margin: 20px 0; color: #007bff; }
        .output { background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; margin: 10px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 20px 0; }
        .stat-card { background: #e9ecef; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Importador de Productos</h1>
        <p>Sincroniza datos desde Google Sheets a la base de datos</p>
        
        <div class="card">
            <h3>📊 Estadísticas</h3>
            <div class="stats-grid" id="estadisticas">
                <div class="stat-card">
                    <div class="stat-value">--</div>
                    <div>Productos Totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">--</div>
                    <div>Con Stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">--</div>
                    <div>Última Actualización</div>
                </div>
            </div>
            <button class="btn" onclick="cargarEstadisticas()">🔄 Actualizar Estadísticas</button>
        </div>
        
        <div class="card">
            <h3>⚡ Ejecutar Importación</h3>
            <p>Ejecuta manualmente el proceso de importación desde Google Sheets</p>
            <button class="btn" id="btnEjecutar" onclick="ejecutarImportacion()">🚀 Ejecutar Importación</button>
            <button class="btn" onclick="verLogs()">📋 Ver Logs Completos</button>
        </div>
        
        <div class="loading" id="loading">
            <p>⏳ Ejecutando importación, por favor espere (puede tomar hasta 2 minutos)...</p>
        </div>
        
        <div id="resultado"></div>
    </div>

    <script>
        function cargarEstadisticas() {
            const formData = new FormData();
            formData.append('accion', 'estadisticas');
            
            fetch('ui_importador_final.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('estadisticas').innerHTML = `
                        <div class="stat-card">
                            <div class="stat-value">${data.total_productos}</div>
                            <div>Productos Totales</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.con_stock}</div>
                            <div>Con Stock</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${data.ultima_actualizacion.split(' ')[0]}</div>
                            <div>Última Actualización</div>
                        </div>
                    `;
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cargando estadísticas');
            });
        }
        
        function ejecutarImportacion() {
            const btn = document.getElementById('btnEjecutar');
            const loading = document.getElementById('loading');
            const resultado = document.getElementById('resultado');
            
            btn.disabled = true;
            loading.style.display = 'block';
            resultado.innerHTML = '';
            
            const formData = new FormData();
            formData.append('accion', 'ejecutar');
            
            fetch('ui_importador_final.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                btn.disabled = false;
                
                const cardClass = data.success ? 'success' : 'error';
                const icon = data.success ? '✅' : '❌';
                
                resultado.innerHTML = `
                    <div class="card ${cardClass}">
                        <h3>${icon} ${data.message}</h3>
                        <div class="output">${Array.isArray(data.output) ? data.output.join('\n') : data.output}</div>
                    </div>
                `;
                
                // Actualizar estadísticas
                if (data.success) {
                    setTimeout(cargarEstadisticas, 2000);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                btn.disabled = false;
                resultado.innerHTML = `<div class="card error">❌ Error: ${error.message}</div>`;
            });
        }
        
        function verLogs() {
            window.open('/reports/logs/importacion_detalle.log', '_blank');
        }
        
        // Cargar estadísticas al iniciar
        document.addEventListener('DOMContentLoaded', cargarEstadisticas);
    </script>
</body>
</html>