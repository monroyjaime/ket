// presupuesto.js - Versión simplificada con márgenes desde PHP
console.log('✅ presupuesto.js cargado - Márgenes disponibles:', ganancia_min_glob, descuento_max_glob);

// Variables globales
var $tableMakePedido, ctrlClientSel;

// Función simplificada para verificar márgenes
function verificarMargenPrecio(costo, precio) {
    if (precio <= 0) {
        return { cumpleMargen: false, esCero: true };
    }
    
    if (costo <= 0) {
        return { cumpleMargen: true, esCero: false };
    }
    
    const precioMinimoRequerido = costo * ganancia_min_glob;
    const cumpleMargen = precio >= precioMinimoRequerido;
    
    console.log(`💰 Margen verificado: ${costo} × ${ganancia_min_glob} = ${precioMinimoRequerido.toFixed(3)} vs ${precio} → ${cumpleMargen ? '✅' : '❌'}`);
    
    return { cumpleMargen: cumpleMargen, esCero: false };
}

// Función getSelected 

function getSelected() {
    console.log('🎯 getSelected ejecutado');
    verificarEstadoCarrito();
    
    forzarActualizacionCarrito().then(() => {
        console.log('✅ Después de forzarActualizacionCarrito:');
        verificarEstadoCarrito();
        
        $('#ModalMakePedido').modal('show');
        
        setTimeout(() => {
            if ($tableMakePedido && $tableMakePedido.length > 0) {
                console.log('🔄 Refrescando tabla del carrito...');
                $tableMakePedido.bootstrapTable('refresh');
                
                // Verificar datos de la tabla después de refrescar
                setTimeout(() => {
                    const tableData = $tableMakePedido.bootstrapTable('getData');
                    console.log('📊 Datos en tabla carrito:', tableData.length, 'filas');
                }, 1000);
            }
        }, 500);
    });
}



// Función para inicializar el Tom Select en el modal
function initPresupuestoModal() {
    console.log('🔧 Inicializando Tom Select...');
    
    // VERIFICAR si Tom Select ya está inicializado y destruirlo primero
    if (ctrlClientSel && ctrlClientSel.initialized) {
        console.log('🔄 Tom Select ya inicializado, destruyendo...');
        ctrlClientSel.destroy();
    }
    
    // Verificar que el elemento existe antes de inicializar Tom Select
    if ($('#clients-tom-sel').length > 0) {
        try {
            ctrlClientSel = new TomSelect("#clients-tom-sel", {
                sortField: { field: "text", direction: "asc" },
                onChange: function() {
                    var selectedClient = parseInt(ctrlClientSel.getValue()) || 0;
                    $('#reg-presupuesto').prop('disabled', selectedClient <= 0);
                },
                create: true,
                createOnBlur: true
            });
            // Marcar como inicializado
            ctrlClientSel.initialized = true;
            console.log('✅ Tom Select inicializado correctamente');
        } catch (e) {
            console.error('❌ Error inicializando Tom Select:', e);
        }
    } else {
        console.error('❌ Elemento #clients-tom-sel no encontrado');
    }
}

// Función para forzar actualización del carrito

function forzarActualizacionCarrito() {
    return new Promise((resolve) => {
        console.log('🔄 Forzando actualización del carrito...');
        $.get("../../php/getCarritoCurrentData.php", function(data) {
            try {
                const carritoData = JSON.parse(data);
                codes_carrito = carritoData.map(item => ({
                    code: item.code,
                    cantidad: item.cantidad,
                    precio: item.precio,
                    tiempo_entrega: item.tiempo_entrega
                }));
                console.log('✅ Carrito sincronizado:', codes_carrito.length, 'productos');
                
                // Refrescar tabla principal para actualizar checks
                if (typeof $tableMain !== 'undefined' && $tableMain.length > 0) {
                    $tableMain.bootstrapTable('refresh');
                }
                
                resolve(); // Resolver la Promise cuando termine
            } catch (e) {
                console.error('Error parseando carrito:', e);
                codes_carrito = [];
                resolve();
            }
        }).fail(function() {
            console.error('Error cargando carrito');
            codes_carrito = [];
            resolve();
        });
    });
}

// Función debounce optimizada
function debounce(func, timeout = 1000) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
}

