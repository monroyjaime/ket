<?php
session_start();
//require_once("app/php/db.php");
require_once("dbcat.php");

$numPed = (isset($_GET['num']))?  intval($_GET['num']) : -1;

if($numPed>0)
{
    $db = new DB();
    $query = "SELECT SUM(cantidad*precio) FROM pedido_detail WHERE num=".$numPed;
    $consult = $db->consultas($query);
    foreach ($consult as $value)
        $totPedido=round(floatval($value->sum),3);

    $query  = 'SELECT a.fecha,b.full_name AS usr_name,c.full_name AS client_name FROM pedido_general a,';     
    $query .= ' usuario b,cliente c WHERE a.pedido_num='.$numPed.' AND a.user_num=b.num AND a.client_num=c.num ';
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $user = $value->usr_name;
        $fecha = $value->fecha;
        $client = $value->client_name;
    }
    $tags  = '<div class="col text-center" style="margin: 4px 2px 4px;">';
    $tags .= '<h5>Confirma que desea borrar pedido generado por '.$user.' de fecha: '.$fecha.' para el cliente: '.$client.' por $'.$totPedido.'?</h5>';
    $tags .= '<button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#ModalDeletePedido"';
    $tags .= ' onClick="deletePedido('.$numPed.')" style="margin: 4px 2px 4px;"><i class="bi bi-trash"></i>Borrar pedido</button>';
    $tags .= '</div>';
    echo $tags;
}


