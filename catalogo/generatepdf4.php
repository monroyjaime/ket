<?php
//require_once("app/php/db.php");
require_once("php/dbcat.php");

// $catId =  (isset($_GET['category']))?  intval($_GET['category']) : 1;  
$catId =  intval($_GET['category']);
$db = new DB();

$consult = $db->consultas("SELECT id,name,img_route FROM gal_cat ORDER BY id");
foreach ($consult as $value){
    $objAux = new stdClass();
    $objAux->id = $value->id;
    $objAux->catName = $value->name;
    $objAux->imgRoute = $value->img_route;
    $arrCategories[] =$objAux;
}


if($catId>0)
{
    $productVals = [];

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


    $numPagesPdf = ($numProducts%16 == 0)? intval($numProducts/16) : intval($numProducts/16 + 1);

    $tags2  = '<div class="container"';
    $tags2 .= '<div class="row">';
    $tags2 .= '<div class="col-12">';

	$tags2 .= '<table class="table table-borderless  table-image">';
	$tags2 .= '<thead>';
	$tags2 .= '<tr>';
    $tags2 .= '<th scope="col">Fila</th>';
    $tags2 .= '<th scope="col">Prod.1</th>';
    $tags2 .= '<th scope="col">Prod.2</th>';
    $tags2 .= '<th scope="col">Prod.3</th>';
    $tags2 .= '<th scope="col">Prod.4</th>';
    $tags2 .= '</tr>';
    $tags2 .= '</thead>';

    $tags2 .= '<tbody>';



    
}


for($i=0;$i<intval($numProducts/4);$i++)
{

    $tags2 .= '<tr>';

        $tags2 .= '<th scope="row">'.($i+1).'</th>';
        $tags2 .= '<td class="w-25">';
        $currUrl = $arrCategories[$catId-1]->imgRoute.$productVals[$i*4]->url;
        $tags2 .= '<h5>'.$productVals[$i*4]->code.'</h5>';
        $tags2 .= '<img src="'.$currUrl.'" alt="'.$productVals[$i*4]->code.'" class="img-thumbnail">';
        $tags2 .= '<p>'.$productVals[$i*4]->desc.'</p>';
        $tags2 .= '</td>';
        $tags2 .= '<td class="w-25">';
        $currUrl = $arrCategories[$catId-1]->imgRoute.$productVals[$i*4+1]->url;
        $tags2 .= '<h5>'.$productVals[$i*4+1]->code.'</h5>';
        $tags2 .= '<img src="'.$currUrl.'" alt="'.$productVals[$i*4+1]->code.'" class="img-thumbnail">';
        $tags2 .= '<p>'.$productVals[$i*4+1]->desc.'</p>';
        $tags2 .= '<td class="w-25">';
        $currUrl = $arrCategories[$catId-1]->imgRoute.$productVals[$i*4+2]->url;
        $tags2 .= '<h5>'.$productVals[$i*4+2]->code.'</h5>';
        $tags2 .= '<img src="'.$currUrl.'" alt="'.$productVals[$i*4+2]->code.'" class="img-thumbnail">';
        $tags2 .= '<p>'.$productVals[$i*4+2]->desc.'</p>';
        $tags2 .= '</td>';
        $tags2 .= '<td class="w-25">';
        $currUrl = $arrCategories[$catId-1]->imgRoute.$productVals[$i*4+3]->url;
        $tags2 .= '<h5>'.$productVals[$i*4+3]->code.'</h5>';
        $tags2 .= '<img src="'.$currUrl.'" alt="'.$productVals[$i*4+3]->code.'" class="img-thumbnail">';
        $tags2 .= '<p>'.$productVals[$i*4+3]->desc.'</p>';

        $tags2 .= '</tr>';

}

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
        <div id="productos" >
            <?php echo $tags2; ?>
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