<?php
session_start();
require_once 'conexion.php';

// Verificar permisos de administrador
if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Acceso no autorizado'
    ]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Iniciar transacción
mysqli_begin_transaction($conexion);

try {
    // 1. Crear ubicación
    $query_ubicacion = "INSERT INTO ubicaciones (comunidad, direccion_exacta, utm_norte, utm_este, id_municipio, id_parroquia) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($query_ubicacion);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta de ubicación: " . $conexion->error);
    }
    
    $comunidad = trim($_POST['comunidad'] ?? '');
    $direccion_exacta = trim($_POST['direccion_exacta'] ?? '');
    $utm_norte = !empty($_POST['utm_norte']) ? floatval($_POST['utm_norte']) : null;
    $utm_este = !empty($_POST['utm_este']) ? floatval($_POST['utm_este']) : null;
    $id_municipio = !empty($_POST['municipio']) ? intval($_POST['municipio']) : null;
    $id_parroquia = !empty($_POST['parroquia']) ? intval($_POST['parroquia']) : null;
    
    $stmt->bind_param("ssddii", $comunidad, $direccion_exacta, $utm_norte, $utm_este, $id_municipio, $id_parroquia);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear ubicación: " . $stmt->error);
    }
    
    $id_ubicacion = $conexion->insert_id;
    $stmt->close();
    
    // 2. Crear beneficiario
    $query_beneficiario = "INSERT INTO beneficiarios (nombre_beneficiario, cedula, telefono, id_cod_obra, id_ubicacion, status, fecha_actualizacion) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conexion->prepare($query_beneficiario);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta de beneficiario: " . $conexion->error);
    }
    
    $nombre = trim($_POST['nombre_beneficiario'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $id_cod_obra = !empty($_POST['codigo_obra']) ? intval($_POST['codigo_obra']) : null;
    $status = trim($_POST['status'] ?? 'activo');
    
    $stmt->bind_param("sssiis", $nombre, $cedula, $telefono, $id_cod_obra, $id_ubicacion, $status);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear beneficiario: " . $stmt->error);
    }
    
    $id_beneficiario = $conexion->insert_id;
    $stmt->close();
    
    // 3. Crear registro inicial en datos_de_construccion
    $query_construccion = "INSERT INTO datos_de_construccion (id_beneficiario) VALUES (?)";
    $stmt = $conexion->prepare($query_construccion);
    if (!$stmt) {
        throw new Exception("Error al preparar datos de construcción: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $id_beneficiario);
    if (!$stmt->execute()) {
        throw new Exception("Error al crear datos de construcción: " . $stmt->error);
    }
    $stmt->close();
    
    // Confirmar transacción
    mysqli_commit($conexion);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Beneficiario creado exitosamente']);
    
} catch (Exception $e) {
    // Rollback en caso de error
    mysqli_rollback($conexion);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    
    error_log("Error creando beneficiario: " . $e->getMessage());
}

mysqli_close($conexion);
?>
