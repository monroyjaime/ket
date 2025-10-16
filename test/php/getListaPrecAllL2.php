<?php
require_once("dbcat.php");
$db = new DB();
$tipoPrec = (isset($_GET['prec']))? intval($_GET['prec']) : 0; 
$quePrec = ($tipoPrec == 0)? "cost_max" : "cost_mayor";

$numProd = 0;
$query  = "SELECT a.id,a.code,a.name,a.".$quePrec.",a.unit,b.name AS dpto, a.photo_url, a.current_stock";
$query .= " FROM productos a, departamentos b where b.num = 2 AND a.dpto_id = b.id";
$query .= " AND a.".$quePrec." > 0 and a.show='t' order by a.dpto_id,a.code";
$consult = $db->consultas($query);
foreach ($consult as $value){
    $objRtn = new stdClass();
    $objRtn->div = "Ferreteria";
    $objRtn->dpto = $value->dpto;
    $objRtn->code = $value->code;
    $objRtn->name = $value->name;
    
    $objRtn->cost_max = ($tipoPrec == 0)? number_format(floatval($value->cost_max),3,",") : number_format(floatval($value->cost_mayor),3,",");
    $objRtn->cost_max_80 = ($tipoPrec == 0)? number_format(floatval($value->cost_max)*0.8,3,",") : number_format(floatval($value->cost_mayor)*0.8,3,",");

    $objRtn->current_stock = $value->current_stock;

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