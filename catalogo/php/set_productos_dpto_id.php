<?php

require_once("php/dbcat.php");;

$db = new DB();

$dptos = [];


$query = "SELECT id,code  FROM departamentos ORDER BY id";
$consult1=$db->consultas($query);

foreach ($consult1 as $value1)
{
    $objAux = new stdClass();
    $objAux->id = $value1->id;
    $objAux->code = $value1->code;
    $dptos[] = $objAux;
}


echo "id pa AUTO.18: ".searchId($dptos,'AUTO.18')."\n";

//echo "id pa AUTO.18: ".$currId."\n";


//loop throught all productos table ad update accordly
$currId=3969;
while ($currId < 3985)
{
    $query="SELECT dpto_code FROM productos WHERE id = ".$currId;
    $consult = $db->consultas($query);
    foreach($consult as $value)
        $currDptoCode = $value->dpto_code;
    if($currDptoCode != null)    
    {
        $querySet  = "UPDATE productos SET dpto_id =".searchId($dptos,$currDptoCode);
        $querySet .= " WHERE id=".$currId;
        if($db->querySet($querySet) == 1)
        echo "updated dpto id for: ".$currId."\n";
    }
    $currId++;
} 



function searchId($arr,$cod){
    for($i=0;$i<count($arr);$i++){
        if($arr[$i]->code == $cod)
            return $arr[$i]->id;
    }
    return null;
}

?>    