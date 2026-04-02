<?php
// test_simple.php - Script de prueba ultra simple
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Usar ruta absoluta basada en document root
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . "/php/dbcat.php");

header('Content-Type: text/html');

echo "<h1>Test Simple de Actualización de Fotos</h1>";

// Verificar sesión
$isAdmin = isset($_SESSION['usr_admin']) ? $_SESSION['usr_admin'] : 0;
$role = isset($_SESSION['role']) ? intval($_SESSION['role']) : -1;

echo "<p>usr_admin: $isAdmin</p>";
echo "<p>role: $role</p>";

if ($role == -1 || $isAdmin != 1) {
    echo "<p style='color:red'>ACCESO DENEGADO</p>";
    exit;
}

echo "<p style='color:green'>✓ Acceso permitido</p>";

// Probar conexión a BD
try {
    $db = new DB();
    echo "<p style='color:green'>✓ Conexión DB OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error DB: " . $e->getMessage() . "</p>";
    exit;
}

// Obtener lista de departamentos para el selector
$deptosQuery = "SELECT id, name, img_route FROM departamentos ORDER BY name";
$deptos = $db->consultas($deptosQuery);

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<hr>";
    echo "<h2>Resultado del POST:</h2>";
    
    echo "<h3>POST:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>FILES:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : null;
    $dptoId = isset($_POST['dpto_id']) ? (int)$_POST['dpto_id'] : null;
    
    if ($codigo && $dptoId && isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        echo "<h3>Procesando archivo...</h3>";
        
        $archivo = $_FILES['archivo'];
        echo "Nombre original: " . htmlspecialchars($archivo['name']) . "<br>";
        echo "Tipo: " . $archivo['type'] . "<br>";
        echo "Tamaño: " . $archivo['size'] . " bytes<br>";
        echo "Temporal: " . $archivo['tmp_name'] . "<br>";
        
        // Verificar que sea JPG
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        echo "MIME detectado: " . $mime . "<br>";
        
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            echo "<span style='color:green'>✓ Tipo de archivo válido</span><br>";
            
            // Obtener ruta del departamento
            $deptoQuery = "SELECT img_route FROM departamentos WHERE id = $dptoId";
            $deptoResult = $db->consultas($deptoQuery);
            
            if (!empty($deptoResult)) {
                $imgRoute = $deptoResult[0]->img_route;
                echo "imgRoute original: " . htmlspecialchars($imgRoute) . "<br>";
                
                // Limpiar ruta
                $imgRoute = preg_replace('#^https?://[^/]+/#', '', $imgRoute);
                $imgRoute = preg_replace('#^ketelectropartes\.com/#', '', $imgRoute);
                $imgRoute = ltrim($imgRoute, '/');
                
                if (empty($imgRoute)) {
                    $imgRoute = 'catalogo/images/departamentos/';
                }
                
                if (substr($imgRoute, -1) !== '/') {
                    $imgRoute .= '/';
                }
                
                echo "imgRoute limpia: " . htmlspecialchars($imgRoute) . "<br>";
                
                $nombreArchivo = $codigo . '.jpg';
                echo "Nombre archivo: " . htmlspecialchars($nombreArchivo) . "<br>";
                
                $directorioDestino = $docRoot . '/' . $imgRoute;
                $rutaCompleta = $directorioDestino . $nombreArchivo;
                
                echo "Directorio destino: " . htmlspecialchars($directorioDestino) . "<br>";
                echo "Ruta completa: " . htmlspecialchars($rutaCompleta) . "<br>";
                
                // Verificar/crear directorio
                if (!file_exists($directorioDestino)) {
                    echo "Directorio no existe, intentando crear...<br>";
                    if (mkdir($directorioDestino, 0777, true)) {
                        echo "<span style='color:green'>✓ Directorio creado</span><br>";
                    } else {
                        echo "<span style='color:red'>✗ No se pudo crear el directorio</span><br>";
                    }
                } else {
                    echo "✓ Directorio existe<br>";
                    echo "Permisos: " . substr(sprintf('%o', fileperms($directorioDestino)), -4) . "<br>";
                    echo "¿Escribible? " . (is_writable($directorioDestino) ? 'Sí' : 'No') . "<br>";
                }
                
                // Intentar copiar/mover el archivo
                if (is_writable($directorioDestino) || chmod($directorioDestino, 0777)) {
                    if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
                        echo "<span style='color:green'>✓ Archivo guardado correctamente en: " . htmlspecialchars($rutaCompleta) . "</span><br>";
                        
                        // Actualizar BD
                        $rutaRelativa = $imgRoute . $nombreArchivo;
                        $updateQuery = "UPDATE productos SET photo_url = '$rutaRelativa' WHERE code = '$codigo'";
                        echo "Query update: " . htmlspecialchars($updateQuery) . "<br>";
                        
                        if ($db->ejecutar($updateQuery)) {
                            echo "<span style='color:green; font-size:1.2em'>✓ ¡ÉXITO! La foto se ha actualizado correctamente.</span><br>";
                            
                            // Mostrar la imagen subida
                            echo "<h3>Vista previa:</h3>";
                            echo "<img src='/" . $rutaRelativa . "' style='max-width: 300px; border: 1px solid #ccc; padding: 5px;'>";
                        } else {
                            echo "<span style='color:red'>✗ Error al actualizar la BD</span><br>";
                            // Eliminar archivo si falló la BD
                            if (file_exists($rutaCompleta)) {
                                unlink($rutaCompleta);
                                echo "Archivo eliminado por fallo en BD<br>";
                            }
                        }
                    } else {
                        echo "<span style='color:red'>✗ Error al mover el archivo</span><br>";
                        echo "Error de PHP: " . print_r(error_get_last(), true) . "<br>";
                    }
                } else {
                    echo "<span style='color:red'>✗ No se puede escribir en el directorio incluso después de intentar cambiar permisos</span><br>";
                    // Intentar cambiar owner con comandos del sistema
                    echo "Intentando cambiar permisos con comandos del sistema...<br>";
                    $output = [];
                    $return = 0;
                    exec("sudo chmod -R 777 " . escapeshellarg($directorioDestino), $output, $return);
                    echo "Resultado: " . ($return === 0 ? "OK" : "Falló") . "<br>";
                }
            } else {
                echo "<span style='color:red'>✗ No se encontró el departamento ID: $dptoId</span><br>";
            }
        } else {
            echo "<span style='color:red'>✗ Tipo de archivo no válido. Solo JPG. Detectado: $mime</span><br>";
        }
    } else {
        echo "<p style='color:red'>Datos incompletos o error en archivo</p>";
        if (isset($_FILES['archivo']['error']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $errores = [
                1 => 'El archivo excede el tamaño máximo permitido por el servidor',
                2 => 'El archivo excede el tamaño máximo permitido por el formulario',
                3 => 'El archivo solo se subió parcialmente',
                4 => 'No se seleccionó ningún archivo',
                6 => 'Falta la carpeta temporal',
                7 => 'Error al escribir el archivo en el disco',
                8 => 'Una extensión de PHP detuvo la subida'
            ];
            echo "Error de subida: " . ($errores[$_FILES['archivo']['error']] ?? 'Error desconocido') . "<br>";
        }
    }
}
?>

