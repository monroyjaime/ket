<?php

require_once("dbcat.php");;

$db = new DB();

$query = "SELECT code,name,cost_max,unit  FROM prod_name ORDER BY code";
$consult1=$db->consultas($query);
$count1=1;
$count2=1;
$count3=1;



foreach ($consult1 as $value1)
{
    $query1 = "SELECT name,cost_max,unit FROM productos where code='".$value1->code."'";
    $consult2=$db->consultas($query1);
    foreach($consult2 as $value2)
    {
        if($value1->name != $value2->name)
        {
            if($db->querySet("UPDATE productos SET name = '".$value1->name."' WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count1."::updated description:: \nCODE: ".$value1->code."\nbefore: ".$value2->name."\nafter : ".$value1->name."\n");
                $count1++;    
            }
        }
        if(number_format(floatval($value1->cost_max),2) != number_format(floatval($value2->cost_max),2))
        {
            if($db->querySet("UPDATE productos SET cost_max = ".$value1->cost_max." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count2."::updated cost_max:: \nCODE: ".$value1->code."\nbefore: ".$value2->cost_max."\nafter : ".$value1->cost_max."\n");
                $count2++;
            }
            
        }
        if($value1->unit != $value2->unit)
        {
            if($db->querySet("UPDATE productos SET unit = ".$value1->unit."' WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count3."::updated unit:: \nCODE: ".$value1->code."\nbefore: ".$value2->unit."\nafter : ".$value1->unit."\n");
                $count3++;
            }
        }

    }
}




?>    