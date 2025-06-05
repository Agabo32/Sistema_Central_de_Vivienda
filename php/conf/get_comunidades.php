<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['parroquia_id'])) {
    echo json_encode(['error' => 'ID de parroquia no especificado']);
    exit;
}

$parroquia_id = intval($_GET['parroquia_id']);

$query = "SELECT id_comunidad, comunidad FROM comunidades WHERE id_parroquia = ? ORDER BY comunidad ASC";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $parroquia_id);
$stmt->execute();
$result = $stmt->get_result();

$comunidades = [];
while ($row = $result->fetch_assoc()) {
    $comunidades[] = $row;
}

echo json_encode($comunidades);

$stmt->close();
$conexion->close();
?>
