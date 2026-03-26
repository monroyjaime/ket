<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$codigosStr = 'SW09C187,SW10C250';
echo "<h2>Test de productos</h2>";

require_once("../php/dbcat.php");
$db = new DB();
$conn = $db->getLink();

$codigos = explode(',', $codigosStr);
$codigos_escapados = array();
foreach ($codigos as $codigo) {
    $codigos_escapados[] = "'" . pg_escape_string($conn, trim($codigo)) . "'";
}
$codigos_lista = implode(',', $codigos_escapados);

// Consulta SIN JOIN de departamentos primero
$query = "SELECT code, name, photo_url, cost_max 
          FROM productos 
          WHERE code IN ($codigos_lista)
            AND show = true
            AND cost_max > 0";

echo "Query: " . htmlspecialchars($query) . "<br><br>";

$result = pg_query($conn, $query);

if (!$result) {
    echo "Error: " . pg_last_error($conn);
} else {
    $rows = pg_fetch_all($result);
    echo "Productos encontrados: " . count($rows) . "<br>";
    foreach ($rows as $row) {
        echo "<div style='border:1px solid #ccc; margin:10px; padding:10px; display:inline-block; width:200px;'>";
        echo "<strong>" . $row['code'] . "</strong><br>";
        echo $row['name'] . "<br>";
        echo "Foto: " . ($row['photo_url'] ?: 'empty.jpg') . "<br>";
        echo "Precio: $" . number_format($row['cost_max'], 3, ',', '.') . "<br>";
        echo "</div>";
    }
}
?>