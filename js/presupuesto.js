// presupuesto.js - Versión con preloader durante actualizaciones
console.log('✅ presupuesto.js cargado - Márgenes disponibles:', ganancia_min_glob, descuento_max_glob);

// Variables globales
var $tableMakePedido, ctrlClientSel;

// Variables para control del scroll
let filaScrollIndex = 0;
let codigoProductoScroll = '';

// Función para mostrar/ocultar preloader
function mostrarPreloader(mostrar) {
    if (mostrar) {
        // Crear preloader si no existe
        if ($('#preloader-tabla').length === 0) {
            const preloader = `
                <div id="preloader-tabla" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255, 255, 255, 0.9);
                    z-index: 9999;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    flex-direction: column;
                ">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <div class="mt-3" style="color: #037C79; font-weight: bold;">Actualizando...</div>
                </div>
            `;
            $('body').append(preloader);
        } else {
            $('#preloader-tabla').show();
        }
        
        // Deshabilitar interacciones
        $('body').css('overflow', 'hidden');
        $('.precio-radio, .precio-manual-input, .cantidad-input, .tiempo-select').prop('disabled', true);
        
    } else {
        // Ocultar preloader
        $('#preloader-tabla').fadeOut(300, function() {
            $(this).remove();
        });
        
        // Rehabilitar interacciones
        $('body').css('overflow', '');
        $('.precio-radio, .precio-manual-input, .cantidad-input, .tiempo-select').prop('disabled', false);
    }
}

// Función para verificar márgenes
function verificarMargenPrecio(costo, precio) {
    if (precio <= 0) {
        return { cumpleMargen: false, esCero: true };
    }
    
    if (costo <= 0) {
        return { cumpleMargen: true, esCero: false };
    }
    
    const precioMinimoRequerido = costo * ganancia_min_glob/descuento_max_glob;
    const cumpleMargen = precio >= precioMinimoRequerido;
    
    console.log(`💰 Margen verificado: ${costo} × ${ganancia_min_glob}/${descuento_max_glob} = ${precioMinimoRequerido.toFixed(3)} vs ${precio} → ${cumpleMargen ? '✅' : '❌'}`);
    
    return { cumpleMargen: cumpleMargen, esCero: false };
}

// Funciones para control del scroll usando scrollTo (SIN animación)
function guardarPosicionScroll(code) {
    if ($tableMakePedido && $tableMakePedido.length > 0) {
        const rows = $tableMakePedido.bootstrapTable('getData');
        filaScrollIndex = rows.findIndex(row => row.code === code);
        codigoProductoScroll = code;
        console.log('📏 Scroll guardado - Fila:', filaScrollIndex, 'Producto:', code);
    }
}

