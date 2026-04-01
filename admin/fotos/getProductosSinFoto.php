<?php
// getProductosSinFoto.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Parámetros de paginación y búsqueda
    $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    
    // Consulta para contar total de registros (productos con foto = empty.jpg)
    $countQuery = "SELECT COUNT(*) as total 
                   FROM productos p 
                   INNER JOIN departamentos d ON p.cod_departamento = d.cod_departamento
                   WHERE p.foto = 'empty.jpg' OR p.foto IS NULL OR p.foto = ''";
    
    // Agregar búsqueda si existe
    if (!empty($searchValue)) {
        $countQuery .= " AND (p.codigo LIKE :search OR p.descripcion LIKE :search OR d.nombre LIKE :search)";
    }
    
    $countStmt = $db->prepare($countQuery);
    if (!empty($searchValue)) {
        $searchParam = "%{$searchValue}%";
        $countStmt->bindParam(':search', $searchParam);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Consulta principal con paginación
    $query = "SELECT p.codigo, 
                     p.descripcion, 
                     d.nombre as departamento,
                     p.foto,
                     p.cod_departamento
              FROM productos p 
              INNER JOIN departamentos d ON p.cod_departamento = d.cod_departamento
              WHERE p.foto = 'empty.jpg' OR p.foto IS NULL OR p.foto = ''";
    
    if (!empty($searchValue)) {
        $query .= " AND (p.codigo LIKE :search OR p.descripcion LIKE :search OR d.nombre LIKE :search)";
    }
    
    $query .= " ORDER BY d.nombre ASC, p.codigo ASC LIMIT :start, :length";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    if (!empty($searchValue)) {
        $searchParam = "%{$searchValue}%";
        $stmt->bindParam(':search', $searchParam);
    }
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':length', $length, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $productos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productos[] = [
            'codigo' => $row['codigo'],
            'descripcion' => $row['descripcion'],
            'departamento' => $row['departamento'],
            'foto_actual' => $row['foto'],
            'cod_departamento' => $row['cod_departamento']
        ];
    }
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $productos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener productos: ' . $e->getMessage()
    ]);
}
?>