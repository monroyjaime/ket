<?php
// update_productos-upd-cvs.php - versión basada en update_productos.php con restauración de registros en preciosChanged.csv

// ================= CONFIGURACIÓN MEJORADA =================
set_time_limit(300); // 5 minutos
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ================= MANEJO DE ERRORES ROBUSTO =================
function handle_fatal_error() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR])) {
        error_log("❌ ERROR FATAL en update_productos-upd-cvs: " . $error['message'] . " en " . $error['file'] . ":" . $error['line']);
    }
}
register_shutdown_function('handle_fatal_error');

// ================= LOGGING MEJORADO =================
$log_file = '/var/www/html/reports/logs/update_productos.log';
function log_update($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    // Asegurar directorio existe puede omitirse aquí; se asume configurado en el servidor
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo $message . "\n"; // Mantener output original también
}

log_update("🚀 INICIANDO UPDATE_PRODUCTOS-UPD-CSV.PHP - " . date('Y-m-d H:i:s'));

// ================= CÓDIGO BASE =================
require_once("dbcat.php");

$db = new DB();
log_update("✅ Conexión a BD establecida");

// Configuración de archivo de precios (manteniendo tu código original)
$filePrecio = '/var/www/html/reports/preciosChanged.csv';
$headers = ['Fecha', 'tipoPrecio', 'Codigo', 'Previo', 'Nuevo', 'costoPrevio', 'costoNuevo'];

// Primera vez - crea el archivo con headers
if(!file_exists($filePrecio)){ 
    agregarLineaCSV(
        [date('Y-m-d H:i:s'), 'prueba', 0, 0, 0, 0],
        $filePrecio,
        $headers
    );
    log_update("📁 Archivo de precios creado: $filePrecio");
}

// Helper para centralizar el registro de cambios de precio/costo
function reportPrecioChange($tipo, $code, $prevPrecio, $newPrecio, $prevCosto = '---', $newCosto = '---') {
    global $filePrecio;
    $fila = [date('Y-m-d H:i:s'), $tipo, $code, $prevPrecio, $newPrecio, $prevCosto, $newCosto];
    // Intentar escribir en CSV principal
    if (function_exists('agregarLineaCSV')) {
        if (agregarLineaCSV($fila, $filePrecio) === false) {
            log_update("⚠️ No se pudo escribir en $filePrecio para $code ($tipo)");
        } else {
            log_update("📝 preciosChanged.csv actualizado: $code ($tipo) $prevPrecio -> $newPrecio ; costo $prevCosto -> $newCosto");
        }
    } else {
        log_update("⚠️ agregarLineaCSV no disponible para registrar cambio: $code");
    }
    // Mantener también el log legacy si existe la función log_echo
    if (function_exists('log_echo')) {
        // registrar una línea compacta en el log legacy
        log_echo("$tipo,$code,$prevPrecio,$newPrecio,$prevCosto,$newCosto", false);
    }
}

// Consulta principal
$query  = "SELECT code,name,cost_max,unit,current_stock,dpto_code,orden,cost_oferta,cost_mayor,cost_min,stock_lleg,relacionado,costo";
$query .= " FROM prod_name ORDER BY code";

log_update("📊 Ejecutando consulta principal...");
$consult1 = $db->consultas($query);
totalRegistros = count($consult1);
log_update("📥 Total registros a procesar: $totalRegistros");

// Inicializar contadores
$counters = [
    'descripcion' => 1, 'cost_max' => 1, 'unit' => 1, 'current_stock' => 1,
    'dpto_code' => 1, 'orden' => 1, 'cost_oferta' => 1, 'cost_mayor' => 1,
    'cost_min' => 1, 'stock_lleg' => 1, 'relacionado' => 1, 'costo' => 1,
    'nuevos' => 0, 'eliminados' => 0
];

