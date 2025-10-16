<?php
session_start();

require_once("dbcat.php");

$db = new DB();
$data =[];

$pedidoNum = -1;
$tipoPrecio = (isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0;  
$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
if($numUsr > -1 && isset($_POST["data"]))
{
    $data = json_decode($_POST["data"]);
    $productos = $data->productos;
    $clientNum = $data->cliente;
    $vendedorNum = $data->vendedor;
    $comentario = $data->comentario;
    $consult=$db->consultas("SELECT max(pedido_num) FROM pedido_general");
    foreach ($consult as $value)
        $pedidoNum= intval($value->max) + 1;
    $queryInsert  = "INSERT INTO pedido_general(pedido_num,user_num,tipo_precio,client_num,vendedor_num,comentarios)";
   
    $queryInsert .= " VALUES(".$pedidoNum.",".$numUsr.",".$tipoPrecio.",".$clientNum.",".$vendedorNum.",'".$comentario."')";

    if($db->querySet($queryInsert) == 1)
    {
        $queryMultiInsert = "INSERT INTO pedido_detail VALUES";

        //$arrData = $data->productos;
        $num = 0;
        foreach($productos as $producto)
        {
            $strInc = ($num >0)? ",(" : "(";
            $queryMultiInsert .= $strInc.$pedidoNum.",'".$producto->code."',".$producto->amount.",";
            $queryMultiInsert .= $producto->precio.",'".$producto->comentario."',".$producto->tipo_prec.")";
            $num++;
        }
        $retVal = $db->querySet($queryMultiInsert);
        echo($retVal);
    }
} 




?>    