<?php
require_once 'conexion.php';

// Validar y sanitizar datos
$nombre = mysqli_real_escape_string($conexion, $_POST['nombre'] ?? '');
$cedula = mysqli_real_escape_string($conexion, $_POST['cedula'] ?? '');
$telefono = mysqli_real_escape_string($conexion, $_POST['telefono'] ?? '');
$codigo_obra = mysqli_real_escape_string($conexion, $_POST['codigo_obra'] ?? '');
$comunidad = mysqli_real_escape_string($conexion, $_POST['comunidad'] ?? '');
$status = mysqli_real_escape_string($conexion, $_POST['status'] ?? 'activo');

// Iniciar transacción
mysqli_begin_transaction($conexion);

try {
    // Insertar en beneficiarios
    $query_beneficiario = "INSERT INTO beneficiarios 
        (nombre_beneficiario, cedula, telefono, codigo_obra, status) 
        VALUES ('$nombre', '$cedula', '$telefono', '$codigo_obra', '$status')";
    
    $result_beneficiario = mysqli_query($conexion, $query_beneficiario);
    
    // Get the last inserted ID
    $last_id = mysqli_insert_id($conexion);

    // Verificar columnas en la tabla ubicaciones
    $columns_check = mysqli_query($conexion, "SHOW COLUMNS FROM ubicaciones");
    $columns = [];
    while ($column = mysqli_fetch_assoc($columns_check)) {
        $columns[] = $column['Field'];
    }

    // Preparar consulta de ubicación basada en columnas existentes
    $ubicacion_columns = [];
    $ubicacion_values = [];

    if (in_array('comunidad', $columns)) {
        $ubicacion_columns[] = 'comunidad';
        $ubicacion_values[] = "'$comunidad'";
    }

    if (in_array('beneficiario_id', $columns)) {
        $ubicacion_columns[] = 'beneficiario_id';
        $ubicacion_values[] = $last_id;
    } elseif (in_array('id_beneficiario', $columns)) {
        $ubicacion_columns[] = 'id_beneficiario';
        $ubicacion_values[] = $last_id;
    }

    if (in_array('status', $columns)) {
        $ubicacion_columns[] = 'status';
        $ubicacion_values[] = "'$status'";
    }

    // Construir consulta dinámica si hay columnas
    if (!empty($ubicacion_columns)) {
        $columns_str = implode(', ', $ubicacion_columns);
        $values_str = implode(', ', $ubicacion_values);
        
        $query_ubicacion = "INSERT INTO ubicaciones 
            ($columns_str) 
            VALUES ($values_str)";
        
        $result_ubicacion = mysqli_query($conexion, $query_ubicacion);
    } else {
        // Si no hay columnas compatibles, considerar la inserción exitosa
        $result_ubicacion = true;
    }

    // Confirmar transacción si todo está bien
    if ($result_beneficiario && $result_ubicacion) {
        mysqli_commit($conexion);
        echo 'ok';
    } else {
        mysqli_rollback($conexion);
        echo 'Error al insertar datos: ' . mysqli_error($conexion);
    }
} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo 'Error: ' . $e->getMessage();
}

mysqli_close($conexion);
?>