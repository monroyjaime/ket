<?php
session_start();

require_once("dbcat.php");

$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$currCode = (isset($_POST["code"]))? $_POST["code"] : "";
$currValue = (isset($_POST["value"]))? intval($_POST["value"]) : -1;
if($numUsr > -1 && $currValue > -1)
{
    $queryUpdate  = "UPDATE pedido_carrito SET tipo_precio=".$currValue;
    $queryUpdate .= " WHERE user_num=".$numUsr." AND product_code = '".$currCode."'";
    $retVal = $db->querySet($queryUpdate);
    echo($retVal);
   
} 

?>    