// Procesar cada producto
foreach ($consult1 as $index => $value1) {
    if ($index % 500 === 0) {
        log_update("⏳ Procesando registro $index de $totalRegistros...");
    }

    $found = 0;
    $query1 = "SELECT name,cost_max,unit,current_stock,dpto_code,orden,cost_oferta,cost_mayor,cost_min,stock_lleg,relacionado,costo FROM productos where code='".$value1->code."'";
    $consult2 = $db->consultas($query1);

    foreach($consult2 as $value2) {
        $found = 1;

        // 1. Actualizar nombre/descripcion
        if($value1->name != $value2->name) {
            if($db->querySet("UPDATE productos SET name = '".$value1->name."' WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['descripcion']."::updated description:: \nCODE: ". $value1->code."\nbefore: ". $value2->name."\nafter : ". $value1->name."\n");
                $counters['descripcion']++;
            }
        }

        // 2. Actualizar cost_max
        if(number_format(floatval($value1->cost_max),3) != number_format(floatval($value2->cost_max),3)) {
            if($db->querySet("UPDATE productos SET cost_max = ". $value1->cost_max." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['cost_max']."::updated cost_max:: \nCODE: ". $value1->code."\nbefore: ". $value2->cost_max."\nafter : ". $value1->cost_max."\n");
                // Registrar cambio en CSV y log
                reportPrecioChange('prec1(Min)', $value1->code, $value2->cost_max, $value1->cost_max, $value2->costo, $value1->costo);
                $counters['cost_max']++;
            }
        }

        // 3. Actualizar unit
        if($value1->unit != $value2->unit) {
            if($db->querySet("UPDATE productos SET unit = '".$value1->unit."' WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['unit']."::updated unit:: \nCODE: ". $value1->code."\nbefore: ". $value2->unit."\nafter : ". $value1->unit."\n");
                $counters['unit']++;
            }
        }

        // 4. Actualizar current_stock
        if($value1->current_stock != $value2->current_stock) {
            if($db->querySet("UPDATE productos SET current_stock = ". $value1->current_stock." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['current_stock']."::updated Stock:: \nCODE: ". $value1->code."\nbefore: ". $value2->current_stock."\nafter : ". $value1->current_stock."\n");
                $counters['current_stock']++;
            }
        }

        // 5. Actualizar dpto_code
        if($value1->dpto_code != $value2->dpto_code) {
            $consult3 = $db->consultas("SELECT id FROM departamentos WHERE code ='".$value1->dpto_code."'");
            $newDptoId = 1; // valor por defecto
            foreach($consult3 as $value3) {
                $newDptoId = intval($value3->id);
            }

            if($db->querySet("UPDATE productos SET dpto_code ='".$value1->dpto_code."', dpto_id=".$newDptoId." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['dpto_code']."::updated dpto_code:: \nCODE: ". $value1->code."\nbefore: ". $value2->dpto_code."\nafter : ". $value1->dpto_code." (id:".$newDptoId.")\n");
                $counters['dpto_code']++;
            }
        }

        // 6. Actualizar orden
        if($value1->orden != $value2->orden) {
            if($db->querySet("UPDATE productos SET orden = ". $value1->orden." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['orden']."::updated Orden:: \nCODE: ". $value1->code."\nbefore: ". $value2->orden."\nafter : ". $value1->orden."\n");
                $counters['orden']++;
            }
        }

        // 7. Actualizar cost_oferta
        if(number_format(floatval($value1->cost_oferta),3) != number_format(floatval($value2->cost_oferta),3)) {
            if($db->querySet("UPDATE productos SET cost_oferta = ". $value1->cost_oferta." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['cost_oferta']."::updated cost_oferta:: \nCODE: ". $value1->code."\nbefore: ". $value2->cost_oferta."\nafter : ". $value1->cost_oferta."\n");
                $counters['cost_oferta']++;
            }
        }

        // 8. Actualizar cost_mayor
        if(number_format(floatval($value1->cost_mayor),3) != number_format(floatval($value2->cost_mayor),3)) {
            if($db->querySet("UPDATE productos SET cost_mayor = ". $value1->cost_mayor." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['cost_mayor']."::updated cost_mayor:: \nCODE: ". $value1->code."\nbefore: ". $value2->cost_mayor."\nafter : ". $value1->cost_mayor."\n");
                reportPrecioChange('prec2(May)', $value1->code, $value2->cost_max, $value1->cost_max, $value2->costo, $value1->costo);
                $counters['cost_mayor']++;
            }
        }

        // 9. Actualizar cost_min
        if(number_format(floatval($value1->cost_min),3) != number_format(floatval($value2->cost_min),3)) {
            if($db->querySet("UPDATE productos SET cost_min = ". $value1->cost_min." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['cost_min']."::updated cost_min:: \nCODE: ". $value1->code."\nbefore: ". $value2->cost_min."\nafter : ". $value1->cost_min."\n");
                reportPrecioChange('prec3', $value1->code, $value2->cost_max, $value1->cost_max, $value2->costo, $value1->costo);
                $counters['cost_min']++;
            }
        }

        // 10. Actualizar stock_lleg
        if($value1->stock_lleg != $value2->stock_lleg) {
            if($db->querySet("UPDATE productos SET stock_lleg = ". $value1->stock_lleg." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['stock_lleg']."::updated Stock_llegando:: \nCODE: ". $value1->code."\nbefore: ". $value2->stock_lleg."\nafter : ". $value1->stock_lleg."\n");
                $counters['stock_lleg']++;
            }
        }

        // 11. Actualizar relacionado
        if($value1->relacionado != $value2->relacionado) {
            if($db->querySet("UPDATE productos SET relacionado = '".$value1->relacionado."' WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['relacionado']."::updated relacionado:: \nCODE: ". $value1->code."\nbefore: ". $value2->relacionado."\nafter : ". $value1->relacionado."\n");
                $counters['relacionado']++;
            }
        }

        // 12. Actualizar costo
        if(number_format(floatval($value1->costo),3) != number_format(floatval($value2->costo),3)) {
            if($db->querySet("UPDATE productos SET costo = ". $value1->costo." WHERE code ='".$value1->code."'") == 1) {
                log_update($counters['costo']."::updated costo:: \nCODE: ". $value1->code."\nbefore: ". $value2->costo."\nafter : ". $value1->costo."\n");
                reportPrecioChange('costo', $value1->code, '---', '---', $value2->costo, $value1->costo);
                $counters['costo']++;
            }
        }
    }

    // INSERTAR NUEVO PRODUCTO (si no se encontró)
    if($found == 0) {
        if (empty(trim($value1->code))) {
            continue;
        }

        if ($value1->dpto_code === '#VALUE!' || empty(trim($value1->dpto_code))) {
            continue;
        }

        $current_stock = empty(trim($value1->current_stock)) ? 0 : $value1->current_stock;
        $cost_max = empty(trim($value1->cost_max)) ? 0 : $value1->cost_max;
        $orden = empty(trim($value1->orden)) ? 1 : $value1->orden;

        $consult = $db->consultas("SELECT MAX(id) + 1 AS next_id FROM productos");
        $nextId = 1;
        foreach ($consult as $value) {
            $nextId = intval($value->next_id);
        }

        $queryGetDptoId = "SELECT id FROM departamentos WHERE code='".$value1->dpto_code."'";
        $consult = $db->consultas($queryGetDptoId);

        $dptoId = 1;
        foreach ($consult as $value) {
            $dptoId = intval($value->id);
        }

        $queryInsert  = "INSERT INTO productos VALUES(";
        $queryInsert .= $nextId . ",";
        $queryInsert .= "'" . $value1->code . "',";
        $queryInsert .= "'" . pg_escape_string($value1->name) . "',";
        $queryInsert .= "'" . $value1->dpto_code . "',";
        $queryInsert .= "'" . $value1->unit . "',";
        $queryInsert .= $current_stock . ",";
        $queryInsert .= $cost_max . ",";
        $queryInsert .= "'empty.jpg',";
        $queryInsert .= $dptoId . ",";
        $queryInsert .= "'t',";
        $queryInsert .= $orden . ")";

        log_update("🔍 Query INSERT: " . substr($queryInsert, 0, 100) . "...");

        if($db->querySet($queryInsert) == 1) {
            log_update("🆕 INSERTADO nuevo producto: " . $value1->code);
            $counters['nuevos']++;
        } else {
            log_update("❌ ERROR insertando nuevo código: " . $value1->code);
            continue;
        }
    }
}

// ELIMINAR PRODUCTOS QUE YA NO EXISTEN
log_update("🗑️  Verificando productos a eliminar...");
$consult4 = $db->consultas("SELECT COUNT(code) as count FROM productos");
$currNumProductos = 0;
foreach($consult4 as $val) {
    $currNumProductos = intval($val->count);
}

$consult4 = $db->consultas("SELECT COUNT(code) as count FROM prod_name");
$newNumProductos = 0;
foreach($consult4 as $val) {
    $newNumProductos = intval($val->count);
}

if($newNumProductos > 0) {
    $prodDeleted = $currNumProductos - $newNumProductos;
    if($prodDeleted > 0) {
        log_update("🗑️  $prodDeleted productos a eliminar");
        $query = "SELECT code FROM productos WHERE code NOT IN (SELECT code FROM prod_name) ORDER BY code";
        $consult4 = $db->consultas($query);
        foreach($consult4 as $val) {
            $queryDel = "DELETE FROM productos WHERE code='".$val->code."'";
            if($db->querySet($queryDel) == 1) {
                log_update("✅ ELIMINADO: " . $val->code);
                $counters['eliminados']++;
            } else {
                log_update("❌ ERROR eliminando: " . $val->code);
            }
        }
    }
}

// ACTUALIZAR stock_tot
log_update("📦 Actualizando stock_tot global...");
if($db->querySet("UPDATE productos SET stock_tot = current_stock+stock_lleg") == 1) {
    log_update("✅ stock_tot actualizado exitosamente");
} else {
    log_update("❌ Error actualizando stock_tot");
}

// ================= RESUMEN FINAL =================
$resumen = [
    "🎉 PROCESO COMPLETADO - RESUMEN:",
    "📝 Descripciones actualizadas: " . ($counters['descripcion'] - 1),
    "💰 Precios actualizados: " . (($counters['cost_max'] - 1) + ($counters['cost_oferta'] - 1) + ($counters['cost_mayor'] - 1) + ($counters['cost_min'] - 1) + ($counters['costo'] - 1)),
    "📦 Stocks actualizados: " . (($counters['current_stock'] - 1) + ($counters['stock_lleg'] - 1)),
    "🏷️ Productos nuevos: " . $counters['nuevos'],
    "🗑️ Productos eliminados: " . $counters['eliminados'],
    "⏰ Tiempo total: " . date('Y-m-d H:i:s')
];

foreach ($resumen as $linea) {
    log_update($linea);
}

// Output resumen para UI
echo "========================================\n";
echo "🎉 RESUMEN FINAL - UPDATE_PRODUCTOS-UPD-CSV.PHP\n";
echo "========================================\n";
foreach ($resumen as $linea) {
    echo $linea . "\n";
}
echo "========================================\n";

// ================= FUNCIONES AUXILIARES =================
function agregarLineaCSV($datos, $archivo = 'datos.csv', $headers = null) {
    $archivoExiste = file_exists($archivo);
    $handle = fopen($archivo, 'a');

    if ($handle === false) {
        error_log("No se pudo abrir el archivo: $archivo");
        return false;
    }

    if (flock($handle, LOCK_EX)) {
        if (!$archivoExiste && $headers !== null) {
            fputcsv($handle, $headers);
        }
        fputcsv($handle, $datos);
        flock($handle, LOCK_UN);
    }

    fclose($handle);
    return true;
}

function escribirCSV($datos, $archivo = 'registro.csv', $headers = null) {
    $modo = file_exists($archivo) ? 'a' : 'w';
    $handle = fopen($archivo, $modo);

    if ($handle === false) {
        throw new Exception("No se pudo abrir el archivo: $archivo");
    }

    flock($handle, LOCK_EX);
    try {
        if ($modo === 'w' && $headers !== null) {
            fputcsv($handle, $headers);
        }
        fputcsv($handle, $datos);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
    return true;
}

function log_echo($mensaje, $mostrar_pantalla = false) {
    $archivo_log = '/ketcore/log/preciosChanged.csv';
    $timestamp = date('Y-m-d H:i:s');
    $linea = "[$timestamp] $mensaje\n";
    file_put_contents($archivo_log, $linea, FILE_APPEND | LOCK_EX);
    if ($mostrar_pantalla) {
        echo $mensaje . "\n";
    }
}

?>