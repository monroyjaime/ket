<?php
require_once("dbcat.php");

class ImportadorUI {
    private $db;
    private $logFile = '/var/www/html/reports/logs/importacion.log';
    
    public function __construct() {
        $this->db = new DB();
    }
    
    public function ejecutarImportacion() {
        $startTime = microtime(true);
        
        try {
            // Ejecutar el script directamente
            $scriptPath = '/var/www/html/php/importa_google_sheets.php';
            $command = "php " . escapeshellarg($scriptPath) . " 2>&1";
            
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            // Filtrar y limpiar el output
            $filteredOutput = [];
            foreach ($output as $line) {
                $cleanLine = trim($line);
                if (!empty($cleanLine)) {
                    $filteredOutput[] = $cleanLine;
                }
            }
            
            // Si no hay output pero fue exitoso
            if (empty($filteredOutput) && $returnCode === 0) {
                $filteredOutput[] = "✅ Proceso completado exitosamente";
            }
            
            $success = ($returnCode === 0);
            
        } catch (Exception $e) {
            $filteredOutput = ["❌ ERROR: " . $e->getMessage()];
            $success = false;
        }
        
        $executionTime = round(microtime(true) - $startTime, 2);
        
        return [
            'success' => $success,
            'output' => $filteredOutput,
            'execution_time' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    public function getUltimaEjecucion() {
        $query = "SELECT MAX(updated_at) as ultima_actualizacion FROM productos";
        $result = $this->db->consultas($query);
        return $result[0]->ultima_actualizacion ?? 'Nunca';
    }
    
    public function getEstadisticas() {
        $stats = [];
        
        // Total productos
        $result = $this->db->consultas("SELECT COUNT(*) as total FROM productos");
        $stats['total_productos'] = $result[0]->total;
        
        // Productos con stock
        $result = $this->db->consultas("SELECT COUNT(*) as con_stock FROM productos WHERE current_stock > 0");
        $stats['con_stock'] = $result[0]->con_stock;
        
        // Última actualización
        $stats['ultima_actualizacion'] = $this->getUltimaEjecucion();
        
        return $stats;
    }
}

// Procesar solicitud AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json');
    
    $importador = new ImportadorUI();
    
    if ($_POST['accion'] === 'ejecutar') {
        $resultado = $importador->ejecutarImportacion();
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['accion'] === 'estadisticas') {
        $estadisticas = $importador->getEstadisticas();
        echo json_encode($estadisticas);
        exit;
    }
}

// Si no es AJAX, mostrar la página HTML
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
    </div>

    <script>
        // Cargar estadísticas al iniciar
        document.addEventListener('DOMContentLoaded', cargarEstadisticas);
        
        function cargarEstadisticas() {
            const formData = new FormData();
            formData.append('accion', 'estadisticas');
            
            fetch('ui_importador.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
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
            })
            .catch(error => {
                console.error('Error cargando estadísticas:', error);
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
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
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
                
                // Actualizar estadísticas después de la importación
                if (data.success) {
                    setTimeout(cargarEstadisticas, 1000);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                btn.disabled = false;
                resultado.innerHTML = `<div class="card error">❌ Error: ${error.message}</div>`;
                console.error('Error ejecutando importación:', error);
            });
        }
        
        function verLogs() {
            window.open('/reports/logs/importacion.log', '_blank');
        }
    </script>
</body>
</html>