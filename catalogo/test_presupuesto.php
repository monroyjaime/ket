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

/<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$codigosStr = 'SW09C187,SW10C250';
echo "<h2>Test de consulta</h2>";

require_once("../php/dbcat.php");
$db = new DB();

// Escapar valores
$codigos = explode(',', $codigosStr);
$codigos_escapados = array();
foreach ($codigos as $codigo) {
    $codigos_escapados[] = "'" . pg_escape_string($db->getLink(), trim($codigo)) . "'";
}
$codigos_lista = implode(',', $codigos_escapados);

$field_order = "FIELD(p.code, $codigos_lista)";

$query = "SELECT p.code, p.name, p.photo_url, d.img_route 
          FROM productos p
          JOIN departamentos d ON p.dpto_id = d.id
          WHERE p.code IN ($codigos_lista)
            AND p.show = true
            AND p.cost_max > 0
          ORDER BY $field_order";

echo "Query: " . htmlspecialchars($query) . "<br><br>";

$result = pg_query($db->getLink(), $query);

if (!$result) {
    echo "Error: " . pg_last_error($db->getLink());
} else {
    $rows = pg_fetch_all($result);
    echo "Productos encontrados: " . count($rows) . "<br>";
    foreach ($rows as $row) {
        echo $row['code'] . " - " . $row['name'] . "<br>";
    }
}
?>