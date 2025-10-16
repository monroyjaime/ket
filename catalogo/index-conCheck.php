<?php
//require_once("app/php/db.php");
require_once("../php/dbcat.php");

$db = new DB();


$tags1  =   '<div class="container">';
$tags1 .=   '<div class="row align-items-start">';
$tags1 .=   '<div class="col" >';  
$tags1 .=   '  <h2>Catálogos Ket</h2>';
$tags1 .=   '</div>';


$tags1 .=   '<div class="col text-end" >';  
$tags1 .=   '<div class="btn-group" style="padding: 2px;">';

$tags1 .=     '<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=       'División Automotriz';
$tags1 .=     '</button>';
$tags1 .=     '<div class="dropdown-menu">';


$consult = $db->consultas("SELECT id,name,img_route,unique_photo FROM departamentos WHERE num=1 AND img_route != 'no' ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_name = $value->name;
    $dpto_uniq_photo = $value->unique_photo;
    $tags1 .= '<a class="dropdown-item" href="#" )">';
    $tags1 .= '<div class="form-check">';
    $tags1 .= '<input class="form-check-input" type="checkbox" value="" id="Checkme1" />';
    if($value->unique_photo == 'f')
        $tags1 .= '<label class="form-check-label" for="Checkme1" onClick="javascript:getCatalogoNormal('.$dpto_Id.',1)">'.$dpto_name.'</label>';
    else
        $tags1 .= '<label class="form-check-label" for="Checkme1" onClick="javascript:getCatalogoUnique('.$dpto_Id.')">'.$dpto_name.'</label>';

    $tags1 .= '</div>';
    $tags1 .= '</a>';
}
$tags1 .=   '</div>';
$tags1 .= '</div>';

$tags1 .=   '<div class="btn-group" style="padding: 2px;">';
$tags1 .=     '<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=       'División Ferreteria';
$tags1 .=     '</button>';
$tags1 .=     '<div class="dropdown-menu">';


$consult = $db->consultas("SELECT id,name,img_route,unique_photo FROM departamentos WHERE num=2 AND img_route != 'no' ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_name = $value->name;
    $dpto_uniq_photo = $value->unique_photo;
    $tags1 .= '<a class="dropdown-item" href="#" )">';
    $tags1 .= '<div class="form-check">';
    $tags1 .= '<input class="form-check-input" type="checkbox" value="" id="Checkme2" />';
    if($value->unique_photo == 'f')
        $tags1 .= '<label class="form-check-label" for="Checkme2" onClick="javascript:getCatalogoNormal('.$dpto_Id.',2)">'.$dpto_name.'</label>';
    else
        $tags1 .= '<label class="form-check-label" for="Checkme2" onClick="javascript:getCatalogoUnique('.$dpto_Id.')">'.$dpto_name.'</label>';

    $tags1 .= '</div>';
    $tags1 .= '</a>';
}
$tags1 .=   '</div>';

$tags1 .= '</div>';

$tags1 .=   '<div class="btn-group" style="padding: 2px;">';

$tags1 .=     '<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=       'Otros';
$tags1 .=     '</button>';
$tags1 .=     '<div class="dropdown-menu">';


$consult = $db->consultas("SELECT id,name,img_route,img_route,unique_photo FROM departamentos WHERE num=3 AND img_route != 'no' ORDER BY id");
foreach ($consult as $value){
    $dpto_Id = $value->id;
    $dpto_name = $value->name;
    $dpto_uniq_photo = $value->unique_photo;
    $tags1 .= '<a class="dropdown-item" href="#" )">';
    $tags1 .= '<div class="form-check">';
    $tags1 .= '<input class="form-check-input" type="checkbox" value="" id="Checkme3" />';
    if($value->unique_photo == 'f')
        $tags1 .= '<label class="form-check-label" for="Checkme3" onClick="javascript:getCatalogoNormal('.$dpto_Id.',3)">'.$dpto_name.'</label>';
    else
        $tags1 .= '<label class="form-check-label" for="Checkme3" onClick="javascript:getCatalogoUnique('.$dpto_Id.')">'.$dpto_name.'</label>';
    $tags1 .= '</div>';
    $tags1 .= '</a>';
}
$tags1 .=   '</div>';
$tags1 .= '</div>';

$tags1 .=   '</div>';
$tags1 .= '</div>';

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<title>Catalogo Ket</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

       
	</head>	
	<body >


    <div class="w-100 p-3" style="background-color: #FFF;">
        <div class="row align-items-start">
            <div class="col text-start">
                <img src="../catalogo/images/logo.png" alt="logo" />
            </div>    
            <div class="col text-end">
                <img src="../catalogo/images/sublogo.png" alt="logo" />
            </div>

        </div>
    </div>
    <div class="w-100 p-3" style="background-color: #DDD;">    
        <?php echo $tags1; ?>
       
    </div>

    <script type="text/javascript">
        function getCatalogoNormal(idDpto,checkNum){
            
            var checki = "Checkme"+checkNum;

            //console.log("check: "+document.getElementById(checki).checked);

            //console.log( "selected Departamento: "+idDpto );
            urlString = (document.getElementById(checki).checked === true)? "indexDptoAll1.php?dpto_id="+idDpto : "indexDptoAll2.php?dpto_id="+idDpto;
            window.location.href = urlString;
        }
        
        function getCatalogoUnique(idDpto){
            //console.log( "selected Departamento: "+idDpto );
            urlString ="indexDptoAllUniPhotoList.php?dpto_id="+idDpto;
            window.location.href = urlString;
        }
    </script>    
</body>

</html>	