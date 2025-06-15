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

try {
    // Obtener y validar datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'usuario';
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre)) $errores[] = 'El nombre es requerido';
    if (empty($apellido)) $errores[] = 'El apellido es requerido';
    if (empty($nombre_usuario)) $errores[] = 'El nombre de usuario es requerido';
    if (empty($cedula)) $errores[] = 'La cédula es requerida';
    if (empty($correo)) $errores[] = 'El correo es requerido';
    if (empty($telefono)) $errores[] = 'El teléfono es requerido';
    if (empty($password)) $errores[] = 'La contraseña es requerida';
    
    // Validar formato de correo
    if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del correo no es válido';
    }
    
    // Validar longitud de contraseña
    if (!empty($password) && strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres';
    }
    
    // Validar rol
    if (!in_array($rol, ['usuario', 'root'])) {
        $errores[] = 'El rol seleccionado no es válido';
    }
    
    // Validar que la cédula sea numérica
    if (!empty($cedula) && !ctype_digit($cedula)) {
        $errores[] = 'La cédula debe contener solo números';
    }
    
    // Validar que el teléfono sea numérico
    if (!empty($telefono) && !ctype_digit($telefono)) {
        $errores[] = 'El teléfono debe contener solo números';
    }

    if (!empty($errores)) {
        echo json_encode(['status' => 'error', 'message' => implode(', ', $errores)]);
        exit;
    }

    // Verificar si ya existe un usuario con la misma cédula, correo o nombre de usuario
    $sql_verificar = "SELECT id_usuario FROM usuario WHERE cedula = ? OR correo = ? OR nombre_usuario = ?";
    $stmt_verificar = $conexion->prepare($sql_verificar);
    $stmt_verificar->bind_param("sss", $cedula, $correo, $nombre_usuario);
    $stmt_verificar->execute();
    $resultado_verificar = $stmt_verificar->get_result();
    
    if ($resultado_verificar->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe un usuario con esa cédula, correo o nombre de usuario']);
        exit;
    }

    // Encriptar contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Preparar consulta de inserción
    $sql_insertar = "INSERT INTO usuario (Nombre, Apellido, nombre_usuario, cedula, correo, telefono, password, rol, fecha_registro, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    
    $stmt_insertar = $conexion->prepare($sql_insertar);
    $stmt_insertar->bind_param("ssssssssi", $nombre, $apellido, $nombre_usuario, $cedula, $correo, $telefono, $password_hash, $rol, $activo);
    
    if ($stmt_insertar->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Usuario creado exitosamente',
            'user_id' => $conexion->insert_id
        ]);
    } else {
        throw new Exception('Error al insertar el usuario en la base de datos');
    }

} catch (Exception $e) {
    error_log("Error al crear usuario: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor']);
}
?>
