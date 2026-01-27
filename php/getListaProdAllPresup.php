<?php
session_start();
require_once("dbcat.php");
$db = new DB();
$division = (isset($_GET['div']))? intval($_GET['div']) : 0;
//$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;
$onlyStock = (isset($_SESSION['only_stock']))? intval($_SESSION['only_stock']) : 0; 


$numProd = 0;
$query  = "SELECT id,code,relacionado,name,cost_max AS prec_min,cost_mayor AS prec_may, costo,";
$query .= "unit,photo_url, current_stock AS stock,stock_lleg AS llegando, checked, no_code";
$query .= " FROM productos order by dpto_id,code";
$consult = $db->consultas($query);
foreach ($consult as $value){
    $objRtn = new stdClass();

    $objRtn->code = $value->code;
    $objRtn->relacionado = $value->relacionado;

    $objRtn->name = $value->name;
    $objRtn->prec_min = $value->prec_min;
    $objRtn->prec_may = $value->prec_may;
    $objRtn->costo = $value->costo;

    $objRtn->stock = $value->stock;
    $objRtn->llegando =$value->llegando;
    $objRtn->unit = $value->unit;
    $objRtn->photo_url = $value->photo_url;
    $objRtn->no_code = $value->no_code; // ← Asegúrate que esto venga de la BD
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