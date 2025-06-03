<?php
// registro_usuario.php: Procesa el registro de usuario de forma segura
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $cedula = $_POST['cedula'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    // Validar que todos los campos estén llenos
    if (empty($nombre) || empty($apellido) || empty($cedula) || empty($usuario) || empty($contrasena) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    // Verificar si el usuario ya existe
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? OR cedula = ? OR email = ?");
    $stmt->bind_param("sss", $usuario, $cedula, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'El usuario, cédula o email ya está registrado']);
        exit;
    }

    // Hash de la contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Insertar nuevo usuario con acceso total
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellido, cedula, usuario, contrasena, email, telefono, rol, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 'activo')");
    $stmt->bind_param("sssssss", $nombre, $apellido, $cedula, $usuario, $contrasena_hash, $email, $telefono);

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