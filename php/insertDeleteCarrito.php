<?php
session_start();

require_once("dbcat.php");

$db = new DB();

$pedidoNum = -1;
$tipoPrecio = (isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0;  
$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
if($numUsr > -1 && isset($_POST["strCodes"]))
{
    $strCodes = $_POST["strCodes"];
    $arrCodes = explode(',',$strCodes);
    for($i=0;$i<count($arrCodes);$i++)
    {
        if($i==0)
            $formatStrCodes  = "'".$arrCodes[$i]."'";
        else
            $formatStrCodes .=",'".$arrCodes[$i]."'";
        
    }
    $consult=$db->consultas("SELECT count(user_num) FROM pedido_carrito WHERE user_num=".$numUsr);
    foreach ($consult as $value)
        $NumProdUsr= intval($value->count);
    if($NumProdUsr==0)  //first cart interaction
    {
        $queryInsert="INSERT INTO pedido_carrito(user_num,product_code) VALUES";
        
        for($i=0;$i<count($arrCodes);$i++)
        {
            $firstElem = ($i==0)? "(" : ",(";
            $queryInsert .= $firstElem.$numUsr.",'".$arrCodes[$i]."')";
            
        }
        if($db->querySet($queryInsert) != 1)
            echo("error en insert inicial carrito: ".$queryInsert);
        else
            echo("insert inicial carrito succed");

    }
    else    //cart close/open action here ony can be new or delete 
    {
        $queryDelete  = "DELETE FROM pedido_carrito WHERE user_num=".$numUsr." AND ";
        $queryDelete .= "product_code NOT IN (".$formatStrCodes.")";
        if($db->querySet($queryDelete) != 1)
            echo("error en delete carrito: ".$queryDelete);
        else
            echo("delete from carrito succed");

        for($i=0;$i<count($arrCodes);$i++)   
        {
            $queryInsert ="INSERT INTO pedido_carrito(user_num,product_code) VALUES(";
            $queryInsert.=$numUsr.",'".$arrCodes[$i]."') ON CONFLIT DO NOTHING";
            echo "query insert: ".$queryInsert."\n";
            if($db->querySet($queryInsert) != 1)
                echo("error en insert continuo carrito: ".$queryInsert);
            else
                echo("insert continuo carrito succed");
        } 


    }
   
} 




?>    