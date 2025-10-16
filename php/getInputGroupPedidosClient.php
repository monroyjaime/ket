<?php
session_start();

require_once("dbcat.php");
$statuses =array("Registrado","Procesandose","Despachado");
$colorStatus = array("#AA0000","#AA5200","#188203");
$db = new DB();
$tags='';
$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
if($numUsr > -1 )
{
    $query = "SELECT show_all_ped FROM usuario WHERE num=".$numUsr;
    $consult = $db->consultas($query);
    foreach ($consult as $value)
        $showAllPed= $value->show_all_ped;

    $allPedidoFlag = ($showAllPed=='f')? " and user_num=".$numUsr : "";

    $countPedidos = 1;
    $query  = "select a.*, b.full_name from pedido_general a, cliente b";
    $query .= " where  a.client_num=b.num ".$allPedidoFlag." order by pedido_num desc";
    
    $consult = $db->consultas($query);
    foreach ($consult as $value)
    {
        $currNum = intval($value->pedido_num);
        $currUsr = intval($value->user_num);
        $currNumValery = ($value->num_valery=="no")? "n/d" : $value->num_valery;
        $colorNum = ($value->num_valery=="no")? "#AAA" : "#000";
        $currTipoPrec = intval($value->tipo_precio);
        $currStatus = intval($value->status);
        $currFecha = $value->fecha;
        $currHora = explode(":",$value->hora)[0].":".explode(":",$value->hora)[1];
        $currCliente = $value->full_name;
        $query1  = "select cast(cast(sum(cantidad*precio)*1000 AS int) AS real)/1000";
        $query1 .= " AS total_pedido from pedido_detail where num=".$currNum;
        $consult1 = $db->consultas($query1);
        foreach ($consult1 as $value1)
            $currTotal = $value1->total_pedido;

        $tags .= '<option value="'.$currNum.'">';
        $tags .= '     Pedido Num. '.$currNum.'-'.$currUsr.' ('.$statuses[$currStatus].') -- ';

        $tags .=       $currFecha.' / '.$currHora.' -- ';

        $tags .= '     Cliente: '.$currCliente.' -- ';
        $tags .= '     Total: $'.$currTotal;
        $tags .= '</option>';                                                                                                                   

        $countPedidos++;
    }

}
echo $tags;


?>    