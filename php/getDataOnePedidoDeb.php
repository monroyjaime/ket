<?php
//session_start();

require_once("dbcat.php");
$db = new DB();

$numUsr = 1; //(isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$tipoPrecio = 0;    //(isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0; 
$precProdTable = ($tipo_precio == 0)? "cost_max" : "cost_mayor";
if($numUsr >-1)
{
    if (isset($_GET['num']))
        $numPedido =  intval($_GET['num']);
    else{
        $query  = "SELECT MAX(pedido_num) FROM pedido_general WHERE user_num=".$numUsr;
        foreach ($consult as $value)
            $numPedido = intval($value->max);
    }    

    $query  = "SELECT a.pedido_num, a.comentarios, a.fecha,b.full_name FROM pedido_general a,";
    $query .= "cliente b WHERE a.user_num=".$numUsr." AND a.client_num = b.num ";
    $query .= "AND a.pedido_num=".$numPedido;
   // echo $query;




    $consult = $db->consultas($query);
        foreach ($consult as $value){
            $numPedido = intval($value->pedido_num);
            $clientName = $value->full_name;
            $coments = $value->comentarios;
            $fecha = $value->fecha;
        }

    $numProd = 0;
    $query  = "SELECT a.product_code,a.cantidad,b.name, b.".$precProdTable." AS precio,b.unit";
    $query .= " FROM pedido_detail a, productos b WHERE a.num=".$numPedido." AND b.";
    $query .= "code=a.product_code";
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $objRtn = new stdClass();
        $objRtn->cantidad = intval($value->cantidad);
        $objRtn->code = $value->product_code;
        $objRtn->name = $value->name;
        $objRtn->unidad = $value->unit;
        $objRtn->precio =$value->precio;
        $objRtn->cliente = $clientName;
        $objRtn->$fecha =$fecha;
        $objRtn->$comentario = $coments;
        $objRtn->monto =floatval($value->precio)*intval($value->cantidad);
        $listaProd[] = $objRtn;
        $numProd++;
    }
        

    $objPag = new stdClass();
    $objPag->total=$numProd;
    $objPag->totalNotFiltered=$numProd;
    $objPag->rows = $listaProd;

    //$listaPrecDpto = db->getListaPrecDpto($dptoId);
    echo json_encode($objPag); 
}
?>