<?php
require_once("dbcat.php");
$db = new DB();

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


$consulta = $db->consultas("select count(num) from sesion where active = 'f'");
foreach ($consulta as $value)
    $numSesionsAvail=intval($value->count);
  
if($numSesionsAvail == 0)
{
    echo "<script>alert('En este momento no podemos atender su requerimiento, por favor intente mas tarde');
                             window.location.href ='../index.php';
                            </script>";
}

$username = $_POST['input_email'];
$password = $_POST['input_password'];

if(count($consulta)!=0)
{
    $queryValidate = "SELECT COUNT(num) FROM usuario WHERE email = '".$username."' AND psw = md5('".$password."')";
    //echo "query validate: ".$queryValidate."\n";

    $validateUsr = $db->consultas($queryValidate);
    foreach ($validateUsr as $value) {
      # code...
      $numUsuarios=intval($value->count);
    }
    if($numUsuarios == 0)
    {
      echo "<script>alert('Credenciales invalidas');
                window.location.href ='../index.php';
            </script>";
    }

    $queryValidatePass = "SELECT num FROM usuario WHERE email = '".$username."' AND psw = md5('".$password."')";
    //echo "query validate pass: ".$queryValidatePass."\n";
    $consulta = $db->consultas($queryValidatePass);
    foreach($consulta as $value)
        $numUsr = intval($value->num);
    
    //first lets set a new sesion:
    $numSesQuery = $db->consultas("select min(num) from sesion where active = 'f'");
    foreach ($numSesQuery as $value)
        $numCurrSesion=intval($value->min);

    $sesionID = rand(1000,10000);  

     //Update DB with sesion info
    $querySetSesion = "UPDATE sesion SET active = 't', timer = 0, id = ".$sesionID.", usuario =".$numUsr.", ip_client = '".$ip."' WHERE num = ".$numCurrSesion;
     //echo "Query set session: ".$querySetSesion."\n";
     $sesionQuery = $db->querySet($querySetSesion);  
     if($sesionQuery == -1)
     {
       echo "error creando sesion";
       exit -1;
     }

     // Now lets reload home page with user / sesion info just set

        $page = "../";
        $redirectStr = '{ usr_num: '.$numUsr.',ses_num: '.$numCurrSesion.', ses_id: '.$sesionID.'});';
        $salida  = '<!DOCTYPE html>';
        $salida .=    '<html>';
        $salida .=         '<head>';
        $salida .=            '<meta charset="utf-8"/>';
        $salida .=            '<script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>';
        $salida .=            '<script src="../js/jquery.redirect.js" type="text/javascript"></script>';
        $salida .=            '<script>';
        $salida .=              'window.onload = function() {';
        $salida .=                '$.redirect("'.$page.'",'.$redirectStr;
        $salida .=              '}';
        $salida .=            '</script>';
        $salida .=        '</head>';
        $salida .=        '<body>';
        $salida .=        '</body>';       
        $salida .=    '</html>';

    //echo "<script>windows.location.href='../index.php?usr_num=".$numUsr."&ses_num=".$numCurrSesion."&ses_id=".$sesionID."</script>";
    echo $salida;
}
else
{
    echo "<script>alert('count(consultas) == 0');
        window.location.href ='../index.php';
        </script>";

}

?>
