<?php
require_once("dbcat.php");
$db = new DB();

$catId =  (isset($_POST['category']))?  intval($_POST['category']) : 1; 

if($catId>0)
{
    $consult = $db->consultas("SELECT name,img_route FROM gal_cat WHERE id=".$catId);
    foreach ($consult as $value){
        $catName = $value->name;
        $imgRoute = $value->img_route;
    }

    $tags  = '<div class="row row-cols-1 row-cols-md-2 g-2 ">';
    $tags .= '<div class="col text-end">';
    $tags .=    '<h2>Catalogo de '.$catName.'</h2>';
    $tags .= '</div>';

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
    $tags .=         '<i class="bi bi-search""></i>';
    $tags .=     '</button>';
    $tags .= '</form>';


    $tags .= '</div>';

    $tags .= '</div>';


    $tags .=    '<div class="row row-cols-1 row-cols-md-4 g-4 ">';
    for($i=0;$i<$numProducts;$i++)
    {
        $currUrl = $imgRoute.$productVals[$i]->url;
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

    $tags .= '</div>';
}
else
    $tags = '<p></p>';
echo $tags; 

/*$consult = $db->getProdCat($categoria);
echo $consult;*/
?>