<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en output para no romper JSON

// Incluir archivo de conexión
require_once 'conexion.php';

$stmt_ubicacion = null;
$stmt_beneficiario = null;
$stmt_response = null;

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
            throw new Exception("Falta el campo requerido: $campo");
        }
    }

    // Sanitizar datos
    $nombre = trim($_POST['nombre_beneficiario']);
    $cedula = trim($_POST['cedula']);
    $telefono = trim($_POST['telefono']);
    $codigo_obra = trim($_POST['codigo_obra']);
    $comunidad = trim($_POST['comunidad']);
    $direccion_exacta = isset($_POST['direccion_exacta']) ? trim($_POST['direccion_exacta']) : '';
    $utm_norte = isset($_POST['utm_norte']) && $_POST['utm_norte'] !== '' ? floatval($_POST['utm_norte']) : null;
    $utm_este = isset($_POST['utm_este']) && $_POST['utm_este'] !== '' ? floatval($_POST['utm_este']) : null;
    $status = trim($_POST['status']);
    $id_municipio = intval($_POST['municipio']);
    $id_parroquia = intval($_POST['parroquia']);

    // Validar IDs
    if ($id_municipio <= 0) {
        throw new Exception('Municipio no válido');
    }

    if ($id_parroquia <= 0) {
        throw new Exception('Parroquia no válida');
    }

    // Verificar que la cédula no exista
    $check_cedula = $conexion->prepare("SELECT id_beneficiario FROM beneficiarios WHERE cedula = ?");
    $check_cedula->bind_param("s", $cedula);
    $check_cedula->execute();
    $result_check = $check_cedula->get_result();
    
    if ($result_check->num_rows > 0) {
        $check_cedula->close();
        throw new Exception('Ya existe un beneficiario con esta cédula');
    }
    $check_cedula->close();

    // Iniciar transacción
    $conexion->begin_transaction();

    // 1. Insertar ubicación
    $query_ubicacion = "INSERT INTO ubicaciones (
        comunidad, 
        direccion_exacta, 
        utm_norte, 
        utm_este,
        id_municipio,
        id_parroquia
    ) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt_ubicacion = $conexion->prepare($query_ubicacion);
    if (!$stmt_ubicacion) {
        throw new Exception("Error al preparar consulta de ubicación: " . $conexion->error);
    }

    $stmt_ubicacion->bind_param("ssddii", $comunidad, $direccion_exacta, $utm_norte, $utm_este, $id_municipio, $id_parroquia);

    if (!$stmt_ubicacion->execute()) {
        throw new Exception("Error al insertar ubicación: " . $stmt_ubicacion->error);
    }

    $id_ubicacion = $conexion->insert_id;
    $stmt_ubicacion->close();

    // 2. Insertar beneficiario
    $query_beneficiario = "INSERT INTO beneficiarios (
        nombre_beneficiario, 
        cedula, 
        telefono, 
        codigo_obra, 
        id_ubicacion,
        status,
        fecha_actualizacion
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt_beneficiario = $conexion->prepare($query_beneficiario);
    if (!$stmt_beneficiario) {
        throw new Exception("Error al preparar consulta de beneficiario: " . $conexion->error);
    }

    $stmt_beneficiario->bind_param("sssiis", $nombre, $cedula, $telefono, $codigo_obra, $id_ubicacion, $status);

    if (!$stmt_beneficiario->execute()) {
        throw new Exception("Error al insertar beneficiario: " . $stmt_beneficiario->error);
    }

    $id_beneficiario = $conexion->insert_id;
    $stmt_beneficiario->close();

    // 3. Crear registro inicial en datos_de_construccion
    $query_construccion = "INSERT INTO datos_de_construccion (id_beneficiario) VALUES (?)";
    $stmt_construccion = $conexion->prepare($query_construccion);
    if (!$stmt_construccion) {
        throw new Exception("Error al preparar consulta de construcción: " . $conexion->error);
    }
    
    $stmt_construccion->bind_param("i", $id_beneficiario);
    if (!$stmt_construccion->execute()) {
        throw new Exception("Error al crear registro de construcción: " . $stmt_construccion->error);
    }
    $stmt_construccion->close();

    // Commit transaction
    $conexion->commit();

    // Obtener datos de ubicación para la respuesta
    $query_ubicacion_response = "SELECT b.*, p.parroquia, m.municipio, e.estado 
    FROM beneficiarios b
    LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
    LEFT JOIN parroquias p ON u.id_parroquia = p.id_parroquia
    LEFT JOIN municipios m ON p.id_municipio = m.id_municipio
    LEFT JOIN estados e ON m.id_estado = e.id_estado
    WHERE b.id_beneficiario = ?";

    $stmt_response = $conexion->prepare($query_ubicacion_response);
    if (!$stmt_response) {
        throw new Exception("Error al preparar consulta de respuesta: " . $conexion->error);
    }
    
    $stmt_response->bind_param("i", $id_beneficiario);
    $stmt_response->execute();
    $result = $stmt_response->get_result();
    $ubicacion_data = $result->fetch_assoc();
    $stmt_response->close();

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
        'beneficiario' => [
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
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conexion) && $conexion->in_transaction) {
        $conexion->rollback();
    }

    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    
    // Log error for debugging
    error_log("Error en guardar_beneficiario.php: " . $e->getMessage());
    
} finally {
    // Cerrar conexión solo si existe y está abierta
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
?>
