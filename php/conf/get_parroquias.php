<?php
require_once '../conf/conexion.php';

$municipio_id = $_GET['municipio_id'] ?? null;

if ($municipio_id) {
    $query = "SELECT id_parroquia, parroquia FROM parroquias WHERE id_municipio = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $municipio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $parroquias = [];
    while ($row = $result->fetch_assoc()) {
        $parroquias[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($parroquias);
}
?>