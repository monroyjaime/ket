<?php
// ui_simple.php - Versión con timeout

// 1. PRIMERO: Probar solo las estadísticas
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

// 2. TEST IMPORTACIÓN CON TIMEOUT
if (isset($_GET['run_import'])) {
    header('Content-Type: application/json');
    
    try {
        $scriptPath = '/var/www/html/php/importa_google_sheets.php';
        
        // Verificar que el archivo existe
        if (!file_exists($scriptPath)) {
            throw new Exception("Archivo no encontrado: " . $scriptPath);
        }
        
        // Configurar timeout de 30 segundos
        $timeout = 30;
        $outputFile = '/tmp/import_output_' . time() . '.log';
        $pidFile = '/tmp/import_pid_' . time() . '.pid';
        
        // Comando con timeout
        $command = "timeout {$timeout}s php " . escapeshellarg($scriptPath) . " > " . escapeshellarg($outputFile) . " 2>&1 & echo $!";
        
        // Ejecutar en background y capturar PID
        $pid = shell_exec($command);
        $pid = trim($pid);
        
        // Guardar PID para referencia
        file_put_contents($pidFile, $pid);
        
        // Esperar un poco y verificar resultado
        sleep(5);
        
        // Verificar si el proceso sigue corriendo
        $isRunning = false;
        if ($pid && file_exists("/proc/{$pid}")) {
            $isRunning = true;
        }
        
        // Leer output del archivo
        $output = [];
        if (file_exists($outputFile)) {
            $output = file($outputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            // Tomar solo las últimas 15 líneas
            $output = array_slice($output, -15);
        }
        
        // Limpiar archivos temporales
        if (file_exists($outputFile)) unlink($outputFile);
        if (file_exists($pidFile)) unlink($pidFile);
        
        if ($isRunning) {
            // Si todavía está corriendo después de 5 segundos, probablemente se colgó
            exec("kill -9 " . escapeshellarg($pid)); // Forzar terminación
            throw new Exception("El script se colgó y tuvo que ser terminado forzosamente (timeout)");
        }
        
        if (empty($output)) {
            $output = ["⚠️ El script ejecutó pero no generó output visible"];
        }
        
        echo json_encode([
            'success' => true,
            'output' => $output,
            'pid' => $pid,
            'was_running' => $isRunning,
            'message' => 'Importación completada'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'script_path' => $scriptPath ?? 'No definido'
        ]);
    }
    exit;
}

// 3. TEST DIRECTO DEL SCRIPT (sin ejecutarlo)
if (isset($_GET['check_script'])) {
    header('Content-Type: application/json');
    
    $scriptPath = '/var/www/html/php/importa_google_sheets.php';
    
    $checks = [
        'file_exists' => file_exists($scriptPath),
        'is_readable' => is_readable($scriptPath),
        'file_size' => file_exists($scriptPath) ? filesize($scriptPath) : 0,
        'syntax_check' => null
    ];
    
    // Verificar sintaxis PHP
    if ($checks['file_exists']) {
        $syntaxOutput = [];
        exec("php -l " . escapeshellarg($scriptPath) . " 2>&1", $syntaxOutput, $syntaxCode);
        $checks['syntax_check'] = [
            'output' => $syntaxOutput,
            'code' => $syntaxCode,
            'valid' => ($syntaxCode === 0)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'checks' => $checks,
        'script_path' => $scriptPath
    ]);
    exit;
}
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
        .loading { display: none; color: blue; }
    </style>
</head>
<body>
    <h1>🧪 Test Importador</h1>
    
    <div>
        <button class="btn" onclick="testBD()">1. Test Base de Datos</button>
        <button class="btn" onclick="checkScript()">2. Verificar Script</button>
        <button class="btn" onclick="testImportacion()">3. Test Importación Completa</button>
    </div>
    
    <div id="loading" class="loading">⏳ Ejecutando, por favor espere (puede tomar hasta 30 segundos)...</div>
    <div id="result"></div>

    <script>
        function showResult(data, isError = false) {
            const resultDiv = document.getElementById('result');
            const className = isError ? 'result error' : 'result success';
            resultDiv.innerHTML = `<div class="${className}">${JSON.stringify(data, null, 2)}</div>`;
        }
        
        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }
        
        function testBD() {
            showLoading(true);
            fetch('ui_simple.php?test=1')
                .then(r => r.json())
                .then(data => showResult(data))
                .catch(err => showResult({error: err.message}, true))
                .finally(() => showLoading(false));
        }
        
        function checkScript() {
            showLoading(true);
            fetch('ui_simple.php?check_script=1')
                .then(r => r.json())
                .then(data => showResult(data))
                .catch(err => showResult({error: err.message}, true))
                .finally(() => showLoading(false));
        }
        
        function testImportacion() {
            showLoading(true);
            fetch('ui_simple.php?run_import=1')
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(data => showResult(data))
                .catch(err => showResult({error: err.message}, true))
                .finally(() => showLoading(false));
        }
    </script>
</body>
</html>