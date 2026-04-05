<?php
// getProductos.php - Con información de agrupación
session_start();

$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . "/php/dbcat.php");

// Verificar administrador
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

if ($role == -1 || $isAdmin != 1) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => true, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new DB();
    
    $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 25;
    $searchValue = isset($_GET['search']['value']) ? pg_escape_string($_GET['search']['value']) : '';
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
    
    // Consulta para contar total
    $countQuery = "SELECT COUNT(*) as total 
                   FROM productos p 
                   INNER JOIN departamentos d ON p.dpto_id = d.id";
    
    if (!empty($searchValue)) {
        $countQuery .= " WHERE (p.code ILIKE '%$searchValue%' 
                              OR p.name ILIKE '%$searchValue%' 
                              OR d.name ILIKE '%$searchValue%')";
    }
    
    $resultCount = $db->consultas($countQuery);
    $totalRecords = !empty($resultCount) ? (int)$resultCount[0]->total : 0;
    
    // Consulta principal
    $query = "SELECT p.code, 
                     p.name as descripcion, 
                     d.name as departamento,
                     d.img_route,
                     p.photo_url as foto_actual,
                     p.dpto_id
              FROM productos p 
              INNER JOIN departamentos d ON p.dpto_id = d.id";
    
    if (!empty($searchValue)) {
        $query .= " WHERE (p.code ILIKE '%$searchValue%' 
                    OR p.name ILIKE '%$searchValue%' 
                    OR d.name ILIKE '%$searchValue%')";
    }
    
    $query .= " ORDER BY d.name ASC, p.code ASC";
    $query .= " LIMIT $length OFFSET $start";
    
    $productos = $db->consultas($query);
    
    if (!$productos) {
        $productos = [];
    }
    
    // Agrupar por departamento para enviar también los grupos
    $grupos = [];
    $data = [];
    $lastDepto = null;
    $rowIndex = 0;
    
    foreach ($productos as $row) {
        $hasPhoto = !empty($row->foto_actual) 
                    && $row->foto_actual !== 'empty.jpg' 
                    && $row->foto_actual !== 'none';
        
        $producto = [
            'codigo' => $row->code,
            'descripcion' => $row->descripcion,
            'departamento' => $row->departamento,
            'img_route' => $row->img_route ?? '',
            'foto_actual' => $row->foto_actual ?? '',
            'dpto_id' => $row->dpto_id,
            'has_photo' => $hasPhoto,
            'is_group_header' => false
        ];
        
        // Verificar si es el primer producto de un nuevo departamento
        if ($row->departamento !== $lastDepto) {
            // Agregar un marcador de grupo antes del producto
            $grupo = [
                'is_group_header' => true,
                'departamento' => $row->departamento,
                'codigo' => '',
                'descripcion' => '',
                'img_route' => '',
                'foto_actual' => '',
                'dpto_id' => 0,
                'has_photo' => false
            ];
            $data[] = $grupo;
            $lastDepto = $row->departamento;
        }
        
        $data[] = $producto;
    }
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("Error en getProductos.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener productos: ' . $e->getMessage()
    ]);
}
?>