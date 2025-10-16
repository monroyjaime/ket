<?php
//require_once("app/php/db.php");
require_once("php/dbcat.php");

$line= 1;
$role = -1;                         

if ( isset($_GET['role_num']) ) 
  $role = intval($_GET['role_num']);

if ( isset($_GET['line']) ) 
  $line = intval($_GET['line']);  


$backCond =   '<a href="#" onClick="backHome('.$role.','.$line.')" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>';


$dptoId =  (isset($_GET['dpto_id']))?  intval($_GET['dpto_id']) : 1;

$db = new DB();

$consult = $db->consultas("SELECT name,img_route FROM departamentos WHERE id=".$dptoId);
foreach ($consult as $value){
    $currCatName = $value->name;
    $currCatImgRoute = $value->img_route;
}

$tags = '<div class="col text-center">';

$tags .=    '<h2>Catalogo de '.$currCatName;
$tags .= '</div>';

$tags .=    '<div class="row row-cols-1 row-cols-sm-4 g-4 ">';

$query  = "SELECT id,code,name,photo_url,cost_max,unit,current_stock FROM productos WHERE show='t' AND dpto_id=";
$query .= $dptoId." AND photo_url != 'empty.jpg' AND cost_max > 0 ORDER BY orden,code";

//echo "query: ".$query."\n";

$consult1 = $db->consultas($query);
foreach ($consult1 as $value1){
    $productVal_id = $value1->id;
    $productVal_code = $value1->code;
    $productVal_desc = $value1->name;
    $productVal_url = $value1->photo_url;
    $productVal_unit = $value1->unit;

    $productVal_current_stock = $value1->current_stock;


    $productVal_cost = floatval($value1->cost_max);
    $productVal_cost_80 = floatval($value1->cost_max)*.8;


    $currUrl = $currCatImgRoute.$productVal_url;
    $tags .=    '<div class="col" style="background-color: #DDD;">';
    $tags .=        '<div class="card h-100 text-bg-light">';
    $tags .=            '<div class="card-header" style="background-color: #037C79;">';
    $tags .=                '<h3 style="color: #FFF;">'.$productVal_code.'</h3>';
    $tags .=            '</div>';
    $tags .=            '<img src="'.$currUrl.'" class="card-img-top" alt="'.$productVal_code.'">';
    $tags .=            '<div class="card-body" style="background-color: #0CC;">';
    $tags .=                '<h6 class="card-text">'.$productVal_desc.'</h6>';
    if($role>-1)
    {
        $tags .=            '<h5 class="card-text" >Prec. : $'.number_format($productVal_cost,3,",").'</h5>';
        $tags .=            '<h5 class="card-text" >-20% : $'.number_format($productVal_cost_80,3,",").'</h5>';
        $tags .=            '<h6 class="card-text">Unidad: '.$productVal_unit.'</h6>';
    }
    $tags .=            '</div>';
    $tags .=        '</div>';
    $tags .=    '</div>';

    }

$tags .=    '</div>'; 

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
        <link rel="stylesheet" href="css/non-responsive.css">  

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

        <style>
            .icon-large {
                font-size: 25px;
            }
            .icon-dark-blue{
                color: #003272;
            }
        </style>
	</head>

	<body>

    <div class="w-100 p-0" style="background-color: #CCC;">
        <div class="row align-items-start" style="max-height: 50px;">
            <div class="col text-start" style="max-height: 40px; padding-left: 20px;  " > 
                <?php echo $backCond; ?>
            </div>    
        

            <div class="col text-end" style="max-height: 40px;" >
                <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
            </div>       

        </div>
    </div>

    <div class="w-100 p-3" style="background-color: #DDD;"> 
        <div id="productos" >
            <?php echo $tags; ?>
        </div>    
    </div>
   <script>
       function backHome(rol,line){    
           
        switch(line)
        {
          case 1:
            urlString =  "../listas/indexL1.php?role_num="+rol;

          break;
          case 2:
            urlString =  "../listas/indexL2.php?role_num="+rol;

          break;
        }     
        window.location.href = urlString;
    }

  </script> 

</body>

</html>	