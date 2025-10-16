<?php
//session_start();

require_once("dbcat.php");

$db = new DB();

$pedidoNum = -1;
$tipoPrecio = 0; //(isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0;  
$numUsr = 1; //(isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$strCodes ="WA101GR,WA101MA";
if($numUsr > -1 && $strCodes) //isset($_POST["strCodes"]))
{
    $consult=$db->consultas("SELECT count(user_num) FROM pedido_carrito WHERE user_num=".$numUsr);
    foreach ($consult as $value)
        $NumProdUsr= intval($value->count);
    if($NumProdUsr==0)
    {
        $queryInsert="INSERT INTO pedido_carrito(user_num,product_code) VALUES";
//        $strCodes = $_POST["strCodes"];
        $arrCodes = explode(',',$strCodes);
	echo("arrCodes: ");
var_dump($arrCodes);  
      for($i=0;$i<count($arrCodes);$i++)
        {
            $firstElem = ($i==0)? "(" : ",(";
            $queryInsert .= $firstElem.$numUsr.",'".$arrCodes[$i]."')";
            if($db->querySet($queryInsert) != 1)
                echo("error en insert inicial carrito: ".$queryInsert);
            else
                echo("insert inicial carrito succed");
        }

    }
    else
    {

    }
   
} 




?>    
