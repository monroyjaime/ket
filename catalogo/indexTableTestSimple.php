<?php
require_once("php/dbcat.php");
$db = new DB();
$tags1  =   '<div class="col text-end" >';
$tags1 .=     '<button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
$tags1 .=       'Departamentos';
$tags1 .=     '</button>';
$tags1 .=     '<ul class="dropdown-menu">';

$consult = $db->consultas("SELECT id,name FROM categorias ORDER BY id");
foreach ($consult as $value)
{
    $dptoId = $value->id;
    $dptoName = $value->name;
    $tags1 .= '<li><a class="dropdown-item" href="#" onClick="javascript:setProductos('.$dtpoId.')">'.$dptoName.'</a></li>';
}
$tags1 .=   '</ul>';
$tags1 .= '</div>';

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<title>catalogo ket</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

       
</head>

<body>

    <div id="productos" >
        <?php echo $tags1; ?>
    </div>
<!--
    <table
    id="table"
    data-toggle="table"
    data-search="true"
    data-height="600"
    data-pagination="true"
    data-url="https://ketelectropartes.com/catalogo/php/getListaPrec.php?dpto=6">
    <thead>
      <tr>
        <th data-field="code">CODIGO</th>
        <th data-field="name">DESCRIPCION</th>
        <th data-field="cost_max">PRECIO</th>
        <th data-field="unit">UNIDAD</th>

      </tr>
    </thead>
  </table>
    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
-->
 <script type="text/javascript">

        function setProductos(dptoId)
        {
            console.log("selected Id: "+dptoId);
        }

</script>

    <!-- <script>
    var $table = $('#table')
  
    function refreshTable() {
      $table.bootstrapTable('refreshOptions', {
        paginationSuccessivelySize: +$('#paginationSuccessivelySize').val(),
        paginationPagesBySide: +$('#paginationPagesBySide').val(),
        paginationUseIntermediate: $('#paginationUseIntermediate').prop('checked')
      })
    }
  
    $(function() {
      $('.toolbar input').change(refreshTable)
    })
  </script> --> 
</body>
</html>
