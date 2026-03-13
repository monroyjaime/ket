import asyncio
from playwright.async_api import async_playwright
import PyPDF2
import os
import time
import argparse
import psycopg2
from psycopg2.extras import RealDictCursor
from dataclasses import dataclass
from typing import List, Optional

@dataclass
class DepartamentoEstado:
    id: int
    nombre: str
    num: int  # 1: Automotriz, 2: Ferretero
    orden: int
    num_productos: int
    first_prod: int
    img_route: str

class GeneradorCatalogoConBD:
    def __init__(self, linea, conn_params, base_url, limite=None, carpeta_salida="/var/www/html/pdfs"):
        self.linea = linea
        self.linea_num = 1 if linea == 'A' else 2
        self.prefijo = 'A' if linea == 'A' else 'F'
        self.nombre_linea = 'Automotriz' if linea == 'A' else 'Ferretero'
        self.base_url = base_url  # Ahora usa indexDpto5X5Continuo.php
        self.limite = limite
        self.carpeta_salida = os.path.join(carpeta_salida, f"catalogo_{self.nombre_linea.lower()}")
        os.makedirs(self.carpeta_salida, exist_ok=True)
        
        # Conexión a PostgreSQL
        self.conn = psycopg2.connect(**conn_params, cursor_factory=RealDictCursor)
        self.conn.autocommit = False
    
    def obtener_departamentos_de_bd(self):
        """
        Obtiene los departamentos directamente de la BD
        Aplica límite si está definido ..q
        """
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
            
            # Aplicar límite si está definido
            if self.limite:
                query += " LIMIT %s"
                params.append(self.limite)
            
            cur.execute(query, params)
            
            resultados = cur.fetchall()
            
            # Mostrar información
            if self.limite:
                print(f"  📋 Modo PRUEBA: procesando {len(resultados)} de {self.limite} departamentos (límite={self.limite})")
            else:
                print(f"  📋 Modo COMPLETO: procesando {len(resultados)} departamentos")
            
            # Mostrar los primeros para verificar
            for r in resultados[:5]:
                print(f"    - Orden {r['orden']}: {r['nombre'][:40]}... (productos: {r['num_productos']})")
            
            if len(resultados) > 5:
                print(f"    ... y {len(resultados) - 5} más")
            
            return [DepartamentoEstado(**row) for row in resultados]
    
    def actualizar_first_prod(self, dpto_id, nuevo_first_prod):
        """Actualiza desde qué producto debe empezar un departamento"""
        with self.conn.cursor() as cur:
            cur.execute("""
                UPDATE departamentos 
                SET catalogo_first_prod = %s 
                WHERE id = %s
            """, (nuevo_first_prod, dpto_id))
            self.conn.commit()
    
    def marcar_procesado(self, dpto_id):
        """Marca un departamento como procesado"""
        with self.conn.cursor() as cur:
            cur.execute("""
                UPDATE departamentos 
                SET catalogo_procesado = true 
                WHERE id = %s
            """, (dpto_id,))
            self.conn.commit()
    
    def reset_estado(self):
        """Resetea el estado para una nueva generación"""
        print(f"  🔴 ENTRANDO A reset_estado() para {self.nombre_linea}")
    
        with self.conn.cursor() as cur:
            # Resetear solo los departamentos que vamos a procesar (según límite)
            if self.limite:
                print(f"     📊 Modo PRUEBA: resetear hasta orden {self.limite}")
                cur.execute("""
                    UPDATE departamentos 
                    SET catalogo_first_prod = 1,
                        catalogo_procesado = false
                    WHERE num = %s 
                      AND catalogo_orden > 0
                      AND catalogo_orden <= %s
                """, (self.linea_num, self.limite))
            else:
                print(f"     📊 Modo COMPLETO: resetear todos")
                cur.execute("""
                    UPDATE departamentos 
                    SET catalogo_first_prod = 1,
                        catalogo_procesado = false
                    WHERE num = %s AND catalogo_orden > 0
                """, (self.linea_num,))
            # Ver qué se actualizó
                resultados = cur.fetchall()
                print(f"     🔄 Filas actualizadas: {len(resultados)}")
                for r in resultados:
                    print(f"        - Orden {r['catalogo_orden']}: {r['name'][:30]}")
            
            self.conn.commit()

            print(f"  🟢 Saliendo de reset_estado()")
            modo = "PRUEBA" if self.limite else "COMPLETO"
            print(f"  🔄 Estado reseteado para {self.nombre_linea} (modo {modo})")
    
    async def obtener_escala_optima(self, dpto_id, num_pagina, first_prod):
        """Encuentra la escala óptima para una página"""
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()
            
            url = f"{self.base_url}?dpto_id={dpto_id}&page_num={num_pagina}&role_num=-1&first_prod={first_prod}"
            print(f"      📡 Cargando: {url}")
            await page.goto(url, wait_until="networkidle")
            
            escalas = [0.5, 0.45, 0.4, 0.35, 0.3]
            
            for escala in escalas:
                pdf_data = await page.pdf(
                    format="Letter",
                    scale=escala,
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
                
                if paginas == 1:
                    await browser.close()
                    return escala
            
            await browser.close()
            return 0.3
    
    async def contar_productos_pagina(self, dpto_id, num_pagina):
        """Cuenta cuántos productos tiene una página específica"""
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()
            
            url = f"{self.base_url}?dpto_id={dpto_id}&page_num={num_pagina}&role_num=-1"
            await page.goto(url, wait_until="networkidle")
            
            # 🔴 CORREGIDO: Contar TODAS las cards que son productos
            productos = await page.evaluate("""
                () => {
                    const cards = document.querySelectorAll('.card');
                    let count = 0;
                    cards.forEach(card => {
                        // Un producto tiene header con código y NO es el título especial
                        if (card.querySelector('.card-header h3')) {
                            // Verificar que no sea la card del título (si existe)
                            const header = card.querySelector('.card-header');
                            if (header && header.style.backgroundColor !== 'transparent') {
                                count++;
                            }
                        }
                    });
                    return count;
                }
            """)
            print("    🔍 DEBUG - Productos contados:", productos)
            await browser.close()
            return productos
    
    async def generar_pagina(self, dpto_id, num_pagina, escala, first_prod):
        """Genera una página PDF"""
        archivo = os.path.join(self.carpeta_salida, f"temp_{dpto_id}_{num_pagina}.pdf")
        
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()
            
            url = f"{self.base_url}?dpto_id={dpto_id}&page_num={num_pagina}&role_num=-1&first_prod={first_prod}"
            print(f"      📡 Cargando: {url}")
            
            await page.goto(url, wait_until="networkidle", timeout=30000)
            
            await page.pdf(
                path=archivo,
                format="Letter",
                scale=escala,
                print_background=True,
                margin={"top": "10mm", "bottom": "10mm", "left": "10mm", "right": "10mm"}
            )
            
            await browser.close()
        
        return archivo
    
    def calcular_paginas_departamento(self, num_productos, first_prod):
        """
        Calcula cuántas páginas necesita un departamento
        considerando desde qué producto empezar
        """
        productos_restantes = num_productos - (first_prod - 1)
        
        if productos_restantes <= 0:
            return 0
        
        if first_prod == 1:
            # Primera página: título + 20 productos
            if productos_restantes <= 20:
                return 1
            else:
                # 1 (primera) + páginas de 25 productos
                return 1 + ((productos_restantes - 20 + 24) // 25)
        else:
            # Ya no lleva título, todas las páginas de 25
            return (productos_restantes + 24) // 25
    
    async def generar_catalogo(self):
        print(f"\n{'='*60}")
        print(f"🚀 GENERANDO CATÁLOGO {self.nombre_linea.upper()} (MODO {'PRUEBA' if self.limite else 'COMPLETO'})")
        print(f"{'='*60}")
        print(f"📁 Usando: {self.base_url}")
        
        # 🔴 PRIMERO: Resetear estado
        self.reset_estado()
        
        # Luego obtener departamentos
        print(f"\n📡 Leyendo departamentos de BD...")
        departamentos = self.obtener_departamentos_de_bd()
        
        if not departamentos:
            print(f"❌ No hay departamentos para la línea {self.nombre_linea}")
            return
        
        print(f"\n📋 Total departamentos a procesar: {len(departamentos)}")
        
        paginas_generadas = []
        dpto_idx = 0
        total_productos = 0
        total_paginas_estimadas = 0
        
        while dpto_idx < len(departamentos):
            dpto = departamentos[dpto_idx]
            print(f"\n📦 [{dpto_idx + 1}/{len(departamentos)}] Procesando {dpto.nombre} (ID: {dpto.id})")
            print(f"  📊 Productos totales: {dpto.num_productos}")
            print(f"  🎯 Empezando desde producto: {dpto.first_prod}")
            
            total_productos += dpto.num_productos
            
            # Calcular páginas necesarias
            paginas_necesarias = self.calcular_paginas_departamento(
                dpto.num_productos, dpto.first_prod
            )
            total_paginas_estimadas += paginas_necesarias
            
            if paginas_necesarias == 0:
                print(f"  ⏭️  Departamento ya completado en página anterior")
                dpto_idx += 1
                continue
            
            print(f"  📄 Páginas necesarias: {paginas_necesarias}")
            
            # Generar cada página
            for num_pag in range(1, paginas_necesarias + 1):
                # Obtener escala óptima
                escala = await self.obtener_escala_optima(dpto.id, num_pag)
                
                # Generar página
                archivo = await self.generar_pagina(dpto.id, num_pag, escala, dpto.first_prod)
                paginas_generadas.append(archivo)
                
                print(f"    Página {num_pag}/{paginas_necesarias} generada (escala: {escala:.2f})")
                
                # Si es la última página, verificar espacio para siguiente departamento
                if num_pag == paginas_necesarias and dpto_idx < len(departamentos) - 1:
                    productos_en_pagina = await self.contar_productos_pagina(dpto.id, num_pag)
                    print(f"    📊 Productos en última página: {productos_en_pagina}/25")
                    
                    # Si hay espacio para al menos 1 fila (menos de 20 productos)
                    if productos_en_pagina <= 20:
                        siguiente_dpto = departamentos[dpto_idx + 1]
                        espacio_libre = 25 - productos_en_pagina
                        # Calcular filas ocupadas en la última página
                        filas_ocupadas = productos_en_pagina // 5
                        print(f"    🧮 filas_ocupadas = {productos_en_pagina} // 5 = {filas_ocupadas}")
                        if filas_ocupadas >= 4:  # Ya hay 4 o más filas, no cabe título completo
                            nuevo_first_prod = 1
                            print(f"    ✅ CASO A: filas_ocupadas >= 4 → nuevo_first_prod = 1")
                            print(f"    ✨ No cabe título completo, {siguiente_dpto.nombre} empezará con título en nueva página")
                        else:
                            # Cabe el título + algunas filas
                            filas_disponibles = 4 - filas_ocupadas
                            productos_que_caben = filas_disponibles * 5
                            nuevo_first_prod = productos_que_caben + 1
                            print(f"    ✅ CASO B: filas_disponibles = {filas_disponibles}")
                            print(f"               productos_que_caben = {productos_que_caben}")
                            print(f"               nuevo_first_prod = {productos_que_caben} + 1 = {nuevo_first_prod}")
                        print(f"    🎯 VALOR FINAL: nuevo_first_prod = {nuevo_first_prod}")
                        print(f"    ✨ Espacio libre: {espacio_libre} productos")
                        print(f"    ➡️  {siguiente_dpto.nombre} empezará sin título")
                        
                        
                        # El siguiente departamento empezará sin título
                        self.actualizar_first_prod(siguiente_dpto.id, nuevo_first_prod)
                        
                        # Actualizar el objeto en memoria
                        departamentos[dpto_idx + 1].first_prod = nuevo_first_prod
            
            # Marcar como procesado
            self.marcar_procesado(dpto.id)
            dpto_idx += 1
        
        if not paginas_generadas:
            print("❌ No se generó ninguna página")
            return
        
        # Combinar todas las páginas
        print(f"\n📚 Combinando {len(paginas_generadas)} páginas...")
        
        merger = PyPDF2.PdfMerger()
        for archivo in paginas_generadas:
            merger.append(archivo)
        
        # DESPUÉS (sin timestamp, nombre fijo)
        modo_sufijo = f"prueba_{self.limite}" if self.limite else "completo"
        output_filename = os.path.join(
            self.carpeta_salida, 
            f"catalogo_{self.nombre_linea.lower()}_{modo_sufijo}.pdf"  # Sin timestamp
        )

        # Si el archivo ya existe, lo sobrescribiremos
        print(f"📁 Generando: {output_filename}")
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
        print(f"📊 Páginas estimadas: {total_paginas_estimadas}")
        
        # Mostrar resumen de fusión
        with self.conn.cursor() as cur:
            cur.execute("""
                SELECT id, name as nombre, catalogo_first_prod 
                FROM departamentos 
                WHERE num = %s AND catalogo_first_prod > 1
                ORDER BY catalogo_orden
            """, (self.linea_num,))
            fusionados = cur.fetchall()
            
            if fusionados:
                print(f"\n🔗 Departamentos que empezaron sin título:")
                for d in fusionados[:10]:
                    print(f"  - {d['nombre'][:40]}: empezó desde producto {d['catalogo_first_prod']}")
                if len(fusionados) > 10:
                    print(f"  ... y {len(fusionados) - 10} más")
        
        return output_filename
    
    def __del__(self):
        """Cerrar conexión a BD al finalizar"""
        if hasattr(self, 'conn'):
            self.conn.close()

def main():
    parser = argparse.ArgumentParser(description='Generador de catálogos KET con BD')
    parser.add_argument('--linea', type=str, choices=['A', 'F'], required=True,
                       help='A para Automotriz, F para Ferretero')
    parser.add_argument('--limite', type=int, default=None,
                       help='Límite de departamentos a procesar (para pruebas)')
    
    args = parser.parse_args()
    
    # Configuración de conexión a PostgreSQL - ¡ACTUALIZAR!
    conn_params = {
        'host': 'localhost',
        'port': 5432,
        'database': 'ketdb',
        'user': 'ketadmin',
        'password': 'LondonTown'
    }
    
    # URL CORREGIDA - Usando la versión Continuo
    base_url = "https://ketelectropartes.com/catalogo/indexDpto5X5Continuo.php"
    
    print(f"\n{'='*60}")
    print(f"🔧 CONFIGURACIÓN")
    print(f"{'='*60}")
    print(f"📁 Línea: {args.linea} ({'Automotriz' if args.linea == 'A' else 'Ferretero'})")
    print(f"📁 Modo: {'PRUEBA' if args.limite else 'COMPLETO'}")
    if args.limite:
        print(f"📁 Límite: {args.limite} departamentos")
    print(f"📁 Base de datos: {conn_params['database']}@{conn_params['host']}")
    print(f"📁 URL base: {base_url}")
    
    try:
        generador = GeneradorCatalogoConBD(
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