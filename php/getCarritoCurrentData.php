<?php
session_start();

require_once("dbcat.php");
$db = new DB();
$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;
//$tipoPrecio = (isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0; 
//$precProdTable = ($tipo_precio == 0)? "cost_max" : "cost_mayor"; 


$numProd = 0;
$query  = "SELECT a.product_code,a.cantidad,a.tipo_precio,b.name, b.cost_max AS precio_min,b.cost_mayor AS precio_may,b.unit";
$query .= " FROM pedido_carrito a, productos b WHERE a.user_num=".$numUsr." AND b.code=a.product_code ORDER BY a.product_code";
$consult = $db->consultas($query);
foreach ($consult as $value){
    $objRtn = new stdClass();
    $tipoPrecio = intval($value->tipo_precio);
    $currPrecio = ($value->tipo_precio == 0)? $value->precio_min : $value->precio_may;
    $objRtn->cantidad = intval($value->cantidad);
    $objRtn->edo = ($objRtn->cantidad ==0)? 0 : 1;
    $objRtn->code = $value->product_code;
    $objRtn->name = $value->name;
    $objRtn->unidad = $value->unit;
    $objRtn->prec_min =$value->precio_min;
    $objRtn->prec_may =$value->precio_may;
    $objRtn->check_prec = $tipoPrecio;
    $objRtn->monto =  0;  //number_format (floatval($currPrecio)*intval($value->cantidad),3);
    $listaProd[] = $objRtn;
    $numProd++;
}
	

$objPag = new stdClass();
$objPag->total=$numProd;
$objPag->totalNotFiltered=$numProd;
$objPag->rows = $listaProd;

//$listaPrecDpto = db->getListaPrecDpto($dptoId);
echo json_encode($objPag);
?>