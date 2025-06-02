<?php
session_start();
require_once 'conexion.php';

// Verificar permisos de administrador
if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Acceso no autorizado'
    ]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Validar datos recibidos
if (!isset($_POST['id_beneficiario']) || !isset($_POST['proyecto'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

$id_beneficiario = intval($_POST['id_beneficiario']);
$proyecto = trim($_POST['proyecto']);

// Validar que el ID sea válido
if ($id_beneficiario <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID de beneficiario no válido']);
    exit;
}

// Validar que el proyecto no esté vacío
if (empty($proyecto)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'El nombre del proyecto no puede estar vacío']);
    exit;
}

try {
    // Verificar que el beneficiario existe
    $query_check = "SELECT id_beneficiario FROM beneficiarios WHERE id_beneficiario = ?";
    $stmt_check = $conexion->prepare($query_check);
    
    if (!$stmt_check) {
        throw new Exception("Error al preparar consulta de verificación: " . $conexion->error);
    }
    
    $stmt_check->bind_param("i", $id_beneficiario);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        throw new Exception("Beneficiario no encontrado");
    }
    
    $stmt_check->close();
    
    // Actualizar el proyecto
    $query_update = "UPDATE beneficiarios SET proyecto = ?, fecha_actualizacion = NOW() WHERE id_beneficiario = ?";
    $stmt_update = $conexion->prepare($query_update);
    
    if (!$stmt_update) {
        throw new Exception("Error al preparar consulta de actualización: " . $conexion->error);
    }
    
    $stmt_update->bind_param("si", $proyecto, $id_beneficiario);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el proyecto: " . $stmt_update->error);
    }
    
    $stmt_update->close();
    
    // Respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Proyecto actualizado correctamente',
        'proyecto' => $proyecto
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    error_log("Error actualizando proyecto: " . $e->getMessage());
}

mysqli_close($conexion);
?>
