<?php
// test_error.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Probar include de dbcat.php
try {
    require_once("dbcat.php");
    echo "✅ dbcat.php cargado correctamente<br>";
    
    $db = new DB();
    echo "✅ Objeto DB creado correctamente<br>";
    
    $result = $db->consultas("SELECT 1 as test");
    echo "✅ Consulta a BD exitosa<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Probar ejecución de comando
try {
    $output = [];
    exec("php -v", $output, $returnCode);
    echo "✅ Comando PHP ejecutado: " . implode("<br>", $output) . "<br>";
} catch (Exception $e) {
    echo "❌ Error en comando: " . $e->getMessage() . "<br>";
}
?>