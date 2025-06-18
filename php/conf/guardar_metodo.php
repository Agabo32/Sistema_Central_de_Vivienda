<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $metodo = trim($_POST['metodo']);
        
        if (empty($metodo)) {
            throw new Exception('El nombre del método constructivo es requerido');
        }
        
        // Verificar si el método ya existe
        $check_stmt = $conexion->prepare("SELECT id_metodo FROM metodos_constructivos WHERE metodo = ?");
        $check_stmt->bind_param("s", $metodo);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception('Ya existe un método constructivo con ese nombre');
        }
        
        // Insertar nuevo método constructivo
        $stmt = $conexion->prepare("INSERT INTO metodos_constructivos (metodo) VALUES (?)");
        $stmt->bind_param("s", $metodo);
        
        if ($stmt->execute()) {
            $id_metodo = $conexion->insert_id;
            echo json_encode([
                'status' => 'success',
                'message' => 'Método constructivo creado exitosamente',
                'id_metodo' => $id_metodo,
                'metodo' => $metodo
            ]);
        } else {
            throw new Exception('Error al guardar el método constructivo');
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