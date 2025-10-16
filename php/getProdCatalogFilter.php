<?php
require_once("dbcat.php");
$db = new DB();

$catId =  (isset($_POST['category']))?  intval($_POST['category']) : 1; 
$filter = (isset($_POST['filtro']))? $_POST['filtro'] : "";

$numProducts=0;

if($catId>0 && $filter!="")
{
    $consult = $db->consultas("SELECT name,img_route FROM gal_cat WHERE id=".$catId);
    foreach ($consult as $value){
        $catName = $value->name;
        $imgRoute = $value->img_route;
    }
    $tags  = '<div class="container text-center">';
    $tags  = '<div class="row row-cols-1 row-cols-md-2 g-2 ">';
    $tags .= '<div class="col text-end">';
    $tags .=     '<button id="return-button" class="btn btn-primary" type="button" onClick="javascript:setProductos('.$catId.')">';
    $tags .=         '<i class="bi bi-arrow-return-left"></i>';
    $tags .=     '</button>';
    $tags .= '</div>';
    $tags .= '<div class="col text-start">';
    $tags .=    '<h1>'.$catName.' (*'.$filter.'*)</h1>';
    $tags .= '</div>';
    $tags .= '</div>';
    $tags .=    '<div class="row row-cols-1 row-cols-md-4 g-4 ">';
    $query  = "SELECT id,code,descripcion,photo_url FROM hist_gal WHERE show='t' AND category=".$catId;
    $query .= " AND (LOWER(code) LIKE LOWER('%".$filter."%') OR LOWER(descripcion) LIKE LOWER('%";
	$query .= $filter."%')) ORDER BY code";
    $consult = $db->consultas($query);
    foreach ($consult as $value){
        $currCode = $value->code;
        $currDesc = $value->descripcion;
        $currUrl = $imgRoute.$value->photo_url;

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
    
    $tags .=    '</div>';
    $tags .= '</div>';
}
else
    $tags = NULL;

if($numProducts==0)  
    $tags = NULL;
    
echo $tags; 

/*$consult = $db->getProdCat($categoria);
echo $consult;*/
?>