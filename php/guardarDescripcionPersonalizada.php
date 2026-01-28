<?php
// guardarDescripcionPersonalizada.php - VERSIÓN CORREGIDA
session_start();
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'error' => ''];

try {
    // 1. Validar sesión
    if (!isset($_SESSION['usr_num'])) {
        throw new Exception('Sesión expirada. Por favor, vuelva a iniciar sesión.');
    }
    
    // 2. Obtener datos
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    
    if (empty($code)) {
        throw new Exception('Código del producto no especificado');
    }
    
    if (empty($descripcion)) {
        throw new Exception('La descripción no puede estar vacía');
    }
    
    // 3. Conectar a la base de datos
    require_once("dbcat.php");
    $db = new DB();
    
    // 4. ESCAPAR los valores para evitar SQL injection
    // Ya que tu clase DB no usa parámetros preparados
    $code_escaped = pg_escape_string($code);
    $descripcion_escaped = pg_escape_string($descripcion);
    
    // 5. Verificar que el producto existe y es editable (no_code = 't')
    $sql_verificar = "SELECT code, no_code, name FROM productos WHERE code = '$code_escaped'";
    $producto = $db->consultas($sql_verificar);
    
    if (empty($producto)) {
        throw new Exception("Producto '$code' no encontrado en la base de datos");
    }
    
    // 6. Verificar si permite modificación (no_code debe ser 't' o true)
    $no_code_val = $producto[0]->no_code;
    $es_editable = false;
    
    if (is_bool($no_code_val)) {
        $es_editable = $no_code_val;
    } elseif (is_string($no_code_val)) {
        $es_editable = ($no_code_val === 't' || $no_code_val === 'true' || $no_code_val === '1');
    } elseif (is_numeric($no_code_val)) {
        $es_editable = ($no_code_val == 1);
    }
    
    if (!$es_editable) {
        throw new Exception("Este producto no permite modificar la descripción (no_code = '$no_code_val')");
    }
    
    // 7. Actualizar la descripción
    $sql_actualizar = "UPDATE productos SET name = '$descripcion_escaped' WHERE code = '$code_escaped'";
    $resultado = $db->consultas($sql_actualizar);
    
    // 8. Verificar que se actualizó correctamente
    $sql_verificar_update = "SELECT name FROM productos WHERE code = '$code_escaped'";
    $verificar = $db->consultas($sql_verificar_update);
    
    if (!empty($verificar)) {
        // Comparar la descripción actualizada
        $descripcion_actual = $verificar[0]->name;
        
        if ($descripcion_actual === $descripcion) {
            $response = [
                'success' => true,
                'message' => '✅ Descripción actualizada correctamente',
                'code' => $code,
                'new_descripcion' => $descripcion
            ];
        } else {
            // Puede haber diferencias de encoding, hacer comparación insensible
            if (strcasecmp($descripcion_actual, $descripcion) === 0) {
                $response = [
                    'success' => true,
                    'message' => '✅ Descripción actualizada (con diferencia de mayúsculas)',
                    'code' => $code,
                    'new_descripcion' => $descripcion_actual
                ];
            } else {
                throw new Exception("No se pudo verificar la actualización. Esperado: '$descripcion', Obtenido: '$descripcion_actual'");
            }
        }
    } else {
        throw new Exception('Error al verificar la actualización');
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    
    // Log para debugging
    error_log("Error en guardarDescripcionPersonalizada: " . $e->getMessage());
}

// 9. Enviar respuesta JSON
echo json_encode($response);
exit;
?>