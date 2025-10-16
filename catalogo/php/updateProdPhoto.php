<?php

require_once("php/dbcat.php");;

$db = new DB();

$dptos = [];


$query = "SELECT code,photo_url  FROM hist_gal WHERE photo_url !='empty.jpg' ORDER BY code";
$consult1=$db->consultas($query);

foreach ($consult1 as $value1)
{
    $objAux = new stdClass();
    $objAux->photo = $value1->photo_url;
    $objAux->code = $value1->code;
    $photos[] = $objAux;
}


//loop throught all productos table ad update accordly
$currId=1;
while ($currId < 3985)
{
    $query="SELECT code FROM productos WHERE id = ".$currId;
    $consult = $db->consultas($query);
    foreach($consult as $value)
        $currCode = $value->code;

    if($currCode != null)    
    {
        $currPhoto = searchPhoto($photos,$currCode);
        if($currPhoto != null)
        {
            $querySet  = "UPDATE productos SET photo_url ='".$currPhoto;
            $querySet .= "' WHERE code='".$currCode."'";
            if($db->querySet($querySet) == 1)
                echo "updated photo_url for Code: ".$currCode."\n";
        }
        
    }
    $currId++;
} 



function searchPhoto($arr,$cod){
    for($i=0;$i<count($arr);$i++){
        if($arr[$i]->code == $cod)
            return $arr[$i]->photo;
    }
    return null;
}

?>    