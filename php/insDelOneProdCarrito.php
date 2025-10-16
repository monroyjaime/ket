<?php
session_start();

require_once("dbcat.php");

$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$currCode = (isset($_POST["code"]))? $_POST["code"] : "";
$currAction = (isset($_POST["action"]))? intval($_POST["action"]) : -1;
if($numUsr > -1 && $currAction > -1)
{
    //echo ("ins/del one prod carrito: ".$currCode." usr # ".$numUsr." action: ".$currAction."\n");
    if($currAction == 1)
    {
        $queryInsert ="INSERT INTO pedido_carrito(user_num,product_code) VALUES(";
        $queryInsert.=$numUsr.",'".$currCode."') ON CONFLICT DO NOTHING";
        $retVal = $db->querySet($queryInsert);
        echo($retVal);
    }
    elseif($currAction == 0)
    {
        $queryDelete  = "DELETE FROM pedido_carrito WHERE user_num=".$numUsr;
        $queryDelete .=" AND product_code = '".$currCode."'";
        $retVal = $db->querySet($queryDelete);
        echo($retVal);
            
    }
    elseif ($currAction == 2)
    {
        $queryDelete  = "DELETE FROM pedido_carrito WHERE user_num=".$numUsr;
        $retVal = $db->querySet($queryDelete);
        echo($retVal);
    }
    
   
} 

?>    