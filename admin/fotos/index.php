<?php
// admin/fotos/index.php - Gestión de fotos de productos
session_start();
require_once("../../php/dbcat.php");

// ============================================
// VERIFICACIÓN DE AUTORIZACIÓN (misma lógica que actualizar_catalogos.php)
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
                    "url": "getProductosSinFoto.php",
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
                    { 
                        "data": "departamento",
                        "render": function(data, type, row) {
                            return '<span class="badge-departamento"><i class="bi bi-building"></i> ' + data + '</span>';
                        }
                    },
                    { "data": "codigo" },
                    { "data": "descripcion" },
                    {
                        "data": null,
                        "render": function(data, type, row) {
                            return '<button class="btn btn-actualizar btn-sm" onclick="actualizarFoto(\'' + row.codigo + '\', \'' + row.departamento.replace(/'/g, "\\'") + '\')">' +
                                   '<i class="bi bi-camera"></i> Actualizar Foto</button>';
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
        
        // Función para actualizar foto
        function actualizarFoto(codigo, departamento) {
            Swal.fire({
                title: 'Actualizar Foto',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Producto:</strong> ${codigo}</p>
                        <p><strong>Departamento:</strong> ${departamento}</p>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label">URL de la nueva imagen:</label>
                            <input type="text" class="form-control" id="fotoUrl" placeholder="https://ejemplo.com/imagen.jpg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">O selecciona un archivo:</label>
                            <input type="file" class="form-control" id="archivoFoto" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        </div>
                        <div id="previewImagen" class="mt-2 text-center" style="display:none;">
                            <img id="preview" src="" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#037C79',
                preConfirm: () => {
                    const url = document.getElementById('fotoUrl').value;
                    const archivo = document.getElementById('archivoFoto').files[0];
                    
                    if (!url && !archivo) {
                        Swal.showValidationMessage('Debes proporcionar una URL o seleccionar un archivo');
                        return false;
                    }
                    
                    if (url && archivo) {
                        Swal.showValidationMessage('Solo puedes usar URL o archivo, no ambos');
                        return false;
                    }
                    
                    if (archivo) {
                        // Validar tipo de archivo
                        const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        if (!tiposPermitidos.includes(archivo.type)) {
                            Swal.showValidationMessage('Tipo de archivo no permitido. Usa JPG, PNG, GIF o WEBP');
                            return false;
                        }
                        
                        // Validar tamaño (máximo 2MB)
                        if (archivo.size > 2 * 1024 * 1024) {
                            Swal.showValidationMessage('El archivo no debe superar los 2MB');
                            return false;
                        }
                        
                        // Preparar FormData para subir archivo
                        const formData = new FormData();
                        formData.append('archivo', archivo);
                        formData.append('codigo', codigo);
                        formData.append('tipo', 'archivo');
                        
                        return fetch('actualizarFoto.php', {
                            method: 'POST',
                            body: formData
                        }).then(response => response.json());
                        
                    } else if (url) {
                        // Validar URL
                        if (!url.match(/^https?:\/\/.+\/.+\.(jpg|jpeg|png|gif|webp)$/i)) {
                            Swal.showValidationMessage('URL no válida. Debe ser una imagen (jpg, png, gif, webp)');
                            return false;
                        }
                        
                        return fetch('actualizarFoto.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                codigo: codigo,
                                url: url,
                                tipo: 'url'
                            })
                        }).then(response => response.json());
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: 'La foto se ha actualizado correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Recargar la tabla
                            $('#tablaProductos').DataTable().ajax.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.value.message || 'Error al actualizar la foto'
                        });
                    }
                }
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
        
        // Limpiar preview cuando se ingresa URL
        $(document).on('input', '#fotoUrl', function() {
            if ($(this).val()) {
                $('#previewImagen').hide();
                $('#archivoFoto').val('');
            }
        });
        
        // Limpiar URL cuando se selecciona archivo
        $(document).on('change', '#archivoFoto', function() {
            if ($(this).val()) {
                $('#fotoUrl').val('');
            }
        });
    </script>
</body>
</html>