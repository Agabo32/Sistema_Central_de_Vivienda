<?php
header('Content-Type: application/json');

// Incluir archivo de conexión
require_once 'conexion.php';

try {
    // Verificar que se envió el parámetro municipio_id
    if (!isset($_GET['municipio_id']) || empty($_GET['municipio_id'])) {
        throw new Exception('ID de municipio no proporcionado');
    }

    $municipio_id = intval($_GET['municipio_id']);

    if ($municipio_id <= 0) {
        throw new Exception('ID de municipio no válido');
    }

    // Consultar parroquias del municipio
    $query = "SELECT id_parroquia, parroquia FROM parroquias WHERE id_municipio = ? ORDER BY parroquia ASC";
    $stmt = $conexion->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . $conexion->error);
    }

    $stmt->bind_param("i", $municipio_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar consulta: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $parroquias = [];

    while ($row = $result->fetch_assoc()) {
        $parroquias[] = [
            'id_parroquia' => $row['id_parroquia'],
            'parroquia' => $row['parroquia']
        ];
    }

    $stmt->close();
    $conexion->close();

    // Devolver respuesta JSON
    echo json_encode($parroquias);

} catch (Exception $e) {
    // En caso de error, devolver array vacío con mensaje de error
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'parroquias' => []
    ]);
}
?>
