<?php
// test_endpoint.php - Para ver el error específico
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once("../../php/dbcat.php");

echo "<h2>Prueba de getProductosSinFoto.php</h2>";

// Verificar sesión
echo "<h3>1. Verificación de sesión:</h3>";
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;
echo "usr_admin: " . $isAdmin . "<br>";
echo "role: " . $role . "<br>";

if ($role == -1 || $isAdmin != 1) {
    echo "<span style='color:red'>ACCESO DENEGADO</span>";
    exit;
}
echo "<span style='color:green'>✓ Acceso permitido</span><br>";

// Probar conexión a BD
echo "<h3>2. Conexión a BD:</h3>";
try {
    $db = new DB();
    echo "<span style='color:green'>✓ Conexión OK</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>Error: " . $e->getMessage() . "</span><br>";
    exit;
}

// Probar la consulta completa
echo "<h3>3. Ejecutando consulta principal:</h3>";

$start = 0;
$length = 10;
$searchValue = '';
$draw = 1;

$searchValue = addslashes($searchValue);

$countQuery = "SELECT COUNT(*) as total 
               FROM productos p 
               INNER JOIN departamentos d ON p.dpto_id = d.id
               WHERE p.photo_url IS NULL 
                  OR p.photo_url = '' 
                  OR p.photo_url = 'none'
                  OR p.photo_url = 'empty.jpg'";

echo "Count Query: " . htmlspecialchars($countQuery) . "<br>";

try {
    $resultCount = $db->consultas($countQuery);
    $totalRecords = !empty($resultCount) ? (int)$resultCount[0]->total : 0;
    echo "Total registros: " . $totalRecords . "<br>";
    echo "<span style='color:green'>✓ Count query OK</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>Error en count: " . $e->getMessage() . "</span><br>";
}

$query = "SELECT p.code, 
                 p.name as descripcion, 
                 d.name as departamento,
                 p.photo_url as foto_actual,
                 p.dpto_id
          FROM productos p 
          INNER JOIN departamentos d ON p.dpto_id = d.id
          WHERE p.photo_url IS NULL 
             OR p.photo_url = '' 
             OR p.photo_url = 'none'
             OR p.photo_url = 'empty.jpg'
          ORDER BY d.name ASC, p.code ASC LIMIT $start, $length";

echo "<br>Main Query: " . htmlspecialchars($query) . "<br>";

try {
    $productos = $db->consultas($query);
    
    if (!$productos) {
        echo "No hay resultados o error en consulta<br>";
        $productos = [];
    } else {
        echo "<span style='color:green'>✓ Se encontraron " . count($productos) . " resultados</span><br>";
        
        echo "<h3>4. Primeros resultados:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Código</th><th>Descripción</th><th>Departamento</th><th>Foto actual</th></tr>";
        foreach ($productos as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row->code) . "</td>";
            echo "<td>" . htmlspecialchars($row->descripcion) . "</td>";
            echo "<td>" . htmlspecialchars($row->departamento) . "</td>";
            echo "<td>" . htmlspecialchars($row->foto_actual) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Formatear datos como JSON
    $data = [];
    foreach ($productos as $row) {
        $data[] = [
            'codigo' => $row->code,
            'descripcion' => $row->descripcion,
            'departamento' => $row->departamento,
            'foto_actual' => $row->foto_actual,
            'dpto_id' => $row->dpto_id
        ];
    }
    
    $jsonResponse = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ];
    
    echo "<h3>5. Respuesta JSON que se enviaría:</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($jsonResponse, JSON_PRETTY_PRINT)) . "</pre>";
    
} catch (Exception $e) {
    echo "<span style='color:red'>Error en main query: " . $e->getMessage() . "</span><br>";
    echo "<pre>" . print_r($e, true) . "</pre>";
}
?>