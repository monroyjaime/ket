<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Iniciando test...<br>";

require_once("../php/dbcat.php");
echo "2. dbcat.php cargado<br>";

$db = new DB();
echo "3. DB instanciado<br>";

$conn = $db->getLink();
echo "4. Conexión obtenida<br>";

if ($conn) {
    echo "5. Conexión OK<br>";
    
    $result = pg_query($conn, "SELECT 1 as test");
    if ($result) {
        $row = pg_fetch_assoc($result);
        echo "6. Query simple OK: " . $row['test'] . "<br>";
    } else {
        echo "6. Error en query: " . pg_last_error($conn) . "<br>";
    }
} else {
    echo "5. Error de conexión<br>";
}
?>