<?php
require_once 'session_helper.php';
require_once 'conexion.php';

verificar_autenticacion();
if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] !== 'root') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para realizar esta acción']);
    exit;
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de usuario inválido']);
    exit;
}

$id_usuario = (int)$_GET['id'];

$sql = "SELECT id_usuario, Nombre, Apellido, nombre_usuario, cedula, correo, telefono, rol, activo, pregunta_seguridad, respuesta_seguridad FROM usuario WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($usuario = $result->fetch_assoc()) {
    echo json_encode(['status' => 'success', 'usuario' => $usuario]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
} 