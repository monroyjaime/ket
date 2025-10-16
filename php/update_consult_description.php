<?php

require_once("dbcat.php");;

$db = new DB();

$query = "SELECT code,name,cost_max,unit,current_stock,dpto_code FROM prod_name ORDER BY code";
$consult1=$db->consultas($query);
$count1=1;
$count2=1;
$count3=1;



foreach ($consult1 as $value1)
{
    $found = 0;
    $query1 = "SELECT name,cost_max,unit,current_stock FROM productos where code='".$value1->code."'";
    $consult2=$db->consultas($query1);
    foreach($consult2 as $value2)
    {
        $found = 1;
        if($value1->name != $value2->name)
        {
 //           if($db->querySet("UPDATE productos SET name = '".$value1->name."' WHERE code ='".$value1->code."'") == 1)
//            {
                echo ($count1."::updated description:: \nCODE: ".$value1->code."\nbefore: ".$value2->name."\nafter : ".$value1->name."\n");
                $count1++;    
//            }
        }
        if(number_format(floatval($value1->cost_max),2) != number_format(floatval($value2->cost_max),2))
        {
//            if($db->querySet("UPDATE productos SET cost_max = ".$value1->cost_max." WHERE code ='".$value1->code."'") == 1)
//            {
                echo ($count2."::updated cost_max:: \nCODE: ".$value1->code."\nbefore: ".$value2->cost_max."\nafter : ".$value1->cost_max."\n");
                $count2++;
//            }
            
        }
        if($value1->unit != $value2->unit)
        {
//            if($db->querySet("UPDATE productos SET unit = ".$value1->unit."' WHERE code ='".$value1->code."'") == 1)
//            {
                echo ($count3."::updated unit:: \nCODE: ".$value1->code."\nbefore: ".$value2->unit."\nafter : ".$value1->unit."\n");
                $count3++;
//            }
        }

    }

    if($found == 0 ) //code not found, so we must inser this new product
    {
        $consult=$db->consultas("SELECT MAX(id) + 1 AS next_id FROM productos");
        foreach ($consult as $value)
            $nextId = intval($value->next_id);
        $queryGetDptoId="SELECT id FROM departamentos WHERE code='".$value1->dpto_code."'";
        echo("get codeID: ".$queryGetDptoId."\n");
        $consult=$db->consultas($queryGetDptoId);
        foreach ($consult as $value)
            $dptoId=intval($value->id);

        $queryInsert  = "INSERT INTO productos VALUES(".$nextId.",'".$value1->code."','".$value1->name."','";
        $queryInsert .= $value1->dpto_code."','".$value1->unit."',".$value1->current_stock.",".$value1->cost_max;
        $queryInsert .= ",'empty.jpg',".$dptoId.",'t'";
        echo ("insertar: ".$queryInsert."\n");
    }

}

?>    