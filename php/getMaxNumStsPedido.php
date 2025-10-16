<?php
session_start();

require_once("dbcat.php");
$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
//$numPedido =  intval($_POST['num']);
if($numUsr >-1)
{
    $query ="SELECT MAX(pedido_num) FROM pedido_general WHERE user_num=".$numUsr;
    $consult= $db->consultas($query);
    foreach($consult as $value)
        $numPedido=$value->max;

    $objRtn = new stdClass();
    $objRtn->num_pedido =$numPedido."-".$numUsr;
    $objRtn->ped_sts = intval($value->status);
    
    echo json_encode($objRtn);
}
?>