<?php

require_once("dbcat.php");

$db = new DB();

$query  = "SELECT code,name,cost_max,unit,current_stock,dpto_code,orden,orden,cost_oferta,cost_mayor,cost_min,stock_lleg,relacionado,costo";
$query .= " FROM prod_name ORDER BY code";
$consult1=$db->consultas($query);
$count1=1;
$count2=1;
$count3=1;
$count4=1;
$count5=1;
$count6=1;
$count7=1;
$count8=1;
$count9=1;
$count10=1;
$count11=1;
$count12=1;



foreach ($consult1 as $value1)
{
    $found = 0;
    $query1 = "SELECT name,cost_max,unit,current_stock,dpto_code,orden,cost_oferta,cost_mayor,cost_min,stock_lleg,relacionado,costo FROM productos where code='".$value1->code."'";
    $consult2=$db->consultas($query1);
    foreach($consult2 as $value2)
    {
        $found = 1;
        if($value1->name != $value2->name)
        {
            if($db->querySet("UPDATE productos SET name = '".$value1->name."' WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count1."::updated description:: \nCODE: ".$value1->code."\nbefore: ".$value2->name."\nafter : ".$value1->name."\n");
                $count1++;    
            }
        }
        if(number_format(floatval($value1->cost_max),3) != number_format(floatval($value2->cost_max),3))
        {
            if($db->querySet("UPDATE productos SET cost_max = ".$value1->cost_max." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count2."::updated cost_max:: \nCODE: ".$value1->code."\nbefore: ".$value2->cost_max."\nafter : ".$value1->cost_max."\n");
                $count2++;
            }
            
        }
        if($value1->unit != $value2->unit)
        {
            echo ("pieza mala:".$value1->unit."----".$value2->unit."---code:".$value1->code."\n");
            if($db->querySet("UPDATE productos SET unit = '".$value1->unit."' WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count3."::updated unit:: \nCODE: ".$value1->code."\nbefore: ".$value2->unit."\nafter : ".$value1->unit."\n");
                $count3++;
            }
        }

        if($value1->current_stock != $value2->current_stock)
        {
            if($db->querySet("UPDATE productos SET current_stock = ".$value1->current_stock." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count4."::updated Stock:: \nCODE: ".$value1->code."\nbefore: ".$value2->current_stock."\nafter : ".$value1->current_stock."\n");
                $count4++;
            }
        }

        if($value1->dpto_code != $value2->dpto_code)
        {
            //first get dpto_id for this new dpto_code..
            $consult3 = $db->consultas("SELECT id FROM departamentos WHERE code ='".$value1->dpto_code."'");
            foreach($consult3 as $value3)
                $newDptoId=intval($value3->id);

            if($db->querySet("UPDATE productos SET dpto_code ='".$value1->dpto_code."', dpto_id=".$newDptoId." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count5."::updated Sdpto_code:: \nCODE: ".$value1->code."\nbefore: ".$value2->dpto_code."\nafter : ".$value1->dpto_code." (id:".$newDptoId.")\n");
                $count5++;
            }
        }

        if($value1->orden != $value2->orden)
        {
            if($db->querySet("UPDATE productos SET orden = ".$value1->orden." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count6."::updated Orden:: \nCODE: ".$value1->code."\nbefore: ".$value2->orden."\nafter : ".$value1->orden."\n");
                $count6++;
            }


        }
        if(number_format(floatval($value1->cost_oferta),3) != number_format(floatval($value2->cost_oferta),3))
        {
            if($db->querySet("UPDATE productos SET cost_oferta = ".$value1->cost_oferta." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count7."::updated cost_oferta:: \nCODE: ".$value1->code."\nbefore: ".$value2->cost_oferta."\nafter : ".$value1->cost_oferta."\n");
                $count7++;
            }
            
        }

        if(number_format(floatval($value1->cost_mayor),3) != number_format(floatval($value2->cost_mayor),3))
        {
            if($db->querySet("UPDATE productos SET cost_mayor = ".$value1->cost_mayor." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count8."::updated cost_mayor:: \nCODE: ".$value1->code."\nbefore: ".$value2->cost_mayor."\nafter : ".$value1->cost_mayor."\n");
                $count8++;
            }
            
        }

        if(number_format(floatval($value1->cost_min),3) != number_format(floatval($value2->cost_min),3))
        {
            if($db->querySet("UPDATE productos SET cost_min = ".$value1->cost_min." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count9."::updated cost_min:: \nCODE: ".$value1->code."\nbefore: ".$value2->cost_min."\nafter : ".$value1->cost_min."\n");
                $count9++;
            }
            
        }

        if($value1->stock_lleg != $value2->stock_lleg)
        {
            if($db->querySet("UPDATE productos SET stock_lleg = ".$value1->stock_lleg." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count10."::updated Stock_llegando:: \nCODE: ".$value1->code."\nbefore: ".$value2->stock_lleg."\nafter : ".$value1->stock_lleg."\n");
                $count10++;
            }
        }

        if($value1->relacionado != $value2->relacionado)
        {
            if($db->querySet("UPDATE productos SET relacionado = '".$value1->relacionado."' WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count11."::updated relacionado:: \nCODE: ".$value1->code."\nbefore: ".$value2->relacionado."\nafter : ".$value1->relacionado."\n");
                $count11++;
            }
        }

        if(number_format(floatval($value1->costo),3) != number_format(floatval($value2->costo),3))
        {
            if($db->querySet("UPDATE productos SET costo = ".$value1->costo." WHERE code ='".$value1->code."'") == 1)
            {
                echo ($count12."::updated costo:: \nCODE: ".$value1->code."\nbefore: ".$value2->costo."\nafter : ".$value1->costo."\n");
                $count12++;
            }
            
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
        $queryInsert .= ",'empty.jpg',".$dptoId.",'t',".$value1->orden.")";
        if($db->querySet($queryInsert) == 1)
        {
            echo ("insertado nuevo producto: ".$queryInsert."\n");

        }
        else{
            echo ("Error insertado nuevo  codigo: ".$value1->dpto_code."\n");

        }

    }

}

$conult4=$db->consultas("SELECT COUNT(code) FROM productos");
foreach($conult4 AS $val)
    $currNumProductos = intval($val->count);
$conult4=$db->consultas("SELECT COUNT(code) FROM prod_name");
foreach($conult4 AS $val)
    $newNumProductos = intval($val->count); 
if($newNumProductos > 0) //Prevent delete all productos table
{
    $prodDeleted = $currNumProductos - $newNumProductos;
    if($prodDeleted>0)
    {
        echo $prodDeleted." Items a ser eliminados:\n";
        $query = "SELECT code FROM productos WHERE code NOT IN (SELECT code FROM prod_name) ORDER BY code";
        $consult4 = $db->consultas($query);
        foreach($consult4 AS $val)
        {
            $queryDel = "DELETE FROM productos WHERE code='".$val->code."'";
            if($db->querySet($queryDel) == 1)
                echo "borrado item de código: ".$val->code."\n";
            else
                echo "Errror borrando item código: ".$val->code."\n";
        }
    }
}


?>    