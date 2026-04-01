<?php
// getProductosSinFoto.php
session_start();
require_once("../../php/dbcat.php");

// Verificar autenticación
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

if ($role == -1 || $isAdmin != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new DB();
    
    // Parámetros de paginación de DataTables
    $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
    
    // Consulta base para contar total de registros (productos con foto = empty.jpg)
    $countQuery = "SELECT COUNT(*) as total 
                   FROM productos p 
                   INNER JOIN departamentos d ON p.cod_departamento = d.cod_departamento
                   WHERE p.foto = 'empty.jpg' OR p.foto IS NULL OR p.foto = ''";
    
    // Agregar condición de búsqueda si existe
    $searchCondition = "";
    if (!empty($searchValue)) {
        $searchCondition = " AND (p.codigo LIKE '%$searchValue%' 
                              OR p.descripcion LIKE '%$searchValue%' 
                              OR d.name LIKE '%$searchValue%')";
        $countQuery .= $searchCondition;
    }
    
    $totalRecords = $db->single($countQuery);
    
    // Consulta principal con paginación
    $query = "SELECT p.codigo, 
                     p.descripcion, 
                     d.name as departamento,
                     p.foto as foto_actual,
                     p.cod_departamento
              FROM productos p 
              INNER JOIN departamentos d ON p.cod_departamento = d.cod_departamento
              WHERE (p.foto = 'empty.jpg' OR p.foto IS NULL OR p.foto = '')";
    
    if (!empty($searchValue)) {
        $query .= " AND (p.codigo LIKE '%$searchValue%' 
                    OR p.descripcion LIKE '%$searchValue%' 
                    OR d.name LIKE '%$searchValue%')";
    }
    
    $query .= " ORDER BY d.name ASC, p.codigo ASC LIMIT $start, $length";
    
    $productos = $db->consultas($query);
    
    // Formatear datos para DataTables
    $data = [];
    foreach ($productos as $row) {
        $data[] = [
            'codigo' => $row->codigo,
            'descripcion' => $row->descripcion,
            'departamento' => $row->departamento,
            'foto_actual' => $row->foto_actual,
            'cod_departamento' => $row->cod_departamento
        ];
    }
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => (int)$totalRecords,
        'recordsFiltered' => (int)$totalRecords,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener productos: ' . $e->getMessage()
    ]);
}
?>