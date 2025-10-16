<?php
session_start();

require_once("dbcat.php");

$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$currCode = (isset($_POST["code"]))? $_POST["code"] : "";
$currCant = (isset($_POST["cantidad"]))? intval($_POST["cantidad"]) : -1;
if($numUsr > -1 && $currCant > -1 && $currCode !="")
{

    $queryUpdate ="UPDATE pedido_carrito SET cantidad=".$currCant." WHERE product_code='";
    $queryUpdate.=$currCode."' AND user_num = ".$numUsr;
    $retVal = $db->querySet($queryUpdate);
    echo($retVal);
        /*
        echo("error update catidad de ".$currCode." en carrito: ".$queryInsert);
    else
        echo("update cantidad de ".$currCode." en carrito exitoso");
        */
    
   
} 

?>    