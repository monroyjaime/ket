import asyncio
from playwright.async_api import async_playwright
import PyPDF2
import os
import time
import argparse
import psycopg2
from psycopg2.extras import RealDictCursor

class GeneradorCatalogoBaseBD:
    def __init__(self, linea, conn_params, base_url, limite=None, carpeta_salida="/var/www/html/pdfs"):
        self.linea = linea
        self.linea_num = 1 if linea == 'A' else 2
        self.prefijo = 'A' if linea == 'A' else 'F'
        self.nombre_linea = 'Automotriz' if linea == 'A' else 'Ferretero'
        self.base_url = base_url
        self.limite = limite
        self.carpeta_salida = os.path.join(carpeta_salida, f"catalogo_{self.nombre_linea.lower()}")
        os.makedirs(self.carpeta_salida, exist_ok=True)
        
        # Conexión a PostgreSQL
        self.conn = psycopg2.connect(**conn_params, cursor_factory=RealDictCursor)
        self.conn.autocommit = True
    
    def obtener_departamentos_de_bd(self):
        """Obtiene los departamentos directamente de la BD, ordenados por catalogo_orden"""
        with self.conn.cursor() as cur:
            query = """
                SELECT 
                    id, 
                    name as nombre, 
                    num, 
                    catalogo_orden as orden,
                    catalogo_num_prod as num_productos,
                    img_route
                FROM departamentos 
                WHERE num = %s 
                  AND catalogo_orden > 0
                  AND catalogo_num_prod > 0
                ORDER BY catalogo_orden
            """
            
            params = [self.linea_num]
            
            # Aplicar límite si está definido
            if self.limite:
                query += " LIMIT %s"
                params.append(self.limite)
            
            cur.execute(query, params)
            
            resultados = cur.fetchall()
            
            print(f"  📋 Departamentos encontrados: {len(resultados)}")
            for r in resultados:
                print(f"    - Orden {r['orden']}: {r['nombre'][:50]}... ({r['num_productos']} productos)")
            
            return resultados
    
    async def obtener_escala_optima(self, dpto_id, num_pagina, total_productos_pagina=None):
        """
        Escala simplificada:
        - 0.50 para la mayoría
        - 0.40 solo si es necesario (cuando 0.50 da 2 páginas)
        """
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()
            
            url = f"{self.base_url}?dpto_id={dpto_id}&page_num={num_pagina}&role_num=-1"
            await page.goto(url, wait_until="networkidle")
            
            # Probar primero con 0.50
            pdf_data = await page.pdf(
                format="Letter",
                scale=0.50,
                print_background=True,
                margin={"top": "10mm", "bottom": "10mm", "left": "10mm", "right": "10mm"}
            )
            
            temp = "temp_scale_test.pdf"
            with open(temp, "wb") as f:
                f.write(pdf_data)
            
            with open(temp, "rb") as f:
                reader = PyPDF2.PdfReader(f)
                paginas = len(reader.pages)
            
            os.remove(temp)
            
            await browser.close()
            
            # Si 0.50 da 1 página, úsalo
            if paginas == 1:
                print(f"      ✅ Usando escala 0.50 (cabe en 1 página)")
                return 0.50
            else:
                # Si no, usa 0.40
                print(f"      ⚠️ 0.50 da {paginas} páginas, usando 0.40")
                return 0.40
    
    async def generar_pagina(self, dpto_id, num_pagina):
        """Genera una página PDF"""
        # archivo = os.path.join(self.carpeta_salida, f"temp_{dpto_id}_{num_pagina}.pdf")
        import uuid
        archivo = os.path.join(self.carpeta_salida, f"temp_{dpto_id}_{num_pagina}_{uuid.uuid4().hex[:8]}.pdf")
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()
            
            url = f"{self.base_url}?dpto_id={dpto_id}&page_num={num_pagina}&role_num=-1"
            print(f"      🌐 CARGANDO URL: {url}")
            # Opcional: guardar el HTML para depuración
            html_content = await page.content()
            with open(f"/tmp/debug_{dpto_id}_{num_pagina}.html", "w") as f:
                f.write(html_content)
                
            await page.goto(url, wait_until="networkidle", timeout=30000)
            
            await page.pdf(
                path=archivo,
                format="Letter",
                scale=0.40,
                print_background=True,
                margin={"top": "10mm", "bottom": "10mm", "left": "10mm", "right": "10mm"}
            )
            
            await browser.close()
        
        return archivo
    
    async def generar_catalogo(self):
        """Genera el catálogo completo (cada departamento empieza en hoja nueva)"""
        print(f"\n{'='*60}")
        print(f"🚀 GENERANDO CATÁLOGO {self.nombre_linea.upper()} (SIN FUSIÓN)")
        print(f"{'='*60}")
        print(f"📁 Usando: {self.base_url}")
        
        # Obtener departamentos de BD
        print(f"\n📡 Leyendo departamentos de BD...")
        departamentos = self.obtener_departamentos_de_bd()
        
        if not departamentos:
            print(f"❌ No hay departamentos para la línea {self.nombre_linea}")
            return
        
        print(f"\n📋 Total departamentos a procesar: {len(departamentos)}")
        
        paginas_generadas = []
        total_productos = 0
        
        for idx, dpto in enumerate(departamentos, 1):
            print(f"\n📦 [{idx}/{len(departamentos)}] Procesando {dpto['nombre']} (ID: {dpto['id']})")
            print(f"  📊 Productos totales: {dpto['num_productos']}")
            
            total_productos += dpto['num_productos']
            
            # Calcular páginas necesarias para este departamento
            if dpto['num_productos'] <= 20:
                paginas_necesarias = 1
                print(f"  📄 Cabe en 1 página (título + {dpto['num_productos']} productos)")
            else:
                paginas_necesarias = 1 + ((dpto['num_productos'] - 20 + 24) // 25)
                print(f"  📄 Páginas necesarias: {paginas_necesarias}")
            
            # Generar cada página del departamento
            for num_pag in range(1, paginas_necesarias + 1):
                # Calcular cuántos productos tiene esta página (solo para información)
                if num_pag == 1:
                    if dpto['first_prod'] == 1:
                        productos_en_pagina = min(20, dpto['num_productos'])
                    else:
                        productos_en_pagina = min(25, dpto['num_productos'] - (dpto['first_prod'] - 1))
                else:
                    productos_en_pagina = min(25, dpto['num_productos'] - (20 + (num_pag - 2) * 25))
                
                # Obtener escala (0.50 o 0.40)
                escala = await self.obtener_escala_optima(dpto['id'], num_pag)
                
                # Generar página
                archivo = await self.generar_pagina(dpto['id'], num_pag, escala)
                paginas_generadas.append(archivo)
                
                print(f"    Página {num_pag}/{paginas_necesarias} generada ({productos_en_pagina} productos, escala: {escala:.2f})")
        
        # Combinar todas las páginas
        print(f"\n📚 Combinando {len(paginas_generadas)} páginas...")
        
        merger = PyPDF2.PdfMerger()
        for archivo in paginas_generadas:
            merger.append(archivo)
        
        # Nombre del archivo (sin timestamp para que siempre sea el mismo)
        output_filename = os.path.join(
            self.carpeta_salida, 
            f"catalogo_{self.nombre_linea.lower()}_completo.pdf"
        )
        print(f"📁 Guardando como: {output_filename}")
        
        merger.write(output_filename)
        merger.close()
        
        # Limpiar archivos temporales
        for archivo in paginas_generadas:
            try:
                os.remove(archivo)
            except:
                pass
        
        print(f"\n✅ Catálogo generado exitosamente!")
        print(f"📄 Total páginas: {len(paginas_generadas)}")
        print(f"📦 Total productos: {total_productos}")
        print(f"📁 Ubicación: {output_filename}")
        print(f"🌐 URL: https://ketelectropartes.com/pdfs/catalogo_{self.nombre_linea.lower()}/catalogo_{self.nombre_linea.lower()}_completo.pdf")
        
        return output_filename
    
    def __del__(self):
        """Cerrar conexión a BD al finalizar"""
        if hasattr(self, 'conn'):
            self.conn.close()

def main():
    parser = argparse.ArgumentParser(description='Generador de catálogos KET (versión estable con BD)')
    parser.add_argument('--linea', type=str, choices=['A', 'F'], required=True,
                       help='A para Automotriz, F para Ferretero')
    parser.add_argument('--limite', type=int, default=None,
                       help='Límite de departamentos a procesar (para pruebas)')
    
    args = parser.parse_args()
    
    # Configuración de conexión a PostgreSQL
    conn_params = {
        'host': 'localhost',
        'port': 5432,
        'database': 'ketdb',
        'user': 'ketadmin',
        'password': 'LondonTown'
    }
    
    base_url = "https://ketelectropartes.com/catalogo/indexDpto5X5Continuo.php"
    
    print(f"\n{'='*60}")
    print(f"🔧 CONFIGURACIÓN")
    print(f"{'='*60}")
    print(f"📁 Línea: {args.linea} ({'Automotriz' if args.linea == 'A' else 'Ferretero'})")
    if args.limite:
        print(f"📁 Modo PRUEBA: {args.limite} departamentos")
    else:
        print(f"📁 Modo COMPLETO")
    print(f"📁 Base de datos: {conn_params['database']}@{conn_params['host']}")
    print(f"📁 URL base: {base_url}")
    
    try:
        generador = GeneradorCatalogoBaseBD(
            linea=args.linea, 
            conn_params=conn_params, 
            base_url=base_url,
            limite=args.limite
        )
        asyncio.run(generador.generar_catalogo())
    except Exception as e:
        print(f"❌ Error: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()