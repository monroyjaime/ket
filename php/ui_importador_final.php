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
    
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    return $log_entry;
}

// MANEJAR SOLICITUDES AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    if (ob_get_level()) ob_clean();
    
    if ($_POST['accion'] === 'estadisticas') {
        try {
            require_once("dbcat.php");
            $db = new DB();
            
            $result = $db->consultas("SELECT COUNT(*) as total FROM productos");
            $total_productos = $result[0]->total ?? 0;
            
            $result = $db->consultas("SELECT COUNT(*) as con_stock FROM productos WHERE current_stock > 0");
            $con_stock = $result[0]->con_stock ?? 0;
            
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
            $script_path = '/var/www/html/php/importa_google_sheets.php';
            
            if (!file_exists($script_path)) {
                throw new Exception("Script no encontrado: $script_path");
            }
            
            $output = [];
            $return_code = 0;
            
            chdir('/var/www/html/php/');
            $command = "timeout 120 php " . escapeshellarg($script_path) . " 2>&1";
            exec($command, $output, $return_code);
            
            web_log("📋 Output recibido: " . count($output) . " líneas");
            web_log("🔚 Código de retorno: " . $return_code);
            
            // FILTRAR LÍNEAS RELEVANTES - VERSIÓN MEJORADA CON RESUMEN
            $filtered_output = [];
            foreach ($output as $line) {
                // Incluir TODAS las líneas del resumen especial de update_productos
                if (strpos($line, '========================================') !== false ||
                    strpos($line, 'RESUMEN FINAL') !== false ||
                    strpos($line, 'PROCESO COMPLETADO - RESUMEN:') !== false ||
                    strpos($line, '📝 Descripciones actualizadas:') !== false ||
                    strpos($line, '💰 Precios actualizados:') !== false ||
                    strpos($line, '📦 Stocks actualizados:') !== false ||
                    strpos($line, '🆕 Productos nuevos:') !== false ||
                    strpos($line, '🗑️ Productos eliminados:') !== false ||
                    strpos($line, '⏰ Tiempo total:') !== false ||
                    strpos($line, '✅') !== false || 
                    strpos($line, '❌') !== false || 
                    strpos($line, '🚀') !== false ||
                    strpos($line, '🎉') !== false ||
                    strpos($line, '📝') !== false ||
                    strpos($line, '💾') !== false) {
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #CCC;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .header-top {
            background-color: #CCC;
            padding: 10px 0;
            border-bottom: 1px solid #999;
        }
        .header-main {
            background-color: #037C79;
            color: white;
            padding: 15px 0;
            text-align: center;
        }
        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .content-area {
            padding: 30px;
        }
        .card-custom {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #007bff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-custom.success {
            border-left-color: #28a745;
        }
        .card-custom.error {
            border-left-color: #dc3545;
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
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
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
        .btn-custom {
            margin: 5px;
        }
        .loading {
            text-align: center;
            padding: 30px;
            display: none;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
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
        .progress-bar-custom {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 15px 0;
            display: none;
        }
        .progress-custom {
            height: 100%;
            background: #007bff;
            width: 0%;
            transition: width 0.3s;
        }
        /* ESTILOS PARA EL ÍCONO DE FLECHA */
        .icon-dark-blue {
            color: #037C79 !important;
        }
        .icon-large {
            font-size: 2rem !important;
        }
        .alert-custom {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-weight: 500;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Header Superior -->
    <div class="header-top">
        <div class="container-fluid">
            <div class="row align-items-center">
                <!-- Ícono de flecha izquierda -->
                <div class="col-auto">
                    <a href="#" onclick="history.back()" title="Página anterior">
                        <i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i>
                    </a>
                </div>
                
                <!-- Logo centrado -->
                <div class="col text-end">
                    <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" style="max-height: 40px;">
                </div>
                
                <!-- Espacio balanceado (invisible) -->
                <div class="col-auto" style="visibility: hidden;">
                    <i class="bi bi-arrow-left-circle-fill icon-large"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Principal -->
    <div class="header-main">
        <div class="container-fluid">
            <h2 class="mb-0">🔄 Sistema de Importación</h2>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="main-container">
        <div class="content-area">
            <div class="text-center mb-4">
                <h4 class="text-muted">Sincronización automatizada Google Sheets → PostgreSQL</h4>
            </div>
            
            <!-- Estadísticas -->
            <div class="card-custom">
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
                <button class="btn btn-primary btn-custom" onclick="cargarEstadisticas()">
                    🔄 Actualizar Estadísticas
                </button>
            </div>
            
            <!-- Control de Importación -->
            <div class="card-custom">
                <h3>⚡ Control de Sincronización</h3>
                <p class="mb-3">Ejecuta manualmente el proceso de importación desde Google Sheets</p>
                
                <div class="alert alert-info" id="infoAlert" style="display: none;"></div>
                
                <div class="mb-3">
                    <button class="btn btn-success btn-custom" id="btnEjecutar" onclick="ejecutarImportacion()">
                        🚀 Ejecutar Importación Manual
                    </button>
                    <button class="btn btn-warning btn-custom" onclick="verLogsCompletos()">
                        📋 Ver Logs Completos
                    </button>
                    <button class="btn btn-secondary btn-custom" onclick="limpiarResultados()">
                        🧹 Limpiar Resultados
                    </button>
                </div>
                
                <div class="progress-bar-custom" id="progressBar">
                    <div class="progress-custom" id="progress"></div>
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
        
        <div class="text-center py-3 bg-light">
            <p class="mb-0 text-muted">Sistema de Importación Automatizado | <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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
                    mostrarAlerta('❌ Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('❌ Error cargando estadísticas', 'danger');
            });
        }
        
        function ejecutarImportacion() {
            if (importacionEnCurso) {
                mostrarAlerta('⏳ Ya hay una importación en curso', 'warning');
                return;
            }
            
            const btn = document.getElementById('btnEjecutar');
            const loading = document.getElementById('loading');
            const progressBar = document.getElementById('progressBar');
            const progress = document.getElementById('progress');
            const resultado = document.getElementById('resultado');
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Ejecutando...';
            loading.style.display = 'block';
            progressBar.style.display = 'block';
            resultado.innerHTML = '';
            importacionEnCurso = true;
            
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
                        <div class="card-custom ${cardClass}">
                            <h3>${icon} ${data.message}</h3>
                            <div class="output">${Array.isArray(data.output) ? data.output.join('\n') : data.output}</div>
                            <p><strong>Código de retorno:</strong> ${data.return_code || 'N/A'}</p>
                        </div>
                    `;
                    
                    if (data.success) {
                        mostrarAlerta('🎉 Importación completada exitosamente', 'success');
                        setTimeout(cargarEstadisticas, 1000);
                    } else {
                        mostrarAlerta('❌ Error en la importación', 'danger');
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
                    <div class="card-custom error">
                        <h3>❌ Error de Conexión</h3>
                        <div class="output">No se pudo conectar con el servidor: ${error.message}</div>
                    </div>
                `;
                mostrarAlerta('❌ Error de conexión con el servidor', 'danger');
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