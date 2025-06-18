<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fiscalizador = trim($_POST['fiscalizador']);
        
        if (empty($fiscalizador)) {
            throw new Exception('El nombre del fiscalizador es requerido');
        }
        
        // Verificar si el fiscalizador ya existe
        $check_stmt = $conexion->prepare("SELECT id_fiscalizador FROM fiscalizadores WHERE fiscalizador = ?");
        $check_stmt->bind_param("s", $fiscalizador);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception('Ya existe un fiscalizador con ese nombre');
        }
        
        // Insertar nuevo fiscalizador
        $stmt = $conexion->prepare("INSERT INTO fiscalizadores (fiscalizador) VALUES (?)");
        $stmt->bind_param("s", $fiscalizador);
        
        if ($stmt->execute()) {
            $id_fiscalizador = $conexion->insert_id;
            echo json_encode([
                'status' => 'success',
                'message' => 'Fiscalizador creado exitosamente',
                'id_fiscalizador' => $id_fiscalizador,
                'fiscalizador' => $fiscalizador
            ]);
        } else {
            throw new Exception('Error al guardar el fiscalizador');
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