<?php
//require_once("app/php/db.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("https://ketelectropartes.com/php/dbcat.php");


$role = -1;                         

if ( isset($_GET['role_num']) ) 
  $role = intval($_GET['role_num']);

$presupestoId =  (isset($_GET['pres_num']))?  intval($_GET['pres_num']) : 0;
$pageNum =  (isset($_GET['page_num']))?  intval($_GET['page_num']) : 1;

$db = new DB();


    
$query  = "SELECT a.product_code,CONCAT(b.img_route,c.photo_url) AS img_full_route FROM presupuesto_detail a,departamentos b, productos c";
$query .= " WHERE b.img_route !='no' AND a.product_code = c.code and b.id=c.dpto_id AND a.pres_idx=".$presupestoId;

$consult = $db->consultas($query);



foreach ($consult as $value){
    $productVal = new stdClass();
    $productVal->code = $value->code;
    $productVal->url = $value->img_full_route;

    $productVals[] = $productVal;
    $numProducts++;
}

$numPages = ceil($numProducts/25);

$lastPageProdNum = ($pageNum == $numPages)? 25 - (($numPages*25 - $numProducts) + 1) : 24;
$tags = '<div class="col text-center">';

$tags .=    '<h2>Catalogo de '.$currCatName.' (Pag. '.$pageNum.' / '.$numPages.')';
$tags .= '</div>';

$tags .=    '<div class="row row-cols-1 row-cols-sm-5 g-5 ">';



$currProd=0;
$currRangeFrom = ($pageNum-1)*25;
$currRangeTo = ($pageNum-1)*25 + $lastPageProdNum;


for ($i=$currRangeFrom; $i<=$currRangeTo; $i++){

    $productVal_code =$productVals[$i]->code;
    $productVal_url = $productVals[$i]->url;



    //echo "currUrl: ".$currUrl;
    $tags .=    '<div class="col" style="background-color: #DDD;">';
    $tags .=        '<div class="card h-100 text-bg-light">';
    $tags .=            '<div class="card-header" style="background-color: #037C79;">';
    $tags .=                '<h3 style="color: #FFF;">'.$productVal_code.'</h3>';
    $tags .=            '</div>';
    $tags .=            '<img src="'.$productVal_url.'" class="card-img-top" alt="'.$productVal_code.'">';
    $tags .=            '<div class="card-body" style="background-color: #0CC;">';
    $tags .=                '<h6 class="card-text">'.$productVal_code.'</h6>';
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
                <a href="#" onClick="backHome()" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>
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
       function backHome(){      
        urlString =  "../index.php";
        window.location.href = urlString;
    }

  </script> 

</body>

</html>	