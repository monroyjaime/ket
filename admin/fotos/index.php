<?php
// index.php - Sección de administración de fotos de productos
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Verificar permisos de administrador si es necesario
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = "Actualización de Fotos - Catálogo";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container-fluid {
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        .btn-actualizar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            transition: transform 0.2s;
        }
        .btn-actualizar:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .badge-departamento {
            background-color: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 15px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-camera-retro me-2"></i>
                    Actualización Masiva de Fotos - Productos sin Imagen
                </h4>
                <p class="mb-0 mt-2 small">
                    <i class="fas fa-info-circle"></i> 
                    Productos que actualmente tienen la imagen por defecto (empty.jpg) y necesitan ser actualizados
                </p>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    
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
                            return '<span class="badge-departamento"><i class="fas fa-building"></i> ' + data + '</span>';
                        }
                    },
                    { "data": "codigo" },
                    { "data": "descripcion" },
                    {
                        "data": null,
                        "render": function(data, type, row) {
                            return '<button class="btn btn-actualizar btn-sm" onclick="actualizarFoto(\'' + row.codigo + '\', \'' + row.departamento.replace(/'/g, "\\'") + '\')">' +
                                   '<i class="fas fa-camera"></i> Actualizar Foto</button>';
                        },
                        "orderable": false
                    }
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
                },
                "order": [[0, "asc"], [1, "asc"]], // Ordenar por departamento y luego por código
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
                        <div class="form-group">
                            <label for="fotoUrl">URL de la nueva imagen:</label>
                            <input type="text" class="form-control" id="fotoUrl" placeholder="https://ejemplo.com/imagen.jpg">
                        </div>
                        <div class="form-group mt-2">
                            <label>O selecciona un archivo:</label>
                            <input type="file" class="form-control-file" id="archivoFoto" accept="image/*">
                        </div>
                        <div id="previewImagen" class="mt-2 text-center" style="display:none;">
                            <img id="preview" src="" style="max-width: 200px; max-height: 200px;">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#667eea',
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