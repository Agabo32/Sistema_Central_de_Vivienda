<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $modelo = trim($_POST['modelo']);
        
        if (empty($modelo)) {
            throw new Exception('El nombre del modelo constructivo es requerido');
        }
        
        // Verificar si el modelo ya existe
        $check_stmt = $conexion->prepare("SELECT id_modelo FROM modelos_constructivos WHERE modelo = ?");
        $check_stmt->bind_param("s", $modelo);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception('Ya existe un modelo constructivo con ese nombre');
        }
        
        // Insertar nuevo modelo constructivo
        $stmt = $conexion->prepare("INSERT INTO modelos_constructivos (modelo) VALUES (?)");
        $stmt->bind_param("s", $modelo);
        
        if ($stmt->execute()) {
            $id_modelo = $conexion->insert_id;
            echo json_encode([
                'status' => 'success',
                'message' => 'Modelo constructivo creado exitosamente',
                'id_modelo' => $id_modelo,
                'modelo' => $modelo
            ]);
        } else {
            throw new Exception('Error al guardar el modelo constructivo');
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