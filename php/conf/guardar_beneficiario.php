<?php
require_once '../conf/conexion.php';

// Inicializar variables para manejar errores
$status = 'error';
$message = 'Error desconocido';
$beneficiario = null;
$ubicacion = null;

try {
    // Validar y sanitizar datos
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre'] ?? '');
    $cedula = mysqli_real_escape_string($conexion, $_POST['cedula'] ?? '');
    $telefono = mysqli_real_escape_string($conexion, $_POST['telefono'] ?? '');
    $codigo_obra = mysqli_real_escape_string($conexion, $_POST['codigo_obra'] ?? '');
    $status_beneficiario = mysqli_real_escape_string($conexion, $_POST['status'] ?? 'activo');

    // Iniciar transacción
    mysqli_begin_transaction($conexion);

    // Insertar beneficiario
    $query_beneficiario = "INSERT INTO beneficiarios 
    (cedula, nombre_beneficiario, telefono, codigo_obra, status, fecha_actualizacion) 
    VALUES (?, ?, ?, ?, ?, CURRENT_DATE())";

    $stmt = $conexion->prepare($query_beneficiario);
    
    $stmt->bind_param(
        "sssss", 
        $cedula,
        $nombre, 
        $telefono, 
        $codigo_obra, 
        $status_beneficiario
    );
    
    $result_beneficiario = $stmt->execute();
    
    if (!$result_beneficiario) {
        throw new Exception("Error al insertar beneficiario: " . $stmt->error);
    }

    // Obtener el ID del beneficiario recién insertado
    $beneficiario_id = $conexion->insert_id;

    // Inicializar variables de ubicación
    $ubicacion = [
        'municipio' => 'No especificado',
        'parroquia' => 'No especificado',
        'comunidad' => 'No especificado',
        'direccion_exacta' => 'No especificado',
        'nombre_estado' => 'No especificado'
    ];

    // Verificar si se proporcionaron municipio y parroquia
    if (!empty($_POST['municipio']) && !empty($_POST['parroquia'])) {
        $municipio_id = intval($_POST['municipio']);
        $parroquia_id = intval($_POST['parroquia']);

        // Obtener nombres de municipio y parroquia desde la vista
        $query_ubicacion_detalle = "SELECT 
        COALESCE(municipio, 'No especificado') AS municipio, 
        COALESCE(parroquia, 'No especificado') AS parroquia,
        COALESCE(comunidad, 'No especificado') AS comunidad,
        COALESCE(direccion_exacta, 'No especificado') AS direccion_exacta,
        COALESCE(nombre_estado, 'No especificado') AS nombre_estado
        FROM vista_ubicaciones 
        WHERE id_municipio = ? AND id_parroquia = ?";

        $stmt_ubicacion_detalle = $conexion->prepare($query_ubicacion_detalle);
        $stmt_ubicacion_detalle->bind_param(
            "ii", 
            $municipio_id, 
            $parroquia_id
        );
        $stmt_ubicacion_detalle->execute();
        $result_ubicacion_detalle = $stmt_ubicacion_detalle->get_result();
        $ubicacion_temp = $result_ubicacion_detalle->fetch_assoc();

        if ($ubicacion_temp) {
            $ubicacion = $ubicacion_temp;
        }
    }

    // Confirmar transacción
    mysqli_commit($conexion);

    // Obtener detalles del beneficiario
    $query_detalle = "SELECT * FROM beneficiarios WHERE id_beneficiario = ?";
    
    $stmt_detalle = $conexion->prepare($query_detalle);
    $stmt_detalle->bind_param("i", $beneficiario_id);
    $stmt_detalle->execute();
    $result_detalle = $stmt_detalle->get_result();
    $beneficiario = $result_detalle->fetch_assoc();

    // Establecer estado de éxito
    $status = 'ok';
    $message = 'Beneficiario agregado exitosamente';

} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($conexion);
    $message = $e->getMessage();
} finally {
    // Cerrar conexión
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_ubicacion_detalle)) $stmt_ubicacion_detalle->close();
    if (isset($stmt_detalle)) $stmt_detalle->close();
    mysqli_close($conexion);

    // Devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'beneficiario' => $beneficiario,
        'ubicacion' => $ubicacion
    ], JSON_PRETTY_PRINT);
    exit;
}
?>