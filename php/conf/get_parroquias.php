<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../conf/conexion.php';

try {
    // Verificar si se recibió el parámetro
    if (!isset($_GET['municipio_id'])) {
        throw new Exception("Parámetro municipio_id no proporcionado");
    }

    // Validar que sea un número entero positivo
    $municipio_id = filter_var($_GET['municipio_id'], FILTER_VALIDATE_INT);
    if ($municipio_id === false || $municipio_id <= 0) {
        throw new Exception("ID de municipio no válido");
    }

    // Preparar consulta SQL
    $query = "SELECT id_parroquia, parroquia FROM parroquias 
              WHERE id_municipio = ? 
              ORDER BY parroquia ASC";
    
    $stmt = $conexion->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conexion->error);
    }

    $stmt->bind_param("i", $municipio_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $parroquias = [];

    while ($row = $result->fetch_assoc()) {
        $parroquias[] = [
            'id_parroquia' => (int)$row['id_parroquia'],
            'parroquia' => htmlspecialchars($row['parroquia'], ENT_QUOTES, 'UTF-8')
        ];
    }

    if (empty($parroquias)) {
        throw new Exception("No se encontraron parroquias para este municipio");
    }

    echo json_encode($parroquias);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Cerrar conexiones
    if (isset($stmt)) $stmt->close();
    if (isset($conexion)) $conexion->close();
}
?>