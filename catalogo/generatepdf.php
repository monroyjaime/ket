<?php
//require_once("app/php/db.php");
require_once("php/dbcat.php");

// $catId =  (isset($_GET['category']))?  intval($_GET['category']) : 1;  
$catId =  intval($_GET['category']);
$db = new DB();
$tags1  =   '<div class="invisible"><p id="categoryVal">'.$catId.'</p></div>';
$tags1 .=   '<nav class="navbar navbar-expand-lg">';
$tags1 .=   '<div class="container-fluid">';
$tags1 .=   '<a class="navbar-brand" href="#">';
$tags1 .=   '<i class="bi bi-book"></i>';
$tags1 .=   '<span class="text-secundary">Catalogo</span>';
$tags1 .=   '</a>';
$tags1 .=   '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mymenu" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">';
$tags1 .=         '<span class="navbar-toggler-icon"></span>';
$tags1 .=   '</button>';

$tags1 .=   '<div class="collapse navbar-collapse" id="mymenu"';

$tags1 .=   '<ul class="navbar-nav me-auto">';
$tags1 .=   '<li class="nav-item  list-unstyled dropdown">';
$tags1 .=   '<a class="nav-link  active dropdown-toggle" id="navbarDropdown" role="button" data-bs-toggle="dropdown" href="#" >Categoria</a>';
$tags1 .=   '<ul class="dropdown-menu bg-secondary">';
$consult = $db->consultas("SELECT id,name,img_route FROM gal_cat ORDER BY id");
foreach ($consult as $value){
    $objAux = new stdClass();
    $objAux->id = $value->id;
    $objAux->catName = $value->name;
    $objAux->imgRoute = $value->img_route;
    $arrCategories[] =$objAux;
    $tags1 .= '<li><a class="dropdown-item" href="#" onClick="javascript:setProductos('.$objAux->id.')">'.$objAux->catName.'</a></li>';
}
$tags1 .=   '</ul>';
$tags1 .=   '</li>';
$tags1 .= '</ul>';      //navbar-nav
$tags1 .= '</div>';     //collapse navbar-col
$tags1 .= '</div>';     //container-fluid
$tags1 .= '</nav>';

