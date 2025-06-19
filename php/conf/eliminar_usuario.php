<?php
require_once 'session_helper.php';
require_once 'conexion.php';

// Verificar autenticación y permisos de administrador
verificar_autenticacion();
if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] !== 'root') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para realizar esta acción']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['id_usuario']) || !ctype_digit($_POST['id_usuario'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de usuario inválido']);
    exit;
}

$id_usuario = (int)$_POST['id_usuario'];

// No permitir que un admin se elimine a sí mismo
if ($_SESSION['user']['id_usuario'] == $id_usuario) {
    echo json_encode(['status' => 'error', 'message' => 'No puedes eliminar tu propio usuario']);
    exit;
}

$sql = "DELETE FROM usuario WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id_usuario);
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Usuario eliminado exitosamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el usuario']);
}
$stmt->close();
$conexion->close(); 