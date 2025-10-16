<?php
//require_once("app/php/db.php");
require_once("php/dbcat.php");

$db = new DB();

$consult = $db->consultas("SELECT id,name,img_route FROM gal_cat WHERE id<3 ORDER BY id");
foreach ($consult as $value){
    $objAux = new stdClass();
    $objAux->id = $value->id;
    $objAux->catName = $value->name;
    $objAux->imgRoute = $value->img_route;
    $arrCategories[] =$objAux;
 //   $tags1 .= '<li><a class="dropdown-item" href="#" onClick="javascript:setProductos('.$objAux->id.')">'.$objAux->catName.'</a></li>';
}

$tags = '<div class="col text-center">';

for($cat=0;$cat<count($arrCategories);$cat++)
{
    $productVals = [];
 //   $tags .= '<div class="row align-items-start row-md">';
    $tags .= '<div class="col text-center">';

    $tags .=    '<h2>Catalogo de '.$arrCategories[$cat]->catName;
    
    $numProducts=0;
    $query  = "SELECT id,code,descripcion,photo_url FROM hist_gal WHERE show='t' AND category=";
    $query .= $arrCategories[$cat]->id." AND photo_url != 'empty.jpg' ORDER BY id";
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

    $tags .= '</div>';



    $tags .=    '<div class="row row-cols-1 row-cols-md-4 g-4 ">';

    for($i=0;$i<count($productVals);$i++)
    {
        $currUrl = $arrCategories[$cat]->imgRoute.$productVals[$i]->url;
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
    }
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
            <?php echo $tags; ?>
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
            //$.get(urlString);
/*            $.post("php/getProdCatalog.php",
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
            }) */
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