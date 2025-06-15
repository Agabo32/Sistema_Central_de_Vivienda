<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

// Verificar si el usuario está autenticado y tiene permisos
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'root') {
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción']);
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar los datos
$nombre_comunidad = trim($_POST['nombre_comunidad'] ?? '');
$id_parroquia = intval($_POST['id_parroquia'] ?? 0);

if (empty($nombre_comunidad)) {
    echo json_encode(['status' => 'error', 'message' => 'El nombre de la comunidad es requerido']);
    exit;
}

if ($id_parroquia <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'La parroquia seleccionada no es válida']);
    exit;
}

try {
    // Verificar si la comunidad ya existe en la parroquia
    $stmt = $conexion->prepare("SELECT id_comunidad FROM comunidades WHERE comunidad = ? AND id_parroquia = ?");
    $stmt->bind_param("si", $nombre_comunidad, $id_parroquia);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe una comunidad con este nombre en la parroquia seleccionada']);
        exit;
    }
    
    // Insertar la nueva comunidad
    $stmt = $conexion->prepare("INSERT INTO comunidades (comunidad, id_parroquia) VALUES (?, ?)");
    $stmt->bind_param("si", $nombre_comunidad, $id_parroquia);
    
    if ($stmt->execute()) {
        $id_comunidad = $conexion->insert_id;
        echo json_encode([
            'status' => 'success',
            'message' => 'Comunidad creada exitosamente',
            'id_comunidad' => $id_comunidad
        ]);
    } else {
        throw new Exception("Error al crear la comunidad: " . $stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conexion->close();
?>