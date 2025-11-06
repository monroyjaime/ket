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
        .form-check {
            margin-bottom: 2px;
        }
        .form-check-label {
            font-size: 0.8rem;
        }
        .badge {
            font-size: 0.75rem;
        }
        .precio-manual-input {
            width: 100px;
        }
        .cantidad-input {
            width: 80px;
        }
        .tiempo-select {
            width: 110px;
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

<!-- Modal Definir Presupuesto (COMPLETO) -->
<div class="modal fade" id="ModalMakePedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 95%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Definir Presupuesto</h4>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="row">
                        <div class="col">
                            <div class="p-4">
                                <h6>Cliente:</h6>
                                <select id="clients-tom-sel" placeholder="Seleccione Cliente..." autocomplete="off">
                                    <option value="0">Seleccione Cliente...</option>
                                </select> 
                            </div> 
                        </div>
                        <div class="col">
                            <div class="p-4">
                                <h6>Número de Presupuesto:</h6>
                                <input type="text" class="form-control" id="numero-presupuesto" placeholder="Número automático o manual">
                            </div>
                        </div>  
                    </div>
                </div> 

                <table
                    id="table-carrito"
                    class="bootstrap-table"
                    data-table-type="make-pedido"
                    data-toggle="table"  
                    data-height="400"
                    data-checkbox-header="false"
                    data-url="../../php/getCarritoCurrentData.php">
                    <thead>
                        <tr>
                            <th data-field="edo" data-formatter="edoFormater" data-width="40"></th>
                            <th data-field="code" data-halign="center" data-align="left" data-width="100">Código</th>
                            <th data-field="name" data-halign="center" data-align="left" data-width="300">Descripción</th>
                            <th data-field="stock" data-halign="center" data-align="center" data-width="80" data-formatter="stockFormater">Stock</th>
                            <th data-field="llegando" data-halign="center" data-align="center" data-width="90" data-formatter="llegandoFormater">Llegando</th>
                            <th data-field="precio_opciones" data-halign="center" data-align="center" data-width="200" data-formatter="precioOpcionesFormater">Precio</th>
                            <th data-field="precio_manual" data-halign="center" data-align="center" data-width="120" data-formatter="precioManualFormater">Precio Manual</th>
                            <th data-field="cantidad" data-halign="center" data-align="center" data-width="100" data-formatter="cantidadFormater">Cantidad</th>
                            <th data-field="unidad" data-halign="center" data-align="center" data-width="80">Unidad</th>
                            <th data-field="tiempo_entrega" data-halign="center" data-align="center" data-width="120" data-formatter="tiempoEntregaFormater">Tiempo Entrega</th>
                            <th data-field="monto" data-halign="center" data-align="right" data-width="100" data-formatter="montoFormater">Monto</th>
                        </tr>
                    </thead>
                </table>
                
                <div style="text-align: right; margin-top: 20px;">
                    <a class="updTot" href="javascript:void(0)" onClick="updateTotal()" title="update">
                        <i class="bi bi-arrow-clockwise"></i>
                        <h4 id="MontoTotal" style="color: #037C79; font-weight: bold;"></h4>
                    </a>
                </div>

                <div class="input-group mt-3">
                    <span class="input-group-text">Comentarios del Presupuesto:</span>
                    <textarea class="form-control" id="comentarioPresupuesto" rows="2"></textarea>
                </div>

                <button type="button" class="btn btn-success btn-lg" id="reg-presupuesto" onClick="guardarPresupuesto()" style="margin: 20px 40px 10px;">
                    <i class="bi bi-save"></i> Guardar Presupuesto
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal"><i class="bi bi-arrow-return-left"></i> Regresar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mostrar Pedidos (SIMPLIFICADO) -->
<div class="modal fade" id="ModalShowPedido" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                <h4 class="modal-title">Mostrar Presupuestos</h4>
            </div>
            <div class="modal-body">
                <p>Modal para mostrar presupuestos - En desarrollo</p>
            </div>
        </div> 
    </div>
</div>

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