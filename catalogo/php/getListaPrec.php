<?php
require_once("dbcat.php");
$db = new DB();

$dptoId =  (isset($_GET['dpto']))?  intval($_GET['dpto']) : 6; 

if($dptoId>0)
{
    $numProd = 0;
    $query ="SELECT code,name,cost_max,unit,photo_url FROM productos WHERE dpto_id=".$dptoId." AND cost_max > 0 ORDER BY code";
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $objRtn = new stdClass();
        $objRtn->code = $value->code;
        $objRtn->name = $value->name;
        $objRtn->cost_max = $value->cost_max;
        $objRtn->unit = $value->unit;
        $objRtn->photo_url = $value->photo_url;
        $listaPrecDpto[] = $objRtn;
        $numProd++;
    }
}	

$objPag = new stdClass();
$objPag->total=$numProd;
$objPag->totalNotFiltered=$numProd;
$objPag->rows = $listaPrecDpto;

//$listaPrecDpto = db->getListaPrecDpto($dptoId);
echo json_encode($objPag);
?>