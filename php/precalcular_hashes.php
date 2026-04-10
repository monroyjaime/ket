<?php
// precalcular_hashes.php - Ejecutar UNA SOLA VEZ desde terminal
// Uso: php php/precalcular_hashes.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/dbcat.php';

use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

echo "🚀 INICIANDO PRECÁLCULO DE HASHES PERCEPTUALES\n";
echo "================================================\n\n";

$db = new DB();

// 1. Verificar que la columna existe
$checkColumn = $db->consultas("
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name = 'productos' AND column_name = 'perceptual_hash'
");

if (empty($checkColumn)) {
    die("❌ ERROR: La columna 'perceptual_hash' no existe en la tabla 'productos'. Ejecuta primero el ALTER TABLE.\n");
}

// 2. Obtener productos con foto pero sin hash
$productos = $db->consultas("
    SELECT id, code, photo_url 
    FROM productos 
    WHERE show = 't' 
      AND photo_url IS NOT NULL 
      AND photo_url != 'empty.jpg'
      AND photo_url != ''
      AND (perceptual_hash IS NULL OR perceptual_hash = '')
    ORDER BY id
");

$total = count($productos);
echo "📸 Productos sin hash encontrados: $total\n\n";

if ($total == 0) {
    echo "✅ Todos los productos ya tienen hash perceptual.\n";
    exit;
}

$hasher = new ImageHash(new DifferenceHash());
$procesados = 0;
$errores = 0;

foreach ($productos as $producto) {
    $rutaImagen = __DIR__ . "/../catalogo/images/" . $producto->photo_url;
    
    if (!file_exists($rutaImagen)) {
        echo "⚠️  [ERROR] No existe: {$producto->code} - {$producto->photo_url}\n";
        $errores++;
        continue;
    }
    
    try {
        // Calcular hash perceptual
        $hash = $hasher->hash($rutaImagen);
        $hashHex = $hash->toHex();
        
        // Guardar en BD
        $db->consultaSegura(
            "UPDATE productos SET perceptual_hash = $1 WHERE id = $2",
            [$hashHex, $producto->id]
        );
        
        $procesados++;
        echo "✅ [{$procesados}/{$total}] {$producto->code} - Hash: {$hashHex}\n";
        
    } catch (Exception $e) {
        echo "❌ [ERROR] {$producto->code}: {$e->getMessage()}\n";
        $errores++;
    }
}

echo "\n================================================\n";
echo "📊 RESUMEN FINAL:\n";
echo "   ✅ Procesados: $procesados\n";
echo "   ❌ Errores: $errores\n";
echo "   📸 Total productos con foto: $total\n";
echo "✅ ¡Proceso completado!\n";