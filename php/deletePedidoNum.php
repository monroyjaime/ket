<?php
require_once("dbcat.php");
$db = new DB();

$pedNum =  (isset($_POST['num']))?  intval($_POST['num']) : -1; 
$retSts = -1;

if($pedNum>0)
{
    $queryDelete = "DELETE FROM pedido_general WHERE pedido_num=".$pedNum;
    if($db->querySet($queryDelete) == 1)
        $queryDelete = "DELETE FROM pedido_detail WHERE num=".$pedNum;
            if($db->querySet($queryDelete) == 1)
                $retSts = 1;
}

echo $retSts; 

?>