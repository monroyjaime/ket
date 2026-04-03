<?php
// test_ultra_simple.php - Lo más simple posible
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'El script funciona',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>