<?php
// ui_simple.php - Versión mejorada

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

// 3. TEST IMPORTACIÓN CON CAPTURA DE ERRORES
if (isset($_GET['run_import'])) {
    header('Content-Type: application/json');
    
    try {
        $scriptPath = '/var/www/html/php/importa_google_sheets.php';
        
        // Verificar que el archivo existe
        if (!file_exists($scriptPath)) {
            throw new Exception("Archivo no encontrado: " . $scriptPath);
        }
        
        // Ejecutar con captura completa de output
        $output = [];
        $returnCode = 0;
        
        // Usar shell_exec para capturar TODO el output
        $fullOutput = shell_exec("php " . escapeshellarg($scriptPath) . " 2>&1");
        
        // Si shell_exec devuelve null, hubo un error
        if ($fullOutput === null) {
            throw new Exception("Error ejecutando el script (shell_exec devolvió null)");
        }
        
        // Dividir en líneas y filtrar
        $outputLines = explode("\n", $fullOutput);
        $cleanOutput = [];
        
        foreach ($outputLines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                $cleanOutput[] = $trimmed;
            }
        }
        
        // Tomar solo las últimas 10 líneas para no saturar
        $cleanOutput = array_slice($cleanOutput, -10);
        
        echo json_encode([
            'success' => true,
            'output' => $cleanOutput,
            'total_lines' => count($outputLines),
            'message' => 'Importación ejecutada'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'script_path' => $scriptPath ?? 'No definido',
            'file_exists' => file_exists($scriptPath ?? '') ? 'Sí' : 'No'
        ]);
    }
    exit;
}

// HTML SIMPLE
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Importador</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .btn { background: #007bff; color: white; padding: 10px; border: none; cursor: pointer; margin: 5px; }
        .result { margin: 10px 0; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap; font-family: monospace; }
        .success { border-color: green; background: #f0fff0; }
        .error { border-color: red; background: #fff0f0; }
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
        function showResult(data, isError = false) {
            const resultDiv = document.getElementById('result');
            const className = isError ? 'result error' : 'result success';
            resultDiv.innerHTML = `<div class="${className}">${JSON.stringify(data, null, 2)}</div>`;
        }
        
        function testBD() {
            fetch('ui_simple.php?test=1')
                .then(r => r.json())
                .then(data => showResult(data))
                .catch(err => showResult({error: err.message}, true));
        }
        
        function testComando() {
            fetch('ui_simple.php?run_simple=1')
                .then(r => r.json())
                .then(data => showResult(data))
                .catch(err => showResult({error: err.message}, true));
        }
        
        function testImportacion() {
            fetch('ui_simple.php?run_import=1')
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(data => showResult(data))
                .catch(err => showResult({error: err.message}, true));
        }
    </script>
</body>
</html>