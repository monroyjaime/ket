<?php
session_start();
//require_once("app/php/db.php");
require_once("dbcat.php");

$codigo = (isset($_GET['code']))?  $_GET['code'] : 'GA000-007';

$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;

$tipoPrecio = (isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0;

$cualPrecio = ($tipoPrecio==0)? "cost_max" :"cost_mayor";
$labelPrecio = ($tipoPrecio==0)? "Precio" : "Precio mayorista";
$fecha = date("d/m/Y");

$db = new DB();

$tags = '<div class="col text-center">';

//$tags .=    '<div class="myclass">';
$tags .=    '<div class="row row-cols-1 row-cols-md-1 g-1 ">';


$query  = "select a.code,a.name,b.name as dpto, a.unit,a.".$cualPrecio." AS precio, a.current_stock, concat(b.img_route,a.photo_url) as foto from productos a, ";
$query .= "departamentos b where a.dpto_id=b.id and a.code='".$codigo."'";

//echo "query: ".$query."\n";

$consult1 = $db->consultas($query);
foreach ($consult1 as $value1){
    $productVal_dpto =$value1->dpto;
    $productVal_code = $value1->code;
    $productVal_desc = $value1->name;
    $productVal_unit = $value1->unit;
    $productVal_prec = number_format(floatval($value1->precio),3,",");
    $productVal_prec_80 = number_format(floatval($value1->precio)*0.8,3,",");
    $productVal_current_stock = $value1->current_stock;
    $currUrl = $value1->foto;

    $tags .=    '<div class="col" style="background-color: #DDD;">';
    $tags .=        '<div class="card h-100 text-bg-light">';
    $tags .=            '<div class="card-header" style="background-color: #eee;">';
    $tags .=                '<h5>Dpto: '.$productVal_dpto.'</h5>'; 
    $tags .=                '<h3>Código: '.$productVal_code.'</h3>';
    $tags .=            '</div>';
    $tags .=            '<img src="'.$currUrl.'" class="card-img-top" alt="'.$productVal_code.'">';
    $tags .=            '<div class="card-body" style="background-color: #eee;">';
    $tags .=                '<h4 class="card-text">'.$productVal_desc.'</h4>';
    if($role > -1)
        $tags .=        '<h4 class="card-text">'.$labelPrecio.': $'.$productVal_prec.' <h6>('.$fecha.')</h6></h4>';
    $tags .=        '<h5 class="card-text">Unidad: '.$productVal_unit.'</h5>';
    $tags .=            '</div>';
    $tags .=        '</div>';
    $tags .=    '</div>';
}

$tags .=    '</div>'; 
$tags .= '</div>';


echo $tags;

