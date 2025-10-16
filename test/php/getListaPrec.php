<?php
require_once("dbcat.php");
$db = new DB();

$dptoId =  (isset($_GET['dpto']))?  intval($_GET['dpto']) : 6; 
$tipoPrec = (isset($_GET['prec']))? intval($_GET['prec']) : 0; 

if($dptoId>0)
{
    $numProd = 0;
    $quePrec = ($tipoPrec == 0)? "cost_max" : "cost_mayor";
    $query ="SELECT id,code,name,".$quePrec.",unit,photo_url,current_stock FROM productos WHERE show='t' AND dpto_id=".$dptoId." AND ".$quePrec." > 0 ORDER BY code";
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $objRtn = new stdClass();
        $objRtn->code = $value->code;
        $objRtn->name = $value->name;
        $objRtn->cost_max = ($tipoPrec == 0)? number_format(floatval($value->cost_max),3,",") : number_format(floatval($value->cost_mayor),3,",");
        $objRtn->cost_max_80 = ($tipoPrec == 0)? number_format(floatval($value->cost_max)*0.8,3,",") : number_format(floatval($value->cost_mayor)*0.8,3,",");
        $objRtn->current_stock = $value->current_stock;

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