// Función para refrescar tabla con Promise
function refreshCarritoTable() {
    return new Promise((resolve) => {
        if ($tableMakePedido && $tableMakePedido.length > 0) {
            $tableMakePedido.bootstrapTable('refresh');
            $tableMakePedido.one('load-success.bs.table', function() {
                resolve();
            });
        } else {
            resolve();
        }
    });
}

// Formateadores para la tabla del carrito
function stockFormater(value, row) {
    const stock = parseInt(row.stock) || 0;
    if (stock > 0) {
        return '<span class="badge bg-success">' + stock + '</span>';
    } else {
        return '<span class="badge bg-danger">' + stock + '</span>';
    }
}

function llegandoFormater(value, row) {
    const llegando = parseInt(row.llegando) || 0;
    if (llegando > 0) {
        return '<span class="badge bg-warning text-dark">' + llegando + '</span>';
    } else {
        return '<span class="badge bg-secondary">0</span>';
    }
}

function precioOpcionesFormater(value, row) {
    const precMin = parseFloat(row.prec_min) || 0;
    const precMay = parseFloat(row.prec_may) || 0;
    const prec3 = parseFloat(row.prec_3) || 0;
    const costo = parseFloat(row.costo) || 0;
    const precioActual = parseFloat(row.precio) || 0;
    
    let selectedMin = '';
    let selectedMay = '';
    let selected3 = '';
    
    if (precioActual === precMin) {
        selectedMin = 'checked';
    } else if (precioActual === precMay) {
        selectedMay = 'checked';
    } else if (precioActual === prec3) {
        selected3 = 'checked';
    }
    
    // Verificar márgenes para cada precio
    const resultadoMin = verificarMargenPrecio(costo, precMin);
    const resultadoMay = verificarMargenPrecio(costo, precMay);
    const resultado3 = verificarMargenPrecio(costo, prec3);
    
    // Calcular el factor de ganancia actual para cada precio
    const factorMin = costo > 0 ? (precMin / costo).toFixed(2) : 'N/A';
    const factorMay = costo > 0 ? (precMay / costo).toFixed(2) : 'N/A';
    const factor3 = costo > 0 ? (prec3 / costo).toFixed(2) : 'N/A';
    
    return `
        <div class="form-check">
            <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                   value="${precMin}" ${selectedMin} ${!resultadoMin.cumpleMargen ? 'disabled' : ''} onchange="seleccionarPrecio(this, '${row.code}')">
            <label class="form-check-label small">
                Precio 1: $${precMin.toFixed(3).replace('.', ',')}
                ${costo > 0 ? `<span class="badge badge-margen ${resultadoMin.cumpleMargen ? 'bg-success' : 'bg-danger'}">${factorMin}x</span>` : ''}
                ${!resultadoMin.cumpleMargen && !resultadoMin.esCero ? '<span class="badge bg-danger ms-1">Margen</span>' : ''}
                ${resultadoMin.esCero ? '<span class="badge bg-secondary ms-1">Cero</span>' : ''}
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                   value="${precMay}" ${selectedMay} ${!resultadoMay.cumpleMargen ? 'disabled' : ''} onchange="seleccionarPrecio(this, '${row.code}')">
            <label class="form-check-label small">
                Precio 2: $${precMay.toFixed(3).replace('.', ',')}
                ${costo > 0 ? `<span class="badge badge-margen ${resultadoMay.cumpleMargen ? 'bg-success' : 'bg-danger'}">${factorMay}x</span>` : ''}
                ${!resultadoMay.cumpleMargen && !resultadoMay.esCero ? '<span class="badge bg-danger ms-1">Margen</span>' : ''}
                ${resultadoMay.esCero ? '<span class="badge bg-secondary ms-1">Cero</span>' : ''}
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                   value="${prec3}" ${selected3} ${!resultado3.cumpleMargen ? 'disabled' : ''} onchange="seleccionarPrecio(this, '${row.code}')">
            <label class="form-check-label small">
                Precio 3: $${prec3.toFixed(3).replace('.', ',')}
                ${costo > 0 ? `<span class="badge badge-margen ${resultado3.cumpleMargen ? 'bg-success' : 'bg-danger'}">${factor3}x</span>` : ''}
                ${!resultado3.cumpleMargen && !resultado3.esCero ? '<span class="badge bg-danger ms-1">Margen</span>' : ''}
                ${resultado3.esCero ? '<span class="badge bg-secondary ms-1">Cero</span>' : ''}
            </label>
        </div>
        <div class="small text-muted mt-1">
            Costo: $${costo.toFixed(3).replace('.', ',')} | Mínimo requerido: ${ganancia_min_glob}x
        </div>
    `;
}

