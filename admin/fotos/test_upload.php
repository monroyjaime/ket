<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once("../../php/dbcat.php");

echo "<h2>Test de Subida</h2>";

// Verificar sesión
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

echo "usr_admin: $isAdmin<br>";
echo "role: $role<br>";

if ($role == -1 || $isAdmin != 1) {
    echo "<span style='color:red'>ACCESO DENEGADO</span>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Datos recibidos:</h3>";
    echo "<pre>";
    print_r($_POST);
    print_r($_FILES);
    echo "</pre>";
    
    $codigo = $_POST['codigo'] ?? 'no';
    $dptoId = $_POST['dpto_id'] ?? 'no';
    
    echo "Procesando código: $codigo, dptoId: $dptoId<br>";
    
    // Probar conexión a BD
    $db = new DB();
    $query = "SELECT img_route FROM departamentos WHERE id = $dptoId";
    $result = $db->consultas($query);
    
    echo "Resultado query: <pre>";
    print_r($result);
    echo "</pre>";
    
    if (!empty($result)) {
        $imgRoute = $result[0]->img_route;
        echo "imgRoute: $imgRoute<br>";
    }
}

?>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="codigo" placeholder="Código" value="TEST001">
    <input type="text" name="dpto_id" placeholder="ID Departamento" value="1">
    <input type="file" name="archivo" accept="image/jpeg">
    <button type="submit">Subir</button>
</form>