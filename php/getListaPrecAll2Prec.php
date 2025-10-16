<?php
session_start();
require_once("dbcat.php");
$db = new DB();
$division = (isset($_GET['div']))? intval($_GET['div']) : 0;
//$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;
$onlyStock = (isset($_SESSION['only_stock']))? intval($_SESSION['only_stock']) : 0; 


$numProd = 0;
$query  = "SELECT a.id,a.code,a.name,a.cost_max AS cost_min,cost_mayor AS cost_may,";
$query .= "a.unit,c.name AS div,b.name AS dpto, a.photo_url, a.current_stock,a.stock_tot, a.checked";
$query .= " FROM productos a, departamentos b, divisiones c where b.num = c.id AND a.dpto_id = b.id";
if($division >0)
    $query .= " AND b.num = ".$division;
if($onlyStock == 1)
    $query .= " AND a.current_stock > 0";

$query .= " AND a.cost_max>0 AND a.show='t' order by a.dpto_id,a.code";
$consult = $db->consultas($query);
foreach ($consult as $value){
    $objRtn = new stdClass();
    $objRtn->div = $value->div;
    $objRtn->dpto = $value->dpto;
    $objRtn->code = $value->code;
    $objRtn->name = $value->name;
    $objRtn->cost_min = $value->cost_min;
    $objRtn->cost_may = $value->cost_may;
    $objRtn->cost_max_80 = number_format(floatval($value->cost_min)*0.8,3,",") ;

    $objRtn->current_stock = $value->current_stock;
    $objRtn->stock_tot =$value->stock_tot;
    $objRtn->unit = $value->unit;
    $objRtn->photo_url = $value->photo_url;
    $listaPrecAll[] = $objRtn;
    $numProd++;
}
	

$objPag = new stdClass();
$objPag->total=$numProd;
$objPag->totalNotFiltered=$numProd;
$objPag->rows = $listaPrecAll;

//$listaPrecDpto = db->getListaPrecDpto($dptoId);
echo json_encode($objPag);
?>