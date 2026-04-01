<?php
// getProductosSinFoto.php
session_start();
require_once("../../php/dbcat.php");

// ============================================
// VERIFICACIÓN DE AUTORIZACIÓN
// ============================================
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

// Si no está autenticado o no es administrador, mostrar error
if ($role == -1 || $isAdmin != 1) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => true, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new DB();
    
    // Parámetros de DataTables
    $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
    
    // Escapar valor de búsqueda para evitar inyección SQL
    $searchValue = pg_escape_string($searchValue);
    
    // Consulta para contar total de registros
    $countQuery = "SELECT COUNT(*) as total 
                   FROM productos p 
                   INNER JOIN departamentos d ON p.dpto_id = d.id
                   WHERE p.photo_url IS NULL 
                      OR p.photo_url = '' 
                      OR p.photo_url = 'none'
                      OR p.photo_url = 'empty.jpg'";
    
    // Agregar condición de búsqueda si existe
    if (!empty($searchValue)) {
        $countQuery .= " AND (p.code ILIKE '%$searchValue%' 
                              OR p.name ILIKE '%$searchValue%' 
                              OR d.name ILIKE '%$searchValue%')";
    }
    
    $resultCount = $db->consultas($countQuery);
    $totalRecords = !empty($resultCount) ? (int)$resultCount[0]->total : 0;
    
    // Consulta principal con paginación - Usando sintaxis PostgreSQL
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
                 OR p.photo_url = 'empty.jpg'";
    
    if (!empty($searchValue)) {
        $query .= " AND (p.code ILIKE '%$searchValue%' 
                    OR p.name ILIKE '%$searchValue%' 
                    OR d.name ILIKE '%$searchValue%')";
    }
    
    $query .= " ORDER BY d.name ASC, p.code ASC";
    
    // Agregar LIMIT y OFFSET en sintaxis PostgreSQL
    $query .= " LIMIT $length OFFSET $start";
    
    $productos = $db->consultas($query);
    
    // Si no hay resultados, asegurar array vacío
    if (!$productos) {
        $productos = [];
    }
    
    // Formatear datos para DataTables
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
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("Error en getProductosSinFoto.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener productos: ' . $e->getMessage()
    ]);
}
?>