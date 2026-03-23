import asyncio
from playwright.async_api import async_playwright
import PyPDF2
import os
import argparse
import psycopg2
from psycopg2.extras import RealDictCursor
import shutil

class GeneradorCatalogo3x7:
    def __init__(self, linea=None, dptos=None, productos=None, conn_params=None, 
                 base_url=None, calidad="borrador", carpeta_salida="/var/www/html/pdfs"):
        self.linea = linea
        self.dptos = dptos
        self.productos = productos
        self.calidad = calidad  # 'borrador' o 'impresion'
        self.base_url = base_url
        self.carpeta_base = carpeta_salida
        self.temp_dir = "/home/jaime/catalogo_ket/tmp"
        # Determinar la línea si tenemos dptos
        if dptos and not linea:
            with psycopg2.connect(**conn_params, cursor_factory=RealDictCursor) as conn:
                with conn.cursor() as cur:
                    cur.execute("SELECT num FROM departamentos WHERE id = %s", (dptos[0],))
                    result = cur.fetchone()
                    if result:
                        self.linea_num = result['num']
                        self.linea = 'A' if self.linea_num == 1 else 'F'
                    else:
                        self.linea_num = None
                        self.linea = None
        else:
            self.linea_num = 1 if linea == 'A' else 2 if linea else None
        
        self.prefijo = 'A' if self.linea == 'A' else 'F' if self.linea else 'GEN'
        self.nombre_linea = 'Automotriz' if self.linea == 'A' else 'Ferretero' if self.linea else 'Personalizado'
        
        # Conexión a PostgreSQL
        self.conn = psycopg2.connect(**conn_params, cursor_factory=RealDictCursor)
        self.conn.autocommit = True
    
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
    
    async def generar_pagina_productos_especificos(self, productos, pagina_global, total_paginas):
        """Genera una página con productos específicos (agrupados por dpto)"""
        archivo = os.path.join("self.temp_dir", f"temp_especial_{pagina_global}.pdf")
        
        # Construir URL con los códigos de producto
        codigos_str = ','.join([p['code'] for p in productos])
        url = f"{self.base_url}?modo=especial&codigos={codigos_str}&page_global={pagina_global}&total_paginas={total_paginas}"
        
        async with async_playwright() as p:
            # Configurar Chromium según la calidad
            if self.calidad == "borrador":
                browser = await p.chromium.launch(
                    headless=True,
                    args=['--disable-pdf-tagging']
                )
            else:
                browser = await p.chromium.launch(headless=True)
            
            page = await browser.new_page()
            
            print(f"      🌐 Cargando productos especiales: {url[:100]}...")
            await page.goto(url, wait_until="networkidle", timeout=30000)
            
            pdf_options = {
                "path": archivo,
                "format": "Letter",
                "scale": 0.85,
                "print_background": True,
                "margin": {"top": "5.5mm", "bottom": "5.5mm", "left": "10mm", "right": "10mm"}
            }
            
            if self.calidad == "impresion":
                pdf_options["tagged"] = True
                pdf_options["prefer_css_page_size"] = True
            
            await page.pdf(**pdf_options)
            await browser.close()
        
        return archivo
    
    async def generar_pagina(self, dpto_id, num_pagina, first_prod, pagina_global, total_paginas):
        """Genera una página PDF con calidad ajustable"""
        archivo = os.path.join("self.temp_dir", f"temp_{dpto_id}_{num_pagina}_{pagina_global}.pdf")
        
        async with async_playwright() as p:
            # Configurar Chromium según la calidad
            if self.calidad == "borrador":
                browser = await p.chromium.launch(
                    headless=True,
                    args=['--disable-pdf-tagging']
                )
            else:
                browser = await p.chromium.launch(headless=True)
            
            page = await browser.new_page()
            
            url = f"{self.base_url}?dpto_id={dpto_id}&page_num={num_pagina}&role_num=-1&first_prod={first_prod}&page_global={pagina_global}&total_paginas={total_paginas}"
            print(f"      🌐 Cargando: {url}")
            
            await page.goto(url, wait_until="networkidle", timeout=30000)
            
            pdf_options = {
                "path": archivo,
                "format": "Letter",
                "scale": 0.85,
                "print_background": True,
                "margin": {"top": "5.5mm", "bottom": "5.5mm", "left": "10mm", "right": "10mm"}
            }
            
            if self.calidad == "impresion":
                pdf_options["tagged"] = True
                pdf_options["prefer_css_page_size"] = True
            
            await page.pdf(**pdf_options)
            await browser.close()
        
        return archivo
    
    async def generar_catalogo_linea(self, departamentos):
        """Genera catálogo para una lista de departamentos"""
        
        if not departamentos:
            print("❌ No hay departamentos para procesar")
            return None
        
        # Calcular páginas totales
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
        
        # Generar páginas
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
                archivo = await self.generar_pagina(
                    dpto['id'], 
                    num_pag, 
                    dpto['first_prod'],
                    pagina_global,
                    total_paginas_global
                )
                paginas_generadas.append(archivo)
                
                # Calcular productos en esta página (solo para info)
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
            nombre_archivo = f"catalogo_{sufijo}.pdf"
        elif self.dptos:
            if len(self.dptos) == 1:
                nombre_archivo = f"catalogo_dptos_{self.dptos[0]}.pdf"
            else:
                sufijo = f"dptos_{'_'.join(map(str, self.dptos))}"
                nombre_archivo = f"catalogo_{sufijo}.pdf"
        else:
            nombre_archivo = "catalogo_personalizado.pdf"
        
        output_filename = os.path.join("self.temp_dir", nombre_archivo)
        merger.write(output_filename)
        merger.close()
        
        # Limpiar temporales
        for archivo in paginas_generadas:
            try:
                os.remove(archivo)
            except:
                pass
        
        print(f"\n✅ Catálogo generado temporalmente: {output_filename}")
        print(f"📄 Total páginas: {len(paginas_generadas)}")
        print(f"📦 Total productos: {total_productos}")
        print(f"🎚️ Calidad: {self.calidad}")
        
        return output_filename
    
    async def generar_catalogo(self):
        """Genera el catálogo según el modo seleccionado"""
        print(f"\n🔴 DEBUG - Modo: linea={self.linea}, dptos={self.dptos}, productos={self.productos}")
        print(f"\n{'='*60}")
        
        # ============================================
        # MODO 1: LÍNEA COMPLETA
        # ============================================
        if self.linea and not self.dptos and not self.productos:
            print(f"🚀 GENERANDO CATÁLOGO {self.nombre_linea.upper()} (LÍNEA COMPLETA)")
            print(f"{'='*60}")
            print(f"🎚️ Calidad: {self.calidad}")
            
            self.resetear_first_prod()
            departamentos = self.obtener_departamentos_por_linea()
            
            if not departamentos:
                print("❌ No hay departamentos para procesar")
                return None
            
            archivo_temporal = await self.generar_catalogo_linea(departamentos)
            
            if archivo_temporal and os.path.exists(archivo_temporal):
                # Carpeta base
                carpeta_base = f"{self.carpeta_base}/catalogo_{self.nombre_linea.lower()}"
                
                # Si es calidad impresión, usar subcarpeta print
                if self.calidad == "impresion":
                    carpeta_destino = os.path.join(carpeta_base, "print")
                else:
                    carpeta_destino = carpeta_base

                os.makedirs(carpeta_destino, exist_ok=True)   

                nombre_final = f"catalogo_linea_{self.linea}.pdf"
                destino = os.path.join(carpeta_destino, nombre_final)

                print(f"🔴 DEBUG - Intentando guardar en: {destino}")
                print(f"   Archivo temporal existe: {os.path.exists(archivo_temporal)}")
                
                shutil.move(archivo_temporal, destino)
                print(f"📁 Archivo guardado en: {destino}")
                
                if os.path.exists(destino):
                    print(f"   ✅ Confirmado: {destino}")
                else:
                    print(f"   ❌ Error: No se pudo guardar en {destino}")
                
                return destino
            
            return None
        
        # ============================================
        # MODO 2: DEPARTAMENTOS ESPECÍFICOS
        # ============================================
        elif self.dptos and not self.linea and not self.productos:
            print(f"🚀 GENERANDO CATÁLOGO DE DEPARTAMENTOS ESPECÍFICOS")
            print(f"{'='*60}")
            print(f"📋 IDs: {self.dptos}")
            print(f"🎚️ Calidad: {self.calidad}")
            
            self.resetear_first_prod(self.dptos)
            departamentos = self.obtener_departamentos_por_ids(self.dptos)
            
            if not departamentos:
                print("❌ No se encontraron departamentos con esos IDs")
                return None
            
            archivo_temporal = await self.generar_catalogo_linea(departamentos)
            
            if archivo_temporal and os.path.exists(archivo_temporal):
                # Determinar carpeta según los departamentos
                if len(departamentos) == 1:
                    # Un solo departamento - usar su línea
                    dpto = departamentos[0]
                    if dpto['num'] == 1:
                        carpeta_base = f"{self.carpeta_base}/catalogo_automotriz"
                    else:
                        carpeta_base = f"{self.carpeta_base}/catalogo_ferretero"
                else:
                    # Múltiples departamentos - carpeta personalizada
                    carpeta_base = f"{self.carpeta_base}/catalogo_personalizado"
                    
                # Si es calidad impresión, usar subcarpeta print
                if self.calidad == "impresion":
                    carpeta_destino = os.path.join(carpeta_base, "print")
                else:
                    carpeta_destino = carpeta_base

                os.makedirs(carpeta_destino, exist_ok=True)
                
                if len(self.dptos) == 1:
                    nombre_final = f"catalogo_dptos_{self.dptos[0]}.pdf"
                else:
                    nombre_final = f"catalogo_dptos_{'_'.join(map(str, self.dptos))}.pdf"
                
                destino = os.path.join(carpeta_destino, nombre_final)
                
                print(f"🔴 DEBUG - Intentando guardar en: {destino}")
                print(f"   Archivo temporal existe: {os.path.exists(archivo_temporal)}")
                
                shutil.move(archivo_temporal, destino)
                print(f"📁 Archivo guardado en: {destino}")
                
                if os.path.exists(destino):
                    print(f"   ✅ Confirmado: {destino}")
                else:
                    print(f"   ❌ Error: No se pudo guardar en {destino}")
                
                return destino
            
            return None
        
        # ============================================
        # MODO 3: PRODUCTOS ESPECÍFICOS
        # ============================================
        elif self.productos:
            print(f"🚀 GENERANDO CATÁLOGO DE PRODUCTOS ESPECÍFICOS")
            print(f"{'='*60}")
            print(f"📋 Códigos: {len(self.productos)} productos")
            print(f"🎚️ Calidad: {self.calidad}")
            
            productos = self.obtener_productos_por_codigos(self.productos)
            print(f"📦 Productos encontrados: {len(productos)}")
            
            if not productos:
                print("❌ No se encontraron productos con esos códigos")
                return None
            
            paginas = self.organizar_productos_especiales(productos)
            total_paginas = len(paginas)
            print(f"📄 Total páginas: {total_paginas}")
            
            paginas_generadas = []
            pagina_global = 1
            
            for pagina in paginas:
                productos_pagina = [item for item in pagina if item['tipo'] == 'producto']
                archivo = await self.generar_pagina_productos_especificos(
                    productos_pagina,
                    pagina_global,
                    total_paginas
                )
                paginas_generadas.append(archivo)
                print(f"  Página {pagina_global}/{total_paginas} generada")
                pagina_global += 1
            
            merger = PyPDF2.PdfMerger()
            for archivo in paginas_generadas:
                merger.append(archivo)
            
            output_filename = os.path.join("self.temp_dir", "catalogo_productos_especiales.pdf")
            merger.write(output_filename)
            merger.close()
            
            carpeta_base = f"{self.carpeta_base}/catalogo_personalizado"
    
            # Si es calidad impresión, usar subcarpeta print
            if self.calidad == "impresion":
                carpeta_destino = os.path.join(carpeta_base, "print")
            else:
                carpeta_destino = carpeta_base

            os.makedirs(carpeta_destino, exist_ok=True)
            
            destino = os.path.join(carpeta_destino, "catalogo_productos.pdf")
            
            print(f"🔴 DEBUG - Intentando guardar en: {destino}")
            print(f"   Archivo temporal existe: {os.path.exists(output_filename)}")
            
            shutil.move(output_filename, destino)
            print(f"📁 Archivo guardado en: {destino}")
            
            if os.path.exists(destino):
                print(f"   ✅ Confirmado: {destino}")
            else:
                print(f"   ❌ Error: No se pudo guardar en {destino}")
            
            return destino
        
        else:
            print("❌ Modo no válido")
            return None
    
    def __del__(self):
        if hasattr(self, 'conn'):
            self.conn.close()

def main():
    parser = argparse.ArgumentParser(description='Generador de catálogos KET - Modos flexibles')
    
    # Grupo exclusivo
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument('--linea', type=str, choices=['A', 'F'], help='Línea completa (A o F)')
    group.add_argument('--dptos', type=str, help='Lista de IDs de departamentos separados por coma')
    group.add_argument('--productos', type=str, help='Lista de códigos de producto separados por coma')
    
    parser.add_argument('--limite', type=int, default=None, help='Límite de departamentos')
    parser.add_argument('--calidad', type=str, choices=['web', 'impresion'], default='web',
                       help='Calidad del PDF: web (comprimido) o impresion (máxima calidad)')
    
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
        base_url=base_url,
        calidad=args.calidad
    )
    
    asyncio.run(generador.generar_catalogo())

if __name__ == "__main__":
    main()