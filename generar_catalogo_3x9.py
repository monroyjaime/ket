import asyncio
from playwright.async_api import async_playwright
import PyPDF2
import os
import argparse
import psycopg2
from psycopg2.extras import RealDictCursor

class GeneradorCatalogo3x9:
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
        """Obtiene los departamentos directamente de la BD"""
        with self.conn.cursor() as cur:
            query = """
                SELECT 
                    id, 
                    name as nombre, 
                    num, 
                    catalogo_orden as orden,
                    catalogo_num_prod as num_productos,
                    catalogo_first_prod as first_prod,
                    img_route
                FROM departamentos 
                WHERE num = %s 
                  AND catalogo_orden > 0
                  AND catalogo_num_prod > 0
                ORDER BY catalogo_orden
            """
            
            params = [self.linea_num]
            
            if self.limite:
                query += " LIMIT %s"
                params.append(self.limite)
            
            cur.execute(query, params)
            resultados = cur.fetchall()
            
            print(f"  📋 Departamentos encontrados: {len(resultados)}")
            for r in resultados:
                print(f"    - Orden {r['orden']}: {r['nombre'][:50]}... ({r['num_productos']} productos)")
            
            return resultados
    
    async def generar_pagina(self, dpto_id, num_pagina, first_prod):
        """Genera una página PDF con escala fija 0.85"""
        archivo = os.path.join(self.carpeta_salida, f"temp_{dpto_id}_{num_pagina}.pdf")
        
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()
            
            url = f"{self.base_url}?dpto_id={dpto_id}&page_num={num_pagina}&role_num=-1&first_prod={first_prod}"
            print(f"      🌐 Cargando: {url}")
            
            await page.goto(url, wait_until="networkidle", timeout=30000)
            
            await page.pdf(
                path=archivo,
                format="Letter",
                scale=0.85,
                print_background=True,
                tagged=True,
                margin={"top": "5.5mm", "bottom": "5.5mm", "left": "10mm", "right": "10mm"}
            )
            
            await browser.close()
        
        return archivo
    
    def calcular_paginas_departamento(self, num_productos, first_prod):
        """
        Calcula páginas para formato 3 columnas:
        - Con título: 27 productos (3x9)
        - Sin título: 30 productos (3x10)
        """
        if first_prod == 1:
            if num_productos <= 27:
                return 1
            else:
                return 1 + ((num_productos - 27 + 29) // 30)
        else:
            return (num_productos + 29) // 30
    
    async def generar_catalogo(self):
        """Genera el catálogo completo con formato 3 columnas"""
        print(f"\n{'='*60}")
        print(f"🚀 GENERANDO CATÁLOGO {self.nombre_linea.upper()} (3X9/3X10)")
        print(f"{'='*60}")
        
        print(f"\n📡 Leyendo departamentos de BD...")
        departamentos = self.obtener_departamentos_de_bd()
        
        if not departamentos:
            print(f"❌ No hay departamentos")
            return
        
        print(f"\n📋 Total departamentos: {len(departamentos)}")
        
        paginas_generadas = []
        total_productos = 0
        
        for idx, dpto in enumerate(departamentos, 1):
            print(f"\n📦 [{idx}/{len(departamentos)}] Procesando {dpto['nombre']} (ID: {dpto['id']})")
            print(f"  📊 Productos totales: {dpto['num_productos']}")
            print(f"  🎯 first_prod: {dpto['first_prod']}")
            
            total_productos += dpto['num_productos']
            
            paginas_necesarias = self.calcular_paginas_departamento(
                dpto['num_productos'], dpto['first_prod']
            )
            
            if paginas_necesarias == 0:
                print(f"  ⏭️  Departamento ya completado")
                continue
            
            print(f"  📄 Páginas: {paginas_necesarias}")
            
            for num_pag in range(1, paginas_necesarias + 1):
                archivo = await self.generar_pagina(dpto['id'], num_pag, dpto['first_prod'])
                paginas_generadas.append(archivo)
                
                if num_pag == 1 and dpto['first_prod'] == 1:
                    prod_pag = min(27, dpto['num_productos'])
                else:
                    prod_pag = min(30, dpto['num_productos'] - (27 if num_pag > 1 else 0))
                
                print(f"    Página {num_pag}/{paginas_necesarias}: {prod_pag} productos")
        
        print(f"\n📚 Combinando {len(paginas_generadas)} páginas...")
        
        merger = PyPDF2.PdfMerger()
        for archivo in paginas_generadas:
            merger.append(archivo)
        
        output_filename = os.path.join(
            self.carpeta_salida, 
            f"catalogo_{self.nombre_linea.lower()}_3x9.pdf"
        )
        
        merger.write(output_filename)
        merger.close()
        
        for archivo in paginas_generadas:
            try:
                os.remove(archivo)
            except:
                pass
        
        print(f"\n✅ Catálogo generado: {output_filename}")
        print(f"📄 Total páginas: {len(paginas_generadas)}")
        print(f"📦 Total productos: {total_productos}")
        print(f"📊 Formato: 3x9 (27 prod) con título, 3x10 (30 prod) sin título")
        print(f"📏 Escala: 0.85 | Márgenes: 5.5mm/10mm")
        
        return output_filename
    
    def __del__(self):
        if hasattr(self, 'conn'):
            self.conn.close()

def main():
    parser = argparse.ArgumentParser(description='Generador de catálogos KET - Formato 3x9')
    parser.add_argument('--linea', type=str, choices=['A', 'F'], required=True)
    parser.add_argument('--limite', type=int, default=None)
    
    args = parser.parse_args()
    
    conn_params = {
        'host': 'localhost',
        'port': 5432,
        'database': 'ketdb',
        'user': 'ketadmin',
        'password': 'ColocarPasswordAqui'
    }
    
    base_url = "https://ketelectropartes.com/catalogo/indexDpto3x9.php"
    
    print(f"\n{'='*60}")
    print(f"🔧 CONFIGURACIÓN 3 COLUMNAS")
    print(f"{'='*60}")
    print(f"📁 Línea: {args.linea}")
    print(f"📁 Grid: 3x9 (27 prod) con título / 3x10 (30 prod) sin título")
    print(f"📁 Escala fija: 0.85")
    print(f"📁 Márgenes: 5.5mm sup/inf, 10mm izq/der")
    
    generador = GeneradorCatalogo3x9(args.linea, conn_params, base_url, args.limite)
    asyncio.run(generador.generar_catalogo())

if __name__ == "__main__":
    main()