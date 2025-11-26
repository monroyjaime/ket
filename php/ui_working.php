<?php
// ui_working.php - VERSIÓN QUE SÍ FUNCIONA

// MANEJO ESTRICTO DE OUTPUT
function clean_output() {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
}

// ESTADÍSTICAS
if ($_POST['accion'] ?? '' === 'estadisticas') {
    clean_output();
    
    try {
        // Incluir CAPTURANDO cualquier output
        ob_start();
        require_once("dbcat.php");
        $unwanted_output = ob_get_clean();
        
        // Si hubo output no deseado, loguearlo pero continuar
        if (!empty($unwanted_output)) {
            error_log("UI: Output no deseado en dbcat.php: " . $unwanted_output);
        }
        
        $db = new DB();
        
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

// EJECUTAR IMPORTACIÓN
if ($_POST['accion'] ?? '' === 'ejecutar') {
    clean_output();
    
    try {
        $output = [];
        $returnCode = 0;
        $scriptPath = '/var/www/html/php/importa_google_sheets.php';
        
        exec("php " . escapeshellarg($scriptPath) . " 2>&1", $output, $returnCode);
        
        // Filtrar líneas importantes
        $important = array_filter($output, function($line) {
            return strpos($line, '✅') !== false || 
                   strpos($line, '❌') !== false ||
                   strpos($line, '🚀') !== false ||
                   strpos($line, '🎉') !== false;
        });
        
        echo json_encode([
            'success' => ($returnCode === 0),
            'output' => array_values($important),
            'return_code' => $returnCode
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador - Funcional</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .card { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:disabled { background: #6c757d; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .loading { display: none; text-align: center; margin: 20px 0; color: #007bff; }
        .output { background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0; }
        .stat-card { background: #e9ecef; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Importador de Productos</h1>
        <p>Sincroniza datos desde Google Sheets</p>
        
        <div class="card">
            <h3>📊 Estadísticas</h3>
            <div class="stats-grid" id="estadisticas">
                <div class="stat-card"><div class="stat-value">--</div><div>Productos</div></div>
                <div class="stat-card"><div class="stat-value">--</div><div>Con Stock</div></div>
                <div class="stat-card"><div class="stat-value">--</div><div>Actualización</div></div>
            </div>
            <button class="btn" onclick="cargarEstadisticas()">🔄 Actualizar</button>
        </div>
        
        <div class="card">
            <h3>⚡ Ejecutar Importación</h3>
            <button class="btn" id="btnEjecutar" onclick="ejecutarImportacion()">🚀 Ejecutar</button>
        </div>
        
        <div class="loading" id="loading">⏳ Procesando...</div>
        <div id="resultado"></div>
    </div>

    <script>
        function cargarEstadisticas() {
            fetch('ui_working.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'accion=estadisticas'
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('estadisticas').innerHTML = `
                        <div class="stat-card"><div class="stat-value">${data.total_productos}</div><div>Productos</div></div>
                        <div class="stat-card"><div class="stat-value">${data.con_stock}</div><div>Con Stock</div></div>
                        <div class="stat-card"><div class="stat-value">${data.ultima_actualizacion.split(' ')[0]}</div><div>Actualización</div></div>
                    `;
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
            });
        }

        function ejecutarImportacion() {
            const btn = document.getElementById('btnEjecutar');
            const loading = document.getElementById('loading');
            const resultado = document.getElementById('resultado');
            
            btn.disabled = true;
            loading.style.display = 'block';
            resultado.innerHTML = '';
            
            fetch('ui_working.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'accion=ejecutar'
            })
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                btn.disabled = false;
                
                resultado.innerHTML = `
                    <div class="card ${data.success ? 'success' : 'error'}">
                        <h3>${data.success ? '✅ Éxito' : '❌ Error'}</h3>
                        <div class="output">${data.output.join('\n')}</div>
                    </div>
                `;
                
                if (data.success) setTimeout(cargarEstadisticas, 2000);
            })
            .catch(err => {
                loading.style.display = 'none';
                btn.disabled = false;
                resultado.innerHTML = `<div class="card error">❌ Error: ${err.message}</div>`;
            });
        }

        // Cargar al iniciar
        cargarEstadisticas();
    </script>
</body>
</html>