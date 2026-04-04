<?php
// admin/fotos/index.php - Gestión de fotos de productos
session_start();

require_once("../../php/dbcat.php");

// ============================================
// VERIFICACIÓN DE AUTORIZACIÓN
// ============================================
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

// Si no está autenticado o no es administrador, mostrar error
if ($role == -1 || $isAdmin != 1) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Acceso Denegado - KET</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
        <style>
            body {
                background-color: #DDD;
                margin: 0;
                padding: 0;
                font-family: 'Segoe UI', Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .error-container {
                text-align: center;
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                max-width: 500px;
                margin: 20px;
            }
            .error-icon {
                font-size: 5rem;
                color: #dc3545;
                margin-bottom: 20px;
            }
            .error-title {
                color: #dc3545;
                font-size: 1.8rem;
                margin-bottom: 15px;
            }
            .error-message {
                color: #666;
                margin-bottom: 25px;
            }
            .btn-back {
                background-color: #003272;
                color: white;
                padding: 10px 25px;
                border-radius: 30px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s;
            }
            .btn-back:hover {
                background-color: #037C79;
                color: white;
            }
        </style>
        <script>
        // ============================================
        // AGREGAR ESTO AQUÍ - Dentro del <head> o antes de cerrar </head>
        // ============================================
        var SESSION_ID = '<?php echo session_id(); ?>';
        console.log('Session ID:', SESSION_ID);
    </script>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1 class="error-title">Acceso Denegado</h1>
            <p class="error-message">
                No tienes permisos para acceder a esta página.<br>
                Esta área está restringida a usuarios administradores.
            </p>
            <a href="../../index.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver al inicio
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$db = new DB();
$pageTitle = "Actualización de Fotos - Catálogo";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1">
    <title><?php echo $pageTitle; ?> - KET</title>
    <link rel="Shortcut Icon" href="../../favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            background-color: #DDD;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        /* Barra superior estilo página principal */
        .top-bar {
            background-color: #DDD;
            padding: 0px 5px;
        }
        
        .top-bar .row {
            align-items: center;
        }
        
        .top-bar .back-icon {
            font-size: 25px;
            color: #003272;
            text-decoration: none;
        }
        
        .top-bar .logo-mini {
            max-height: 40px;
            width: auto;
        }
        
        /* Título centrado sobre franja verde agua */
        .title-banner {
            background-color: #037c79;
            padding: 7px 0;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .title-banner h1 {
            color: white;
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .title-banner h1 i {
            font-size: 2rem;
        }
        
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
            border-radius: 8px;
        }
        
        .card-header {
            background-color: #003272;
            color: white;
            font-weight: bold;
            border-radius: 8px 8px 0 0 !important;
        }
        
        .btn-actualizar {
            background-color: #037C79;
            color: white;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-actualizar:hover {
            background-color: #003272;
            color: white;
            transform: translateY(-2px);
        }
        
        .badge-departamento {
            background-color: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            color: #003272;
            font-weight: 600;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 15px;
            border: 1px solid #ddd;
            margin-left: 10px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #037C79;
            color: white !important;
            border: none;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #003272;
            color: white !important;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #037C79;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-box i {
            color: #037C79;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Barra superior estilo página principal -->
    <div class="top-bar">
        <div class="row">
            <div class="col text-start">
                <a href="../../index.php" class="back-icon" title="Volver al inicio">
                    <i class="bi bi-arrow-left-circle-fill"></i>
                </a>
            </div>
            <div class="col text-end">
                <img src="../../catalogo/images/logoMini.png" class="logo-mini" alt="KET" />
            </div>
        </div>
    </div>
    
    <!-- Título centrado sobre franja verde agua -->
    <div class="title-banner">
        <h1>
            <i class="bi bi-camera-fill"></i>
            Actualización de Fotos - Catálogo
            <i class="bi bi-image-fill"></i>
        </h1>
    </div>

    <div class="container-fluid px-4">
        <div class="info-box">
            <i class="bi bi-info-circle-fill"></i>
            <strong>Instrucciones:</strong> Selecciona la imagen del producto. El sistema la renombrará automáticamente con el formato 
            <code>[código_producto].jpg</code> y la guardará en la carpeta correspondiente según el departamento.
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-image"></i> Productos sin imagen asignada
                <span class="float-end">
                    <i class="bi bi-info-circle-fill"></i> 
                    Productos con foto = empty.jpg
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaProductos" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Departamento</th>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables llenará aquí los datos -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            var table = $('#tablaProductos').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "getProductos.php",
                    "type": "GET",
                    "dataSrc": function(json) {
                        console.log("Datos recibidos:", json);
                        return json.data;
                    },
                    "error": function(xhr, error, thrown) {
                        console.error("Error en DataTable:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al cargar los productos: ' + error
                        });
                    }
                },
                "columns": [
                    { "data": "departamento" },
                    { "data": "codigo" },
                    { "data": "descripcion" },
                    {
                        "data": null,
                        "render": function(data, type, row) {
                        console.log('Row en render:', row);  // Ver qué contiene row
                        console.log('img_route en row:', row.img_route);
                        // Limpiar img_route
                        var imgRoute = row.img_route || '';
                        imgRoute = imgRoute.replace(/^https?:\/\/[^/]+\//, '');
                        imgRoute = imgRoute.replace(/^ketelectropartes\.com\//, '');
                        
                        // Escapar foto_actual para pasarla como parámetro
                        var fotoActual = row.foto_actual || '';
                        
                        if (data.has_photo) {
                            return '<button class="btn btn-warning btn-sm" onclick="actualizarFoto(\'' + data.codigo + '\', \'' + data.departamento.replace(/'/g, "\\'") + '\', ' + data.dpto_id + ', true, \'' + imgRoute + '\', \'' + fotoActual + '\')">' +
                                '<i class="bi bi-camera"></i> Cambiar Foto</button>';
                        } else {
                            return '<button class="btn btn-actualizar btn-sm" onclick="actualizarFoto(\'' + data.codigo + '\', \'' + data.departamento.replace(/'/g, "\\'") + '\', ' + data.dpto_id + ', false, \'' + imgRoute + '\', \'' + fotoActual + '\')">' +
                                '<i class="bi bi-camera"></i> Actualizar Foto</button>';
                        }
                    },
                        "orderable": false
                    }
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
                },
                "order": [[0, "asc"], [1, "asc"]],
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]]
            });
        });
        
        // Función para actualizar foto - MODIFICADA sin opción URL
