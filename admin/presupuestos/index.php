<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar session solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../php/dbcat_async.php");
$db = new DBAsync();

$clientNum = 0;
$usrName = "Usuario no identificado";
$clientName = "";
$clientCode = "";
$ganan_glob = 0;
$desc_glob = 0;

$numUsr = filter_var($_SESSION['usr_num'] ?? -1, FILTER_VALIDATE_INT) ?: -1;
$role = filter_var($_SESSION['role'] ?? -1, FILTER_VALIDATE_INT) ?: -1;

$ableToPresupuesto = 'f';
$showAllPres = 'f';

// Consulta de datos de usuario
if ($numUsr > 0) {
    try {
        $consult = $db->consultaSegura("SELECT do_presupuesto, show_all_pres, full_name, client FROM usuario WHERE num = $1", [$numUsr]);
        
        if (!empty($consult)) {
            foreach ($consult as $value) {
                $ableToPresupuesto = $value->do_presupuesto;
                $showAllPres = $value->show_all_pres;
                $usrName = $value->full_name;
                $clientNum = intval($value->client);
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta usuario: " . $e->getMessage());
    }

    if ($ableToPresupuesto == 't') {
        $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 2px 1px;"><i class="bi bi-gear"></i> Def. Presup.</button> ';
        $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalShowPedido" onClick="showPedidoClient()" style="margin: 1px 2px 1px;"><i class="bi bi-file-earmark-ppt"></i> Ver Presup.</button> ';
    }
}

// Consulta de productos en carrito
$prodsCarrito = [];
if ($numUsr > 0 && $ableToPresupuesto == 't') {
    try {
        $consult = $db->consultaSegura("SELECT product_code,cantidad,precio,tiempo_entrega FROM presupuesto_carrito WHERE user_num = $1 ORDER BY product_code", [$numUsr]);
        
        foreach ($consult as $value) {
            $objRtn = new stdClass();
            $objRtn->code = $value->product_code;
            $objRtn->cantidad = intval($value->cantidad);
            $objRtn->precio = floatval($value->precio);
            $objRtn->tiempo_entrega =intval($value->tiempo_entrega);
            $prodsCarrito[] = $objRtn;
        }
    } catch (Exception $e) {
        error_log("Error en consulta carrito: " . $e->getMessage());
    }
}

$pedidoCheckColumn = ($numUsr > 0 && $ableToPresupuesto == 't') ? '<th data-field="checked" data-checkbox="true"  data-formatter="checkFormater"></th>' : '';

// Consulta de datos de cliente
if ($clientNum > 0) {
    try {
        $consult = $db->consultaSegura("SELECT code, full_name FROM cliente WHERE num = $1", [$clientNum]);
        
        if (!empty($consult)) {
            foreach ($consult as $value) {
                $clientCode = $value->code;
                $clientName = $value->full_name;
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta cliente: " . $e->getMessage());
    }
}

$clientDefined = ($clientNum > 0);

$queUsuario = ($showAllPres == 't') ? "todos los usuarios" : htmlspecialchars($usrName);
$usrNameTag = '<h4 style="background-color: #6c757d; padding-bottom: 14px; color: #FFF;">Lista de presupuestos de '.$queUsuario.'</h4>';

// Preparar opciones de clientes
$optionText = ($clientNum == 0) ? "Seleccione Cliente..." : htmlspecialchars($clientCode.' --- '.$clientName);
$inputCliTomSel = '<option value="'.$clientNum.'">'.$optionText.'</option>';

$queryClients = ($showAllPres == 't') ? 
    "SELECT num, code, full_name FROM cliente ORDER BY num" : 
    "SELECT num, code, full_name FROM cliente WHERE vendedor = (SELECT vendedor FROM usuario WHERE num = $1) ORDER BY num";

try {
    if ($showAllPres == 't') {
        $consult = $db->consultaSegura("SELECT num, code, full_name FROM cliente ORDER BY num");
    } else {
        $consult = $db->consultaSegura($queryClients, [$numUsr]);
    }
    
    foreach ($consult as $value) {
        $inputCliTomSel .= '<option value="'.$value->num.'">'.htmlspecialchars($value->code.' --- '.$value->full_name).'</option>';
    }
} catch (Exception $e) {
    error_log("Error en consulta clientes: " . $e->getMessage());
}

// Consulta de valores globales
try {
    $consult = $db->consultaSegura("SELECT ganancia_min_glob, descuento_max_glob FROM all_ket_values");
    
    if (!empty($consult)) {
        foreach ($consult as $value) {
            $ganan_glob = floatval($value->ganancia_min_glob);
            $desc_glob = floatval($value->descuento_max_glob);
        }
    }
} catch (Exception $e) {
    error_log("Error en consulta all_ket_values: " . $e->getMessage());
}

$tituloLista = '<h2 style="background-color: #037C79; padding-bottom: 14px; color: #FFF;">Presupuestos</h2>';

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Ket-Listas de Precios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/css/tom-select.css" rel="stylesheet">
    
    <!-- Estilos específicos de presupuesto -->
    <link rel="stylesheet" href="../../css/presupuesto.css">

    <script type="text/javascript">
        var roleNum = <?php echo $role; ?>;  
        var client_num = <?php echo $clientNum; ?>;
        var client_code = <?php echo json_encode($clientCode); ?>;
        var client_name = <?php echo json_encode($clientName); ?>;
        var codes_carrito = <?php echo json_encode($prodsCarrito); ?>;
        var numUsr = <?php echo $numUsr; ?>;
    </script>

    <style>
        /* Solo estilos generales que ya tenías */
        body { text-align: center; padding: 0px 0px; }
        .nav-link { color: #003272; }
        /* ... (mantener solo los estilos generales) */
    </style>
</head>

<body>
<!-- Todo el HTML del body se mantiene igual -->
<!-- ... -->

<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/mobile/bootstrap-table-mobile.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/tableExport.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF/jspdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF-AutoTable/jspdf.plugin.autotable.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/extensions/export/bootstrap-table-export.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.0.0-rc.4/dist/js/tom-select.complete.min.js"></script>
<script src="../../js/jquery.redirect.js" type="text/javascript"></script>

<!-- JavaScript específico de presupuestos -->
<script src="../../js/presupuesto.js" type="text/javascript"></script>

<script type="text/javascript">
    // Solo el JavaScript general que no es de presupuestos
    var $tableMain = $('#table-main');
    var $tableShowPedido = $('#table-pedidos');
    
    // Funciones generales que ya tenías
    function fotoFormater(value, row) { /* ... */ }
    function checkFormater(value, row) { /* ... */ }
    function precioFormatergen(value, row) { /* ... */ }
    // ... (solo mantener funciones generales)
    
    function backHome() {      
        urlString = "../../";
        window.location.href = urlString;
    }

    function backToSelfAlt() {
        window.location.reload();
    }

    function showPedidoClient() {
        $.post("../../php/getInputGroupPedidosClient.php", {}, 
            function(data, status) {
                $('#ModalShowPedido #inputGroupPedidos').html(data);
                $('#ModalShowPedido').modal({show:true});
            });
    }

    // Event handlers generales
    $(function() {
        $tableMain.bootstrapTable({})
            .on('check.bs.table', function(e, row) {
                $.post("../../php/insDelOneProdCarrito.php", { action: 1, code: row.code });
            })
            .on('uncheck.bs.table', function(e, row) {
                $.post("../../php/insDelOneProdCarrito.php", { action: 0, code: row.code });
            });

        // Mejorar la barra de búsqueda
        $('.float-right.search.btn-group').find('input').attr('placeholder', '....');
        $('.float-right.search.btn-group').find('input').wrap("<div class='input-group' id='awsearch'> </div>"); 
        $('#awsearch').prepend("<span class='input-group-addon'><i class='bi bi-search icon-dark-blue'></i> Buscar</span>");
    });
</script>
</body>
</html>