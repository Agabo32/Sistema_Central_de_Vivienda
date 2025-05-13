<?php
session_start();

if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] !== 'admin') {
    die(json_encode([
        'status' => 'error',
        'message' => 'Acceso no autorizado'
    ]));
}
require_once '../conf/conexion.php';
// Inicializar respuesta
$response = [
    'status' => 'error',
    'message' => 'Error desconocido',
    'beneficiario' => null,
    'ubicacion' => [
        'municipio' => 'No especificado',
        'parroquia' => 'No especificado',
        'comunidad' => 'No especificado',
        'direccion_exacta' => 'No especificado',
        'nombre_estado' => 'No especificado'
    ]
];

try {
    // Validar datos obligatorios
    $requiredFields = ['nombre', 'cedula', 'telefono', 'codigo_obra'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es requerido");
        }
    }

    // Sanitizar datos
    $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre']));
    $cedula = mysqli_real_escape_string($conexion, trim($_POST['cedula']));
    $telefono = mysqli_real_escape_string($conexion, trim($_POST['telefono']));
    $codigo_obra = mysqli_real_escape_string($conexion, trim($_POST['codigo_obra']));
    $status_beneficiario = isset($_POST['status']) ? mysqli_real_escape_string($conexion, trim($_POST['status'])) : 'activo';

    // Iniciar transacción
    mysqli_begin_transaction($conexion);

    // Insertar beneficiario con consulta preparada
    $query = "INSERT INTO beneficiarios 
              (cedula, nombre_beneficiario, telefono, codigo_obra, status, fecha_actualizacion) 
              VALUES (?, ?, ?, ?, ?, CURRENT_DATE())";
    
    $stmt = $conexion->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("sssss", $cedula, $nombre, $telefono, $codigo_obra, $status_beneficiario);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al insertar beneficiario: " . $stmt->error);
    }

    $beneficiario_id = $conexion->insert_id;

    // Procesar ubicación si se proporcionó
    if (!empty($_POST['municipio']) && !empty($_POST['parroquia'])) {
        $municipio_id = intval($_POST['municipio']);
        $parroquia_id = intval($_POST['parroquia']);
        
        $query_ubicacion = "SELECT 
            COALESCE(municipio, 'No especificado') AS municipio, 
            COALESCE(parroquia, 'No especificado') AS parroquia,
            COALESCE(comunidad, 'No especificado') AS comunidad,
            COALESCE(direccion_exacta, 'No especificado') AS direccion_exacta,
            COALESCE(nombre_estado, 'No especificado') AS nombre_estado
            FROM vista_ubicaciones 
            WHERE id_municipio = ? AND id_parroquia = ?";
            
        $stmt_ubicacion = $conexion->prepare($query_ubicacion);
        if (!$stmt_ubicacion) {
            throw new Exception("Error al preparar consulta de ubicación: " . $conexion->error);
        }
        
        $stmt_ubicacion->bind_param("ii", $municipio_id, $parroquia_id);
        
        if ($stmt_ubicacion->execute()) {
            $result = $stmt_ubicacion->get_result();
            if ($ubicacion_temp = $result->fetch_assoc()) {
                $response['ubicacion'] = $ubicacion_temp;
            }
        }
    }

    // Confirmar transacción
    mysqli_commit($conexion);

    // Obtener detalles del beneficiario recién creado
    $query_detalle = "SELECT * FROM beneficiarios WHERE id_beneficiario = ?";
    $stmt_detalle = $conexion->prepare($query_detalle);
    if ($stmt_detalle) {
        $stmt_detalle->bind_param("i", $beneficiario_id);
        if ($stmt_detalle->execute()) {
            $result = $stmt_detalle->get_result();
            $response['beneficiario'] = $result->fetch_assoc();
        }
    }

    $response['status'] = 'ok';
    $response['message'] = 'Beneficiario agregado exitosamente';

} catch (Exception $e) {
    mysqli_rollback($conexion);
    $response['message'] = $e->getMessage();
} finally {
    // Cerrar statements y conexión
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_ubicacion)) $stmt_ubicacion->close();
    if (isset($stmt_detalle)) $stmt_detalle->close();
    mysqli_close($conexion);

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}