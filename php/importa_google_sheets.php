<?php
// importa_google_sheets.php - VERSIÓN MEJORADA PARA WEB

// CONFIGURACIÓN PARA CONTEXTO WEB
if (php_sapi_name() !== 'cli') {
    set_time_limit(120);
    ini_set('max_execution_time', 120);
    ini_set('memory_limit', '256M');
}

// LOGGING MEJORADO
function log_message($message, $isError = false) {
    $timestamp = date('Y-m-d H:i:s');
    $prefix = $isError ? '❌ ERROR: ' : '📝 INFO: ';
    $logEntry = "[$timestamp] $prefix$message\n";
    
    file_put_contents('/var/www/html/reports/logs/importacion_detalle.log', 
        $logEntry, FILE_APPEND | LOCK_EX);
    
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
}

// MANEJADOR DE ERRORES
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_message("PHP Error: $errstr en $errfile:$errline", true);
    return true;
});

set_exception_handler(function($exception) {
    log_message("Excepción: " . $exception->getMessage(), true);
    log_message("Trace: " . $exception->getTraceAsString(), true);
    exit(1);
});

// CLASE MEJORADA CON MANEJO DE ERRORES
class GoogleSheetsImporter {
    private $db;
    private $googleSheetsUrl;
    private $logFile = '/var/www/html/reports/logs/importacion.log';
    
    public function __construct($googleSheetsUrl) {
        log_message("Inicializando Importador...");
        $this->googleSheetsUrl = $googleSheetsUrl;
        
        try {
            require_once("dbcat.php");
            $this->db = new DB();
            log_message("✅ Conexión a BD establecida");
        } catch (Exception $e) {
            log_message("❌ Error conectando a BD: " . $e->getMessage(), true);
            throw $e;
        }
    }
    
    public function ejecutarProcesoCompleto() {
        log_message("🚀 INICIANDO PROCESO DE ACTUALIZACIÓN");
        
        try {
            // 1. Descargar datos de Google Sheets
            log_message("📥 Descargando datos de Google Sheets...");
            $csvData = $this->descargarDeGoogleSheets();
            log_message("✅ Datos descargados (" . strlen($csvData) . " bytes)");
            
            // 2. Guardar archivo temporal
            $tempFile = $this->guardarArchivoTemporal($csvData);
            log_message("📁 Archivo temporal: " . $tempFile);
            
            // 3. Limpiar tabla prod_name
            log_message("🗑️ Limpiando tabla prod_name...");
            $this->limpiarTablaProdName();
            
            // 4. Cargar datos a PostgreSQL
            log_message("📊 Cargando datos a PostgreSQL...");
            $registrosCargados = $this->cargarAPostgreSQL($tempFile);
            log_message("✅ Datos cargados: " . $registrosCargados . " registros");
            
            // 5. Limpiar archivo temporal - COMENTADO PARA CONSERVAR
            // unlink($tempFile);
            // log_message("🧹 Archivo temporal eliminado");
            log_message("💾 Archivo CSV conservado en: " . $tempFile);
            
            // 6. Ejecutar script de actualización
            log_message("🔄 Ejecutando update_productos.php...");
            $this->ejecutarUpdateProductos();
            
            log_message("🎉 PROCESO COMPLETADO EXITOSAMENTE");
            return true;
            
        } catch (Exception $e) {
            log_message("❌ ERROR en proceso: " . $e->getMessage(), true);
            return false;
        }
    }
    
    private function descargarDeGoogleSheets() {
        log_message("🔗 Conectando a: " . $this->googleSheetsUrl);
        
        // Configuración más robusta para contexto web
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'http' => [
                'timeout' => 45,
                'user_agent' => 'Mozilla/5.0 (compatible; GoogleSheetsImporter/1.0)',
                'header' => "Accept: text/csv,text/plain,*/*\r\n"
            ]
        ];
        
        $context = stream_context_create($contextOptions);
        
        // Intentar descarga con manejo de errores
        $csvData = @file_get_contents($this->googleSheetsUrl, false, $context);
        
        if ($csvData === false) {
            $error = error_get_last();
            throw new Exception("Error descargando de Google Sheets: " . ($error['message'] ?? 'Error desconocido'));
        }
        
        if (empty($csvData)) {
            throw new Exception("Datos vacíos recibidos de Google Sheets");
        }
        
        // Verificar que sean datos CSV válidos
        if (strlen($csvData) < 10 || strpos($csvData, ',') === false) {
            throw new Exception("Datos recibidos no parecen ser CSV válido");
        }
        
