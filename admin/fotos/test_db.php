<?php
// test_db.php - Prueba de conexión y estructura
session_start();
require_once("../../php/dbcat.php");

header('Content-Type: application/json');

$response = [
    'session' => [
        'usr_admin' => isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 'no definido',
        'role' => isset($_SESSION['role']) ? $_SESSION['role'] : 'no definido'
    ]
];

try {
    $db = new DB();
    $response['db_connection'] = 'OK';
    
    // Probar consulta a productos
    $testQuery = "SELECT COUNT(*) as total FROM productos";
    $testResult = $db->consultas($testQuery);
    $response['total_productos'] = $testResult[0]->total ?? 0;
    
    // Probar consulta a departamentos
    $deptQuery = "SELECT COUNT(*) as total FROM departamentos";
    $deptResult = $db->consultas($deptQuery);
    $response['total_departamentos'] = $deptResult[0]->total ?? 0;
    
    // Contar productos sin foto (photo_url = 'none' o vacío)
    $emptyQuery = "SELECT COUNT(*) as total 
                   FROM productos 
                   WHERE photo_url IS NULL 
                      OR photo_url = '' 
                      OR photo_url = 'none'
                      OR photo_url = 'empty.jpg'";
    $emptyResult = $db->consultas($emptyQuery);
    $response['productos_sin_foto'] = $emptyResult[0]->total ?? 0;
    
    // Mostrar algunos ejemplos de productos sin foto
    $sampleQuery = "SELECT p.code, p.name, p.photo_url, d.name as departamento
                    FROM productos p 
                    INNER JOIN departamentos d ON p.dpto_id = d.id
                    WHERE p.photo_url IS NULL 
                       OR p.photo_url = '' 
                       OR p.photo_url = 'none'
                       OR p.photo_url = 'empty.jpg'
                    LIMIT 5";
    $sampleResult = $db->consultas($sampleQuery);
    $response['ejemplos'] = [];
    foreach ($sampleResult as $row) {
        $response['ejemplos'][] = [
            'code' => $row->code,
            'name' => $row->name,
            'departamento' => $row->departamento,
            'photo_url' => $row->photo_url
        ];
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>