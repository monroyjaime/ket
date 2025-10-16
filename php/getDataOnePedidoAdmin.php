<?php
session_start();

require_once("dbcat.php");
$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$tipoPrecio = (isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0; 
$precProdTable = ($tipo_precio == 0)? "cost_max" : "cost_mayor";
$numPedido =  intval($_GET['num']);
$statuses =array("Registrado","Procesandose","Despachado");
$colorStatus = array("#AA0000","#AA5200","#188203");
if($numUsr >-1)
{

    $query1  = "SELECT a.fecha, b.code AS cli, c.code AS vend FROM ";
    $query1 .= "pedido_general a, cliente b, vendedor c WHERE a.client_num=b.num AND ";
    $query1 .= "a.vendedor_num=c.num AND  pedido_num=".$numPedido; 
    $consult = $db->consultas($query1);
    foreach ($consult as $value){
        $clientCode = $value->cli;
        $vendedorCode = $value->vend;
        $pedFecha = explode("-",$value->fecha)[2]."/".explode("-",$value->fecha)[1]."/".explode("-",$value->fecha)[0];
    }


    $numProd = 0;
    $query  = "SELECT a.product_code,a.cantidad,a.tipo_precio, a.precio,b.unit";
    $query .= " FROM pedido_detail a, productos b WHERE a.num=".$numPedido." AND b.";
    $query .= "code=a.product_code ORDER BY a.product_code";
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $objRtn = new stdClass();
        $objRtn->cli_code=($numProd==0)? $clientCode : "";
        $objRtn->ven_code=($numProd==0)? $vendedorCode : "";
        $objRtn->fecha=($numProd==0)? $pedFecha : "";


        $objRtn->cantidad = intval($value->cantidad);
        $objRtn->code = $value->product_code;
        $objRtn->unidad = $value->unit;
        $objRtn->precio =$value->precio;
        $objRtn->tipo_prec = $value->tipo_precio;
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