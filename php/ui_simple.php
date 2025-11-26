<?php
// ui_simple.php - Versión minimalista para debug

// 1. PRIMERO: Probar solo las estadísticas (lo más simple)
if (isset($_GET['test'])) {
    header('Content-Type: application/json');
    
    try {
        require_once("dbcat.php");
        $db = new DB();
        
        $result = $db->consultas("SELECT COUNT(*) as total FROM productos");
        $total = $result[0]->total;
        
        echo json_encode([
            'success' => true,
            'total_productos' => $total,
            'message' => 'Conexión a BD exitosa'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 2. SEGUNDO: Probar ejecución simple
if (isset($_GET['run_simple'])) {
    header('Content-Type: application/json');
    
    try {
        // Ejecutar comando simple primero
        $output = [];
        exec("php -v 2>&1", $output, $returnCode);
        
        echo json_encode([
            'success' => true,
            'output' => $output,
            'return_code' => $returnCode,
            'message' => 'Comando ejecutado'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 3. HTML SIMPLE
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Importador</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .btn { background: #007bff; color: white; padding: 10px; border: none; cursor: pointer; margin: 5px; }
        .result { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>🧪 Test Importador</h1>
    
    <div>
        <button class="btn" onclick="testBD()">1. Test Base de Datos</button>
        <button class="btn" onclick="testComando()">2. Test Comando PHP</button>
        <button class="btn" onclick="testImportacion()">3. Test Importación Completa</button>
    </div>
    
    <div id="result"></div>

    <script>
        function testBD() {
            fetch('ui_simple.php?test=1')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('result').innerHTML = 
                        '<div class="result">' + JSON.stringify(data, null, 2) + '</div>';
                })
                .catch(err => {
                    document.getElementById('result').innerHTML = 
                        '<div class="result" style="color:red">Error: ' + err + '</div>';
                });
        }
        
        function testComando() {
            fetch('ui_simple.php?run_simple=1')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('result').innerHTML = 
                        '<div class="result">' + JSON.stringify(data, null, 2) + '</div>';
                })
                .catch(err => {
                    document.getElementById('result').innerHTML = 
                        '<div class="result" style="color:red">Error: ' + err + '</div>';
                });
        }
        
        function testImportacion() {
            // Ejecutar el script real pero capturando errores
            fetch('ui_simple.php?run_import=1')
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        document.getElementById('result').innerHTML = 
                            '<div class="result">' + JSON.stringify(data, null, 2) + '</div>';
                    } catch (e) {
                        document.getElementById('result').innerHTML = 
                            '<div class="result" style="color:red">JSON Inválido: ' + text.substring(0, 200) + '</div>';
                    }
                })
                .catch(err => {
                    document.getElementById('result').innerHTML = 
                        '<div class="result" style="color:red">Error: ' + err + '</div>';
                });
        }
    </script>
</body>
</html>