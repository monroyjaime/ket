<?php
//session_start();

require_once("dbcat.php");

$db = new DB();

$numUsr = 1; //(isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$currCode = "WA101AZ";    //(isset($_POST["code"]))? $_POST["code"] : "";
$currAction = 1;    //(isset($_POST["action"]))? intval($_POST["action"]) : -1;
if($numUsr > -1 && $currAction > -1 && $currCode !="")
{
    //echo ("ins/del one prod carrito: ".$currCode." usr # ".$numUsr." action: ".$currAction."\n");
    if($currAction == 1)
    {
        $queryInsert = "INSERT INTO pedido_carrito(user_num,product_code) VALUES(";
        $queryInsert.= $numUsr.",'".$currCode."') ON CONFLIT DO NOTHING";
        if($db->querySet($queryInsert) != 1)
            echo("error en insert ".$currCode." en carrito: ".$queryInsert);
        else
            echo("insert de ".$currCode." en carrito exitoso");
    }
    elseif($currAction == 0){
        $queryDelete  = "DELETE FROM pedido_carrito WHERE user_num=".$numUsr;
        $queryDelete .= " AND product_code = '".$currCode."'";
        if($db->querySet($queryDelete) != 1)
            echo("error en delete carrito: ".$queryDelete);
        else
            echo("delete from carrito succed");
    }
    
   
} 

?>    