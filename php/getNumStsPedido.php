<?php
session_start();

require_once("dbcat.php");
$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$numPedido =  intval($_POST['num']);
if($numUsr >-1)
{
    $query1  = "SELECT user_num,status FROM pedido_general Where pedido_num=".$numPedido;

    $consult = $db->consultas($query1);
    foreach ($consult as $value){
        $objRtn = new stdClass();
        $objRtn->num_pedido =$numPedido."-".$value->user_num;
        $objRtn->ped_sts = intval($value->status);
    }
    echo json_encode($objRtn);
}
?>