function precioManualFormater(value, row) {
    const precioActual = parseFloat(row.precio) || 0;
    const precMin = parseFloat(row.prec_min) || 0;
    const precMay = parseFloat(row.prec_may) || 0;
    
    const mostrarManual = (precioActual !== precMin && precioActual !== precMay && precioActual > 0);
    
    return `
        <input class="form-control precio-manual-input" type="number" step="0.001" min="0" 
               value="${mostrarManual ? precioActual : ''}" 
               data-code="${row.code}" 
               placeholder="Manual..."
               onfocus="this.select()" 
               oninput="actualizarPrecioManual(this)"/>
    `;
}

function precioCombinadoFormater(value, row) {
    const precMin = parseFloat(row.prec_min) || 0;
    const precMay = parseFloat(row.prec_may) || 0;
    const prec3 = parseFloat(row.prec_3) || 0;
    const costo = parseFloat(row.costo) || 0;
    const precioActual = parseFloat(row.precio) || 0;
    
    // Determinar qué precio está seleccionado actualmente
    let precioSeleccionado = '';
    let esManual = false;
    
    if (precioActual === precMin) {
        precioSeleccionado = 'precio1';
    } else if (precioActual === precMay) {
        precioSeleccionado = 'precio2';
    } else if (precioActual === prec3) {
        precioSeleccionado = 'precio3';
    } else if (precioActual > 0) {
        precioSeleccionado = 'manual';
        esManual = true;
    }
    
    // Verificar márgenes para cada precio
    const resultadoMin = verificarMargenPrecio(costo, precMin);
    const resultadoMay = verificarMargenPrecio(costo, precMay);
    const resultado3 = verificarMargenPrecio(costo, prec3);
    
    // Calcular factores de ganancia
    const factorMin = costo > 0 ? (precMin / costo).toFixed(2) : 'N/A';
    const factorMay = costo > 0 ? (precMay / costo).toFixed(2) : 'N/A';
    const factor3 = costo > 0 ? (prec3 / costo).toFixed(2) : 'N/A';
    
    return `
        <div class="precio-combinado-container">
            <!-- Selector de precios predefinidos -->
            <div class="precio-opciones mb-2">
                <div class="form-check form-check-inline">
                    <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                           value="${precMin}" ${precioSeleccionado === 'precio1' ? 'checked' : ''} 
                           ${!resultadoMin.cumpleMargen ? 'disabled' : ''} 
                           onchange="seleccionarPrecio(this, '${row.code}')">
                    <label class="form-check-label small">
                        $${precMin.toFixed(3).replace('.', ',')}
                        <span class="badge badge-margen ${resultadoMin.cumpleMargen ? 'bg-success' : 'bg-danger'}">${factorMin}x</span>
                    </label>
                </div>
                
                <div class="form-check form-check-inline">
                    <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                           value="${precMay}" ${precioSeleccionado === 'precio2' ? 'checked' : ''} 
                           ${!resultadoMay.cumpleMargen ? 'disabled' : ''} 
                           onchange="seleccionarPrecio(this, '${row.code}')">
                    <label class="form-check-label small">
                        $${precMay.toFixed(3).replace('.', ',')}
                        <span class="badge badge-margen ${resultadoMay.cumpleMargen ? 'bg-success' : 'bg-danger'}">${factorMay}x</span>
                    </label>
                </div>
                
                <div class="form-check form-check-inline">
                    <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                           value="${prec3}" ${precioSeleccionado === 'precio3' ? 'checked' : ''} 
                           ${!resultado3.cumpleMargen ? 'disabled' : ''} 
                           onchange="seleccionarPrecio(this, '${row.code}')">
                    <label class="form-check-label small">
                        $${prec3.toFixed(3).replace('.', ',')}
                        <span class="badge badge-margen ${resultado3.cumpleMargen ? 'bg-success' : 'bg-danger'}">${factor3}x</span>
                    </label>
                </div>
            </div>
            
            <!-- Input de precio manual -->
            <div class="precio-manual-container">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Manual:</span>
                    <input class="form-control precio-manual-input" type="number" step="0.001" min="0" 
                           value="${esManual ? precioActual : ''}" 
                           data-code="${row.code}" 
                           placeholder="0.000"
                           onfocus="this.select()" 
                           oninput="actualizarPrecioManual(this)"/>
                    <span class="input-group-text">$</span>
                </div>
            </div>
            
            <!-- Información de costos -->
            <div class="precio-info small text-muted mt-1">
                Costo: $${costo.toFixed(3).replace('.', ',')} | Mínimo: ${ganancia_min_glob}x
            </div>
        </div>
    `;
}

