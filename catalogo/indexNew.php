<?php
session_start();

//require_once("app/php/db.php");
require_once("../php/dbcat.php");

$tipoPrecio = 0;  
if( isset($_GET['prec']))
{
  $tipoPrecio = intval($_GET['prec']);
}

$role = (isset($_SESSION['role']))? intval($_SESSION['role']) : -1;

$line= 0;

$comeFrom=0;


if ( isset($_GET['line']) ) 
  $line = intval($_GET['line']);

if( isset($_GET['from']))
  $comeFrom = intval($_GET['from']);  




$backCond =   '<a href="#" onClick="backHome('.$role.','.$line.','.$tipoPrecio.','.$comeFrom.')" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>';

if ($line>0)
    $tag1 = getTag($line);
else
{
    $tag1  = getTag(1);
    $tag1 .= getTag(2);
}

function getTag($linea)
{
    $db = new DB();
    global $role,$tipoPrecio;
    $titulo = "Linea ";
    if ($linea==1) 
        $titulo .="Automotriz";
    elseif($linea==2) 
        $titulo .= "Ferretera";


    $tags = '<div class="col text-center">';

    $tags .=    '<h2> '.$titulo;
    $tags .= '</div>';

    $tags .=    '<div class="row row-cols-1 row-cols-sm-4 g-4 ">';

    $query  = "SELECT id,code,name,img_route FROM departamentos WHERE show='t'";
    $query .= " AND img_route !='no' AND num =".$linea." ORDER BY num,code";

    // echo "query: ".$query."\n";

    $consult1 = $db->consultas($query);
    foreach ($consult1 as $value1){
        $dptoVal_id = $value1->id;
        $dptoVal_code = $value1->code;
        $dptoVal_desc = $value1->name;
        $dptoVal_url = $value1->img_route."linkImg.jpg";

        $tags .=    '<div class="col" style="background-color: #DDD;">';
        $tags .=        '<div class="card h-100 text-bg-light" >';
        $tags .=            '<div class="card-header" style="background-color: #037C79;">';
        $tags .=                '<h3 style="color: #FFF;">'.$dptoVal_code.'</h3>';
        $tags .=            '</div>';
        $tags .=            '<a class="thumbnail" href="#" style="text-decoration : none" onclick="getCatalogo('.$dptoVal_id.','.$role.','.$tipoPrecio.')">';
        $tags .=                '<img src="'.$dptoVal_url.'" class="card-img-top" alt="'.$dptoVal_code.'">';
        $tags .=            '</a>';
        $tags .=            '<div class="card-body" style="background-color: #0CC;">';
        $tags .=                '<h6 class="card-text">'.$dptoVal_desc.'</h6>';
        $tags .=            '</div>';
        $tags .=        '</div>';
        $tags .=    '</div>';

        }

    $tags .=    '</div>'; 
    return $tags;
}

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
            .thumbnail:hover {
            transform: scale(0.97); 
            filter: drop-shadow(5px 5px 5px rgba(3, 124, 121, 0.80));
            }
        </style>
	</head>

	<body>

    <div class="w-100 p-0" style="background-color: #CCC;">
        <div class="row align-items-start" style="max-height: 50px;">
            <div class="col text-start" style="max-height: 40px; padding-left: 20px;  " > 
                <?php echo $backCond; ?>
            </div>    

            <div class="col text-center">
                <h2>Catálogo</h2>
            </div>
        

            <div class="col text-end" style="max-height: 40px;" >
                <img src="../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
            </div>       

        </div>
    </div>

    <div class="w-100 p-3" style="background-color: #DDD;"> 
        <div id="productos" >
            <?php echo $tag1; ?>
        </div>    
    </div>
   <script>

        function getCatalogo(idDpto,role,prec){   //,checkNum){   
            urlString =  "../catalogo/indexDptoAll2.php?dpto_id="+idDpto+"&line=1&prec="+prec+"&from=1";;
            window.location.href = urlString;
        }

       function backHome(rol,line,prec,from){    
        if(from==0)
        {
            switch(line)
            {
                case 1:
                    urlString =  "../listas/indexL1.php?prec="+prec;

                break;
                case 2:
                    urlString =  "../listas/indexL2.php?prec="+prec;

                break;
            }                                               
        }  
        else
            urlString =  "../listas/index.php?prec="+prec;

             
        window.location.href = urlString;
    }

  </script> 

</body>

</html>	