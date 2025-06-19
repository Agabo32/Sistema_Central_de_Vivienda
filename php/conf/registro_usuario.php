<?php
// registro_usuario.php: Procesa el registro de usuario de forma segura
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'usuario';
    $pregunta_seguridad = trim($_POST['pregunta_seguridad'] ?? '');
    $respuesta_seguridad = trim($_POST['respuesta_seguridad'] ?? '');

    // Validar que todos los campos requeridos estén llenos
    if (empty($nombre) || empty($apellido) || empty($nombre_usuario) || 
        empty($cedula) || empty($correo) || empty($telefono) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    if (empty($pregunta_seguridad) || empty($respuesta_seguridad)) {
        echo json_encode(['status' => 'error', 'message' => 'La pregunta y respuesta de seguridad son obligatorias']);
        exit;
    }

    // Verificar si el usuario ya existe
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ? OR cedula = ? OR correo = ?");
    $stmt->bind_param("sss", $nombre_usuario, $cedula, $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre de usuario, cédula o correo ya está registrado']);
        exit;
    }

    // Hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $stmt = $conexion->prepare("INSERT INTO usuario (Nombre, Apellido, nombre_usuario, cedula, correo, telefono, password, rol, pregunta_seguridad, respuesta_seguridad) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $nombre, $apellido, $nombre_usuario, $cedula, $correo, $telefono, $password_hash, $rol, $pregunta_seguridad, $respuesta_seguridad);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Usuario registrado exitosamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar el usuario: ' . $conexion->error]);
    }

    $stmt->close();
    $conexion->close();
} else {
    // Método no permitido
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>