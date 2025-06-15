<?php
require_once '../conf/conexion.php';

// Fijamos el ID del estado Lara
$estado_id = 12;

$query = "SELECT id_municipio, municipio FROM municipios WHERE id_estado = ? ORDER BY municipio ASC";
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
?>