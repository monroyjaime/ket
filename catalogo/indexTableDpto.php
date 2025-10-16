<?php
//require_once("app/php/db.php");
require_once("php/dbcat.php");

// $catId =  (isset($_GET['category']))?  intval($_GET['category']) : 1;  
$db = new DB();
$tags1  =   '<table class="table">';
$tags1 .=   '<thead>';
$tags1 .=    '<tr>';
$tags1 .=      '<th scope="col" style="text-align:right";>Departamento</th>';
$tags1 .=      '<th scope="col" style="text-align:center";>Opt.1</th>';
$tags1 .=      '<th scope="col" style="text-align:center";>Opt.2</th>';
$tags1 .=    '</tr>';
$tags1 .=  '</thead>';

$tags1 .=   '<tbody>';
$consult = $db->consultas("SELECT id,name,img_route,unique_photo FROM departamentos where img_route!='no' ORDER BY id");
foreach ($consult as $value){
    $tags1 .=    '<tr>';
    $tags1 .=    '<td style="text-align:right">'.$value->name.'</td>';
    if($value->unique_photo == 'f')
    {
        $tags1 .=    '<td><div class="d-grid gap-2"><button class="btn btn-primary" type="button" onClick="javascript:callPage('.$value->id.',1)">Adaptable</button></div></td>';   
        $tags1 .=    '<td><div class="d-grid gap-2"><button class="btn btn-primary" type="button" onClick="javascript:callPage('.$value->id.',2)">fijo</button></div></td>';   
    }
    else
    {
        $tags1 .=    '<td><div class="d-grid gap-2"><button class="btn btn-primary" type="button" onClick="javascript:callPageUnique('.$value->id.',1)">Adaptable</button></div></td>';   
        $tags1 .=    '<td><div class="d-grid gap-2"><button class="btn btn-primary" type="button" onClick="javascript:callPageUnique('.$value->id.',2)">fijo</button></div></td>';   
    }
    

    $tags1 .=    '</tr>';
}
$tags1  .=   '</tbody>';
$tags1  .=   '</table>';


?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<title>catalogo ket</title>
        <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">   
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

	</head>	
	<body >


    <div class="w-100 p-3" style="background-color: #FFF;">
        <div class="row align-items-start">
            <div class="col text-start">
                <img src="images/logo.png" class="img-fluid" alt="logo" />
            </div>    
            <div class="col text-end">
                <img src="images/sublogo.png" class="img-fluid" alt="logo" />
            </div>

        </div>
    </div>
    <div class="w-100 p-3" style="background-color: #DDD;">    
        <?php echo $tags1; ?>
    </div>
    <script type="text/javascript">
        function callPage(dpto,opt){
            console.log( "selected category: "+dpto );
            
            urlString = (opt==1)? "indexDptoAll2.php?dpto_id="+dpto : "indexDptoAll1.php?dpto_id="+dpto;

            window.location.href = urlString;

            return false;
        }

        function callPageUnique(dpto,opt){
            console.log( "selected category: "+dpto );
            
           // urlString = (opt==1)? "indexDptoAllUniPhoto2.php?dpto_id="+dpto : "indexDptoAllUniPhoto1.php?dpto_id="+dpto;
            urlString = "indexDptoAllUniPhotoList.php?dpto_id="+dpto;
            window.location.href = urlString;

            return false;        }
    </script>    

   
</body>

</html>	