function cantidadFormater(value, row) {
    return `
        <input class="form-control cantidad-input" type="number" min="0" 
               value="${value}" 
               data-code="${row.code}" 
               onfocus="this.select()" 
               oninput="actualizarCantidad(this)"/>
    `;
}

function tiempoEntregaFormater(value, row) {
    const stock = parseInt(row.stock) || 0;
    const llegando = parseInt(row.llegando) || 0;
    const cantidad = parseInt(row.cantidad) || 0;
    const tiempoActual = parseInt(value) || 0;
    
    let tiempoSugerido = 0;
    if (cantidad > 0) {
        if (stock >= cantidad) {
            tiempoSugerido = 0;
        } else if (llegando >= cantidad) {
            tiempoSugerido = 7;
        } else {
            tiempoSugerido = 30;
        }
    }
    
    const tiempoMostrar = tiempoActual > 0 ? tiempoActual : tiempoSugerido;
    
    return `
        <select class="form-control tiempo-select" data-code="${row.code}" onchange="actualizarTiempoEntrega(this)">
            <option value="0" ${tiempoMostrar === 0 ? 'selected' : ''}>Inmediato</option>
            <option value="7" ${tiempoMostrar === 7 ? 'selected' : ''}>7 días</option>
            <option value="15" ${tiempoMostrar === 15 ? 'selected' : ''}>15 días</option>
            <option value="30" ${tiempoMostrar === 30 ? 'selected' : ''}>30 días</option>
            <option value="45" ${tiempoMostrar === 45 ? 'selected' : ''}>45 días</option>
            <option value="60" ${tiempoMostrar === 60 ? 'selected' : ''}>60 días</option>
            <option value="90" ${tiempoMostrar === 90 ? 'selected' : ''}>90 días</option>
        </select>
    `;
}

function montoFormater(value, row) {
    const cantidad = parseInt(row.cantidad) || 0;
    const precio = parseFloat(row.precio) || 0;
    const monto = cantidad * precio;
    
    if (monto > 0) {
        return `<strong>$${monto.toFixed(3).replace('.', ',')}</strong>`;
    } else {
        return '$0,000';
    }
}

function edoFormater(value, row) {
    const cantidad = parseInt(row.cantidad) || 0;
    if (cantidad > 0) {
        return '<i class="bi bi-check-circle-fill icon-green" title="En presupuesto"></i>';
    }
    return '<i class="bi bi-dash-circle icon-secondary" title="Sin cantidad"></i>';
}

function relacionadoFormater(value, row) {
    if (value && value.trim() !== '') {
        return '<span class="badge bg-info text-dark" title="Productos relacionados">' + value + '</span>';
    }
    return '';
}

// Funciones de interacción del carrito

function seleccionarPrecio(radio, code) {
    const precio = parseFloat(radio.value) || 0;
    
    // CAMBIAR ESTO: en lugar de vaciar el input, poner el precio seleccionado
    $(`.precio-manual-input[data-code="${code}"]`).val(precio);
    
    debounce(() => {
        $.post("../../php/updPrecioOneProdCarrito.php", {
            code: code,
            precio: precio
        }, function(data) {
            if (data == '1') {
                refreshCarritoTable().then(() => {
                    updateTotal();
                });
            }
        });
    })();
}




function actualizarPrecioManual(input) {
    const code = input.getAttribute('data-code');
    const precio = parseFloat(input.value) || 0;
    
    // Deseleccionar cualquier radio button cuando se usa precio manual
    $(`.precio-radio[name="precio_${code}"]`).prop('checked', false);
    
    debounce(() => {
        $.post("../../php/updPrecioOneProdCarrito.php", {
            code: code,
            precio: precio
        }, function(data) {
            if (data == '1') {
                refreshCarritoTable().then(() => {
                    updateTotal();
                });
            }
        });
    })();
}


