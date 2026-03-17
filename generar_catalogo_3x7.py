import asyncio
from playwright.async_api import async_playwright
import PyPDF2
import os
import argparse
import psycopg2
from psycopg2.extras import RealDictCursor

class GeneradorCatalogo3x7:
    def __init__(self, linea=None, dptos=None, productos=None, conn_params=None, base_url=None, carpeta_salida="/var/www/html/pdfs"):
        self.linea = linea
        self.linea_num = 1 if linea == 'A' else 2 if linea else None
        self.dptos = dptos  # Lista de IDs de departamentos
        self.productos = productos  # Lista de códigos de producto
        self.prefijo = 'A' if linea == 'A' else 'F' if linea else 'GEN'
        self.nombre_linea = 'Automotriz' if linea == 'A' else 'Ferretero' if linea else 'Personalizado'
        self.base_url = base_url
        self.carpeta_salida = os.path.join(carpeta_salida, f"catalogo_{self.nombre_linea.lower()}")
        os.makedirs(self.carpeta_salida, exist_ok=True)
        
        # Conexión a PostgreSQL
        self.conn = psycopg2.connect(**conn_params, cursor_factory=RealDictCursor)
        self.conn.autocommit = True

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
    
    def resetear_first_prod(self, dpto_ids=None):
        """Resetea first_prod a 1 para los departamentos especificados"""
        with self.conn.cursor() as cur:
            if dpto_ids:
                cur.execute("""
                    UPDATE departamentos 
                    SET catalogo_first_prod = 1 
                    WHERE id = ANY(%s)
                """, (dpto_ids,))
                print(f"  🔄 First_prod reseteados: {cur.rowcount} departamentos")
            else:
                print("  ⚠️ No se reseteó ningún first_prod")
            self.conn.commit()
    
    def obtener_departamentos_por_linea(self):
        """Obtiene departamentos por línea (modo tradicional)"""
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
            cur.execute(query, (self.linea_num,))
            return cur.fetchall()
    
    def obtener_departamentos_por_ids(self, dpto_ids):
        """Obtiene departamentos específicos por sus IDs"""
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
                WHERE id = ANY(%s)
                  AND catalogo_num_prod > 0
                ORDER BY id
            """
            cur.execute(query, (dpto_ids,))
            return cur.fetchall()
    
    def obtener_productos_por_codigos(self, codigos):
        """Obtiene productos específicos por sus códigos"""
        with self.conn.cursor() as cur:
            query = """
                SELECT 
                    p.code,
                    p.name as descripcion,
                    p.photo_url,
                    p.dpto_id,
                    d.name as dpto_nombre,
                    d.img_route
                FROM productos p
                JOIN departamentos d ON p.dpto_id = d.id
                WHERE p.code = ANY(%s)
                  AND p.show = true
                  AND p.photo_url != 'empty.jpg'
                  AND p.cost_max > 0
                ORDER BY p.dpto_id, p.orden, p.code
            """
            cur.execute(query, (codigos,))
            return cur.fetchall()
    
    async def generar_pagina_productos_especificos(self, productos, pagina_global, total_paginas):
        """Genera una página con productos específicos (agrupados por dpto)"""
        archivo = os.path.join(self.carpeta_salida, f"temp_especial_{pagina_global}.pdf")
        
        # Construir URL con los códigos de producto
        codigos_str = ','.join([p['code'] for p in productos])
        url = f"{self.base_url}?modo=especial&codigos={codigos_str}&page_global={pagina_global}&total_paginas={total_paginas}"
        
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()
            
            print(f"      🌐 Cargando productos especiales: {url[:100]}...")
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
    
    def organizar_productos_especiales(self, productos):
        """
        Organiza productos específicos en páginas de 21/24 productos
        Agrupados por departamento para mantener títulos
        """
        # Agrupar por departamento
        dptos = {}
        for prod in productos:
            dpto_id = prod['dpto_id']
            if dpto_id not in dptos:
                dptos[dpto_id] = {
                    'nombre': prod['dpto_nombre'],
                    'img_route': prod['img_route'],
                    'productos': []
                }
            dptos[dpto_id]['productos'].append(prod)
        
        # Organizar en páginas
        paginas = []
        pagina_actual = []
        productos_en_pagina = 0
        
        for dpto_id, dpto in dptos.items():
            # Agregar título del departamento
            pagina_actual.append({
                'tipo': 'titulo',
                'nombre': dpto['nombre'],
                'dpto_id': dpto_id
            })
            
            for prod in dpto['productos']:
                pagina_actual.append({
                    'tipo': 'producto',
                    'datos': prod,
                    'dpto_id': dpto_id
                })
                productos_en_pagina += 1
                
                # Si llegamos al límite (24 productos), cerrar página
                if productos_en_pagina >= 24:
                    paginas.append(pagina_actual)
                    pagina_actual = []
                    productos_en_pagina = 0
            
            # Después de cada dpto, verificar si hay que cerrar página
            if pagina_actual and productos_en_pagina > 0:
                paginas.append(pagina_actual)
                pagina_actual = []
                productos_en_pagina = 0
        
        return paginas
    
    async def generar_catalogo(self):
        """Genera el catálogo según el modo seleccionado"""
        print(f"\n{'='*60}")
        if self.linea:
            print(f"🚀 GENERANDO CATÁLOGO {self.nombre_linea.upper()} (LÍNEA COMPLETA)")
        elif self.dptos:
            print(f"🚀 GENERANDO CATÁLOGO PERSONALIZADO ({len(self.dptos)} DEPARTAMENTOS)")
        elif self.productos:
            print(f"🚀 GENERANDO CATÁLOGO DE PRODUCTOS ESPECÍFICOS ({len(self.productos)} PRODUCTOS)")
        print(f"{'='*60}")
        
        # Obtener datos según el modo
        if self.linea:
            self.resetear_first_prod()
            departamentos = self.obtener_departamentos_por_linea()
            # Usar la lógica existente para líneas completas...
            return await self.generar_catalogo_linea(departamentos)
        
        elif self.dptos:
            self.resetear_first_prod(self.dptos)
            departamentos = self.obtener_departamentos_por_ids(self.dptos)
            return await self.generar_catalogo_linea(departamentos)
        
        elif self.productos:
            productos = self.obtener_productos_por_codigos(self.productos)
            print(f"\n📦 Productos encontrados: {len(productos)}")
            
            # Organizar productos en páginas
            paginas = self.organizar_productos_especiales(productos)
            total_paginas = len(paginas)
            print(f"📄 Total páginas: {total_paginas}")
            
            paginas_generadas = []
            pagina_global = 1
            
            for pagina in paginas:
                archivo = await self.generar_pagina_productos_especificos(
                    [item for item in pagina if item['tipo'] == 'producto'],
                    pagina_global,
                    total_paginas
                )
                paginas_generadas.append(archivo)
                print(f"  Página {pagina_global}/{total_paginas} generada")
                pagina_global += 1
            
            # Combinar páginas
            output_filename = os.path.join(
                self.carpeta_salida, 
                f"catalogo_productos_especiales.pdf"
            )
            
            merger = PyPDF2.PdfMerger()
            for archivo in paginas_generadas:
                merger.append(archivo)
            
            merger.write(output_filename)
            merger.close()
            
            print(f"\n✅ Catálogo de productos generado: {output_filename}")
            return output_filename
    
    async def generar_catalogo_linea(self, departamentos):
        """Genera catálogo para una lista de departamentos (reutiliza lógica existente)"""
        
        if not departamentos:
            print("❌ No hay departamentos para procesar")
            return
        
        # ============================================
        # Calcular páginas totales
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
        # Generar páginas
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
                # Calcular offset
                if num_pag == 1:
                    offset = dpto['first_prod'] - 1
                else:
                    offset = (dpto['first_prod'] - 1) + 21 + ((num_pag - 2) * 24)
                
                # Calcular productos en esta página
                if num_pag == 1 and dpto['first_prod'] == 1:
                    prod_pag = min(21, dpto['num_productos'])
                else:
                    if dpto['first_prod'] == 1:
                        if num_pag == 2:
                            prod_pag = min(24, dpto['num_productos'] - 21)
                        else:
                            prod_pag = min(24, dpto['num_productos'] - 21 - (24 * (num_pag - 2)))
                    else:
                        prod_pag = min(24, dpto['num_productos'] - (24 * (num_pag - 1)) - (dpto['first_prod'] - 1))
                
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
        
        # Combinar páginas
        print(f"\n📚 Combinando {len(paginas_generadas)} páginas...")
        
        merger = PyPDF2.PdfMerger()
        for archivo in paginas_generadas:
            merger.append(archivo)
        
        # Nombre del archivo según el modo
        if self.linea:
            sufijo = f"linea_{self.linea}"
        elif self.dptos:
            sufijo = f"dptos_{'_'.join(map(str, self.dptos))}"
        else:
            sufijo = "personalizado"
        
        output_filename = os.path.join(
            self.carpeta_salida, 
            f"catalogo_{sufijo}.pdf"
        )
        
        merger.write(output_filename)
        merger.close()
        
        # Limpiar temporales
        for archivo in paginas_generadas:
            try:
                os.remove(archivo)
            except:
                pass
        
        print(f"\n✅ Catálogo generado: {output_filename}")
        print(f"📄 Total páginas: {len(paginas_generadas)}")
        print(f"📦 Total productos: {total_productos}")
        
        return output_filename

def main():
    parser = argparse.ArgumentParser(description='Generador de catálogos KET - Modos flexibles')
    
    # Grupo exclusivo: solo uno de estos puede usarse
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument('--linea', type=str, choices=['A', 'F'], help='Línea completa (A o F)')
    group.add_argument('--dptos', type=str, help='Lista de IDs de departamentos separados por coma (ej: 101,103,105)')
    group.add_argument('--productos', type=str, help='Lista de códigos de producto separados por coma (ej: SW09C187,GA000-05)')
    
    parser.add_argument('--limite', type=int, default=None, help='Límite de departamentos (solo con --linea)')
    
    args = parser.parse_args()
    
    conn_params = {
        'host': 'localhost',
        'port': 5432,
        'database': 'ketdb',
        'user': 'ketadmin',
        'password': 'LondonTown'
    }
    
    base_url = "https://ketelectropartes.com/catalogo/indexDpto3x7.php"
    
    # Procesar argumentos
    dptos = None
    productos = None
    
    if args.dptos:
        dptos = [int(x.strip()) for x in args.dptos.split(',')]
        print(f"📋 Departamentos solicitados: {dptos}")
    
    if args.productos:
        productos = [x.strip() for x in args.productos.split(',')]
        print(f"📋 Productos solicitados: {len(productos)} códigos")
    
    generador = GeneradorCatalogo3x7(
        linea=args.linea,
        dptos=dptos,
        productos=productos,
        conn_params=conn_params,
        base_url=base_url
    )
    
    asyncio.run(generador.generar_catalogo())

if __name__ == "__main__":
    main()