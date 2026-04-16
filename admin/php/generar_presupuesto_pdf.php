<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$presupuesto_num = isset($_GET['presupuesto_num']) ? intval($_GET['presupuesto_num']) : 0;

require_once("../../php/dbcat_async.php");
$db = new DBAsync();

$result = $db->consultaSegura("SELECT presupuesto_num FROM presupuesto_gen WHERE presupuesto_num = $1", [$presupuesto_num]);

echo json_encode([
    'success' => !empty($result),
    'presupuesto_num' => $presupuesto_num,
    'found' => !empty($result),
    'result' => $result
]);
exit;