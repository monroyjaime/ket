<?php
// path_info.php - Ver rutas reales del servidor
echo "<h2>Información de Rutas</h2>";

echo "<h3>Directorios:</h3>";
echo "Script actual: " . __FILE__ . "<br>";
echo "Directorio del script: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

echo "<h3>Probando rutas:</h3>";

$paths = [
    '../../php/dbcat.php',
    '../php/dbcat.php',
    'php/dbcat.php',
    $_SERVER['DOCUMENT_ROOT'] . '/php/dbcat.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../php/dbcat.php',
];

foreach ($paths as $path) {
    $exists = file_exists($path) ? '✅ EXISTE' : '❌ NO EXISTE';
    echo "$path - $exists<br>";
}

echo "<h3>Contenido de /var/www/html/php/ (si existe):</h3>";
$phpDir = $_SERVER['DOCUMENT_ROOT'] . '/php';
if (is_dir($phpDir)) {
    echo "<pre>";
    print_r(scandir($phpDir));
    echo "</pre>";
} else {
    echo "El directorio $phpDir NO existe<br>";
}

echo "<h3>Contenido de /var/www/html/admin/:</h3>";
$adminDir = $_SERVER['DOCUMENT_ROOT'] . '/admin';
if (is_dir($adminDir)) {
    echo "<pre>";
    print_r(scandir($adminDir));
    echo "</pre>";
} else {
    echo "El directorio $adminDir NO existe<br>";
}
?>