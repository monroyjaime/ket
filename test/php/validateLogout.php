<?php
session_start();
require_once("dbcat.php");
$db = new DB();

$sesionNum = $_POST['sesion'];

$queryOffSesion = "UPDATE sesion SET active='f', usuario=0, id=0,curr_timer=0, timer=0, ip_client='0.0.0.0' WHERE num = ".$sesionNum;
//echo "query validate: ".$queryOffSesion."\n";
$sesionQuery = $db->querySet($queryOffSesion);  
if($sesionQuery == -1)
{
  echo "error cerrando sesion";
  exit -1;
}

session_unset();
session_destroy();

echo "<script>window.location.href ='../';</script>";

?>
