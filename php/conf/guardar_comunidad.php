<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Verificar datos requeridos
if (!isset($_POST['nombre_comunidad']) || !isset($_POST['id_parroquia'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos requeridos']);
    exit;
}

$nombre_comunidad = trim($_POST['nombre_comunidad']);
$id_parroquia = intval($_POST['id_parroquia']);

// Validar que el nombre no esté vacío
if (empty($nombre_comunidad)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El nombre de la comunidad no puede estar vacío']);
    exit;
}

try {
    // Verificar si la parroquia existe
    $stmt = $conexion->prepare("SELECT id_parroquia FROM parroquias WHERE id_parroquia = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $id_parroquia);
    if (!$stmt->execute()) {
        throw new Exception("Error al verificar la parroquia: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("La parroquia especificada no existe");
    }
    $stmt->close();
    
    // Verificar si la comunidad ya existe en esa parroquia
    $stmt = $conexion->prepare("SELECT id_comunidad FROM comunidades WHERE comunidad = ? AND id_parroquia = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("si", $nombre_comunidad, $id_parroquia);
    if (!$stmt->execute()) {
        throw new Exception("Error al verificar la comunidad: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception("Ya existe una comunidad con ese nombre en la parroquia seleccionada");
    }
    $stmt->close();
    
    // Insertar la nueva comunidad
    $stmt = $conexion->prepare("INSERT INTO comunidades (comunidad, id_parroquia) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("si", $nombre_comunidad, $id_parroquia);
    if (!$stmt->execute()) {
        throw new Exception("Error al crear la comunidad: " . $stmt->error);
    }
    
    $id_comunidad = $conexion->insert_id;
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Comunidad creada exitosamente',
        'id_comunidad' => $id_comunidad,
        'nombre_comunidad' => $nombre_comunidad
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>