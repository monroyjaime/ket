<?php
if (isset($_GET['usr'])) {
    $numUsr = intval($_GET['usr']);
    require_once("php/dbcat.php");    
    $db = new DB();

    
    $consult = $db->consultas("SELECT direct_login FROM usuario WHERE num = ".$numUsr);
    foreach ($consult as $value)
        $direct = intval($value->direct_login);
    if($direct==1)
    {
        $queryGetUsrData  = "SELECT rol,short_name,full_name,email FROM usuario WHERE num=".$numUsr;
        $consulta = $db->consultas($queryGetUsrData);

        //first lets set a new sesion:
        $numSesQuery = $db->consultas("select min(num) from sesion where active = 'f'");
        foreach ($numSesQuery as $value)
            $numCurrSesion=intval($value->min);



        $sesionID = rand(1000,10000);  

        $redirectStr = '{ usr_num: '.$numUsr.',ses_num: '.$numCurrSesion.', ses_id: '.$sesionID.'});';

        //Update DB with sesion info
        $sesionQuery = $db->querySet("UPDATE sesion SET active = 't', timer = 0, id = ".$sesionID.", usuario =".$numUsr." WHERE num = ".$numCurrSesion);  
        if($sesionQuery == -1)
        {
        echo "error creando sesion";
        exit -1;
        }

        $page="./";
        //$page=($cliNum == 0 && $sucNum == 0)? "../allClient.php": ($sucNum == 0)? "../allSucs.php" : "../mainNav.php";


        
        $salida  = '<!DOCTYPE html>';
        $salida .=    '<html>';
        $salida .=         '<head>';
        $salida .=            '<meta charset="utf-8"/>';
        $salida .=            '<script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>';
        $salida .=            '<script src="./js/jquery.redirect.js" type="text/javascript"></script>';
        $salida .=            '<script>';
        $salida .=              'window.onload = function() {';
        $salida .=                '$.redirect("'.$page.'",'.$redirectStr;
        $salida .=              '}';
        $salida .=            '</script>';
        $salida .=        '</head>';
        $salida .=        '<body>';
        $salida .=        '</body>';       
        $salida .=    '</html>';


    } 
    else
    {
        $salida  = '<!DOCTYPE html>';
        $salida .= '<html>';
        $salida .=  '<head>';
        $salida .=   '<meta charset="utf-8"/>';
        $salida .=    '<meta charset="utf-8">';
        $salida .=    '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
        $salida .=    '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $salida .=    '<meta name="description" content="">';
        $salida .=    '<meta name="author" content="">';


        $salida .=    '<title>Ket electropartes</title>';
        $salida .=    '<link rel="Shortcut Icon" href="img/icon.ico" type="image/x-icon" />';
        $salida .=    '<script>';
        $salida .=        'window.location.replace("https://ketelectropartes.com/login.php")';
        $salida .=    '</script>';
        $salida .= '</head>';
        $salida .='</html>';    
    }
}
else
{
    $salida  = '<!DOCTYPE html>';
    $salida .= '<html>';
    $salida .=  '<head>';
    $salida .=   '<meta charset="utf-8"/>';
    $salida .=    '<meta charset="utf-8">';
	$salida .=    '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
	$salida .=    '<meta name="viewport" content="width=device-width, initial-scale=1">';
	$salida .=    '<meta name="description" content="">';
	$salida .=    '<meta name="author" content="">';


	$salida .=    '<title>ket electropartes</title>';
	$salida .=    '<link rel="Shortcut Icon" href="img/icon.ico" type="image/x-icon" />';
    $salida .=    '<script>';
    $salida .=        'window.location.replace("https://ketelectropartes.com/login.php")';
    $salida .=    '</script>';
    $salida .= '</head>';
    $salida .='</html>';

}

echo $salida;

?>