<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $codigo_obra = trim($_POST['codigo_obra']);
        
        if (empty($codigo_obra)) {
            throw new Exception('El código de obra es requerido');
        }
        
        // Verificar si el código de obra ya existe
        $check_stmt = $conexion->prepare("SELECT id_cod_obra FROM cod_obra WHERE cod_obra = ?");
        $check_stmt->bind_param("s", $codigo_obra);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception('Ya existe un código de obra con ese valor');
        }
        
        // Insertar nuevo código de obra
        $stmt = $conexion->prepare("INSERT INTO cod_obra (cod_obra) VALUES (?)");
        $stmt->bind_param("s", $codigo_obra);
        
        if ($stmt->execute()) {
            $id_cod_obra = $conexion->insert_id;
            echo json_encode([
                'status' => 'success',
                'message' => 'Código de obra creado exitosamente',
                'id_cod_obra' => $id_cod_obra,
                'codigo_obra' => $codigo_obra
            ]);
        } else {
            throw new Exception('Error al guardar el código de obra');
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