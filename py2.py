import pandas as pd
import os

def cargar_y_verificar_csv(archivo_entrada):
    """
    Función para cargar un archivo CSV, verificar sus columnas y adaptarlas a los nombres esperados
    """
    try:
        # 1. Leer el archivo como texto y limpiar las filas
        with open(archivo_entrada, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        
        # Limpiar las filas: eliminar el primer ; si existe
        cleaned_lines = []
        for i, line in enumerate(lines):
            if i == 0:  # Mantener los encabezados sin cambios
                cleaned_lines.append(line)
            else:
                if line.startswith(';'):
                    cleaned_lines.append(line[1:])  # Eliminar el primer ;
                else:
                    cleaned_lines.append(line)
        
        # Crear un archivo temporal limpio
        temp_file = 'temp_cleaned.csv'
        with open(temp_file, 'w', encoding='utf-8') as f:
            f.writelines(cleaned_lines)
        
        # 2. Primera lectura solo para ver las columnas disponibles
        print("\n=== Verificando estructura del CSV ===")
        df_prueba = pd.read_csv(temp_file, nrows=0, sep=';', encoding='utf-8')
        print("Columnas encontradas en el CSV:")
        print(df_prueba.columns.tolist())
        
        # 3. Cargar el archivo completo con el separador detectado
        print("\n=== Cargando archivo completo ===")
        df = pd.read_csv(temp_file, sep=';', encoding='utf-8', low_memory=False)
        
        # Eliminar el archivo temporal
        os.remove(temp_file)
        
        # 4. Mapear nombres de columnas a los nombres deseados
        print("\n=== Ajustando nombres de columnas ===")
        df = renombrar_columnas(df)
        
        # 5. Verificar columnas faltantes
        print("\n=== Verificando columnas requeridas ===")
        df = verificar_columnas_faltantes(df)
        
        print("\n=== Proceso completado con éxito ===")
        return df
        
    except Exception as e:
        print(f"\nError inicial: {str(e)}")
        print("Intentando con codificación latin1 como alternativa...")
        
        try:
            # Reintentar con codificación latin1
            with open(archivo_entrada, 'r', encoding='latin1') as f:
                lines = f.readlines()
            
            cleaned_lines = []
            for i, line in enumerate(lines):
                if i == 0:
                    cleaned_lines.append(line)
                else:
                    if line.startswith(';'):
                        cleaned_lines.append(line[1:])
                    else:
                        cleaned_lines.append(line)
            
            temp_file = 'temp_cleaned.csv'
            with open(temp_file, 'w', encoding='latin1') as f:
                f.writelines(cleaned_lines)
            
            df_prueba = pd.read_csv(temp_file, nrows=0, sep=';', encoding='latin1')
            df = pd.read_csv(temp_file, sep=';', encoding='latin1', low_memory=False)
            os.remove(temp_file)
            
            df = renombrar_columnas(df)
            df = verificar_columnas_faltantes(df)
            return df
        except Exception as e2:
            print(f"Error persistente: {str(e2)}")
            if os.path.exists(temp_file):
                os.remove(temp_file)
            raise Exception("No se pudo cargar el archivo con ninguna codificación estándar")
        
def renombrar_columnas(df):
    """
    Renombra las columnas del DataFrame según un mapeo predefinido
    """
    # Diccionario de mapeo: {'nombre_en_csv': 'nombre_deseado'}
    column_mapping = {   
        'Id': 'id',
        'Cédula': 'cedula',
        'Beneficiario': 'beneficiario',
        'Teléfono': 'telefono',
        'Código de Obra': 'codigo_obra',
        'Comunidad': 'comunidad',
        'Parroquia': 'parroquia',
        'Municipio': 'municipio',
        'Direccion_exacta': 'direccion_exacta',
        'Utm_norte': 'utm_norte',
        'Utm_este': 'utm_este',
        'Metodo_constructivo': 'metodo_constructivo',
        'Modelo_constructivo': 'modelo_constructivo',
        'Fiscalizador': 'fiscalizador',
        'Fecha_actualizacion': 'fecha_actualizacion',
        'ACONDICIONAMIENTO': 'acondicionamiento',
        'Limpieza': 'limpieza',
        'Replanteo': 'replanteo',
        'FUNDACION': 'fundacion',
        'Excavacion': 'excavacion',
        'Acero_en_vigas_de_riostra': 'acero_en_vigas_de_riostra',
        'Encofrado_y_colocacion_de_malla': 'encofrado_y_colocacion_de_malla',
        'Instalaciones_electricas_y_sanitarias': 'instalaciones_electricas_y_sanitarias',
        'Vaciado_de_losa_y_colocacion_de_anclajes': 'vaciado_de_losa_y_colocacion_de_anclajes',
        'ESTRUCTURA': 'estructura',
        'Armado_de_columnas': 'armado_de_columnas',
        'Vaciado_de_columnas': 'vaciado_de_columnas',
        'Armado_de_vigas': 'armado_de_vigas',
        'Vaciado_de_vigas': 'vaciado_de_vigas',
        'CERRAMIENTO': 'cerramiento',
        'Bloqueado': 'bloqueado',
        'Colocacion_de_correas': 'colocacion_de_correas',
        'Colocacion_de_techo': 'colocacion_de_techo',
        'ACABADO': 'acabado',
        'Colocacion_de_ventanas': 'colocacion_de_ventanas',
        'Colocacion_de_puertas_pricipales': 'colocacion_de_puertas_pricipales',
        'Instalaciones_electricas_y_sanitarias_en_paredes': 'instalaciones_electricas_y_sanitarias_en_paredes',
        'Frisos': 'frisos',
        'Sobre-piso': 'sobre-piso',
        'Ceramica_en_bano': 'ceramica_en_bano',
        'Colocacion_de_puertas_internas': 'colocacion_de_puertas_internas',
        'Equipos_y_acc_elctricos': 'equipos_y_acc_elctricos',
        'Equipos_y_acc_sanitarios': 'equipos_y_acc_sanitarios',
        'Colocacion_de_lavaplatos': 'colocacion_de_lavaplatos',
        'Pintura': 'pintura',
        'AVANCE_FISICO': 'avance_fisico',
        'FECHA_DE_CULMINACION': 'fecha_de_culminacion',
        'Acta_entregada?': 'acta_entregada',
        'Observaciones_responsables_control_y_seguimiento': 'observaciones_responsables_control_y_seguimiento',
        'Observaciones_de_fiscalizadores': 'observaciones_de_fiscalizadores',
        # Añade todos los mapeos necesarios
    }
    
    # Renombrar las columnas que existan en el DataFrame
    for original, nuevo in column_mapping.items():
        if original in df.columns:
            df = df.rename(columns={original: nuevo})
    
    return df

def verificar_columnas_faltantes(df):
    """
    Verifica si faltan columnas importantes y las crea vacías si es necesario
    """
    expected_columns = [
        'id','cedula', 'beneficiario', 'telefono', 'codigo_obra', 
        'comunidad', 'parroquia', 'municipio', 'direccion_exacta',
        'utm_norte', 'utm_este', 'metodo_constructivo', 'modelo_constructivo',
        'fiscalizador', 'fecha_actualizacion', 'acondicionamiento', 'limpieza',
        'replanteo', 'fundacion', 'excavacion', 'acero_en_vigas_de_riostra',
        'encofrado_y_colocacion_de_malla', 'instalaciones_electricas_y_sanitarias',
        'vaciado_de_losa_y_colocacion_de_anclajes', 'estructura', 'armado_de_columnas',
        'vaciado_de_columnas', 'armado_de_vigas', 'vaciado_de_vigas', 'cerramiento',
        'bloqueado', 'colocacion_de_correas', 'colocacion_de_techo', 'acabado',
        'colocacion_de_ventanas', 'colocacion_de_puertas_pricipales',
        'instalaciones_electricas_y_sanitarias_en_paredes', 'frisos', 'sobrepiso',
        'ceramica_en_bano', 'colocacion_de_puertas_internas', 'equipos_y_acc_elctricos',
        'equipos_y_acc_sanitarios', 'colocacion_de_lavaplatos', 'pintura', 'avance_fisico',
        'fecha_de_culminacion', 'acta_entregada', 'observaciones_responsables_control_y_seguimiento',
        'observaciones_de_fiscalizadores'
        # Añade todas las columnas requeridas
    ]
    
    # Nombres alternativos para cada columna esperada
    alt_names = {
        'cedula': ['Cédula', 'CEDULA', 'Documento', 'CI'],
        'beneficiario': ['Beneficiario', 'Nombre', 'BENEFICIARIO', 'NOMBRE'],
        'telefono': ['Teléfono', 'TELEFONO', 'Celular', 'Contacto'],
        'codigo_obra': ['Código de Obra:', 'Código de Obra', 'Código de obra', 'Código de obra:'],
        'comunidad': ['Comunidad', 'Comunidad:', 'Comunidad de obra', 'Comunidad de obra:'],
        'parroquia': ['Parroquia', 'Parroquia:', 'Parroquia de obra', 'Parroquia de obra:'],
        'municipio': ['Municipio', 'Municipio:', 'Municipio de obra', 'Municipio de obra:'],
        'direccion_exacta': ['Dirección Exacta', 'Dirección Exacta:', 'Dirección exacta', 'Dirección exacta:'],
        'utm_norte': ['UTM Norte', 'UTM Norte:', 'UTM Norte:', 'UTM Norte'],
        'utm_este': ['UTM Este', 'UTM Este:', 'UTM Este:', 'UTM Este'],
        'metodo_constructivo': ['Metodo Constructivo', 'Metodo Constructivo:', 'Metodo de construcción', 'Metodo de construcción:'],
        'modelo_constructivo': ['Modelo Constructivo', 'Modelo Constructivo:', 'Modelo de construcción', 'Modelo de construcción:'],
        'fiscalizador': ['Fiscalizador', 'Fiscalizador:', 'Fiscalizador:', 'Fiscalizador'],
        'fecha_actualizacion': ['Fecha Actualización', 'Fecha Actualización:', 'Fecha de actualización', 'Fecha de actualización:'],
        'acondicionamiento': ['ACONDICIONAMIENTO', 'ACONDICIONAMIENTO:', 'ACONDICIONAMIENTO:', 'ACONDICIONAMIENTO'],
        'limpieza': ['Limpieza', 'Limpieza:', 'Limpieza:', 'Limpieza'],
        'replanteo': ['Replanteo', 'Replanteo:', 'Replanteo:', 'Replanteo'],
        'fundacion': ['Fundación', 'Fundación:', 'Fundación:', 'Fundación'],
        'excavacion': ['Excavacion', 'Excavacion:', 'Excavación:', 'Excavación'],
        'acero_en_vigas_de_riostra': ['Acero En Vigas De Riostra', 'Acero En Vigas De Riostra:', 'Acero en vigas de riostra', 'Acero en vigas de riostra:'],
        'encofrado_y_colocacion_de_malla': ['Encofrado Y Colocacion De Malla', 'Encofrado Y Colocacion De Malla:', 'Encofrado y colocación de malla', 'Encofrado y colocación de malla:'],
        'instalaciones_electricas_y_sanitarias': ['Instalaciones Electricas Y Sanitarias', 'Instalaciones Electricas Y Sanitarias:', 'Instalaciones electricas y sanitarias', 'Instalaciones electricas y sanitarias:'],
        'vaciado_de_losa_y_colocacion_de_anclajes': ['Vaciado De Losa Y Colocacion De Anclajes', 'Vaciado De Losa Y Colocacion De Anclajes:', 'Vaciado de losa y colocación de anclajes', 'Vaciado de losa y colocación de anclajes:'],
        'estructura': ['ESTRUCTURA', 'ESTRUCTURA:', 'ESTRUCTURA:', 'ESTRUCTURA'],
        'armado_de_columnas': ['Armado De Columnas', 'Armado De Columnas:', 'Armado de columnas', 'Armado de columnas:'],
        'vaciado_de_columnas': ['Vaciado De Columnas', 'Vaciado De Columnas:', 'Vaciado de columnas', 'Vaciado de columnas:'],
        'armado_de_vigas': ['Armado De Vigas', 'Armado De Vigas:', 'Armado de vigas', 'Armado de vigas:'],
        'vaciado_de_vigas': ['Vaciado De Vigas', 'Vaciado De Vigas:', 'Vaciado de vigas', 'Vaciado de vigas:'],
        'ceramica_en_bano': ['Ceramica en Baño', 'Ceramica en Baño:', 'Ceramica en baño', 'Ceramica en baño:'],
        'colocacion_de_puertas_internas': ['Colocacion de Puertas Internas', 'Colocacion de Puertas Internas:', 'Colocación de puertas internas', 'Colocación de puertas internas:'],
        'equipos_y_acc_electricos': ['Equipos y Acc Eléctricos', 'Equipos y Acc Eléctricos:', 'Equipos y acc eléctricos', 'Equipos y acc eléctricos:'],
        'equipos_y_acc_sanitarios': ['Equipos y Acc Sanitarios', 'Equipos y Acc Sanitarios:', 'Equipos y acc sanitarios', 'Equipos y acc sanitarios:'],
        'colocacion_de_lavaplatos': ['Colocacion de Lavaplatos', 'Colocacion de Lavaplatos:', 'Colocación de lavaplatos', 'Colocación de lavaplatos:'],
        'pintura': ['Pintura', 'Pintura:', 'Pintura:', 'Pintura:'],
        'avance_fisico': ['AVANCE FISICO', 'AVANCE FISICO:', 'AVANCE FISICO:', 'AVANCE FISICO:'],
        'fecha_de_culminacion': ['FECHA DE CULMINACION', 'FECHA DE CULMINACION:', 'FECHA DE CULMINACION:', 'FECHA DE CULMINACION:'],
        'acta_entregada': ['Acta entregada?', 'Acta entregada?:', 'Acta entregada?:', 'Acta entregada?:'],
        'Observaciones_Responsables_Control_y_Seguimiento': ['Observaciones Responsables Control y Seguimiento', 'Observaciones Responsables Control y Seguimiento:', 'Observaciones responsables control y seguimiento', 'Observaciones responsables control y seguimiento:'],
        'observaciones_de_fiscalizadores': ['Observaciones de Fiscalizadores', 'Observaciones de Fiscalizadores:', 'Observaciones de fiscalizadores', 'Observaciones de fiscalizadores:'],
        # Añade otros nombres alternativos para cada columna
    }
    
    # Verificar columnas faltantes
    missing_columns = [col for col in expected_columns if col not in df.columns]
    
    if missing_columns:
        print(f"Columnas faltantes iniciales: {missing_columns}")
        
        # Intentar encontrar columnas con nombres alternativos
        for col in missing_columns[:]:  # Usamos [:] para hacer una copia
            if col in alt_names:
                for alt in alt_names[col]:
                    if alt in df.columns:
                        df = df.rename(columns={alt: col})
                        missing_columns.remove(col)
                        print(f"Columna '{alt}' renombrada a '{col}'")
                        break
    
    # Crear columnas faltantes vacías si es necesario
    for col in missing_columns:
        df[col] = None
        print(f"Columna '{col}' creada con valores nulos")
    
    return df

def exportar_a_csv(df, ruta_salida=None):
    """
    Exporta el DataFrame a un archivo CSV con formato estandarizado
    
    Args:
        df: DataFrame de pandas a exportar
        ruta_salida: Ruta donde guardar el archivo (opcional)
    
    Returns:
        Ruta del archivo guardado
    """
    if ruta_salida is None:
        # Crear nombre de archivo automático si no se especifica
        carpeta = os.path.dirname(os.path.abspath(__file__))
        nombre_base = "datos_estandarizados"
        contador = 1
        ruta_salida = os.path.join(carpeta, f"{nombre_base}.csv")
        
        while os.path.exists(ruta_salida):
            ruta_salida = os.path.join(carpeta, f"{nombre_base}_{contador}.csv")
            contador += 1
    
    try:
        # Exportar con configuración óptima
        df.to_csv(ruta_salida, index=False, sep=';', encoding='utf-8', date_format='%Y-%m-%d')
        print(f"\nArchivo exportado exitosamente a: {ruta_salida}")
        return ruta_salida
    except Exception as e:
        print(f"\nError al exportar el archivo: {str(e)}")
        # Intentar con otra codificación si falla
        try:
            df.to_csv(ruta_salida, index=False, sep=';', encoding='latin1', date_format='%Y-%m-%d')
            print(f"Archivo exportado con codificación latin1 a: {ruta_salida}")
            return ruta_salida
        except Exception as e2:
            print(f"Error persistente al exportar: {str(e2)}")
            raise

# Uso de la función principal
if __name__ == "__main__":
    archivo_entrada = 'C:/Users/USER/Pictures/22178.csv'  # Cambia por tu ruta de archivo
    
    try:
        # Cargar y procesar el archivo
        df_final = cargar_y_verificar_csv(archivo_entrada)
        
        # Mostrar resultados
        print("\n=== Primeras filas del DataFrame ===")
        print(df_final.head())
        
        print("\n=== Columnas finales ===")
        print(df_final.columns.tolist())
        
        print("\n=== Tipos de datos ===")
        print(df_final.dtypes)
        
        # Exportar el archivo procesado
        ruta_exportacion = os.path.join(os.path.dirname(archivo_entrada), "datos_procesados_55.csv")
        exportar_a_csv(df_final, ruta_exportacion)
        
    except Exception as e:
        print(f"\nError durante el proceso: {str(e)}")