function actualizarFoto(codigo, departamento, dptoId, tieneFotoActual, imgRoute, fotoActual) {
    console.log('=== actualizarFoto ===');
    console.log('codigo:', codigo);
    console.log('departamento:', departamento);
    console.log('dptoId:', dptoId);
    console.log('tieneFotoActual:', tieneFotoActual);
    console.log('imgRoute RECIBIDO:', imgRoute);
    console.log('fotoActual:', fotoActual);
    // Limpiar y preparar la ruta base
    var rutaBase = imgRoute || '';
    rutaBase = rutaBase.replace(/^https?:\/\/[^/]+\//, '');
    rutaBase = rutaBase.replace(/^ketelectropartes\.com\//, '');
    if (rutaBase && !rutaBase.endsWith('/')) rutaBase += '/';
    
    var nombreEsperado = codigo + '.jpg';
    
    // Construir la URL de la foto actual
    var fotoActualUrl = '';
    if (tieneFotoActual && fotoActual && fotoActual !== 'empty.jpg' && fotoActual !== 'none') {
        fotoActualUrl = '/' + rutaBase + fotoActual;
        console.log('Foto actual URL:', fotoActualUrl); // Para depuración
    }
    
    // Construir el HTML del modal
    var modalHtml = `
        <div style="text-align: left;">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Producto:</strong> ${codigo}<br>
                <strong>Departamento:</strong> ${departamento}<br>
                <strong>Nombre del archivo esperado:</strong> <code>${nombreEsperado}</code>
            </div>`;
    
    // Si tiene foto actual, mostrar miniatura
    if (tieneFotoActual && fotoActualUrl) {
        modalHtml += `
            <div class="alert alert-secondary">
                <strong>Foto actual:</strong><br>
                <img src="${fotoActualUrl}" style="max-width: 150px; max-height: 150px; border-radius: 8px; margin-top: 8px; border: 1px solid #ddd;">
            </div>`;
    }
    
    modalHtml += `
            <div class="mb-3">
                <label class="form-label">Seleccionar nueva imagen:</label>
                <input type="file" class="form-control" id="archivoFoto" accept="image/jpeg,image/jpg">
                <small class="text-muted">Solo archivos JPG. El archivo se renombrará automáticamente.</small>
            </div>
            <div id="previewImagen" class="mt-2 text-center" style="display:none;">
                <img id="preview" src="" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
            </div>
        </div>
    `;
    
    Swal.fire({
        title: tieneFotoActual ? 'Cambiar Foto' : 'Actualizar Foto',
        html: modalHtml,
        showCancelButton: true,
        confirmButtonText: tieneFotoActual ? 'Cambiar Foto' : 'Subir y Actualizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: tieneFotoActual ? '#f39c12' : '#037C79',
        didOpen: () => {
            const input = document.getElementById('archivoFoto');
            if (input) {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const previewDiv = document.getElementById('previewImagen');
                    const previewImg = document.getElementById('preview');
                    
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewDiv.style.display = 'block';
                            previewImg.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        previewDiv.style.display = 'none';
                    }
                });
            }
        },
        preConfirm: () => {
            const archivo = document.getElementById('archivoFoto').files[0];
            
            if (!archivo) {
                Swal.showValidationMessage('Debes seleccionar un archivo de imagen');
                return false;
            }
            
            if (archivo.type !== 'image/jpeg' && archivo.type !== 'image/jpg') {
                Swal.showValidationMessage('Solo se permiten archivos JPG');
                return false;
            }
            
            if (archivo.size > 2 * 1024 * 1024) {
                Swal.showValidationMessage('El archivo no debe superar los 2MB');
                return false;
            }
            
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('codigo', codigo);
            formData.append('dpto_id', dptoId);
            
            return fetch('upload_final.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'La foto se ha actualizado correctamente',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                $('#tablaProductos').DataTable().ajax.reload();
            });
        }
    }).catch((error) => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al actualizar la foto'
        });
    });
}
        
        // Previsualización de imagen al seleccionar archivo
        $(document).on('change', '#archivoFoto', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#previewImagen').show();
                    $('#preview').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            } else {
                $('#previewImagen').hide();
            }
        });
    </script>
</body>
</html>