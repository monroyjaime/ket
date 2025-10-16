<?php
require_once("dbcat.php");

$db = new DB();

$nowNoSeconds = date('Y-m-d h:i').":00";
$updateDate= $db->querySet("UPDATE all_ket_values SET curr_time='".$nowNoSeconds."'");
if($updateDate!=1)
	echo("Unable to Update date ".$nowNoSeconds." in table all_ket_values");

//now manage open sesion counters

$consulta = $db->consultas("SELECT sesion_duration FROM all_ket_values");
foreach ($consulta as $value)
	$sesionDuration = intval($value->sesion_duration);
$consulta = $db->consultas("SELECT num,timer,curr_timer FROM sesion WHERE active = 't' ORDER BY num");
foreach ($consulta as $value)
{
	$justIncrementTimer = True;
	$currNum = $value->num;
	$currMainTimer = $value->timer;
	$currSesTimer = $value->curr_timer;
	if(($currMainTimer+1) > $sesionDuration)
		$justIncrementTimer = false; //time to reset this sesion uncoditionally	(sesion expired normally)
/*	elseif (($currMainTimer - $currSesTimer) >25 )
			$justIncrementTimer = false; // also if current sesion is not longer incrementing it timer from 5 minutes ago */

	if($justIncrementTimer)	
	{
		$currMainTimer += 1;
		$increment = $db->querySet("UPDATE sesion SET timer = ".$currMainTimer." WHERE num = ".$currNum);
	}	
	else
	{
		$resetSesion = $db->querySet("UPDATE sesion SET active = 'f', usuario = 0, id = 0, timer = 0, curr_timer = 0, ip_client='0.0.0.0' WHERE num = ".$currNum);
	}
	
}

?>