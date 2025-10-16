<?php
session_start();

require_once("dbcat.php");
$statuses =array("Registrado","Procesandose","Despachado");
$colorStatus = array("#AA0000","#AA5200","#188203");
$db = new DB();
$tags='';
$pedidoNum = -1;
$tipoPrecio = (isset($_SESSION['prec']))? intval($_SESSION['prec']) : 0;  
$numUsr = (isset($_SESSION['usr_num']))? intval($_SESSION['usr_num']) : -1;
if($numUsr > -1 )
{
    $consult=$db->consultas("SELECT full_name FROM usuario WHERE num=".$numUsr);
    foreach ($consult as $value)
        $usrName= $value->full_name;
    $tags .= '<div class="col text-center">';
    $tags .= '<h2 style="background-color:rgb(0, 0, 0); padding-botton: 14px; color: #FFF;">Lista de pedidos de '.$usrName.'</h2>'; 
    $tags .= '</div>';
    $tags .= '<div id="accordion">';

    $countPedidos = 1;
    $query  = "select * from pedido_general where  user_num=".$numUsr;
    $query .= " order by pedido_num desc";
     $consult = $db->consultas($query);
   foreach ($consult as $value)
   {
        $currNum = intval($value->pedido_num);
        $currNumValery = ($value->num_valery=="no")? "n/d" : $value->num_valery;
        $colorNum = ($value->num_valery=="no")? "#AAA" : "#000";
        $currTipoPrec = intval($value->tipo_precio);
        $currStatus = intval($value->status);
        $currFecha = $value->fecha;
        $currHora = explode(":",$value->hora)[0].":".explode(":",$value->hora)[1];
        $query1  = "select cast(cast(sum(cantidad*precio)*1000 AS int) AS real)/1000";
        $query1 .= " AS total_pedido from pedido_detail where num=".$currNum;
        $consult1 = $db->consultas($query1);
        foreach ($consult1 as $value1)
            $currTotal = $value1->total_pedido;
        $tags .= '<div class="card">';
        $tags .= '<div class="card-header">';
        if($countPedidos==1) 
            $tags .= '<a class="card-link" data-bs-toggle="collapse" href="#collapse'.$countPedidos.'">';
        else
            $tags .= '<a class="collapsed card-link" data-bs-toggle="collapse" href="#collapse'.$countPedidos.'">';
        //$tags .= '     Pedido Num. '.$currNumValery.' (status: '.$statuses[$currStatus].')';
       
        $tags .= '<div class="container">';
        $tags .= '<div class="row">';
        $tags .= '   <div class="col-sm">';
        $tags .= '     Pedido Num. <i style="color:'.$colorNum.'; font-style: normal;font-weight: bold">'.$currNumValery.'</i> (<i style="color:'.$colorStatus[$currStatus].'; font-style: normal;font-weight: bold">'.$statuses[$currStatus].'</i>)';
        $tags .= '   </div>';
        $tags .= '  <div class="col-sm">';
        $tags .= '     Fecha / Hora: <i style="color:rgb(0, 0, 0); font-style: normal;font-weight: bold">'.$currFecha.' / '.$currHora.'</i>';
        $tags .= '   </div>';
        $tags .= '   <div class="col-sm">';
        $tags .= '     Total: <i style="color:rgb(0, 0, 0); font-style: normal;font-weight: bold">$'.$currTotal.'</i>';
        $tags .= '   </div>';
        $tags .= ' </div>';
        $tags .= '</div>'; 
        $tags .= '    </a>';
        $tags .= '</div>';
        if($countPedidos==1) 
            $tags .=  '<div id="collapse'.$countPedidos.'" class="collapse show" data-bs-parent="#accordion">';
        else
            $tags .=  '<div id="collapse'.$countPedidos.'" class="collapse" data-bs-parent="#accordion">';
        $tags .=    '<div class="card-body">';

        $tags .= '<table id ="tableSelected" class="table">';
        $tags .= '<thead>';
        $tags .=   '<tr>';
        $tags .=     '<th scope="col">Código</th>';
        $tags .=     '<th scope="col">descripción</th>';
        $tags .=     '<th scope="col">Cantidad</th>';
        $tags .=     '<th scope="col">Unidad</th>';
        $tags .=     '<th scope="col">Precio</th>';
        $tags .=     '<th scope="col">Monto</th>';
        $tags .=  '</tr>';
        $tags .= '</thead>';
        $tags .='<tbody>';

        $query2  = 'select product_code,(select name from productos where code = product_code),';
        $query2 .= 'cantidad,precio,(select unit from productos where code = product_code),'; 
        $query2 .= 'cast(cast(precio*cantidad*1000 as int) as real)/1000';
        $query2 .= ' as monto from pedido_detail where num='.$currNum;
        $consult2 = $db->consultas($query2);
        foreach($consult2 as $value2)
        {
            $tags .= '<tr>';
            $tags .=   '<td>'.$value2->product_code.'</td>';
            $tags .=   '<td>'.$value2->name.'</td>';
            $tags .=   '<td>'.$value2->cantidad.'</td>';
            $tags .=   '<td>'.$value2->unit.'</td>';
            $tags .=   '<td>$'.$value2->precio.'</td>';
            $tags .=   '<td>$'.$value2->monto.'</td>';
            $tags .= '</tr>';
        }

        $tags .='</tbody>';
        $tags .='</table>';
        $tags .=     '</div>';
        $tags .=    '</div>';
        $tags .= '</div>';
        $countPedidos++;
    }
    $tags .= '</div>';

}
echo $tags;


?>    