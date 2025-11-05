<?php
require_once("dbcat.php");

class DBAsync extends DB {
    private $last_error;
    
    public function __construct() {
        parent::__construct();
        $this->last_error = null;
    }

    /**
     * MÉTODO SEGURO - Previene SQL injection con parámetros preparados
     */
    public function consultaSegura($sql, $params = []) {
        $this->last_error = null;
        
        try {
            // Usar pg_query_params para consultas preparadas
            $result = pg_query_params($this->link, $sql, $params);
            
            if (!$result) {
                $this->last_error = pg_last_error($this->link);
                error_log("DBAsync Error: " . $this->last_error . " - SQL: " . $sql);
                return [];
            }
            
            $return = [];
            while ($row = pg_fetch_object($result)) {
                $return[] = $row;
            }
            pg_free_result($result);
            
            return $return;
            
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            error_log("DBAsync Exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * MÉTODO PARA INSERT/UPDATE/DELETE seguros
     */
    public function querySetSeguro($sql, $params = []) {
        $this->last_error = null;
        
        try {
            $result = pg_query_params($this->link, $sql, $params);
            if (!$result) {
                $this->last_error = pg_last_error($this->link);
                error_log("DBAsync Error querySetSeguro: " . $this->last_error);
                return -1;
            }
            
            $status = pg_result_status($result);
            pg_free_result($result);
            
            return ($status == PGSQL_COMMAND_OK) ? 1 : -1;
            
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            error_log("DBAsync Exception querySetSeguro: " . $e->getMessage());
            return -1;
        }
    }

    /**
     * Obtener último error
     */
    public function getLastError() {
        return $this->last_error;
    }

    /**
     * CONVERSOR - Para migrar consultas existentes fácilmente
     * Convierte consultas con variables embebidas a consultas preparadas
     */
    public function convertirConsulta($consultaAntigua) {
        // Ejemplo de conversión automática
        // "SELECT * FROM tabla WHERE id = $variable" 
        // se convierte en:
        // ["SELECT * FROM tabla WHERE id = $1", [$variable]]
        
        // Esta es una implementación básica - puedes mejorarla
        preg_match_all('/=(\s*)(\$\w+|\'\$?\w+\'|\"\$?\w+\"|\d+)/', $consultaAntigua, $matches);
        
        $nuevosParams = [];
        $nuevaSQL = $consultaAntigua;
        $paramIndex = 1;
        
        // Reemplazar valores por placeholders
        $nuevaSQL = preg_replace_callback('/(WHERE|AND|OR)\s+(\w+)\s*=\s*([^\s]+)/', 
            function($matches) use (&$nuevosParams, &$paramIndex) {
                $valor = trim($matches[3], "'\"");
                $nuevosParams[] = $valor;
                return $matches[1] . " " . $matches[2] . " = $" . $paramIndex++;
            }, 
            $consultaAntigua
        );
        
        return [
            'sql' => $nuevaSQL,
            'params' => $nuevosParams
        ];
    }
}
?>