function restaurarPosicionScroll() {
    if ($tableMakePedido && $tableMakePedido.length > 0 && filaScrollIndex >= 0) {
        console.log('🔄 Intentando restaurar a fila:', filaScrollIndex);
        
        // Estrategia 1: Usar scrollTo sin animación
        if (typeof $tableMakePedido.bootstrapTable('scrollTo') === 'function') {
            setTimeout(() => {
                $tableMakePedido.bootstrapTable('scrollTo', { unit: 'rows', value: filaScrollIndex });
                console.log('✅ ScrollTo ejecutado a fila:', filaScrollIndex);
            }, 100);
        } 
        // Estrategia 2: Buscar la fila por código y hacer scroll instantáneo
        else if (codigoProductoScroll) {
            setTimeout(() => {
                const rows = $tableMakePedido.bootstrapTable('getData');
                const currentIndex = rows.findIndex(row => row.code === codigoProductoScroll);
                if (currentIndex >= 0) {
                    const $fila = $(`tr[data-index="${currentIndex}"]`);
                    if ($fila.length > 0) {
                        $fila[0].scrollIntoView({ behavior: 'instant', block: 'center' });
                        console.log('✅ Scroll manual a fila:', currentIndex);
                    }
                }
            }, 100);
        }
    }
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
    
    if (ctrlClientSel && ctrlClientSel.initialized) {
        console.log('🔄 Tom Select ya inicializado, destruyendo...');
        ctrlClientSel.destroy();
    }
    
    if ($('#clients-tom-sel').length > 0) {
        try {
            ctrlClientSel = new TomSelect("#clients-tom-sel", {
                sortField: { field: "text", direction: "asc" },
                
                onChange: function(value) {
                    console.log('Cliente seleccionado:', value);
                    const tieneValor = value !== null && value !== undefined && value !== '' && value !== '0';
                    $('#reg-presupuesto').prop('disabled', !tieneValor);
                },
                
                create: function(input, callback) {
                    console.log('Creando cliente:', input);
                    const newValue = input.trim();
                    if (newValue.length >= 2) {
                        callback({
                            value: newValue,
                            text: newValue
                        });
                    }
                },
                createOnBlur: true,
                createFilter: function(input) {
                    return input.length >= 2;
                }
            });
            
            ctrlClientSel.initialized = true;
            console.log('✅ Tom Select inicializado correctamente');
            
        } catch (e) {
            console.error('❌ Error inicializando Tom Select:', e);
        }
    }
    
    // Generar número automático cuando se abre el modal
    generarNumeroAutomatico();
}

