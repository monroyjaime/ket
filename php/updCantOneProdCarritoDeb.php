<?php
session_start();

require_once("dbcat.php");

$db = new DB();

$numUsr = 1; //= (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$currCode = 'WA101AM';  //(isset($_POST["code"]))? $_POST["code"] : "";
$currCant = 2;   //(isset($_POST["cantidad"]))? intval($_POST["cantidad"]) : -1;
if($numUsr > -1 && $currCant > -1 && $currCode !="")
{

    $queryUpdate ="UPDATE pedido_carrito SET cantidad=".$currCant." WHERE product_code='";
    $queryInsert.=$currCode."' AND user_num = ".$numUsr;
    if($db->querySet($queryInsert) != 1)
        echo("error update catidad de ".$currCode." en carrito: ".$queryInsert);
    else
        echo("update cantidad de ".$currCode." en carrito exitoso");

    
   
} 

?>    