<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

try {
    $id_parroquia = isset($_GET['id_parroquia']) ? intval($_GET['id_parroquia']) : null;
    
    if ($id_parroquia) {
        $stmt = $conexion->prepare("SELECT id_comunidad, comunidad as nombre
            FROM comunidades 
            WHERE id_parroquia = ?
            ORDER BY comunidad ASC");
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
        }
        $stmt->bind_param("i", $id_parroquia);
    } else {
        $stmt = $conexion->prepare("SELECT c.id_comunidad, c.comunidad as nombre, p.parroquia
            FROM comunidades c
            JOIN parroquias p ON c.id_parroquia = p.id_parroquia
            ORDER BY c.comunidad ASC");
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
        }
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Error al obtener resultados: " . $stmt->error);
    }
    
    $comunidades = [];
    while ($row = $result->fetch_assoc()) {
        $comunidades[] = $row;
    }
    
    echo json_encode($comunidades);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener las comunidades: ' . $e->getMessage()]);
}

if (isset($stmt)) {
    $stmt->close();
}
$conexion->close();
?> 