<?php
//require_once("app/php/db.php");
require_once("php/dbcat.php");
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

$tags .=    '<div class="row row-cols-4 g-4 ">';

$query  = "SELECT id,code,name FROM productos WHERE show='t' AND cost_max > 0 AND dpto_id=";
$query .= $dptoId." ORDER BY code";
//echo "query: ".$query."\n";

$consult1 = $db->consultas($query);
foreach ($consult1 as $value1){
    $productVal_id = $value1->id;
    $productVal_code = $value1->code;
    $productVal_desc = $value1->name;
 //   $productVal_url = $value1->photo_url;

 //   $currUrl = $currCatImgRoute.$productVal_url;
    $tags .=    '<div class="col" style="background-color: #DDD;">';
    $tags .=        '<div class="card h-100 text-bg-light">';
    $tags .=            '<div class="card-header" style="background-color: #eee;">';
    $tags .=                '<h3>'.$productVal_code.'</h3>';
    $tags .=            '</div>';
 //   $tags .=            '<img src="'.$currUrl.'" class="card-img-top" alt="'.$productVal_code.'">';
    $tags .=            '<div class="card-body" style="background-color: #eee;">';
    $tags .=                '<h6 class="card-text">'.$productVal_desc.'</h6>';
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
 <!--       <meta name="viewport" content="initial-scale=1, maximum-scale=1"> -->
        <meta name="viewport" content="initial-scale=0.1">
		<title>catalogo ket</title>
        <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">  
        <link rel="stylesheet" href="css/non-responsive.css">  

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

	</head>

	<body>
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
        <?php echo $tags; ?>
    </div>
    

</body>

</html>	