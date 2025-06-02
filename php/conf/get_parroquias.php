<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['municipio_id']) || !is_numeric($_GET['municipio_id'])) {
    echo json_encode([]);
    exit;
}

$municipio_id = intval($_GET['municipio_id']);

try {
    $query = "SELECT id_parroquia, parroquia FROM parroquias WHERE id_municipio = ? ORDER BY parroquia ASC";
    $stmt = $conexion->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $municipio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $parroquias = [];
    while ($row = $result->fetch_assoc()) {
        $parroquias[] = $row;
    }
    
    echo json_encode($parroquias);
    
} catch (Exception $e) {
    error_log("Error obteniendo parroquias: " . $e->getMessage());
    echo json_encode([]);
}

mysqli_close($conexion);
?>
