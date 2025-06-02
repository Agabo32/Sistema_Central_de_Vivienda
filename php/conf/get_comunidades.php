<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id_parroquia'])) {
    echo json_encode(['error' => 'ID de parroquia no proporcionado']);
    exit;
}

$id_parroquia = intval($_GET['id_parroquia']);

try {
    $stmt = $conexion->prepare("SELECT ID_COMUNIDAD as id_comunidad, COMUNIDAD as nombre 
                               FROM comunidades 
                               WHERE ID_PARROQUIA = ? 
                               ORDER BY COMUNIDAD ASC");
    $stmt->bind_param("i", $id_parroquia);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comunidades = [];
    while ($row = $result->fetch_assoc()) {
        $comunidades[] = $row;
    }
    
    echo json_encode($comunidades);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener las comunidades: ' . $e->getMessage()]);
}

$conexion->close();
?>
