<?php
// ui_html.php - SOLO HTML, nada de lógica PHP
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Productos</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .card { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:disabled { background: #6c757d; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .loading { display: none; text-align: center; margin: 20px 0; color: #007bff; }
        .output { background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0; }
        .stat-card { background: #e9ecef; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Importador de Productos</h1>
        <p>Sincroniza datos desde Google Sheets</p>
        
        <div class="card">
            <h3>📊 Estadísticas</h3>
            <div class="stats-grid" id="estadisticas">
                <div class="stat-card"><div class="stat-value">--</div><div>Productos</div></div>
                <div class="stat-card"><div class="stat-value">--</div><div>Con Stock</div></div>
                <div class="stat-card"><div class="stat-value">--</div><div>Actualización</div></div>
            </div>
            <button class="btn" onclick="cargarEstadisticas()">🔄 Actualizar</button>
        </div>
        
        <div class="loading" id="loading">⏳ Procesando...</div>
        <div id="resultado"></div>
    </div>

    <script>
        function cargarEstadisticas() {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            
            fetch('ui_ajax.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'accion=estadisticas'
            })
            .then(r => {
                loading.style.display = 'none';
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('estadisticas').innerHTML = `
                        <div class="stat-card"><div class="stat-value">${data.total_productos}</div><div>Productos</div></div>
                        <div class="stat-card"><div class="stat-value">${data.con_stock}</div><div>Con Stock</div></div>
                        <div class="stat-card"><div class="stat-value">${data.ultima_actualizacion.split(' ')[0]}</div><div>Actualización</div></div>
                    `;
                } else {
                    document.getElementById('resultado').innerHTML = 
                        `<div class="card error">❌ Error: ${data.error}</div>`;
                }
            })
            .catch(err => {
                loading.style.display = 'none';
                document.getElementById('resultado').innerHTML = 
                    `<div class="card error">❌ Error: ${err.message}</div>`;
            });
        }

        // Cargar al iniciar
        document.addEventListener('DOMContentLoaded', cargarEstadisticas);
    </script>
</body>
</html>