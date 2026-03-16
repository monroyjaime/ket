import asyncio
from playwright.async_api import async_playwright
import PyPDF2
import os
import argparse
import psycopg2
from psycopg2.extras import RealDictCursor

class GeneradorCatalogo3x7:
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
    
    def resetear_first_prod(self):
        """Resetea first_prod a 1 para todos los departamentos de la línea"""
        with self.conn.cursor() as cur:
            if self.limite:
                # Si hay límite, solo resetear hasta ese orden
                cur.execute("""
                    UPDATE departamentos 
                    SET catalogo_first_prod = 1 
                    WHERE num = %s 
                      AND catalogo_orden > 0
                      AND catalogo_orden <= %s
                """, (self.linea_num, self.limite))
            else:
                # Resetear todos los de la línea
                cur.execute("""
                    UPDATE departamentos 
                    SET catalogo_first_prod = 1 
                    WHERE num = %s AND catalogo_orden > 0
                """, (self.linea_num,))
            
            print(f"  🔄 First_prod reseteados: {cur.rowcount} departamentos")
            self.conn.commit()
    
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
                print(f"    - Orden {r['orden']}: {r['nombre'][:50]}... ({r['num_productos']} productos, first_prod={r['first_prod']})")
            
            return resultados
    
    def calcular_paginas_departamento(self, num_productos, first_prod):
        """
        Calcula páginas para formato con foto grande:
        - Con título: 21 productos (3x7)
        - Sin título: 24 productos (3x8)
        """
        if first_prod == 1:
            if num_productos <= 21:
                return 1
            else:
                restantes = num_productos - 21
                paginas_extra = (restantes + 23) // 24
                return 1 + paginas_extra
        else:
            return (num_productos + 23) // 24
    
    async def generar_pagina(self, dpto_id, num_pagina, first_prod, pagina_global, total_paginas):
        """Genera una página PDF con escala fija 0.95 (foto más grande)"""
        archivo = os.path.join(self.carpeta_salida, f"temp_{dpto_id}_{num_pagina}.pdf")
        
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()
            
            url = f"{self.base_url}?dpto_id={dpto_id}&page_num={num_pagina}&role_num=-1&first_prod={first_prod}&page_global={pagina_global}&total_paginas={total_paginas}"
            print(f"      🌐 Cargando: {url}")
            
            await page.goto(url, wait_until="networkidle", timeout=30000)
            
            await page.pdf(
                path=archivo,
                format="Letter",
                scale=0.95,
                print_background=True,
                tagged=True,
                margin={"top": "5.5mm", "bottom": "5.5mm", "left": "10mm", "right": "10mm"}
            )
            
            await browser.close()
        
        return archivo
    
    async def generar_catalogo(self):
        """Genera el catálogo completo con formato de foto grande"""
        print(f"\n{'='*60}")
        print(f"🚀 GENERANDO CATÁLOGO {self.nombre_linea.upper()} (3X7/3X8 - FOTO GRANDE)")
        print(f"{'='*60}")
        
        # ============================================
        # PASO 0: RESETEAR FIRST_PROD
        # ============================================
        self.resetear_first_prod()
        
        print(f"\n📡 Leyendo departamentos de BD...")
        departamentos = self.obtener_departamentos_de_bd()
        
        if not departamentos:
            print(f"❌ No hay departamentos")
            return
        
        # ============================================
        # PASO 1: Calcular páginas totales
        # ============================================
        paginas_por_departamento = []
        total_paginas_global = 0
        
        for dpto in departamentos:
            paginas_necesarias = self.calcular_paginas_departamento(
                dpto['num_productos'], dpto['first_prod']
            )
            paginas_por_departamento.append({
                'dpto': dpto,
                'paginas': paginas_necesarias,
                'inicio': total_paginas_global + 1
            })
            total_paginas_global += paginas_necesarias
        
        print(f"\n📊 Total páginas del catálogo: {total_paginas_global}")
        
        # ============================================
        # PASO 2: Generar páginas con numeración global
        # ============================================
        paginas_generadas = []
        total_productos = 0
        pagina_global = 1
        
        for item in paginas_por_departamento:
            dpto = item['dpto']
            paginas_necesarias = item['paginas']
            
            print(f"\n📦 Procesando {dpto['nombre']} (ID: {dpto['id']})")
            print(f"  📊 Productos totales: {dpto['num_productos']}")
            print(f"  🎯 first_prod: {dpto['first_prod']}")
            print(f"  📄 Páginas: {paginas_necesarias} (global: {item['inicio']}-{item['inicio']+paginas_necesarias-1})")
            
            total_productos += dpto['num_productos']
            
            for num_pag in range(1, paginas_necesarias + 1):
                # Calcular productos en esta página
                if num_pag == 1 and dpto['first_prod'] == 1:
                    inicio = 0
                    fin = min(21, dpto['num_productos']) - 1
                else:
                    if dpto['first_prod'] == 1:
                        inicio = 21 + (24 * (num_pag - 2))
                        fin = min(inicio + 24, dpto['num_productos']) - 1
                    else:
                        inicio = (dpto['first_prod'] - 1) + (24 * (num_pag - 1))
                        fin = min(inicio + 24, dpto['num_productos']) - 1
                
                if inicio <= fin:
                    prod_pag = fin - inicio + 1
                    
                    archivo = await self.generar_pagina(
                        dpto['id'], 
                        num_pag, 
                        dpto['first_prod'],
                        pagina_global,
                        total_paginas_global
                    )
                    paginas_generadas.append(archivo)
                    
                    print(f"    Página {num_pag}/{paginas_necesarias} (global {pagina_global}/{total_paginas_global}): {prod_pag} productos")
                    
                    pagina_global += 1
                else:
                    print(f"    ⚠️ Página {num_pag} sin productos - NO GENERADA")
                    # No incrementamos pagina_global
        
        print(f"\n📚 Combinando {len(paginas_generadas)} páginas...")
        
        if not paginas_generadas:
            print("❌ No se generó ninguna página")
            return
        
        merger = PyPDF2.PdfMerger()
        for archivo in paginas_generadas:
            merger.append(archivo)
        
        output_filename = os.path.join(
            self.carpeta_salida, 
            f"catalogo_{self.nombre_linea.lower()}_3x7_foto_grande.pdf"
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
        print(f"📊 Formato: 3x7 (21 prod) con título / 3x8 (24 prod) sin título")
        print(f"📏 Escala: 0.95 | Foto: 50% ancho")
        
        return output_filename
    
    def __del__(self):
        if hasattr(self, 'conn'):
            self.conn.close()

def main():
    parser = argparse.ArgumentParser(description='Generador de catálogos KET - Foto Grande')
    parser.add_argument('--linea', type=str, choices=['A', 'F'], required=True)
    parser.add_argument('--limite', type=int, default=None)
    
    args = parser.parse_args()
    
    conn_params = {
        'host': 'localhost',
        'port': 5432,
        'database': 'ketdb',
        'user': 'ketadmin',
        'password': 'LondonTown'
    }
    
    base_url = "https://ketelectropartes.com/catalogo/indexDpto3x7.php"
    
    print(f"\n{'='*60}")
    print(f"🔧 CONFIGURACIÓN FOTO GRANDE")
    print(f"{'='*60}")
    print(f"📁 Línea: {args.linea}")
    print(f"📁 Grid: 3x7 (21 prod) con título / 3x8 (24 prod) sin título")
    print(f"📁 Escala: 0.95")
    print(f"📁 Foto: 50% ancho")
    print(f"📁 Márgenes: 5.5mm sup/inf, 10mm izq/der")
    
    generador = GeneradorCatalogo3x7(args.linea, conn_params, base_url, args.limite)
    asyncio.run(generador.generar_catalogo())

if __name__ == "__main__":
    main()