function actualizarCantidad(input) {
    const code = input.getAttribute('data-code');
    const cantidad = parseInt(input.value) || 0;
    
    debounce(() => {
        $.post("../../php/updCantOneProdCarrito.php", {
            code: code,
            cantidad: cantidad
        }, function(data) {
            if (data == '1') {
                refreshCarritoTable().then(() => {
                    updateTotal();
                    recalcularTiempoEntrega(code, cantidad);
                });
            }
        });
    })();
}

function actualizarTiempoEntrega(select) {
    const code = select.getAttribute('data-code');
    const tiempo = parseInt(select.value) || 0;
    
    debounce(() => {
        $.post("../../php/updTiempoOneProdCarrito.php", {
            code: code,
            tiempo_entrega: tiempo
        }, function(data) {
            console.log('Tiempo actualizado: ' + data);
        });
    })();
}

// Funciones de cálculo
function recalcularTiempoEntrega(code, cantidad) {
    if ($tableMakePedido && $tableMakePedido.length > 0) {
        var rows = $tableMakePedido.bootstrapTable('getData');
        const row = rows.find(r => r.code === code);
        
        if (row) {
            const stock = parseInt(row.stock) || 0;
            const llegando = parseInt(row.llegando) || 0;
            let tiempoSugerido = 0;
            
            if (cantidad > 0) {
                if (stock >= cantidad) {
                    tiempoSugerido = 0;
                } else if (llegando >= cantidad) {
                    tiempoSugerido = 7;
                } else {
                    tiempoSugerido = 30;
                }
                
                $(`.tiempo-select[data-code="${code}"]`).val(tiempoSugerido);
                
                $.post("../../php/updTiempoOneProdCarrito.php", {
                    code: code,
                    tiempo_entrega: tiempoSugerido
                });
            }
        }
    }
}

function updateTotal() {
    if ($tableMakePedido && $tableMakePedido.length > 0) {
        var rows = $tableMakePedido.bootstrapTable('getData');
        let total = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const cantidad = parseInt(rows[i].cantidad) || 0;
            const precio = parseFloat(rows[i].precio) || 0;
            total += cantidad * precio;
        }
        
        const totalFormateado = total.toFixed(3).replace('.', ',');
        $('#MontoTotal').html('Total Presupuesto: $' + totalFormateado);
    }
}

function inicializarTiemposEntrega() {
    if ($tableMakePedido && $tableMakePedido.length > 0) {
        var rows = $tableMakePedido.bootstrapTable('getData');
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const stock = parseInt(row.stock) || 0;
            const llegando = parseInt(row.llegando) || 0;
            const cantidad = parseInt(row.cantidad) || 0;
            const tiempoActual = parseInt(row.tiempo_entrega) || 0;
            
            if (cantidad > 0) {
                let tiempoSugerido = 0;
                
                if (stock >= cantidad) {
                    tiempoSugerido = 0;
                } else if (llegando >= cantidad) {
                    tiempoSugerido = 7;
                } else {
                    tiempoSugerido = 30;
                }
                
                if (tiempoSugerido !== tiempoActual) {
                    $.post("../../php/updTiempoOneProdCarrito.php", {
                        code: row.code,
                        tiempo_entrega: tiempoSugerido
                    });
                }
            }
        }
        
        refreshCarritoTable();
    }
}

// Función para guardar presupuesto