if($catId>0)
{
    $productVals = [];
 //   $tags .= '<div class="row align-items-start row-md">';
    $tags  = '<div class="row row-cols-1 row-cols-md-2 g-2 ">';
    $tags .= '<div class="col text-end">';
    $tags .=    '<h2>Catalogo de '.$arrCategories[$catId-1]->catName.'</h2>';
    $tags .= '</div>';
    $tags2  = '<div class="row row-cols-1 row-cols-md-2 g-2 ">';
    $tags2 .= '<div class="col text-end">';
    $tags2 .=    '<h2>Catalogo de '.$arrCategories[$catId-1]->catName.'</h2>';
    $tags2 .= '</div>';

    $numProducts=0;
    $query = "SELECT id,code,descripcion,photo_url FROM hist_gal WHERE show='t' AND category=".$arrCategories[$catId-1]->id." ORDER BY id";
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $productVal = new stdClass();
        $productVal->id = $value->id;
        $productVal->code = $value->code;
        $productVal->desc = $value->descripcion;
        $productVal->url = $value->photo_url;
        $productVals[] = $productVal;
        $numProducts++;
    }
    $tags .= '<div class="col text-start">';
    $tags .= '<form class="d-flex">';
    $tags .=     '<input id="search-input" class="form-control me-1" type="search" placeholder="Buscar" aria-label="search">';
    $tags .=     '<button id="search-button" class="btn btn-primary" type="button">';
    $tags .=         '<i class="bi bi-search"></i>';
    $tags .=     '</button>';
    $tags .= '</form>';


    $tags2 .= '</div>';
    $tags2 .= '</div>';

    $tags2 .= '<div class="col text-start">';
    $tags2 .= '<form class="d-flex">';
    $tags2 .=     '<input id="search-input" class="form-control me-1" type="search" placeholder="Buscar" aria-label="search">';
    $tags2 .=     '<button id="search-button" class="btn btn-primary" type="button">';
    $tags2 .=         '<i class="bi bi-search"></i>';
    $tags2 .=     '</button>';
    $tags2 .= '</form>';


    $tags2 .= '</div>';
    $tags2 .= '</div>';


    $tags2 .= '<div class="container"';
    $tags2 .= '<div class="row">';
    $tags2 .= '<div class="col-12">';
	$tags2 .= '<table class="table table-image">';
	$tags2 .= '<thead>';
	$tags2 .= '<tr>';
    $tags2 .= '<th scope="col">Num.</th>';
    $tags2 .= '<th scope="col">Imagen</th>';
    $tags2 .= '<th scope="col">...Codigo...</th>';
    $tags2 .= '<th scope="col">Descripcion</th>';

    $tags2 .= '</tr>';
    $tags2 .= '</thead>';


    $tags .=    '<div class="row row-cols-1 row-cols-md-4 g-4 ">';
    $tags2 .= '<tbody>';

    for($i=0;$i<$numProducts;$i++)
    {
        $currUrl = $arrCategories[$catId-1]->imgRoute.$productVals[$i]->url;
        $tags .=    '<div class="col" style="background-color: #DDD;">';
        $tags .=        '<div class="card h-100 text-bg-light">';
        $tags .=            '<div class="card-header" style="background-color: #eee;">';
        $tags .=                '<h3>'.$productVals[$i]->code.'</h3>';
        $tags .=            '</div>';
        $tags .=            '<img src="'.$currUrl.'" class="card-img-top" alt="'.$productVals[$i]->code.'">';
        $tags .=            '<div class="card-body" style="background-color: #eee;">';
        $tags .=                '<h6 class="card-text">'.$productVals[$i]->desc.'</h6>';
        $tags .=            '</div>';
        $tags .=        '</div>';
        $tags .=    '</div>';

        $tags2 .= '<tr>';
        $tags2 .= '<th scope="row">'.($i+1).'</th>';
        $tags2 .= '<td class="w-25">';
        $tags2 .= '<h3>'.$productVals[$i]->code.'</h3>';
        $tags2 .= '<img src="'.$currUrl.'" alt="'.$productVals[$i]->code.'" class="img-thumbnail">';
        $tags2 .= '</td>';
        $tags2 .= '<td>'.$productVals[$i]->code.'</td>';
        $tags2 .= '<td>'.$productVals[$i]->desc.'</td>';
        $tags2 .= '</tr>';
    }
}
$tags .=    '</div>';

$tags2 .= '</tbody>';
$tags2 .= '</table>';  
$tags2 .= '</div>';
$tags2 .= '</div>';
$tags2 .= '</div>';


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

        <script type="text/javascript">

        </script> 
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
        <div id="productos" >
            <?php echo $tags2; ?>
        </div>
    </div>

    <script type="text/javascript">
        var currCategory=0;
        const CategoryField = document.getElementById('categoryVal');
        $( document ).ready(function() {
            currCategory = CategoryField.textContent;
            console.log("Current category: "+currCategory);
        });
        
        const searchButton = document.getElementById('search-button');
        const searchInput = document.getElementById('search-input');
        searchButton.addEventListener('click', () => {
        const inputValue = searchInput.value;
        if(inputValue.length < 3)
            alert("Debe itroducir al menos 3 caracteres");
        else
            setProductosFilter(currCategory,inputValue);

        });

        function setProductos(idCate){
            console.log( "selected category2: "+idCate );
            urlString ="index.php?category="+idCate;
            window.location.href = urlString;

            return false;
        }    

        function setProductosFilter(cate,filter){
            //console.log( "selected category: "+idCate );
            $.post("php/getProdCatalogFilter.php",
            {
                category: cate,
                filtro: filter
            },
            function(data,status)
            {
                if(data==null)
                {
                    alert("No se encontararon registros que cumplan ese criterio de busqueda");
                }
                else
                {
                    $("#productos").empty();
                    myhtml = $.parseHTML( data );
                    $("#productos").append(myhtml).trigger("create");
                    //myhtml = $.parseHTML( data );
                    $("#productos").innerHTML = "";
                    $("#productos").innerHTML = myhtml;
                    //$("#table-column-toggle:visible" ).table("refresh");
                }

                
            })    

            return false;
        }    
    </script>    
</body>

</html>	