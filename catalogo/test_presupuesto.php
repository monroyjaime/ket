<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$codigosStr = isset($_GET['codigos']) ? $_GET['codigos'] : 'SW09C187,SW10C250';
echo "=== TEST PRESUPUESTO ===<br>";
echo "Códigos recibidos: " . htmlspecialchars($codigosStr) . "<br><br>";

require_once("../php/dbcat.php");
$db = new DB();

// Verificar conexión
echo "1. Verificando conexión a BD...<br>";
$conn = $db->getConnection();
if ($conn) {
    echo "   ✅ Conexión OK<br><br>";
} else {
    echo "   ❌ Error de conexión<br><br>";
    exit;
}

// Probar consulta simple
echo "2. Probando consulta simple...<br>";
$result = pg_query($conn, "SELECT 1 as test");
$row = pg_fetch_assoc($result);
echo "   Resultado: " . $row['test'] . "<br><br>";

// Probar consulta con los códigos
echo "3. Buscando productos...<br>";
$codigos = explode(',', $codigosStr);
$placeholders = array();
$params = array();
foreach ($codigos as $i => $codigo) {
    $placeholders[] = '$' . ($i + 1);
    $params[] = trim($codigo);
}

$query = "SELECT p.code, p.name, p.photo_url, d.img_route 
          FROM productos p
          JOIN departamentos d ON p.dpto_id = d.id
          WHERE p.code IN (" . implode(',', $placeholders) . ")
            AND p.show = true
            AND p.photo_url != 'empty.jpg'
            AND p.cost_max > 0
          LIMIT 10";

echo "   Query: " . $query . "<br>";
echo "   Params: " . implode(', ', $params) . "<br>";

$result = pg_query_params($conn, $query, $params);

if (!$result) {
    echo "   ❌ Error: " . pg_last_error($conn) . "<br>";
} else {
    $rows = pg_fetch_all($result);
    echo "   ✅ Productos encontrados: " . count($rows) . "<br>";
    foreach ($rows as $row) {
        echo "      - " . $row['code'] . ": " . $row['name'] . "<br>";
    }
}
?>