<?php
// debug_500.php - Para ver el error real
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== INICIANDO DEBUG 500 ===<br>";

// Probar cada paso
try {
    echo "1. Incluyendo dbcat.php...<br>";
    require_once("dbcat.php");
    echo "✅ dbcat.php incluido<br>";
    
    echo "2. Creando objeto DB...<br>";
    $db = new DB();
    echo "✅ Objeto DB creado<br>";
    
    echo "3. Ejecutando consulta...<br>";
    $result = $db->consultas("SELECT COUNT(*) as total FROM productos");
    echo "✅ Consulta ejecutada: " . $result[0]->total . "<br>";
    
    echo "🎉 TODO FUNCIONA<br>";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

echo "=== FIN DEBUG ===";
?>