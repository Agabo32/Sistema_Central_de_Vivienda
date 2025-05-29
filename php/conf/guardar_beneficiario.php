<?php
header('Content-Type: application/json');

// Incluir archivo de conexión
require_once 'conexion.php';

try {
    // Validar campos requeridos
    $campos_requeridos = [
        'nombre_beneficiario', 
        'cedula', 
        'telefono', 
        'codigo_obra', 
        'comunidad',
        'municipio',
        'parroquia',
        'status'
    ];

    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || (is_string($_POST[$campo]) && trim($_POST[$campo]) === '')) {
            die(json_encode(['status' => 'error', 'message' => "Falta el campo requerido: $campo"]));
        }
    }

    // Sanitizar datos
    $nombre = trim($_POST['nombre_beneficiario']);
    $cedula = trim($_POST['cedula']);
    $telefono = trim($_POST['telefono']);
    $codigo_obra = trim($_POST['codigo_obra']);
    $comunidad = trim($_POST['comunidad']);
    $direccion_exacta = isset($_POST['direccion_exacta']) ? trim($_POST['direccion_exacta']) : '';
    $utm_norte = isset($_POST['utm_norte']) ? trim($_POST['utm_norte']) : '';
    $utm_este = isset($_POST['utm_este']) ? trim($_POST['utm_este']) : '';
    $status = trim($_POST['status']);
    $id_municipio = intval($_POST['municipio']);
    $id_parroquia = intval($_POST['parroquia']);

    // Validar IDs
    if ($id_municipio <= 0) {
        die(json_encode(['status' => 'error', 'message' => 'Municipio no válido']));
    }

    if ($id_parroquia <= 0) {
        die(json_encode(['status' => 'error', 'message' => 'Parroquia no válido']));
    }

    // Iniciar transacción
    $conexion->begin_transaction();

    // 1. Insertar ubicación
    $query_ubicacion = "INSERT INTO Ubicaciones (
        comunidad, 
        direccion_exacta, 
        utm_norte, 
        utm_este,
        id_parroquia
    ) VALUES (?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($query_ubicacion);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta de ubicación: " . $conexion->error);
    }

    $stmt->bind_param("ssssi", $comunidad, $direccion_exacta, $utm_norte, $utm_este, $id_parroquia);

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar ubicación: " . $stmt->error);
    }

    $id_ubicacion = $conexion->insert_id;
    $stmt->close();

    // 2. Insertar beneficiario
    $query_beneficiario = "INSERT INTO Beneficiarios (
        nombre_beneficiario, 
        cedula, 
        telefono, 
        codigo_obra, 
        id_ubicacion,
        status
    ) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($query_beneficiario);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta de beneficiario: " . $conexion->error);
    }

    $stmt->bind_param("sssiis", $nombre, $cedula, $telefono, $codigo_obra, $id_ubicacion, $status);

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar beneficiario: " . $stmt->error);
    }

    $id_beneficiario = $conexion->insert_id;
    $stmt->close();

    // Commit transaction
    $conexion->commit();

    // Obtener datos de ubicación para la respuesta
    $query_ubicacion_response = "SELECT b.*, p.parroquia, m.municipio, e.estado 
    FROM Beneficiarios b
    LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
    LEFT JOIN parroquias p ON u.id_parroquia = p.id_parroquia
    LEFT JOIN municipios m ON p.id_municipio = m.id_municipio
    LEFT JOIN estados e ON m.id_estado = e.id_estado
    WHERE b.id_beneficiario = ?";

    $stmt = $conexion->prepare($query_ubicacion_response);
    $stmt->bind_param("i", $id_beneficiario);
    $stmt->execute();
    $result = $stmt->get_result();
    $ubicacion_data = $result->fetch_assoc();
    $stmt->close();

    $ubicacion = [
        'municipio' => $ubicacion_data['municipio'] ?? '',
        'parroquia' => $ubicacion_data['parroquia'] ?? '',
        'estado' => $ubicacion_data['estado'] ?? ''
    ];

    // Construir la respuesta
    $response = [
        'status' => 'success',
        'message' => 'Beneficiario guardado con éxito',
        'id_beneficiario' => $id_beneficiario,
        'nombre_beneficiario' => $nombre,
        'cedula' => $cedula,
        'telefono' => $telefono,
        'codigo_obra' => $codigo_obra,
        'comunidad' => $comunidad,
        'direccion_exacta' => $direccion_exacta,
        'utm_norte' => $utm_norte,
        'utm_este' => $utm_este,
        'status' => $status,
        'ubicacion' => $ubicacion
    ];

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conexion->in_transaction) {
        $conexion->rollback();
    }

    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conexion) && $conexion) {
        $conexion->close();
    }
}
?>
