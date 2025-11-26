<?php
require_once("dbcat.php");

class GoogleSheetsImporter {
    private $db;
    private $googleSheetsUrl;
    private $logFile = '/var/www/html/reports/logs/importacion.log';
    
    public function __construct($googleSheetsUrl) {
        $this->db = new DB();
        $this->googleSheetsUrl = $googleSheetsUrl;
        
        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function ejecutarProcesoCompleto() {
        try {
            $this->log("🚀 INICIANDO PROCESO DE ACTUALIZACIÓN");
            
            // 1. Descargar datos de Google Sheets
            $csvData = $this->descargarDeGoogleSheets();
            $this->log("✅ Datos descargados (" . strlen($csvData) . " bytes)");
            
            // 2. Guardar archivo temporal
            $tempFile = $this->guardarArchivoTemporal($csvData);
            $this->log("📁 Archivo temporal guardado: " . $tempFile);
            
            // 3. Limpiar tabla prod_name
            $this->limpiarTablaProdName();
            $this->log("🗑️  Tabla prod_name limpiada");
            
            // 4. Cargar datos a PostgreSQL
            $registrosCargados = $this->cargarAPostgreSQL($tempFile);
            $this->log("📊 Datos cargados a PostgreSQL: " . $registrosCargados . " registros");
            
            // 5. Limpiar archivo temporal
            unlink($tempFile);
            $this->log("🧹 Archivo temporal eliminado");
            
            // 6. Ejecutar tu script de actualización (PRESERVADO)
            $this->ejecutarUpdateProductos();
            $this->log("🎉 PROCESO COMPLETADO EXITOSAMENTE");
            
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ ERROR: " . $e->getMessage());
            return false;
        }
    }
    
    private function descargarDeGoogleSheets() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; GoogleSheetsImporter)'
            ]
        ]);
        
        $csvData = file_get_contents($this->googleSheetsUrl, false, $context);
        
        if ($csvData === false) {
            throw new Exception("No se pudo descargar datos de Google Sheets");
        }
        
        if (empty($csvData)) {
            throw new Exception("Datos vacíos recibidos de Google Sheets");
        }
        
        return $csvData;
    }
    
    private function guardarArchivoTemporal($csvData) {
        $tempFile = tempnam(sys_get_temp_dir(), 'google_sheets_') . '.csv';
        file_put_contents($tempFile, $csvData);
        return $tempFile;
    }
    
    private function limpiarTablaProdName() {
        $result = $this->db->querySet("TRUNCATE TABLE prod_name");
        if (!$result) {
            throw new Exception("Error al limpiar tabla prod_name");
        }
    }
    
    private function cargarAPostgreSQL($archivoCSV) {
        // ✅ SOLUCIÓN CON psql \copy
        $host = 'localhost';
        $dbname = 'ketdb';
        $user = 'ketadmin'; 
        $password = 'LondonTown';    
        
        // PRIMERO: Analizar el CSV para encontrar problemas
        $this->analizarCSVProblemas($archivoCSV);
        
        $comando = "PGPASSWORD='" . escapeshellarg($password) . "' psql " .
                "-h " . escapeshellarg($host) . " " .
                "-d " . escapeshellarg($dbname) . " " .
                "-U " . escapeshellarg($user) . " " .
                "-c " . escapeshellarg("\copy prod_name FROM '" . $archivoCSV . "' WITH (FORMAT CSV, HEADER)");
        
        $this->log("Ejecutando comando psql \\copy...");
        
        exec($comando . " 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            $errorMsg = implode("\n", $output);
            throw new Exception("Error en psql \\copy (código: $returnCode): " . $errorMsg);
        }
        
        $this->log("✅ Comando psql ejecutado exitosamente");
        
        // Verificar registros insertados
        $consulta = $this->db->consultas("SELECT COUNT(*) as total FROM prod_name");
        $total = $consulta[0]->total;
        $this->log("📊 Registros en prod_name: " . $total);
        
        return $total;
    }

    private function analizarCSVProblemas($archivoCSV) {
        $this->log("🔍 Analizando CSV para problemas...");
        
        $handle = fopen($archivoCSV, 'r');
        if (!$handle) {
            $this->log("❌ No se pudo abrir CSV para análisis");
            return;
        }
        
        // Leer headers
        $headers = fgetcsv($handle);
        $this->log("📋 Headers: " . implode(', ', $headers));
        
        $lineaNum = 1;
        $problemas = [];
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $lineaNum++;
            
            // Buscar #VALUE! en cualquier campo
            foreach ($data as $index => $valor) {
                if (strpos($valor, '#VALUE!') !== false) {
                    $problemas[] = "Línea $lineaNum - Columna " . ($index + 1) . " (" . $headers[$index] . "): '$valor'";
                }
            }
            
            // Buscar campos vacíos problemáticos
            if (empty($data[0])) { // code vacío
                $problemas[] = "Línea $lineaNum - CODE VACÍO: " . implode(' | ', $data);
            }
            
            // Solo revisar primeras líneas para no saturar el log
            if ($lineaNum <= 10) {
                $this->log("Línea $lineaNum: " . implode(' | ', array_slice($data, 0, 3)) . "...");
            }
        }
        
        fclose($handle);
        
        if (!empty($problemas)) {
            $this->log("❌ PROBLEMAS ENCONTRADOS:");
            foreach ($problemas as $problema) {
                $this->log("   - " . $problema);
            }
        } else {
            $this->log("✅ CSV parece estar limpio");
        }
        
        $this->log("📄 Total líneas en CSV: " . $lineaNum);
    }
    
    private function ejecutarUpdateProductos() {
        // Ejecutar tu script actual - PRESERVADO INTACTO
        $scriptPath = '/var/www/html/php/update_productos.php';
        
        if (!file_exists($scriptPath)) {
            throw new Exception("Script update_productos.php no encontrado en: " . $scriptPath);
        }
        
        // Capturar output para logging
        ob_start();
        include $scriptPath;
        $output = ob_get_clean();
        
        $this->log("📝 Output de update_productos.php:\n" . $output);
    }
    
    private function log($mensaje) {
        $timestamp = date('Y-m-d H:i:s');
        $linea = "[$timestamp] $mensaje\n";
        file_put_contents($this->logFile, $linea, FILE_APPEND | LOCK_EX);
        echo $linea; // También mostrar en pantalla
    }
}

// CONFIGURACIÓN
$googleSheetsUrl = 'https://script.google.com/macros/s/AKfycbyoIqU20qYydm_8bxHF4gyzi2qm7ZkCNB9gwPGEgQ5DcKLjCYQDrllMxnfIxw3rSOnwkQ/exec'; // Tu URL de Google Apps Script

// EJECUCIÓN
$importer = new GoogleSheetsImporter($googleSheetsUrl);
$exito = $importer->ejecutarProcesoCompleto();

exit($exito ? 0 : 1);
?>