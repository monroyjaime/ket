<?php
session_start();

require_once("dbcat.php");

$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$numPedido = (isset($_POST["num_ped"]))? intval($_POST["num_ped"]) : -1;
$numValery = $_POST["num_valery"];
$stsPedido = (isset($_POST["sts_ped"]))? intval($_POST["sts_ped"]) : -1;

if($numUsr > -1 )
{
    //echo ("ins/del one prod carrito: ".$currCode." usr # ".$numUsr." action: ".$currAction."\n");
    if($stsPedido == 1)
    {
        $queryUpdate ="UPDATE pedido_general SET num_valery='".$numValery."',status=1";
        $queryUpdate.=" WHERE pedido_num=".$numPedido; 
    }
    elseif($stsPedido == 2)
    {
        $queryUpdate ="UPDATE pedido_general SET status=2";
        $queryUpdate.=" WHERE pedido_num=".$numPedido;      
    }
    elseif ($stsPedido == 3)
    {
        $queryUpdate ="UPDATE pedido_general SET archivado=1";
        $queryUpdate.=" WHERE pedido_num=".$numPedido;
    }

    $retVal = $db->querySet($queryUpdate);
    echo($retVal);
    
   
} 

?>    