<?php
//require_once("app/php/db.php");
require_once("dbcat.php");


if (!empty($_SERVER["HTTP_CLIENT_IP"]))
{
 $ip = $_SERVER["HTTP_CLIENT_IP"];
}
elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
{
 $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
}
else
{
 $ip = $_SERVER["REMOTE_ADDR"];
}

                       


$codigo =  (isset($_GET['code']))?  $_GET['code'] : 'GA000-007';

$db = new DB();

$query = "select a.rol from usuario a, sesion b where b.usuario = a.num and b.active='t' and b.ip_client='".$ip."'";
$consult = $db->consultas($query);
foreach ($consult as $value)
    $role = ($value->rol == NULL)? -1 : intval($value->rol) ;

$precioLabel ="Precio";
if($role >-1 && $role < 3) 
    $precioLabel .= " Mayorista: ";
else    
    $precioLabel .= ": ";    

$tags = '<div class="col text-center">';


//$tags .=    '<div class="myclass">';
$tags .=    '<div class="row row-cols-1 row-cols-md-1 g-1 ">';


$query  = "select a.code,a.name,b.name as dpto, a.unit,a.cost_mayor, concat(b.img_route,a.photo_url) as foto from productos a, ";
$query .= "departamentos b where a.dpto_id=b.id and a.code='".$codigo."'";
//echo "query: ".$query."\n";

$consult1 = $db->consultas($query);
foreach ($consult1 as $value1){
    $productVal_dpto =$value1->dpto;
    $productVal_code = $value1->code;
    $productVal_desc = $value1->name;
    $productVal_unit = $value1->unit;
    $productVal_prec = number_format(floatval($value1->cost_mayor),3,",");
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
        $tags .=            '<h4 class="card-text">'.$precioLabel.' $'.$productVal_prec.'</h4>';
    $tags .=        '<h3 class="card-text">Unidad: '.$productVal_unit.'</h3>';

    $tags .=            '</div>';
    $tags .=        '</div>';
    $tags .=    '</div>';
}

$tags .=    '</div>'; 
$tags .= '</div>';


echo $tags;