function guardarPresupuesto() {
    const selectedClientNum = parseInt(ctrlClientSel.getValue()) || 0;
    const numeroPresupuesto = $('#numero-presupuesto').val();
    const comentarioPresupuesto = $('#comentarioPresupuesto').val();

    if (selectedClientNum === 0) {
        alert('Por favor seleccione un cliente');
        return;
    }

    if (!numeroPresupuesto) {
        alert('Por favor ingrese un número de presupuesto');
        return;
    }

    if ($tableMakePedido && $tableMakePedido.length > 0) {
        var rows = $tableMakePedido.bootstrapTable('getData');
        const productos = [];

        for (let i = 0; i < rows.length; i++) {
            if (parseInt(rows[i].cantidad) > 0) {
                const producto = {
                    code: rows[i].code,
                    name: rows[i].name,
                    cantidad: parseInt(rows[i].cantidad),
                    precio: parseFloat(rows[i].precio) || 0,
                    tiempo_entrega: parseInt(rows[i].tiempo_entrega) || 0,
                    unidad: rows[i].unidad,
                    stock: parseInt(rows[i].stock) || 0,
                    llegando: parseInt(rows[i].llegando) || 0,
                    prec_min: parseFloat(rows[i].prec_min) || 0,
                    prec_may: parseFloat(rows[i].prec_may) || 0
                };
                productos.push(producto);
            }
        }

        if (productos.length === 0) {
            alert('No hay productos en el presupuesto');
            return;
        }

        const presupuesto = {
            numero: numeroPresupuesto,
            cliente: selectedClientNum,
            productos: productos,
            comentario: comentarioPresupuesto,
            usuario: numUsr,
            total: calcularTotalPresupuesto()
        };

        const paramJSON = JSON.stringify(presupuesto);
        
        console.log('📤 Enviando presupuesto a guardar...', presupuesto);
        
        // Usar $.ajax en lugar de $.post para tener más control
        $.ajax({
            url: "../../php/guardarPresupuesto.php",
            type: "POST",
            data: { data: paramJSON },
            dataType: "json", // Esperar JSON como respuesta
            success: function(respuesta) {
                console.log('📥 Respuesta del servidor:', respuesta);
                
                if (respuesta.success) {
                    alert(`✅ Presupuesto guardado correctamente\nNúmero: ${respuesta.presupuesto_num}`);
                    $('#ModalMakePedido').modal('hide');
                    
                    // Redirigir a la página de visualización del presupuesto
                    setTimeout(() => {
                        window.location.href = `../php/verPresupuesto.php?presupuesto_id=${respuesta.presupuesto_id}`;
                    }, 1000);
                }else {
                    alert('❌ Error al guardar el presupuesto: ' + respuesta.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', status, error);
                console.error('Respuesta del servidor:', xhr.responseText);
                alert('❌ Error de conexión al guardar el presupuesto');
            }
        });
    }
}

function calcularTotalPresupuesto() {
    if ($tableMakePedido && $tableMakePedido.length > 0) {
        var rows = $tableMakePedido.bootstrapTable('getData');
        let total = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const cantidad = parseInt(rows[i].cantidad) || 0;
            const precio = parseFloat(rows[i].precio) || 0;
            total += cantidad * precio;
        }
        
        return total;
    }
    return 0;
}


function verificarEstadoCarrito() {
    console.log('=== ESTADO CARRITO ===');
    console.log('codes_carrito:', codes_carrito);
    console.log('Número de productos:', codes_carrito.length);
    console.log('=====================');
}

// Inicialización

$(document).ready(function() {
    console.log('🚀 presupuesto.js inicializado');
    $tableMakePedido = $('#table-carrito');
    
    // Los márgenes ya están disponibles desde index.php
    console.log('📊 Márgenes en presupuesto.js:', {ganancia_min_glob, descuento_max_glob});
    
    // Inicializar Tom Select cuando se abre el modal Y refrescar tabla
    $('#ModalMakePedido').on('show.bs.modal', function() {
        console.log('🎯 Modal de presupuesto abriéndose...');
        
        // Refrescar la tabla del carrito cuando el modal se muestre
        if ($tableMakePedido && $tableMakePedido.length > 0) {
            setTimeout(() => {
                console.log('🔄 Refrescando tabla del carrito en evento show...');
                $tableMakePedido.bootstrapTable('refresh');
            }, 300);
        }
        
        setTimeout(initPresupuestoModal, 100);
    });
    
    // También refrescar cuando el modal esté completamente visible
    $('#ModalMakePedido').on('shown.bs.modal', function() {
        console.log('✅ Modal completamente visible, refrescando tabla...');
        if ($tableMakePedido && $tableMakePedido.length > 0) {
            $tableMakePedido.bootstrapTable('refresh');
            updateTotal();
        }
    });
});




// Función para generar número de presupuesto automático
function generarNumeroPresupuesto() {
    const timestamp = new Date().getTime();
    const random = Math.floor(Math.random() * 1000);
    return `PRES-${timestamp}-${random}`;
}