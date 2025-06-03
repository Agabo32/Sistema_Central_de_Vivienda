<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre_comunidad = trim($_POST['nombre_comunidad']);
        $id_parroquia = intval($_POST['id_parroquia']);
        
        if (empty($nombre_comunidad) || empty($id_parroquia)) {
            throw new Exception('Nombre de comunidad y parroquia son requeridos');
        }
        
        // Verificar si la comunidad ya existe en esa parroquia
        $check_stmt = $conexion->prepare("SELECT id_comunidad FROM comunidades WHERE comunidad = ? AND id_parroquia = ?");
        $check_stmt->bind_param("si", $nombre_comunidad, $id_parroquia);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception('Ya existe una comunidad con ese nombre en la parroquia seleccionada');
        }
        
        // Insertar nueva comunidad
        $stmt = $conexion->prepare("INSERT INTO comunidades (comunidad, id_parroquia) VALUES (?, ?)");
        $stmt->bind_param("si", $nombre_comunidad, $id_parroquia);
        
        if ($stmt->execute()) {
            $id_comunidad = $conexion->insert_id;
            echo json_encode([
                'status' => 'success',
                'message' => 'Comunidad creada exitosamente',
                'id_comunidad' => $id_comunidad,
                'nombre_comunidad' => $nombre_comunidad
            ]);
        } else {
            throw new Exception('Error al guardar la comunidad');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido'
    ]);
}

$conexion->close();
?>