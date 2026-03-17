import asyncio
import subprocess
import os
import time
import psycopg2
from psycopg2.extras import RealDictCursor
from datetime import datetime

class GeneradorCatalogosIndividuales:
    def __init__(self, conn_params):
        self.conn_params = conn_params
        self.script_base = "generar_catalogo_3x7.py"
        self.carpeta_base = "/var/www/html/pdfs"
        self.log_file = "generacion_catalogos.log"
        
        # Conexión a PostgreSQL
        self.conn = psycopg2.connect(**conn_params, cursor_factory=RealDictCursor)
        self.conn.autocommit = True
    
    def obtener_departamentos_desde_bd(self):
        """
        Obtiene los IDs de departamentos directamente desde la BD
        según los criterios especificados
        """
        dptos_por_linea = {'A': [], 'F': []}
        
        with self.conn.cursor() as cur:
            # Automotriz (num = 1)
            cur.execute("""
                SELECT id 
                FROM departamentos 
                WHERE num = 1 
                  AND catalogo_orden > 0 
                ORDER BY catalogo_orden
            """)
            resultados = cur.fetchall()
            dptos_por_linea['A'] = [r['id'] for r in resultados]
            
            # Ferretero (num = 2)
            cur.execute("""
                SELECT id 
                FROM departamentos 
                WHERE num = 2 
                  AND catalogo_orden > 0 
                ORDER BY catalogo_orden
            """)
            resultados = cur.fetchall()
            dptos_por_linea['F'] = [r['id'] for r in resultados]
        
        return dptos_por_linea
    
    def log(self, mensaje, **kwargs):
        """Escribe en archivo de log y consola, acepta argumentos de print"""
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        
        # Para prints sin salto de línea (end="")
        if kwargs.get('end') == '':
            linea = f"[{timestamp}] {mensaje}"
            print(linea, end='')
            with open(self.log_file, 'a', encoding='utf-8') as f:
                f.write(linea)
        else:
            linea = f"[{timestamp}] {mensaje}"
            print(linea)
            with open(self.log_file, 'a', encoding='utf-8') as f:
                f.write(linea + '\n')
    
    async def generar_catalogo_departamento(self, dpto_id, linea):
        """Genera el catálogo para un departamento específico"""
        comando = [
            "python3",
            self.script_base,
            "--dptos",
            str(dpto_id)
        ]
        
        self.log(f"  🚀 Generando departamento {dpto_id} ({linea})...")
        
        try:
            # Ejecutar el comando
            proceso = subprocess.run(
                comando,
                capture_output=True,
                text=True,
                timeout=300  # 5 minutos máximo por departamento
            )
            
            if proceso.returncode == 0:
                self.log(f"    ✅ {dpto_id} generado correctamente")
                return True
            else:
                self.log(f"    ❌ {dpto_id} - Error: {proceso.stderr[:200]}")
                return False
                
        except subprocess.TimeoutExpired:
            self.log(f"    ⚠️ {dpto_id} - Tiempo agotado")
            return False
        except Exception as e:
            self.log(f"    ❌ {dpto_id} - Excepción: {str(e)}")
            return False
    
    async def generar_todos(self):
        """Genera catálogos para todos los departamentos"""
        self.log("=" * 80)
        self.log("🚀 INICIANDO GENERACIÓN DE CATÁLOGOS INDIVIDUALES")
        self.log("=" * 80)
        
        # Crear carpetas si no existen
        os.makedirs(f"{self.carpeta_base}/catalogo_automotriz", exist_ok=True)
        os.makedirs(f"{self.carpeta_base}/catalogo_ferretero", exist_ok=True)
        
        # Obtener departamentos desde BD
        self.log("\n📡 Consultando departamentos en BD...")
        dptos_por_linea = self.obtener_departamentos_desde_bd()
        
        self.log(f"\n📊 Automotriz: {len(dptos_por_linea['A'])} departamentos")
        self.log(f"📊 Ferretero: {len(dptos_por_linea['F'])} departamentos")
        
        total_generados = 0
        total_fallidos = 0
        
        # Procesar Automotriz
        self.log("\n" + "=" * 60)
        self.log("🔧 PROCESANDO LÍNEA AUTOMOTRIZ")
        self.log("=" * 60)
        
        for i, dpto_id in enumerate(dptos_por_linea['A'], 1):
            self.log(f"\n[{i}/{len(dptos_por_linea['A'])}] ", end="")
            exito = await self.generar_catalogo_departamento(dpto_id, 'A')
            
            if exito:
                total_generados += 1
                # Pequeña pausa entre generaciones
                await asyncio.sleep(2)
            else:
                total_fallidos += 1
        
        # Procesar Ferretero
        self.log("\n" + "=" * 60)
        self.log("🔩 PROCESANDO LÍNEA FERRETERA")
        self.log("=" * 60)
        
        for i, dpto_id in enumerate(dptos_por_linea['F'], 1):
            self.log(f"\n[{i}/{len(dptos_por_linea['F'])}] ", end="")
            exito = await self.generar_catalogo_departamento(dpto_id, 'F')
            
            if exito:
                total_generados += 1
                await asyncio.sleep(2)
            else:
                total_fallidos += 1
        
        # Resumen final
        self.log("\n" + "=" * 80)
        self.log("📊 RESUMEN FINAL")
        self.log("=" * 80)
        self.log(f"✅ Generados correctamente: {total_generados}")
        self.log(f"❌ Fallidos: {total_fallidos}")
        self.log(f"📁 Carpeta base: {self.carpeta_base}")
        self.log("=" * 80)
        
        # Crear archivo con índices
        self.generar_indice(dptos_por_linea)
        
        # Cerrar conexión
        self.conn.close()
    
    def generar_indice(self, dptos_por_linea):
        """Genera un archivo HTML con índices para navegar los PDFs"""
        indice_path = f"{self.carpeta_base}/index.html"
        
        html = f"""<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Catálogos KET - Índice</title>
    <style>
        body {{ 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 30px;
            background-color: #f5f5f5;
        }}
        h1 {{ 
            color: #003272;
            border-bottom: 3px solid #037C79;
            padding-bottom: 10px;
        }}
        h2 {{ 
            color: #037C79; 
            margin-top: 30px;
            background-color: #e8f4f4;
            padding: 8px;
            border-radius: 5px;
        }}
        .grid {{
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }}
        .card {{
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }}
        .card:hover {{
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }}
        a {{ 
            text-decoration: none; 
            color: #0066cc;
            font-weight: 500;
            display: block;
        }}
        a:hover {{ 
            color: #003272;
        }}
        .fecha {{ 
            color: #666; 
            font-size: 0.9em; 
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }}
        .badge {{
            background-color: #037C79;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 5px;
        }}
    </style>
</head>
<body>
    <h1>📚 Catálogos KET - Índice por Departamento</h1>
    <p>Generado el: {datetime.now().strftime("%d/%m/%Y %H:%M")}</p>
    
    <h2>🔧 Línea Automotriz <span class="badge">{len(dptos_por_linea['A'])} dptos</span></h2>
    <div class="grid">
"""
        # Agregar automotriz
        for dpto_id in dptos_por_linea['A']:
            html += f'        <div class="card"><a href="catalogo_automotriz/catalogo_dptos_{dpto_id}.pdf" target="_blank">Departamento {dpto_id}</a></div>\n'
        
        html += """    </div>
    
    <h2>🔩 Línea Ferretero <span class="badge">{len(dptos_por_linea['F'])} dptos</span></h2>
    <div class="grid">
"""
        # Agregar ferretero
        for dpto_id in dptos_por_linea['F']:
            html += f'        <div class="card"><a href="catalogo_ferretero/catalogo_dptos_{dpto_id}.pdf" target="_blank">Departamento {dpto_id}</a></div>\n'
        
        html += f"""    </div>
    
    <div class="fecha">
        <p>📁 Los PDFs se encuentran en las carpetas:</p>
        <ul>
            <li><a href="catalogo_automotriz/">catalogo_automotriz/</a></li>
            <li><a href="catalogo_ferretero/">catalogo_ferretero/</a></li>
        </ul>
        <p>⚙️ Script ejecutado: generar_catalogos_individuales.py</p>
    </div>
</body>
</html>
"""
        
        with open(indice_path, 'w', encoding='utf-8') as f:
            f.write(html)
        
        self.log(f"📄 Índice generado: {indice_path}")

async def main():
    # Configuración de conexión a PostgreSQL
    conn_params = {
        'host': 'localhost',
        'port': 5432,
        'database': 'ketdb',
        'user': 'ketadmin',
        'password': 'LondonTown'
    }
    
    generador = GeneradorCatalogosIndividuales(conn_params)
    await generador.generar_todos()

if __name__ == "__main__":
    asyncio.run(main())