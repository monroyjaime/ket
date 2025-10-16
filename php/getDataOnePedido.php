<?php
session_start();

require_once("dbcat.php");
$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
 
$numPedido =  intval($_GET['num']);
$statuses =array("Registrado","Procesandose","Despachado");
$colorStatus = array("#AA0000","#AA5200","#188203");
if($numUsr >-1)
{
    if ($numPedido==0){
        $query  = "SELECT MAX(pedido_num) FROM pedido_general WHERE user_num=".$numUsr;
        $consult = $db->consultas($query);

        foreach ($consult as $value)
            $numPedido = intval($value->max);
    }    

    $numProd = 0;
    $query  = "SELECT a.product_code,a.cantidad,a.comentario, a.precio,a.tipo_precio,";
    $query .= "b.unit FROM pedido_detail a, productos b WHERE a.num=".$numPedido;
    $query .= " AND b.code=a.product_code order by a.product_code";
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $objRtn = new stdClass();
        $objRtn->cantidad = intval($value->cantidad);
        $objRtn->code = $value->product_code;
        $objRtn->comentario = $value->comentario;
        $objRtn->unidad = $value->unit;
        $objRtn->precio = $value->precio;
        $objRtn->tipo_prec = $value->tipo_precio;
        $objRtn->monto =floatval($value->precio)*intval($value->cantidad);
        $listaProd[] = $objRtn;
        $numProd++;
    }
        
    $query1  = "select cast(cast(sum(cantidad*precio)*1000 AS int) AS real)/1000";
    $query1 .= " AS total_pedido from pedido_detail where num=".$numPedido;
    $objRtn = new stdClass();
    $consult = $db->consultas($query1);

    foreach ($consult as $value)
        $objRtn->monto = $value->total_pedido;
    $objRtn->precio = 0.0;


    $query1  = "SELECT b.code AS cli, c.code AS vend, a.fecha,a.num_valery,a.status FROM ";
    $query1 .= "pedido_general a, cliente b, vendedor c WHERE a.client_num=b.num AND ";
    $query1 .= "a.vendedor_num=c.num AND  pedido_num=".$numPedido; 
    $consult = $db->consultas($query1);
    foreach ($consult as $value){
        $clientCode = $value->cli;
        $vendedorCode = $value->vend;
        $pedFecha = $value->fecha;
        $pedNum = ($value->num_valery == "no")? "N/D" : $value->num_valery;
        $pedSts = intval($salue->status);
    }

    $objRtn->code = '<i style="color: #003272; font-style: normal;font-weight: bold">CLI/VEND:</i>';
    $objRtn->cantidad = $clientCode;
    $objRtn->unidad = $vendedorCode;
    $formatColor = '<i style="color:'.$colorStatus[$pedSts].'; font-style: normal;font-weight: bold">';
    $objRtn->comentario = $pedFecha.' -- <i style="color: #003272; font-style: normal;font-weight: bold">PEDIDO NUM:</i> '.$numPedido.'-'.$numUsr.' ('.$formatColor.$statuses[$pedSts].'</i>)';


    $listaProd[] = $objRtn;
/*
    $objRtn->code = '<i style="color: #003272; font-style: normal;font-weight: bold">VENDEDOR:</i>';
    $objRtn->cantidad = $vendedorCode;
    $objRtn->unidad = '<i style="color: #003272; font-style: normal;font-weight: bold">FECHA:</i>';
    $objRtn->precio = $pedFecha;
    $objRtn->monto = '<i style="color: #003272; font-style: normal;font-weight: bold">COMENTARIO:</i>';
    
    $listaProd[] = $objRtn;*/
    
    $objPag = new stdClass();
    $objPag->total=$numProd;
    $objPag->totalNotFiltered=$numProd;
    $objPag->rows = $listaProd;

    //$listaPrecDpto = db->getListaPrecDpto($dptoId);
    echo json_encode($objPag);
}
?>