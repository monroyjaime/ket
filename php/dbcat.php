<?php
require_once("config.php"); /* Configuration File */

// ✅ CORRECCIÓN: Session start condicional mejorado
if (session_status() === PHP_SESSION_NONE) {
    // Solo iniciar session si no estamos en CLI y no hay headers enviados
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
    session_start();
    }
}

class DB{
    
    private $link;
    
    public function __construct(){
        $conn_string = "host=".DB_SERVER." port=".DB_PORT." dbname=".DB_NAME." user=".DB_USER." password=".DB_PASS."\n";
        //echo "conn_string: ".$conn_string."\n";
        $this->link = pg_connect($conn_string);
        if (!$this->link)
        {
            //$errormessage=pg_last_error();
            echo "Error conectandose a BD, error:".$errormessage."\n";
            exit();
        }
            
    }
    
    public function __destruct() {
        // ✅ DEJAR QUE PHP MANEJE LA CONEXIÓN - ELIMINA POSIBLES CONFLICTOS
        // No hacer nada - PHP cerrará la conexión automáticamente al terminar el script
        //pg_close($this->link);
    }

    public function getConnection() {
        return $this->conn;
    }

    public function consultas($consulta)
    {
        $return = array();
        $Qu=pg_query($this->link,$consulta);
        while ($row = pg_fetch_object ($Qu))
        {
            $return[] = $row;
        }
        pg_free_result($Qu);
        return $return;
    }

    public function querySet($query) {
    $result = pg_query($this->link, $query);
    
    // ✅ MEJOR MANEJO DE ERRORES
    if (!$result) {
        $error = pg_last_error($this->link);
        error_log("❌ Error en query: " . $error);
        error_log("❌ Query problemática: " . $query);
        return -1;
    }
    
    // ✅ NO usar pg_result_status que puede fallar
    return 1;
}

    

    public function getProdCat($catId)
    {
        if($catId>0)
        {
            $tags  = '<div class="container text-center">';
            $tags .=    '<h1>Catalogo de '.$arrCategories[$catId-1]->catName.'</h1>';
            $tags .=    '<div class="row row-cols-1 row-cols-md-4 g-4 ">';
            $numProducts=0;
            $query = "SELECT id,code,descripcion,photo_url FROM hist_gal WHERE show='t' AND category=".$catId." ORDER BY id";
            $consult = $db->consultas($query);
            foreach ($consult as $value){
                $currCode = $value->code;
                $currDesc = $value->descripcion;
                $currUrl = $arrCategories[$catId-1]->imgRoute.$value->photo_url;

                $tags .=    '<div class="col" style="background-color: #DDD;">';
                $tags .=        '<div class="card h-100 text-bg-light">';
                $tags .=            '<div class="card-header" style="background-color: #eee;">';
                $tags .=                '<h3>'.$currCode.'</h3>';
                $tags .=            '</div>';
                $tags .=            '<img src="'.$currUrl.'" class="card-img-top" alt="'.$currCode.'">';
                $tags .=            '<div class="card-body" style="background-color: #eee;">';
            //  $tags .=                '<h5 class="card-title">'.$currCode.'</h5>';

                $tags .=                '<h6 class="card-text">'.$currDesc.'</h6>';
                $tags .=            '</div>';
                $tags .=        '</div>';
                $tags .=    '</div>';


                $numProducts++;
            }
            
            $tags .=    '</div>';
            $tags .= '</div>';
        }
        else
            $tags = '<p></p>';
        return $tags;    
    }

    public function getListaPrecDpto($dptoId)
    {
        if($dptoId>0)
        {
            $query ="SELECT code,name,cost_max,photo_url FROM productos WHERE dpto_id=".$dptoId." AND cost_max > 0 ORDER BY code";
            $consult = $db->consultas($query);
            foreach ($consult as $value){
                $objRtn = new stdClass();
                $objRtn->code = $value->code;
                $objRtn->name = $value->name;
                $objRtn->cost_max = $value->cot_max;
                $objRtn->photo_url = $value->photo_url;
                $return[] = $objRtn;
            }
            return $return;
        }	
        return null;
    }

    // Método para obtener la conexión (para DBAsync)
    public function getLink() {
        return $this->link;
    }
}
?>