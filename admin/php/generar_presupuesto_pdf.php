<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$presupuesto_num = isset($_GET['presupuesto_num']) ? intval($_GET['presupuesto_num']) : 0;

echo json_encode([
    'success' => false,
    'debug' => 'Llegó al script',
    'presupuesto_num' => $presupuesto_num
]);
exit;