        return $csvData;
    }
    
    private function guardarArchivoTemporal($csvData) {
        // Guardar en un archivo fijo
        $logDir = '/var/www/html/reports/logs/';;
        $csvFile  = $logDir . '/google_sheets_ultimo.csv';
        
        log_message("💾 Guardando CSV en: " . $csvFile );
        
        // Forzar la escritura del archivo
        if (file_put_contents($csvFile, $csvData) === false) {
            throw new Exception("No se pudo guardar archivo: " . $csvFile);
        }
        
        // Verificar que el archivo se creó
        if (!file_exists($csvFile )) {
            throw new Exception("Archivo no se creó: " . $csvFile);
        }
        
        log_message("✅ Archivo CSV guardado: " . $csvFile  . " (" . filesize($csvFile ) . " bytes)");
        
        return $csvFile;
    }
    
    private function limpiarTablaProdName() {
        $result = $this->db->querySet("TRUNCATE TABLE prod_name");
        if (!$result) {
            throw new Exception("Error al limpiar tabla prod_name");
        }
    }
    
   private function cargarAPostgreSQL($archivoCSV) {
        log_message("📥 Cargando datos manualmente desde CSV...");
        
        $handle = fopen($archivoCSV, 'r');
        if (!$handle) {
            throw new Exception("No se pudo abrir archivo CSV: " . $archivoCSV);
        }
        
        // Leer headers (primera línea) 
        $headers = fgetcsv($handle);
        
        // ✅ SOLO CORREGIR el campo problemático: stock-lleg → stock_lleg
        $headers = array_map(function($header) {
            if ($header === 'stock-lleg') {
                return 'stock_lleg';
            }
            return $header;
        }, $headers);
        
        log_message("📋 Columnas detectadas: " . implode(', ', $headers));
        
        $registros = 0;
        $batchSize = 100;
        $batchValues = [];
        
        // Procesar cada línea del CSV
        while (($data = fgetcsv($handle)) !== FALSE) {
            // Saltar líneas completamente vacías
            if (empty(array_filter($data, function($v) { return $v !== ''; }))) {
                continue;
            }
            
            // Escapar manualmente sin pg_escape_string
            $cleanedData = array_map(function($value) {
                if ($value === '' || $value === null || $value === 'NULL') {
                    return 'NULL';
                }
                // Escapar comillas simples manualmente
                $value = str_replace("'", "''", $value);
                // Escapar backslashes
                $value = str_replace('\\', '\\\\', $value);
                return "'" . $value . "'";
            }, $data);
            
            $batchValues[] = '(' . implode(', ', $cleanedData) . ')';
            $registros++;
            
            // Insertar en lotes para mejor performance
            if (count($batchValues) >= $batchSize) {
                $this->insertarBatch($batchValues, $headers);
                $batchValues = [];
                log_message("✅ Lote de $batchSize registros insertado...");
            }
        }
        
        // Insertar lote final (si queda algo)
        if (!empty($batchValues)) {
            $this->insertarBatch($batchValues, $headers);
            log_message("✅ Lote final de " . count($batchValues) . " registros insertado");
        }
        
        fclose($handle);
        
        log_message("🎉 Carga completada: " . $registros . " registros insertados");
        return $registros;
    }

    private function insertarBatch($batchValues, $headers) {
        if (empty($batchValues)) return;
        
        $columns = implode(', ', $headers);
        $values = implode(', ', $batchValues);
        
        $query = "INSERT INTO prod_name ($columns) VALUES $values";
        
        $result = $this->db->querySet($query);
        if (!$result) {
            throw new Exception("Error insertando lote de datos en PostgreSQL");
        }
    }
    
    private function ejecutarUpdateProductos() {
        $scriptPath = '/var/www/html/php/update_productos.php';
        
        if (!file_exists($scriptPath)) {
            throw new Exception("Script no encontrado: " . $scriptPath);
        }
        
        // EJECUCIÓN ALTERNATIVA - CAPTURAR TODO EL OUTPUT
        log_message("🔄 EJECUTANDO UPDATE_PRODUCTOS.PHP...");
        
        // Opción 1: Usar shell_exec para capturar TODO el output
        $output = shell_exec("php " . escapeshellarg($scriptPath) . " 2>&1");
        
        // Logear TODO el output recibido
        log_message("📋 OUTPUT COMPLETO DE UPDATE_PRODUCTOS:");
        log_message("========================================");
        if ($output) {
            // Dividir por líneas y logear cada una
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (!empty(trim($line))) {
                    log_message("UPDATE_OUTPUT: " . $line);
                }
            }
        } else {
            log_message("❌ NO HAY OUTPUT CAPTURADO");
        }
        log_message("========================================");
        
        // Verificar éxito basado en si hay output
        if ($output && strpos($output, 'RESUMEN FINAL') !== false) {
            log_message("✅ update_productos.php ejecutado exitosamente");
        } else {
            log_message("⚠️ update_productos.php completado pero sin output esperado");
        }
    }
}

// URL CORRECTA - USA LA MISMA QUE FUNCIONA EN TERMINAL
$googleSheetsUrl = 'https://script.google.com/macros/s/AKfycbyoIqU20qYydm_8bxHF4gyzi2qm7ZkCNB9gwPGEgQ5DcKLjCYQDrllMxnfIxw3rSOnwkQ/exec'; // ⚠️ CAMBIA ESTO

// EJECUCIÓN
try {
    $importer = new GoogleSheetsImporter($googleSheetsUrl);
    $exito = $importer->ejecutarProcesoCompleto();
    exit($exito ? 0 : 1);
} catch (Exception $e) {
    log_message("❌ ERROR FATAL: " . $e->getMessage(), true);
    exit(1);
}
?>