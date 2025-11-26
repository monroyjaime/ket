<?php
// ui_debug.php - VERSIÓN CON DEBUG

// LOG PARA DEBUG
$debug_log = '/var/www/html/reports/logs/ui_debug.log';

function debug_log($message) {
    global $debug_log;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debug_log, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

debug_log("=== INICIANDO UI DEBUG ===");

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    debug_log("POST recibido: " . $_POST['accion']);
    
    // LIMPIAR TODO OUTPUT
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    if ($_POST['accion'] === 'estadisticas') {
        debug_log("Procesando estadísticas...");
        
        try {
            debug_log("Incluyendo dbcat.php...");
            require_once("dbcat.php");
            debug_log("dbcat.php incluido OK");
            
            debug_log("Creando objeto DB...");
            $db = new DB();
            debug_log("Objeto DB creado OK");
            
            debug_log("Ejecutando consulta total productos...");
            $result = $db->consultas("SELECT COUNT(*) as total FROM productos");
            $total_productos = $result[0]->total;
            debug_log("Total productos: $total_productos");
            
            $result = $db->consultas("SELECT COUNT(*) as con_stock FROM productos WHERE current_stock > 0");
            $con_stock = $result[0]->con_stock;
            debug_log("Con stock: $con_stock");
            
            $result = $db->consultas("SELECT MAX(updated_at) as ultima_actualizacion FROM productos");
            $ultima_actualizacion = $result[0]->ultima_actualizacion ?? 'Nunca';
            debug_log("Última actualización: $ultima_actualizacion");
            
            $response = [
                'success' => true,
                'total_productos' => $total_productos,
                'con_stock' => $con_stock,
                'ultima_actualizacion' => $ultima_actualizacion
            ];
            
            debug_log("Enviando respuesta: " . json_encode($response));
            echo json_encode($response);
            
        } catch (Exception $e) {
            debug_log("ERROR: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        debug_log("=== FIN ESTADÍSTICAS ===");
        exit;
    }
}

// SI NO ES AJAX, MOSTRAR HTML SIMPLE
debug_log("Mostrando HTML normal");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Debug Importador</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .btn { background: #007bff; color: white; padding: 10px; border: none; cursor: pointer; margin: 5px; }
        .result { margin: 10px 0; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>🐛 Debug Importador</h1>
    
    <div>
        <button class="btn" onclick="testSimple()">1. Test Simple</button>
        <button class="btn" onclick="testEstadisticas()">2. Test Estadísticas</button>
        <button class="btn" onclick="verDebugLog()">3. Ver Debug Log</button>
    </div>
    
    <div id="result"></div>

    <script>
        function testSimple() {
            document.getElementById('result').innerHTML = 
                '<div class="result">✅ JavaScript funcionando</div>';
        }
        
        function testEstadisticas() {
            const formData = new FormData();
            formData.append('accion', 'estadisticas');
            
            fetch('ui_debug.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                document.getElementById('result').innerHTML = 
                    '<div class="result">Response status: ' + response.status + '</div>';
                return response.text();
            })
            .then(text => {
                document.getElementById('result').innerHTML += 
                    '<div class="result">Raw response: ' + text + '</div>';
            })
            .catch(error => {
                document.getElementById('result').innerHTML = 
                    '<div class="result" style="color:red">Error: ' + error + '</div>';
            });
        }
        
        function verDebugLog() {
            window.open('/reports/logs/ui_debug.log', '_blank');
        }
    </script>
</body>
</html>