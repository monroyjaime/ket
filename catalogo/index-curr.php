<?php
//require_once("app/php/db.php");
require_once("php/dbcat.php");

$catId =  (isset($_GET['category']))?  intval($_GET['category']) : 0;  
$db = new DB();

$tags1  =   '<div class="col text-end" >';
$tags1 .=     '<button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=       'Categorias';
$tags1 .=     '</button>';
$tags1 .=     '<ul class="dropdown-menu">';


$consult = $db->consultas("SELECT id,name,img_route FROM gal_cat ORDER BY id");
foreach ($consult as $value){
    $objAux = new stdClass();
    $cat_Id = $value->id;
    $objAux->catName = $value->name;
    $objAux->imgRoute = $value->img_route;
    $arrCategories[] =$objAux;
    $tags1 .= '<li><a class="dropdown-item" href="#" onClick="javascript:setProductos('.$cat_Id.')">'.$objAux->catName.'</a></li>';
}
$tags1 .=   '</ul>';
$tags1 .= '</div>';
if($catId>0)
{
    $tags .= '<div class="container text-center">';
    $tags .=    '<h1>Catalogo de '.$arrCategories[$catId-1]->catName.'</h1>';

    $tags .=    '<div class="row row-cols-1 row-cols-md-4 g-4 ">';
    $numProducts=0;
    $query = "SELECT id,code,descripcion,photo_url FROM hist_gal WHERE show='t' AND category=".$catId." ORDER BY id";
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $currCode = $value->code;
        $currDesc = $value->descripcion;
        $currUrl = $arrCategories[$catId-1]->imgRoute.$value->photo_url;

        $tags .=    '<div class="col" style="background-color: #DDD;">';
        $tags .=        '<div class="card h-100 text-bg-light">';
        $tags .=            '<div class="card-header" style="background-color: #eee;">';
        $tags .=                '<h3>'.$currCode.'</h3>';
        $tags .=            '</div>';
        $tags .=            '<img src="'.$currUrl.'" class="card-img-top" alt="'.$currCode.'">';
        $tags .=            '<div class="card-body" style="background-color: #eee;">';
    //  $tags .=                '<h5 class="card-title">'.$currCode.'</h5>';

        $tags .=                '<h6 class="card-text">'.$currDesc.'</h6>';
        $tags .=            '</div>';
        $tags .=        '</div>';
        $tags .=    '</div>';


        $numProducts++;
    }
}
$tags .=    '</div>';
$tags .= '</div>';


?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<title>catalogo ket</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

       
	</head>	
	<body >


    <div class="w-100 p-3" style="background-color: #FFF;">
        <div class="row align-items-start">
            <div class="col text-start">
                <img src="images/logo.png" alt="logo" />
            </div>    
            <div class="col text-end">
                <img src="images/sublogo.png" alt="logo" />
            </div>

        </div>
    </div>
    <div class="w-100 p-3" style="background-color: #DDD;">    
        <?php echo $tags1; ?>
        <div id="productos" >
            <?php echo $tags; ?>
        </div>
    </div>

    <script type="text/javascript">
        function setProductos(idCate){
            //console.log( "selected category: "+idCate );
            $.post("php/getProdCatalog.php",
            {
                category: idCate
            },
            function(data,status)
            {

                $("#productos").empty();
                myhtml = $.parseHTML( data );
                $("#productos").append(myhtml).trigger("create");
                //myhtml = $.parseHTML( data );
                $("#productos").innerHTML = "";
                $("#productos").innerHTML = myhtml;
                //$("#table-column-toggle:visible" ).table("refresh");
            })

            return false;
        }    
    </script>    
</body>

</html>	