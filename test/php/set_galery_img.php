<?php

require_once("db.php");

$db = new DB();

$numFiles=0;

$queryinsert ="INSERT INTO hist_gal VALUES((select max(id) FROM hist_gal )+1,'";


$query = "SELECT code AS codigo FROM hist_gal ORDER BY id";
$consult1=$db->consultas($query);

foreach ($consult1 as $value1)
{
    $archivo[] = $value1->codigo;
    $numFiles++;
}

echo "guardados en memoria ".$numFiles." archivos\n";  


$query = "SELECT  split_part(file,'.',1) AS codigo FROM hist_aux ORDER BY id";

$consult1=$db->consultas($query);
foreach ($consult1 as $value1)
{

    $currCode = $value1->codigo;
    if (!in_array($currCode,$archivo))
    {
        $query2 = $queryinsert.$currCode."','PENDIENTE','".$currCode.".png)" ;
        //if($db->querySet($query2) == 1)
            echo $query2."\n"; //." excuted\n";
    }
}


?>    