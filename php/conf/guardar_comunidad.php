<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se recibieron los datos necesarios
if (empty($_POST['nombre_comunidad']) || empty($_POST['id_parroquia'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos requeridos']);
    exit;
}

try {
    // Preparar los datos
    $nombre_comunidad = trim($_POST['nombre_comunidad']);
    $id_parroquia = intval($_POST['id_parroquia']);

    // Verificar si la comunidad ya existe
    $stmt = $conexion->prepare("SELECT ID_COMUNIDAD FROM comunidades WHERE COMUNIDAD = ? AND ID_PARROQUIA = ?");
    $stmt->bind_param("si", $nombre_comunidad, $id_parroquia);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Esta comunidad ya existe en la parroquia seleccionada']);
        exit;
    }

    // Insertar la nueva comunidad
    $stmt = $conexion->prepare("INSERT INTO comunidades (COMUNIDAD, ID_PARROQUIA) VALUES (?, ?)");
    $stmt->bind_param("si", $nombre_comunidad, $id_parroquia);
    
    if ($stmt->execute()) {
        $id_comunidad = $conexion->insert_id;
        echo json_encode([
            'status' => 'success',
            'message' => 'Comunidad guardada exitosamente',
            'id_comunidad' => $id_comunidad,
            'nombre_comunidad' => $nombre_comunidad
        ]);
    } else {
        throw new Exception("Error al guardar la comunidad");
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al guardar la comunidad: ' . $e->getMessage()
    ]);
}

$conexion->close();
?> 