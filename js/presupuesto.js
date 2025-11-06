// presupuesto.js - Funciones específicas para el módulo de presupuestos

// Variables globales para presupuestos
var $tableMakePedido, ctrlClientSel;

// Configuración inicial del modal de presupuesto - CORREGIDO
function initPresupuestoModal() {
    console.log('Inicializando modal de presupuesto...');
    
    // Verificar que el elemento existe antes de inicializar Tom Select
    if ($('#clients-tom-sel').length > 0) {
        ctrlClientSel = new TomSelect("#clients-tom-sel", {
            sortField: { field: "text", direction: "asc" },
            onChange: function() {
                var selectedClient = parseInt(ctrlClientSel.getValue()) || 0;
                $('#reg-presupuesto').prop('disabled', selectedClient <= 0);
            },
            create: true,
            createOnBlur: true
        });
        console.log('Tom Select inicializado correctamente');
    } else {
        console.error('Elemento #clients-tom-sel no encontrado');
    }

    // Generar número de presupuesto automáticamente
    if ($('#numero-presupuesto').length > 0) {
        $('#numero-presupuesto').val(generarNumeroPresupuesto());
    }
}

// Función para generar número de presupuesto automático
function generarNumeroPresupuesto() {
    const timestamp = new Date().getTime();
    const random = Math.floor(Math.random() * 1000);
    return `PRES-${timestamp}-${random}`;
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
    const precioActual = parseFloat(row.precio) || 0;
    
    let selectedMin = '';
    let selectedMay = '';
    
    if (precioActual === precMin) {
        selectedMin = 'checked';
    } else if (precioActual === precMay) {
        selectedMay = 'checked';
    }
    
    return `
        <div class="form-check">
            <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                   value="${precMin}" ${selectedMin} onchange="seleccionarPrecio(this, '${row.code}')">
            <label class="form-check-label small">Precio 1: $${precMin.toFixed(3).replace('.', ',')}</label>
        </div>
        <div class="form-check">
            <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                   value="${precMay}" ${selectedMay} onchange="seleccionarPrecio(this, '${row.code}')">
            <label class="form-check-label small">Precio 2: $${precMay.toFixed(3).replace('.', ',')}</label>
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

// Funciones de interacción del carrito
function seleccionarPrecio(radio, code) {
    const precio = parseFloat(radio.value) || 0;
    
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

// Funciones principales de presupuesto
function getSelected() {
    console.log('Abriendo modal de presupuesto...');
    
    // Inicializar Tom Select solo cuando se abre el modal
    setTimeout(() => {
        initPresupuestoModal();
    }, 100);
    
    refreshCarritoTable().then(() => {
        updateTotal();
        setTimeout(inicializarTiemposEntrega, 500);
    });
    $('#ModalMakePedido').modal({show:true});
}

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
        
        $.post("../../php/guardarPresupuesto.php", {
            data: paramJSON
        }, function(data, status) {
            if (status === 'success' && data == '1') {
                alert('Presupuesto guardado correctamente');
                $('#ModalMakePedido').modal('hide');
                $.post("../../php/limpiarCarritoPresupuesto.php", function() {
                    backToSelfAlt();
                });
            } else {
                alert('Error al guardar el presupuesto: ' + data);
            }
        }).fail(function() {
            alert('Error de conexión');
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

// Inicialización - CORREGIDO
$(document).ready(function() {
    console.log('Documento listo, inicializando presupuesto.js');
    $tableMakePedido = $('#table-carrito');
    
    // Solo inicializar elementos que existen en el DOM principal
    if ($('#numero-presupuesto').length > 0) {
        $('#numero-presupuesto').val(generarNumeroPresupuesto());
    }
    
    // Inicializar Tom Select solo cuando se abre el modal
    $('#ModalMakePedido').on('show.bs.modal', function() {
        console.log('Modal de presupuesto abriéndose...');
        setTimeout(initPresupuestoModal, 100);
    });
});