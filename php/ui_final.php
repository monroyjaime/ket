<?php
// ui_final.php - VERSIÓN QUE NO DEPENDE DE ARCHIVOS

// Procesar AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    // LIMPIAR TODO OUTPUT
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    if ($_POST['accion'] === 'estadisticas') {
        try {
            // Incluir con captura de posibles outputs
            ob_start();
            require_once("dbcat.php");
            $output = ob_get_clean();
            
            if (!empty($output)) {
                throw new Exception("dbcat.php generó output: " . $output);
            }
            
            $db = new DB();
            
            // Consulta simple y segura
            $result = $db->consultas("SELECT COUNT(*) as total FROM productos");
            $total = $result[0]->total;
            
            $result = $db->consultas("SELECT COUNT(*) as con_stock FROM productos WHERE current_stock > 0");
            $con_stock = $result[0]->con_stock;
            
            $result = $db->consultas("SELECT MAX(updated_at) as ultima FROM productos");
            $ultima = $result[0]->ultima ?? 'Nunca';
            
            echo json_encode([
                'success' => true,
                'total_productos' => (int)$total,
                'con_stock' => (int)$con_stock,
                'ultima_actualizacion' => $ultima
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
        // Ejecutar el script y capturar resultado
        $output = [];
        $returnCode = 0;
        $scriptPath = '/var/www/html/php/importa_google_sheets.php';
        
        exec("php " . escapeshellarg($scriptPath) . " 2>&1", $output, $returnCode);
        
        // Filtrar solo líneas importantes
        $importantLines = array_filter($output, function($line) {
            return strpos($line, '✅') !== false || 
                   strpos($line, '❌') !== false ||
                   strpos($line, '🚀') !== false ||
                   strpos($line, '🎉') !== false;
        });
        
        echo json_encode([
            'success' => ($returnCode === 0),
            'output' => array_values($importantLines),
            'return_code' => $returnCode
        ]);
        exit;
    }
}
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
        </div>
        
        <div class="loading" id="loading">
            <p>⏳ Ejecutando importación, por favor espere...</p>
        </div>
        
        <div id="resultado"></div>
    </div>

    <script>
        function cargarEstadisticas() {
            const formData = new FormData();
            formData.append('accion', 'estadisticas');
            
            fetch('ui_final.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
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
                alert('Error cargando estadísticas: ' + error.message);
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
            
            fetch('ui_final.php', {
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
                        <h3>${icon} ${data.success ? 'Importación Exitosa' : 'Error en Importación'}</h3>
                        <div class="output">${data.output.join('\n')}</div>
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
            });
        }
        
        // Cargar estadísticas al iniciar
        document.addEventListener('DOMContentLoaded', cargarEstadisticas);
    </script>
</body>
</html>