<hr>
<h2>Formulario de Prueba</h2>
<form method="POST" enctype="multipart/form-data">
    <div style="margin-bottom: 10px;">
        <label><strong>Código del producto:</strong></label><br>
        <input type="text" name="codigo" required style="width: 300px;" placeholder="Ej: DG006NE" value="DG006NE">
        <small>(Ejemplo: DG006NE)</small>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label><strong>Departamento:</strong></label><br>
        <select name="dpto_id" required style="width: 300px;">
            <option value="">Seleccionar...</option>
            <?php foreach ($deptos as $d): ?>
                <option value="<?php echo $d->id; ?>">
                    <?php echo htmlspecialchars($d->name); ?> (ID: <?php echo $d->id; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div style="margin-bottom: 10px;">
        <label><strong>Archivo JPG:</strong></label><br>
        <input type="file" name="archivo" accept="image/jpeg,image/jpg" required>
        <small>Máximo 2MB, solo JPG</small>
    </div>
    
    <div>
        <button type="submit" style="padding: 8px 20px; background: #037C79; color: white; border: none; border-radius: 5px;">Probar Subida</button>
    </div>
</form>

<p><strong>Nota:</strong> Usa el producto DG006NE (TERMOENCOGIBLES) para la prueba. Si no sabes el ID del departamento, selecciona de la lista.</p>