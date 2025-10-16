<?php
require_once("dbcat.php");
$db = new DB();

$numProd = 0;
$query  = "select code,cost_max,
case when photo_url= 'empty.jpg' 
then '0' 
else '1' 
end  AS foto, current_stock
FROM productos order by code ";

$consult = $db->consultas($query);
foreach ($consult as $value){
    $objRtn = new stdClass();
    $objRtn->code = $value->code;
    $objRtn->foto = $value->foto;
    $objRtn->precio = $value->cost_max;
    $objRtn->stock = $value->current_stock;

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