// Función para generar y colocar el número automático
function generarNumeroAutomatico() {
    obtenerProximoNumeroSecuencial().then((numeroAutomatico) => {
        $('#numero-presupuesto').val(numeroAutomatico);
        $('#numero-presupuesto').attr('placeholder', 'Número generado automáticamente');
        console.log('✅ Número automático generado:', numeroAutomatico);
    });
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
                
                resolve();
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

// Función para refrescar tabla con Promise y control de scroll
function refreshCarritoTable() {
    return new Promise((resolve) => {
        if ($tableMakePedido && $tableMakePedido.length > 0) {
            $tableMakePedido.bootstrapTable('refresh');
            $tableMakePedido.one('load-success.bs.table', function() {
                console.log('✅ Tabla refrescada, restaurando posición...');
                restaurarPosicionScroll();
                setTimeout(() => {
                    mostrarPreloader(false);
                    resolve();
                }, 200);
            });
            
            // Timeout de seguridad por si el evento no se dispara
            setTimeout(() => {
                console.log('⏰ Timeout de seguridad, restaurando posición...');
                restaurarPosicionScroll();
                setTimeout(() => {
                    mostrarPreloader(false);
                    resolve();
                }, 200);
            }, 1500);
        } else {
            mostrarPreloader(false);
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

function precioCombinadoFormater(value, row) {
    const precMin = parseFloat(row.prec_min) || 0;
    const precMay = parseFloat(row.prec_may) || 0;
    const prec3 = parseFloat(row.prec_3) || 0;
    const costo = parseFloat(row.costo) || 0;
    const precioActual = parseFloat(row.precio) || 0;
    const minimoPrecio = parseFloat(costo*(ganancia_min_glob/descuento_max_glob)) || 0;
    
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
    
    return `
        <div class="precio-combinado-container">
            <!-- Selector de precios predefinidos -->
            <div class="precio-opciones mb-2">
                <div class="form-check form-check-inline">
                    <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                           value="${precMin}" ${precioSeleccionado === 'precio1' ? 'checked' : ''} 
                           ${precMin === 0 ? 'disabled' : ''} 
                           onchange="seleccionarPrecio(this, '${row.code}')">
                    <label class="form-check-label small">
                        <span class="badge badge-margen ${resultadoMin.cumpleMargen ? 'bg-success' : 'bg-danger'}">$${precMin.toFixed(3).replace('.', ',')}</span> 
                    </label>
                </div>
                
                <div class="form-check form-check-inline">
                    <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                           value="${precMay}" ${precioSeleccionado === 'precio2' ? 'checked' : ''} 
                           ${precMay === 0 ? 'disabled' : ''} 
                           onchange="seleccionarPrecio(this, '${row.code}')">
                    <label class="form-check-label small">
                        <span class="badge badge-margen ${resultadoMay.cumpleMargen ? 'bg-success' : 'bg-danger'}">$${precMay.toFixed(3).replace('.', ',')}</span>
                    </label>
                </div>
                
                <div class="form-check form-check-inline">
                    <input class="form-check-input precio-radio" type="radio" name="precio_${row.code}" 
                           value="${prec3}" ${precioSeleccionado === 'precio3' ? 'checked' : ''} 
                           ${prec3 === 0 ? 'disabled' : ''} 
                           onchange="seleccionarPrecio(this, '${row.code}')">
                    <label class="form-check-label small">
                        <span class="badge badge-margen ${resultado3.cumpleMargen ? 'bg-success' : 'bg-danger'}">$${prec3.toFixed(3).replace('.', ',')}</span>
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
                Costo: $${costo.toFixed(3).replace('.', ',')} | Mínimo: $${minimoPrecio.toFixed(3).replace('.', ',')} | (${ganancia_min_glob}/${descuento_max_glob})x
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

// Funciones de interacción del carrito CON PRELOADER
function seleccionarPrecio(radio, code) {
    guardarPosicionScroll(code);
    mostrarPreloader(true);
    
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
            } else {
                mostrarPreloader(false);
            }
        }).fail(function() {
            mostrarPreloader(false);
        });
    })();
}

function actualizarPrecioManual(input) {
    const code = input.getAttribute('data-code');
    guardarPosicionScroll(code);
    mostrarPreloader(true);
    
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
            } else {
                mostrarPreloader(false);
            }
        }).fail(function() {
            mostrarPreloader(false);
        });
    })();
}

function actualizarCantidad(input) {
    const code = input.getAttribute('data-code');
    guardarPosicionScroll(code);
    mostrarPreloader(true);
    
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
            } else {
                mostrarPreloader(false);
            }
        }).fail(function() {
            mostrarPreloader(false);
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
            // No mostrar preloader para tiempo de entrega (es instantáneo)
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
        let subtotal = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const cantidad = parseInt(rows[i].cantidad) || 0;
            const precio = parseFloat(rows[i].precio) || 0;
            subtotal += cantidad * precio;
        }
        
        // Obtener valores actuales
        const descuentoPorcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
        let descuentoMonto = parseFloat($('#descuento_monto').val()) || 0;
        const recargoMonto = parseFloat($('#recargo_monto').val()) || 0;
        const ivaPorcentaje = parseFloat($('#iva_porcentaje').val()) || 0;
        let ivaMonto = parseFloat($('#iva_monto').val()) || 0;
        
        // Recalcular descuento si hay porcentaje
        if (descuentoPorcentaje > 0) {
            descuentoMonto = subtotal * (descuentoPorcentaje / 100);
            $('#descuento_monto').val(descuentoMonto.toFixed(3));
        } else {
            $('#descuento_monto').val(0);
            descuentoMonto = 0;
        }
        
        // Recalcular IVA si hay porcentaje
        const baseIVA = subtotal - descuentoMonto + recargoMonto;
        if (ivaPorcentaje > 0) {
            ivaMonto = baseIVA * (ivaPorcentaje / 100);
            $('#iva_monto').val(ivaMonto.toFixed(3));
        } else {
            $('#iva_monto').val(0);
            ivaMonto = 0;
        }
        
        const total = baseIVA + ivaMonto;
        
        const totalFormateado = total.toFixed(3).replace('.', ',');
        const subtotalFormateado = subtotal.toFixed(3).replace('.', ',');
        
        // Mostrar solo los campos que tienen valores > 0
        $('#MontoTotal').html(`
            <div>Sub-Total: $${subtotalFormateado}</div>
            ${descuentoMonto > 0 ? `<div>Descuento (${descuentoPorcentaje}%): -$${descuentoMonto.toFixed(3).replace('.', ',')}</div>` : ''}
            ${recargoMonto > 0 ? `<div>Recargo: +$${recargoMonto.toFixed(3).replace('.', ',')}</div>` : ''}
            ${ivaMonto > 0 ? `<div>IVA (${ivaPorcentaje}%): +$${ivaMonto.toFixed(3).replace('.', ',')}</div>` : ''}
            <div><strong>Total: $${totalFormateado}</strong></div>
        `);
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
    console.log('🎯 guardarPresupuesto() EJECUTADA');
    
    const selectedClient = ctrlClientSel.getValue();
    console.log('🔍 Cliente seleccionado:', selectedClient);
    
    // Obtener el número (puede ser automático o manual)
    let numeroPresupuesto = $('#numero-presupuesto').val();
    
    // Si está vacío, usar el placeholder o generar uno
    if (!numeroPresupuesto || numeroPresupuesto.trim() === '') {
        // Intentar usar el placeholder o generar uno de emergencia
        numeroPresupuesto = $('#numero-presupuesto').attr('placeholder') || generarNumeroPresupuesto();
        // Limpiar si es texto del placeholder
        if (numeroPresupuesto.includes('Generando') || numeroPresupuesto.includes('Número')) {
            numeroPresupuesto = generarNumeroPresupuesto();
        }
    }
    
    const comentarioPresupuesto = $('#comentarioPresupuesto').val();
    const descuentoTexto = $('#descuento_texto').val();
    const descuentoMonto = parseFloat($('#descuento_monto').val()) || 0;
    const recargoTexto = $('#recargo_texto').val();
    const recargoMonto = parseFloat($('#recargo_monto').val()) || 0;
    const ivaPorcentaje = parseFloat($('#iva_porcentaje').val()) || 0;
    const ivaMonto = parseFloat($('#iva_monto').val()) || 0;

    // Validaciones (solo cliente es obligatorio)
    if (!selectedClient || selectedClient === '' || selectedClient === '0') {
        alert('Por favor seleccione o ingrese un cliente');
        return;
    }

    // MOSTRAR LOADING
    const btnGuardar = $('#reg-presupuesto');
    const originalText = btnGuardar.html();
    btnGuardar.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Guardando...');

    // Resto del código permanece igual...
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
            btnGuardar.prop('disabled', false).html(originalText);
            return;
        }

        const presupuesto = {
            numero: numeroPresupuesto,  // Ahora siempre tiene valor
            cliente: selectedClient,
            productos: productos,
            comentario: comentarioPresupuesto,
            usuario: numUsr,
            total: calcularTotalPresupuesto(),
            descuento_texto: descuentoTexto,
            descuento_monto: descuentoMonto,
            recargo_texto: recargoTexto,
            recargo_monto: recargoMonto,
            iva_porcentaje: ivaPorcentaje,
            iva_monto: ivaMonto
        };

        const paramJSON = JSON.stringify(presupuesto);
        
        console.log('📤 Enviando presupuesto completo:', presupuesto);
        
        $.ajax({
            url: "https://ketelectropartes.com/admin/php/guardarPresupuesto.php",
            type: "POST",
            data: paramJSON,
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            success: function(respuesta) {
                console.log('📥 Respuesta del servidor:', respuesta);
                
                if (respuesta.success) {
                    $('#ModalMakePedido').modal('hide');
                    window.location.href = "https://ketelectropartes.com/admin/php/verPresupuesto.php?presupuesto_id=" + respuesta.presupuesto_id;
                } else {
                    alert('❌ Error al guardar el presupuesto: ' + respuesta.error);
                    btnGuardar.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', status, error);
                console.error('Respuesta del servidor:', xhr.responseText);
                alert('❌ Error de conexión al guardar el presupuesto');
                btnGuardar.prop('disabled', false).html(originalText);
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

// Función para calcular descuento desde porcentaje
function calcularDescuentoDesdePorcentaje() {
    const porcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
    
    if ($tableMakePedido && $tableMakePedido.length > 0) {
        var rows = $tableMakePedido.bootstrapTable('getData');
        let subtotal = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const cantidad = parseInt(rows[i].cantidad) || 0;
            const precio = parseFloat(rows[i].precio) || 0;
            subtotal += cantidad * precio;
        }
        
        const descuentoMonto = subtotal * (porcentaje / 100);
        $('#descuento_monto').val(descuentoMonto.toFixed(3));
        
        updateTotal();
    }
}

function calcularIVA() {
    const ivaPorcentaje = parseFloat($('#iva_porcentaje').val()) || 0;
    
    if ($tableMakePedido && $tableMakePedido.length > 0) {
        var rows = $tableMakePedido.bootstrapTable('getData');
        let subtotal = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const cantidad = parseInt(rows[i].cantidad) || 0;
            const precio = parseFloat(rows[i].precio) || 0;
            subtotal += cantidad * precio;
        }
        
        // Calcular descuentos primero
        const descuentoPorcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
        const descuentoMonto = subtotal * (descuentoPorcentaje / 100);
        const recargoMonto = parseFloat($('#recargo_monto').val()) || 0;
        
        // Base para IVA: subtotal - descuento + recargo
        const baseIVA = subtotal - descuentoMonto + recargoMonto;
        const ivaMonto = baseIVA * (ivaPorcentaje / 100);
        
        $('#iva_monto').val(ivaMonto.toFixed(3));
        updateTotal();
    }
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
        filaScrollIndex = 0; // Resetear al abrir modal
        codigoProductoScroll = '';
        
        // Refrescar la tabla del carrito cuando el modal se muestre
        if ($tableMakePedido && $tableMakePedido.length > 0) {
            setTimeout(() => {
                console.log('🔄 Refrescando tabla del carrito en evento show...');
                $tableMakePedido.bootstrapTable('refresh');
            }, 300);
        }
        
        setTimeout(initPresupuestoModal, 100);
        generarNumeroAutomatico();
    });
    
    // También refrescar cuando el modal esté completamente visible
    $('#ModalMakePedido').on('shown.bs.modal', function() {
        console.log('✅ Modal completamente visible, refrescando tabla...');
        if ($tableMakePedido && $tableMakePedido.length > 0) {
            $tableMakePedido.bootstrapTable('refresh');
            updateTotal();
        }
    });
    
    $('#descuento_monto, #recargo_monto').on('input', function() {
        updateTotal();
    });

    // Event listener para los campos de descuento/recargo
    $('#descuento_porcentaje, #recargo_monto, #iva_porcentaje').on('input', function() {
        updateTotal();
    });

    // Event listener adicional para el input del Tom Select
    $('#clients-tom-sel').on('change', function() {
        console.log('Change event en Tom Select:', this.value);
        $('#reg-presupuesto').prop('disabled', !this.value);
    });
});

// Función para generar número de presupuesto automático
function generarNumeroPresupuesto() {
    const timestamp = new Date().getTime();
    const random = Math.floor(Math.random() * 1000);
    return `PRES-${timestamp}-${random}`;
}

// Función para obtener el próximo número secuencial desde el servidor
function obtenerProximoNumeroSecuencial() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: "https://ketelectropartes.com/admin/php/getProximoNumeroPresupuesto.php",
            type: "GET",
            dataType: "json",
            success: function(respuesta) {
                if (respuesta.success) {
                    resolve(respuesta.proximo_numero);
                } else {
                    // Si falla, usar generación local
                    resolve(generarNumeroPresupuesto());
                }
            },
            error: function() {
                // Si hay error, usar generación local
                resolve(generarNumeroPresupuesto());
            }
        });
    });
}