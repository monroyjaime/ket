<?php
session_start();

require_once("dbcat.php");
$db = new DB();

$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
$numPedido =  intval($_POST['num']);
$statuses =array("Registrado","Procesandose","Despachado");
$colorStatus = array("#AA0000","#AA5200","#188203");
if($numUsr >-1)
{
    $query1  = "select cast(cast(sum(cantidad*precio)*1000 AS int) AS real)/1000";
    $query1 .= " AS total_pedido from pedido_detail where num=".$numPedido;
    $objRtn = new stdClass();
    $consult = $db->consultas($query1);

    foreach ($consult as $value)
        $total = str_replace(".",",",$value->total_pedido);


    $query1  = "SELECT d.full_name AS usr,b.full_name AS cli, c.full_name AS vend,";
    $query1 .= "a.user_num,a.fecha,a.hora,a.num_valery,";
    $query1 .= "a.status,a.comentarios FROM pedido_general a,";
    $query1 .= " cliente b, vendedor c, usuario d WHERE a.client_num=b.num AND ";
    $query1 .= "a.vendedor_num=c.num AND a.user_num=d.num AND a.pedido_num=".$numPedido; 
    //echo $query1.'\n';
    
    $consult = $db->consultas($query1);
    foreach ($consult as $value){
        $usrNum = $value->user_num;
        $usrName = $value->usr;
        $clientName = $value->cli;
        $vendedorName = $value->vend;
        $hora= explode(":",$value->hora)[0].":".explode(":",$value->hora)[1];
        $fecha= explode("-",$value->fecha)[2]."/".explode("-",$value->fecha)[1]."/".explode("-",$value->fecha)[0];
        $pedFecha = $fecha." - ".$hora;
        $pedNum = ($value->num_valery == "no")? "no Definido" : $value->num_valery;
        $pedSts = intval($value->status);
        $coments = $value->comentarios; 
    }
        
    $tags  = '<form id="dynamicForm" >'; //onsubmit="handleSubmitChangePed(event,'.$numPedido.','.$pedSts.')">';
    $tags .=     '<div class="row">';
    $tags .=    '<div class="form-group col-md-2">';
    $tags .=        '<label for="inputNumWeb">Num. Web:</label>';

    $tags .=        '<input type="text" class="form-control text-end" id="inputNumWeb" value="'.$numPedido.'-'.$usrNum.' "disabled>';
    $tags .=    '</div>';
    $tags .=    '<div class="form-group col-md-2">';
    $tags .=        '<label for="inputNumValery">Num. Valery:</label>';
    if($pedNum == "no Definido")
    {
        $tags .='<input type="text" class="form-control" id="inputNumValery" placeholder="no Definido" required>';
        $tags .='<div class="invalid-feedback">';
        $tags .='Debe definir el número de pedido Valery.';
        $tags .='</div>';
    }
    else
        $tags .='<input type="text" class="form-control" id="inputNumValery" value="'.$pedNum.'" disabled>';
    $tags .=    '</div>';
    $tags .=    '<div class="form-group col-md-2">';
    $tags .=        '<label for="inputStatus">Estado:</label>';
    if($pedSts == 0)
        $tags .= '<input type="text" class="form-control" id="inputStatus" value="Registrado" disabled>';
    elseif($pedSts == 2)
        $tags .= '<input type="text" class="form-control" id="inputStatus" value="Despachado" disabled>';
    elseif($pedSts == 1)
    {
        $tags .= '<select class="form-control"  placeholder="Procesandose" id="inputStatus">';
        $tags .=    '<option selected>Procesandose</option>';
        $tags .=    '<option >Despachado</option>';
        $tags .= '</select>';
    }
    $tags .=    '</div>';
    $tags .=    '<div class="form-group col-md-4">';
    $tags .=        '<label for="inputUsuario">Generado por:</label>';
    $tags .=        '<input type="text" class="form-control" id="inputUsuario" value="'.$usrName.'"disabled>';
    $tags .=    '</div>';
    $tags .=    '<div class="form-group col-md-2">';
    $tags .=        '<label for="inputTotal">Total:</label>';
    $tags .=        '<input type="text" class="form-control text-end" id="inputTotal" value="$'.$total.'"disabled>';
    $tags .=    '</div>';
    $tags .=    '</div>';
    $tags .=    '<div class="row">';
    $tags .=    '<div class="form-group col-md-4">';
    $tags .=        '<label for="inputfecha">Fecha - hora:</label>';
    $tags .=        '<input type="text" class="form-control" id="inputfecha"  value="'.$pedFecha.'" disabled>';
    $tags .=    '</div>';
    $tags .=    '<div class="form-group col-md-4">';
    $tags .=        '<label for="inputCliName">Cliente:</label>';
    $tags .=        '<input type="text" class="form-control" id="inputCliName" value="'.$clientName.'" disabled>';
    $tags .=    '</div>';
    $tags .=    '<div class="form-group col-md-4">';
    $tags .=        '<label for="inputVenName">Vendedor:</label>';
    $tags .=        '<input type="text" class="form-control" id="inputVenName" value="'.$vendedorName.'" disabled>';
    $tags .=    '</div>';
    $tags .=    '</div>';
    $tags .=    '<div class="form-group">';
    $tags .=        '<label for="inputComents">Comentarios:</label>';
    $tags .=        '<textarea class="form-control" rows="1" id="inputComents" disabled>'.$coments.'</textarea>';
    $tags .=    '</div>';
    if($pedSts == 0)
        $tags .=    '<button type="submit" class="btn btn-primary mb-2" id="inputAction" onclick="getFormValues(event,'.$numPedido.',0)" style="margin: 10px 20px 10px;" >Actualizar Num. Valery</button>';
    elseif($pedSts == 1)
        $tags .=    '<button type="submit" class="btn btn-primary mb-2" id="inputAction" onclick="getFormValues(event,'.$numPedido.',1)" style="margin: 10px 20px 10px;" >Cambiar Estado</button>';
    elseif($pedSts == 2)
        $tags .=    '<button type="submit" class="btn btn-success mb-2" id="inputAction" onclick="getFormValues(event,'.$numPedido.',2)" style="margin: 10px 20px 10px;">Archivar</button>';


    $tags .='</form>';

   
    echo $tags;
}
?>