<?php
//session_start();

require_once("dbcat.php");
$db = new DB();

$numUsr = 1;//(isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$numPedido =  1; //intval($_POST['num']);
if($numUsr >-1)
{
    $query1  = "SELECT num_valery,status FROM pedido_general Where pedido_num=".$numPedido;

    $consult = $db->consultas($query1);
    foreach ($consult as $value){
        $objRtn = new stdClass();

        $objRtn->num_valery = $value->num_valery;
        $objRtn->ped_sts = intval($value->status);
    }
    echo json_encode($objRtn);
}
?>
