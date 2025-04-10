import pandas as pd

def ordenar_y_exportar_csv(archivo_entrada, archivo_salida, columna_orden):
    """
    Lee un archivo CSV, lo ordena según la columna especificada y lo exporta a un nuevo archivo CSV.
    
    Args:
        archivo_entrada (str): Ruta del archivo CSV de entrada.
        archivo_salida (str): Ruta del archivo CSV de salida ordenado.
        columna_orden (str): Nombre de la columna por la cual ordenar.
    """
    try:
        # Leer el archivo CSV
        df = pd.read_csv(archivo_entrada, sep=';', encoding='utf-8')
        
        # Verificar si la columna de orden existe en el DataFrame
        if columna_orden not in df.columns:
            print(f"Error: La columna '{columna_orden}' no existe en el archivo CSV.")
            return
        
        # Ordenar el DataFrame por la columna especificada
        df_ordenado = df.sort_values(by=columna_orden)
        
        # Exportar el DataFrame ordenado a un nuevo archivo CSV
        df_ordenado.to_csv(archivo_salida, index=False)
        
        print(f"Archivo ordenado exportado exitosamente a '{archivo_salida}'.")
    
    except FileNotFoundError:
        print(f"Error: No se encontró el archivo '{archivo_entrada}'.")
    except Exception as e:
        print(f"Ocurrió un error: {e}")

# Ejemplo de uso
if __name__ == "__main__":
    # Configuración de rutas y columna de orden
    archivo_entrada = "C:/Users/USER/Pictures/22178-2.csv"  # Cambia esto por la ruta de tu archivo CSV
    archivo_salida = "datos_ordenados.csv"
    columna_orden = "id,cedula,beneficiario,telefono,codigo_obra,comunidad,parroquia,municipio,direccion_exacta,utm_norte,utm_este,metodo_constructivo,modelo_constructivo,fiscalizador,fecha_actualizacion,acondicionamiento,limpieza,replanteo,excavacion,acero_vigas_riostra,encofrado_malla,instalaciones_electricas_sanitarias,vaciado_losa_anclajes,armado_columnas,vaciado_columnas,armado_vigas,vaciado_vigas,bloqueado,colocacion_correas,colocacion_techo,colocacion_ventanas,colocacion_puertas_principales,instalaciones_electricas_sanitarias_paredes,frisos,sobrepiso,ceramica_bano,colocacion_puertas_internas,equipos_accesorios_electricos,equipos_accesorios_sanitarios,colocacion_lavaplatos,pintura,avance_fisico,fecha_culminacion,acta_entregada,observaciones_responsables,observaciones_fiscalizadores"  # Cambia esto por la columna que deseas usar para ordenar
    
    # Llamar a la función
    ordenar_y_exportar_csv(archivo_entrada, archivo_salida, columna_orden)