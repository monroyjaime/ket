<?php
require_once("dbcat.php");
$db = new DB();

$numPedidos = 0;
$query  = "SELECT a.*,b.full_name AS usr_name,c.full_name AS client_name,d.full_name AS vendedor_name ";
$query .= "FROM pedido_general a, usuario b,cliente c,vendedor d WHERE a.archivado=0 AND a.user_num=b.num";
$query .= " AND a.client_num=c.num AND a.vendedor_num=d.num ORDER BY a.status, a.fecha DESC, a.hora DESC";

$consult = $db->consultas($query);
foreach ($consult as $value){
    $objRtn = new stdClass();
    $objRtn->fecha = $value->fecha;
    $objRtn->hora = explode(":",$value->hora)[0].":".explode(":",$value->hora)[1];
    $objRtn->edo = intval($value->status);
    $objRtn->usr = $value->usr_name;
    $objRtn->cli = $value->client_name;
    $objRtn->vend = $value->vendedor_name;
    $objRtn->numk = $value->pedido_num."-".$value->user_num;
    $objRtn->numv = $value->num_valery;
    $objRtn->coment = $value->comentarios;
    $currNum = intval($value->pedido_num);
    $objRtn->pedido_num=$currNum;
    $query2 = "SELECT SUM(cantidad*precio) FROM pedido_detail WHERE num=".$currNum;
    $consult2 = $db->consultas($query2);
    foreach ($consult2 as $value2)
        $objRtn->total = round(floatval($value2->sum),3);

    $listaPrecAll[] = $objRtn;
    $numPedidos++;
}
	

$objPag = new stdClass();
$objPag->total=$numPedidos;
$objPag->totalNotFiltered=$numPedidos;
$objPag->rows = $listaPrecAll;

//$listaPrecDpto = db->getListaPrecDpto($dptoId);
echo json_encode($objPag);
?>