<?php
// ui_importador_final.php - VERSIÓN MEJORADA Y FUNCIONAL
header('Content-Type: text/html; charset=utf-8');

// CONFIGURACIÓN PARA WEB
set_time_limit(180);
ini_set('max_execution_time', 180);
ini_set('memory_limit', '256M');

// FUNCIÓN DE LOG SIMPLIFICADA
function web_log($message) {
    $log_file = '/var/www/html/reports/logs/ui_importador.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    // Crear directorio si no existe
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    return $log_entry;
}

// MANEJAR SOLICITUDES AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    // LIMPIAR BUFFER
    if (ob_get_level()) ob_clean();
    
    if ($_POST['accion'] === 'estadisticas') {
        try {
            require_once("dbcat.php");
            $db = new DB();
            
            // Estadísticas básicas
            $result = $db->consultas("SELECT COUNT(*) as total FROM productos");
            $total_productos = $result[0]->total ?? 0;
            
            $result = $db->consultas("SELECT COUNT(*) as con_stock FROM productos WHERE current_stock > 0");
            $con_stock = $result[0]->con_stock ?? 0;
            
            // Última actualización desde logs
            $log_file = '/var/www/html/reports/logs/importacion_detalle.log';
            $ultima_actualizacion = 'Nunca';
            if (file_exists($log_file)) {
                $logs = file($log_file);
                $last_line = end($logs);
                if (strpos($last_line, '🎉 PROCESO COMPLETADO') !== false) {
                    preg_match('/\[(.*?)\]/', $last_line, $matches);
                    $ultima_actualizacion = $matches[1] ?? 'Reciente';
                }
            }
            
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
        web_log("🚀 EJECUCIÓN MANUAL INICIADA DESDE UI");
        
        try {
            // EJECUCIÓN DIRECTA - NO EN BACKGROUND
            $script_path = '/var/www/html/php/importa_google_sheets.php';
            
            if (!file_exists($script_path)) {
                throw new Exception("Script no encontrado: $script_path");
            }
            
            // Ejecutar y capturar output
            $output = [];
            $return_code = 0;
            
            // Cambiar al directorio correcto
            chdir('/var/www/html/php/');
            
            // Ejecutar con timeout
            $command = "timeout 120 php " . escapeshellarg($script_path) . " 2>&1";
            exec($command, $output, $return_code);
            
            web_log("📋 Output recibido: " . count($output) . " líneas");
            web_log("🔚 Código de retorno: " . $return_code);
            
            // Filtrar líneas relevantes
            $filtered_output = [];
            foreach ($output as $line) {
                if (strpos($line, '✅') !== false || 
                    strpos($line, '❌') !== false || 
                    strpos($line, '🚀') !== false ||
                    strpos($line, '🎉') !== false ||
                    strpos($line, '📝') !== false) {
                    $filtered_output[] = $line;
                }
            }
            
            // Si no hay output relevante, mostrar las últimas líneas
            if (empty($filtered_output)) {
                $filtered_output = array_slice($output, -10);
            }
            
            $success = ($return_code === 0);
            
            echo json_encode([
                'success' => $success,
                'output' => $filtered_output,
                'return_code' => $return_code,
                'message' => $success ? 'Importación completada exitosamente' : 'Error en la importación'
            ]);
            
        } catch (Exception $e) {
            web_log("❌ ERROR: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'output' => ['❌ Error: ' . $e->getMessage()]
            ]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Productos - Sistema de Sincronización</title>
    <style>
        :root {
            --primary: #007bff;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --dark: #343a40;
            --light: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: var(--dark);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .content {
            padding: 30px;
        }
        
        .card {
            background: var(--light);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid var(--primary);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .card.success {
            border-left-color: var(--success);
        }
        
        .card.error {
            border-left-color: var(--danger);
        }
        
        .card h3 {
            color: var(--dark);
            margin-bottom: 15px;
            font-size: 1.4em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-warning {
            background: var(--warning);
            color: var(--dark);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .loading {
            text-align: center;
            padding: 30px;
            display: none;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .output {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
            margin: 15px 0;
            font-size: 0.9em;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: var(--light);
            color: var(--dark);
            font-size: 0.9em;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 15px 0;
            display: none;
        }
        
        .progress {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Sistema de Importación</h1>
            <p>Sincronización automatizada Google Sheets → PostgreSQL</p>
        </div>
        
        <div class="content">
            <!-- Estadísticas -->
            <div class="card">
                <h3>📊 Dashboard de Productos</h3>
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
                        <div>Última Sincronización</div>
                    </div>
                </div>
                <button class="btn" onclick="cargarEstadisticas()">
                    🔄 Actualizar Estadísticas
                </button>
            </div>
            
            <!-- Control de Importación -->
            <div class="card">
                <h3>⚡ Control de Sincronización</h3>
                <p>Ejecuta manualmente el proceso de importación desde Google Sheets</p>
                
                <div class="alert" id="infoAlert" style="display: none;"></div>
                
                <button class="btn btn-success" id="btnEjecutar" onclick="ejecutarImportacion()">
                    🚀 Ejecutar Importación Manual
                </button>
                <button class="btn btn-warning" onclick="verLogsCompletos()">
                    📋 Ver Logs Completos
                </button>
                <button class="btn" onclick="limpiarResultados()">
                    🧹 Limpiar Resultados
                </button>
                
                <div class="progress-bar" id="progressBar">
                    <div class="progress" id="progress"></div>
                </div>
            </div>
            
            <!-- Loading -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>⏳ Ejecutando importación, por favor espere...</p>
                <p><small>Este proceso puede tomar hasta 2 minutos</small></p>
            </div>
            
            <!-- Resultados -->
            <div id="resultado"></div>
        </div>
        
        <div class="footer">
            <p>Sistema de Importación Automatizado | Última actualización: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>

    <script>
        // Estado de la aplicación
        let importacionEnCurso = false;
        
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
                            <div class="stat-value">${data.ultima_actualizacion}</div>
                            <div>Última Sincronización</div>
                        </div>
                    `;
                    
                    mostrarAlerta('✅ Estadísticas actualizadas correctamente', 'success');
                } else {
                    mostrarAlerta('❌ Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('❌ Error cargando estadísticas', 'error');
            });
        }
        
        function ejecutarImportacion() {
            if (importacionEnCurso) {
                mostrarAlerta('⏳ Ya hay una importación en curso', 'error');
                return;
            }
            
            const btn = document.getElementById('btnEjecutar');
            const loading = document.getElementById('loading');
            const progressBar = document.getElementById('progressBar');
            const progress = document.getElementById('progress');
            const resultado = document.getElementById('resultado');
            
            // Configurar UI
            btn.disabled = true;
            btn.innerHTML = '⏳ Ejecutando...';
            loading.style.display = 'block';
            progressBar.style.display = 'block';
            resultado.innerHTML = '';
            importacionEnCurso = true;
            
            // Animación de progreso
            let progressValue = 0;
            const progressInterval = setInterval(() => {
                progressValue += 0.5;
                progress.style.width = Math.min(progressValue, 90) + '%';
            }, 500);
            
            const formData = new FormData();
            formData.append('accion', 'ejecutar');
            
            fetch('ui_importador_final.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progress.style.width = '100%';
                
                setTimeout(() => {
                    loading.style.display = 'none';
                    progressBar.style.display = 'none';
                    btn.disabled = false;
                    btn.innerHTML = '🚀 Ejecutar Importación Manual';
                    importacionEnCurso = false;
                    
                    const cardClass = data.success ? 'success' : 'error';
                    const icon = data.success ? '✅' : '❌';
                    
                    resultado.innerHTML = `
                        <div class="card ${cardClass}">
                            <h3>${icon} ${data.message}</h3>
                            <div class="output">${Array.isArray(data.output) ? data.output.join('\n') : data.output}</div>
                            <p><strong>Código de retorno:</strong> ${data.return_code || 'N/A'}</p>
                        </div>
                    `;
                    
                    if (data.success) {
                        mostrarAlerta('🎉 Importación completada exitosamente', 'success');
                        // Actualizar estadísticas automáticamente
                        setTimeout(cargarEstadisticas, 1000);
                    } else {
                        mostrarAlerta('❌ Error en la importación', 'error');
                    }
                }, 1000);
            })
            .catch(error => {
                clearInterval(progressInterval);
                loading.style.display = 'none';
                progressBar.style.display = 'none';
                btn.disabled = false;
                btn.innerHTML = '🚀 Ejecutar Importación Manual';
                importacionEnCurso = false;
                
                resultado.innerHTML = `
                    <div class="card error">
                        <h3>❌ Error de Conexión</h3>
                        <div class="output">No se pudo conectar con el servidor: ${error.message}</div>
                    </div>
                `;
                
                mostrarAlerta('❌ Error de conexión con el servidor', 'error');
            });
        }
        
        function mostrarAlerta(mensaje, tipo) {
            const alert = document.getElementById('infoAlert');
            alert.textContent = mensaje;
            alert.className = `alert alert-${tipo}`;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
        
        function verLogsCompletos() {
            window.open('/reports/logs/importacion_detalle.log', '_blank');
        }
        
        function limpiarResultados() {
            document.getElementById('resultado').innerHTML = '';
            document.getElementById('infoAlert').style.display = 'none';
        }
        
        // Cargar estadísticas al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarEstadisticas();
            mostrarAlerta('✅ Sistema cargado correctamente', 'success');
        });
    </script>
</body>
</html>