import pandas as pd

# Leer el archivo CSV
df = pd.read_csv('C:/Users/USER/Pictures/TORRES(ESPINOZA DE LOS MONTEROS).csv', sep=';', header=None, names=['Comunidad'])

# Eliminar filas vacías y limpiar espacios en blanco
df = df.dropna()  # Eliminar filas vacías
df['Comunidad'] = df['Comunidad'].str.strip()  # Eliminar espacios en blanco

# Eliminar duplicados manteniendo la primera ocurrencia
df_sin_duplicados = df.drop_duplicates()

# Ordenar alfabéticamente (opcional)
df_sin_duplicados = df_sin_duplicados.sort_values(by='Comunidad')

# Exportar a nuevo archivo CSV
df_sin_duplicados.to_csv('TORRES(ESPINOZA DE LOS MONTEROS).csv', index=False, sep=';')

print(f"Proceso completado. Se eliminaron {len(df) - len(df_sin_duplicados)} registros duplicados.")
print(f"Archivo guardado como 'df_sin_duplicados.to_csv()' con {len(df_sin_duplicados)} registros únicos.")