<?php
// error_log_test.php - Para ver qué error está ocurriendo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Escribir en un archivo de log
$logFile = '/tmp/php_upload_error.log';
file_put_contents($logFile, "=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($logFile, "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents($logFile, "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'none') . "\n", FILE_APPEND);
file_put_contents($logFile, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($logFile, "FILES: " . print_r($_FILES, true) . "\n", FILE_APPEND);

// Verificar si hay errores de PHP
$error = error_get_last();
if ($error) {
    file_put_contents($logFile, "PHP ERROR: " . print_r($error, true) . "\n", FILE_APPEND);
}

// Intentar incluir dbcat.php para ver si ese es el problema
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$dbPath = $docRoot . "/php/dbcat.php";

file_put_contents($logFile, "dbcat.php path: " . $dbPath . "\n", FILE_APPEND);
file_put_contents($logFile, "dbcat.php exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n", FILE_APPEND);

if (file_exists($dbPath)) {
    require_once($dbPath);
    file_put_contents($logFile, "dbcat.php included OK\n", FILE_APPEND);
} else {
    file_put_contents($logFile, "dbcat.php NOT FOUND\n", FILE_APPEND);
}

// Devolver una respuesta simple
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Log creado',
    'log_file' => $logFile
]);
?>