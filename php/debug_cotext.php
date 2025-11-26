<?php
// debug_context.php
header('Content-Type: text/plain');

echo "=== DEBUG CONTEXTO WEB ===\n\n";

// 1. Información del usuario
echo "Usuario: " . exec('whoami') . "\n";
echo "UID: " . posix_getuid() . "\n";
echo "GID: " . posix_getgid() . "\n\n";

// 2. Variables de entorno
echo "=== VARIABLES DE ENTORNO ===\n";
echo "PATH: " . getenv('PATH') . "\n";
echo "PWD: " . getenv('PWD') . "\n";
echo "USER: " . getenv('USER') . "\n\n";

// 3. Permisos de archivos
$scriptPath = '/var/www/html/php/importa_google_sheets.php';
echo "=== PERMISOS ARCHIVO ===\n";
echo "Script: $scriptPath\n";
echo "Existe: " . (file_exists($scriptPath) ? 'Sí' : 'No') . "\n";
echo "Legible: " . (is_readable($scriptPath) ? 'Sí' : 'No') . "\n";
echo "Ejecutable: " . (is_executable($scriptPath) ? 'Sí' : 'No') . "\n";
echo "Permisos: " . substr(sprintf('%o', fileperms($scriptPath)), -4) . "\n\n";

// 4. Test de ejecución simple
echo "=== TEST EJECUCIÓN SIMPLE ===\n";
$testOutput = [];
$testCode = 0;
exec("php -r 'echo \"PHP funciona\\n\";' 2>&1", $testOutput, $testCode);
echo "Código: $testCode\n";
echo "Output: " . implode("\n", $testOutput) . "\n\n";

// 5. Test de Google Sheets (solo la parte de descarga)
echo "=== TEST GOOGLE SHEETS (solo descarga) ===\n";
try {
    $url = 'https://script.google.com/macros/s/TU_ID/exec'; // Tu URL real
    $context = stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        'http' => ['timeout' => 10]
    ]);
    
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        echo "❌ Error descargando de Google Sheets\n";
        $error = error_get_last();
        echo "Error: " . ($error['message'] ?? 'Desconocido') . "\n";
    } else {
        echo "✅ Descarga exitosa: " . strlen($data) . " bytes\n";
    }
} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}
?>