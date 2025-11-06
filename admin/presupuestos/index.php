<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Iniciar session solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SIMPLIFICAR: Variables básicas sin consultas a BD
$numUsr = isset($_SESSION['usr_num']) ? intval($_SESSION['usr_num']) : -1;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

$ableToPresupuesto = 'f';
$usrName = "Usuario no identificado";

// Solo mostrar botones si el usuario puede hacer presupuestos
if ($numUsr > 0) {
    $ableToPresupuesto = 't'; // Temporalmente forzamos a true para testing
    $btnsPedido  = '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalMakePedido" onClick="getSelected()" style="margin: 1px 2px 1px;"><i class="bi bi-gear"></i> Def. Presup.</button> ';
    $btnsPedido .= '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ModalShowPedido" onClick="showPedidoClient()" style="margin: 1px 2px 1px;"><i class="bi bi-file-earmark-ppt"></i> Ver Presup.</button> ';
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

    <script type="text/javascript">
        var roleNum = <?php echo $role; ?>;  
        var numUsr = <?php echo $numUsr; ?>;
        var codes_carrito = [];
    </script>

    <style>
        body {
            text-align: center;
            padding: 0px 0px;
        }
        .nav-link {
            color: #003272;
        }
        .icon-green {
            color: green;
        }
        .icon-yellow {
            color: yellow;
        }
        .icon-red {
            color: red;
        }
        .icon-large {
            font-size: 25px;
        }
        .icon-dark-blue {
            color: #003272;
        }
    </style>
</head>

<body>
<div class="w-100 p-0" style="background-color: #CCC;">
    <div class="row align-items-start" style="max-height: 50px;">
        <div class="col text-start" style="max-height: 40px; padding-left: 20px;">
            <a href="#" onClick="backHome()" title="Pag. Prev."><i class="bi bi-arrow-left-circle-fill icon-dark-blue icon-large"></i></a>
        </div>  
        <div class="col text-center" style="max-height: 40px; padding-bottom: 14px; padding-top: 1px;">
            <?php echo $btnsPedido ?? ''; ?>
        </div>
        <div class="col text-end" style="max-height: 40px;">
            <img src="../../catalogo/images/logoMini.png" class="img-fluid" alt="logo" />
        </div>       
    </div>
</div>

<div class="col text-center">
    <div class="col text-center" style="background-color: #DDD;">
        <?php echo $tituloLista; ?>
        
        <div id="toolbar" class="select">
            <select class="form-control"></select> 
        </div>

        <table
            id="table-main"
            class="bootstrap-table"
            data-toggle="table"
            data-show-export="false"
            data-click-to-select="false"
            data-show-columns="false"
            data-search="true"
            data-height="600"
            data-pagination="true"
            data-page-size="100" 
            data-url="../../php/getListaProdAllPresup.php"
            data-mobile-responsive="false">
            <thead>
                <tr>
                    <th data-field="code" data-halign="center" data-align="left">CODIGO</th>
                    <th data-field="name" data-halign="center" data-align="left" data-width="500">DESCRIPCION</th>
                    <th data-field="stock" data-halign="center" data-align="left">STOCK</th>
                    <th data-field="prec_min" data-halign="center" data-align="left">PREC 1</th>
                    <th data-field="prec_may" data-halign="center" data-align="left">PREC 2</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal Definir Presupuesto (SIMPLIFICADO) -->
<div class="modal fade" id="ModalMakePedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 95%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Definir Presupuesto</h4>
            </div>
            <div class="modal-body">
                <p>Modal de presupuesto - Funcionando correctamente</p>
                <button type="button" class="btn btn-success" onClick="guardarPresupuesto()">
                    <i class="bi bi-save"></i> Guardar Presupuesto
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>

<!-- JavaScript específico de presupuestos -->
<script src="../../js/presupuesto.js" type="text/javascript"></script>

<script type="text/javascript">
    // Funciones básicas
    function backHome() {      
        window.location.href = "../../";
    }

    function backToSelfAlt() {
        window.location.reload();
    }

    function showPedidoClient() {
        alert('Función mostrar pedidos - En desarrollo');
    }

    console.log('Página cargada correctamente');
</script>
</body>
</html>