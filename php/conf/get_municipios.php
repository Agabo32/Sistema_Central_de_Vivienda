<?php
require_once '../conf/conexion.php';

$estado_id = $_GET['estado_id'] ?? null;

if ($estado_id) {
    $query = "SELECT id_municipio, municipio FROM municipios WHERE id_estado = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $estado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $municipios = [];
    while ($row = $result->fetch_assoc()) {
        $municipios[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($municipios);
}
?>