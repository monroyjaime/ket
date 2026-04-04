<?php
function actualizarFoto(codigo, departamento, dptoId, tieneFotoActual) {
    var nombreEsperado = codigo + '.jpg';
    
    // Construir la URL de la foto actual si existe
    var fotoActualUrl = '';
    if (tieneFotoActual) {
        // Necesitamos obtener la URL de la foto actual
        // Podemos obtenerla de la fila actual de la tabla
        var row = $('#tablaProductos').DataTable().row($(this).parents('tr')).data();
        if (row && row.foto_actual && row.foto_actual !== 'empty.jpg') {
            // Obtener la ruta base del departamento (esto requeriría otra consulta o pasar el img_route)
            // Por ahora usamos una función aparte
            fotoActualUrl = obtenerUrlFotoActual(dptoId, row.foto_actual);
        }
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
                <img src="${fotoActualUrl}" style="max-width: 150px; max-height: 150px; border-radius: 8px; margin-top: 8px;">
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

// Función auxiliar para obtener la URL de la foto actual
function obtenerUrlFotoActual(dptoId, nombreFoto) {
    // Esta función debería obtener img_route del departamento
    // Por ahora, retornamos una ruta construida
    // Idealmente, podrías pasar también el img_route desde el DataTable
    return '/catalogo/images/productos/' + nombreFoto;
}

?>