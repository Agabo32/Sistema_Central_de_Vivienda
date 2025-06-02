<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['parroquia_id']) || !is_numeric($_GET['parroquia_id'])) {
    echo json_encode([]);
    exit;
}

$parroquia_id = intval($_GET['parroquia_id']);

try {
    $query = "SELECT ID_COMUNIDAD, COMUNIDAD FROM comunidades WHERE ID_PARROQUIA = ? ORDER BY COMUNIDAD ASC";
    $stmt = $conexion->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $parroquia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comunidades = [];
    while ($row = $result->fetch_assoc()) {
        $comunidades[] = [
            'id_comunidad' => $row['ID_COMUNIDAD'],
            'comunidad' => $row['COMUNIDAD']
        ];
    }
    
    echo json_encode($comunidades);
    
} catch (Exception $e) {
    error_log("Error obteniendo comunidades: " . $e->getMessage());
    echo json_encode([]);
}

mysqli_close($conexion);
?>
