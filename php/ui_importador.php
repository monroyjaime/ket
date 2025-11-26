<?php
// EVITAR CUALQUIER OUTPUT ANTES DEL JSON
ob_start();

// Solo procesar AJAX primero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    require_once("dbcat.php");
    
    class ImportadorUI {
        private $db;
        
        public function __construct() {
            $this->db = new DB();
        }
        
        public function ejecutarImportacion() {
            $startTime = microtime(true);
            
            try {
                // Ejecutar el script en background y capturar output
                $scriptPath = '/var/www/html/php/importa_google_sheets.php';
                $outputFile = '/var/www/html/reports/logs/ui_output_' . date('Ymd_His') . '.log';
                $command = "php " . escapeshellarg($scriptPath) . " > " . escapeshellarg($outputFile) . " 2>&1 & echo $!";
                
                // Ejecutar en background
                $pid = exec($command);
                
                // Esperar un poco y leer el output
                sleep(2);
                $output = [];
                if (file_exists($outputFile)) {
                    $output = file($outputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    // Mantener solo las últimas 20 líneas para no saturar
                    $output = array_slice($output, -20);
                }
                
                // Verificar si el proceso todavía está corriendo
                $isRunning = false;
                if ($pid) {
                    exec("ps -p " . escapeshellarg($pid), $processState);
                    $isRunning = (count($processState) > 1);
                }
                
                $success = !$isRunning; // Si ya terminó, fue exitoso
                
                if (empty($output)) {
                    $output = ["✅ Proceso iniciado en background (PID: $pid)"];
                }
                
            } catch (Exception $e) {
                $output = ["❌ ERROR: " . $e->getMessage()];
                $success = false;
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            return [
                'success' => $success,
                'output' => $output,
                'execution_time' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        public function getEstadisticas() {
            $stats = [];
            
            try {
                $result = $this->db->consultas("SELECT COUNT(*) as total FROM productos");
                $stats['total_productos'] = $result[0]->total;
                
                $result = $this->db->consultas("SELECT COUNT(*) as con_stock FROM productos WHERE current_stock > 0");
                $stats['con_stock'] = $result[0]->con_stock;
                
                $result = $this->db->consultas("SELECT MAX(updated_at) as ultima_actualizacion FROM productos");
                $stats['ultima_actualizacion'] = $result[0]->ultima_actualizacion ?? 'Nunca';
                
            } catch (Exception $e) {
                $stats['error'] = $e->getMessage();
            }
            
            return $stats;
        }
    }

    $importador = new ImportadorUI();
    
    if ($_POST['accion'] === 'ejecutar') {
        // Limpiar cualquier output previo
        ob_clean();
        header('Content-Type: application/json');
        
        $resultado = $importador->ejecutarImportacion();
        echo json_encode($resultado, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        exit;
    }
    
    if ($_POST['accion'] === 'estadisticas') {
        ob_clean();
        header('Content-Type: application/json');
        
        $estadisticas = $importador->getEstadisticas();
        echo json_encode($estadisticas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        exit;
    }
}

// Si llegamos aquí, mostrar HTML normal
ob_clean();
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
        .loading { display: none; text-align: center; margin: 20px 0; }
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
            <p>⏳ Ejecutando importación, por favor espere...</p>
        </div>
        
        <div id="resultado"></div>
        
        <!-- Área de debug -->
        <div class="card" style="display: none;" id="debugArea">
            <h3>🔧 Debug Info</h3>
            <pre id="debugInfo"></pre>
        </div>
    </div>

    <script>
        // Función de debug
        function debug(msg) {
            console.log(msg);
            document.getElementById('debugInfo').innerHTML += msg + '\n';
            document.getElementById('debugArea').style.display = 'block';
        }
        
        function cargarEstadisticas() {
            const formData = new FormData();
            formData.append('accion', 'estadisticas');
            
            fetch('ui_importador.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                debug('Response status: ' + response.status);
                return response.text();
            })
            .then(text => {
                debug('Raw response: ' + text.substring(0, 200));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    debug('JSON parse error: ' + e.message);
                    throw new Error('Respuesta no válida del servidor');
                }
            })
            .then(data => {
                document.getElementById('estadisticas').innerHTML = `
                    <div class="stat-card">
                        <div class="stat-value">${data.total_productos || '--'}</div>
                        <div>Productos Totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${data.con_stock || '--'}</div>
                        <div>Con Stock</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${(data.ultima_actualizacion || '--').split(' ')[0]}</div>
                        <div>Última Actualización</div>
                    </div>
                `;
            })
            .catch(error => {
                debug('Error cargando estadísticas: ' + error.message);
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
            
            fetch('ui_importador.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                debug('Import Response status: ' + response.status);
                return response.text();
            })
            .then(text => {
                debug('Import Raw response: ' + text.substring(0, 500));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    debug('Import JSON parse error: ' + e.message);
                    throw new Error('El servidor devolvió una respuesta no válida: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                loading.style.display = 'none';
                btn.disabled = false;
                
                const cardClass = data.success ? 'success' : 'error';
                const icon = data.success ? '✅' : '❌';
                
                resultado.innerHTML = `
                    <div class="card ${cardClass}">
                        <h3>${icon} Resultado de la Importación</h3>
                        <p><strong>Tiempo de ejecución:</strong> ${data.execution_time} segundos</p>
                        <p><strong>Fecha y hora:</strong> ${data.timestamp}</p>
                        <div class="output">${Array.isArray(data.output) ? data.output.join('\n') : data.output}</div>
                    </div>
                `;
                
                if (data.success) {
                    setTimeout(cargarEstadisticas, 2000);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                btn.disabled = false;
                resultado.innerHTML = `<div class="card error">❌ Error: ${error.message}</div>`;
                debug('Error ejecutando importación: ' + error.message);
            });
        }
        
        function verLogs() {
            window.open('/reports/logs/importacion.log', '_blank');
        }
        
        // Cargar estadísticas al iniciar
        document.addEventListener('DOMContentLoaded', cargarEstadisticas);
    </script>
</body>
</html>