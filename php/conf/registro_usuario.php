<?php
// registro_usuario.php: Procesa el registro de usuario de forma segura
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexion.php';
    
    // Sanitizar y validar datos recibidos
    $nombres    = trim(mysqli_real_escape_string($conexion, $_POST['nombre'] ?? ''));
    $apellidos  = trim(mysqli_real_escape_string($conexion, $_POST['apellido'] ?? ''));
    $usuario    = trim(mysqli_real_escape_string($conexion, $_POST['nombre_usuario'] ?? ''));
    $cedula     = trim(mysqli_real_escape_string($conexion, $_POST['cedula'] ?? ''));
    $correo     = trim(mysqli_real_escape_string($conexion, $_POST['correo'] ?? ''));
    $telefono   = trim(mysqli_real_escape_string($conexion, $_POST['telefono'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $rol        = 'usuario'; // Rol por defecto

    // Validación de campos obligatorios
    if (empty($nombres) || empty($apellidos) || empty($usuario) || 
        empty($cedula) || empty($correo) || empty($telefono) || empty($password)) {
        die('Todos los campos son obligatorios.');
    }

    // Validar formato de email
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die('El formato del correo electrónico no es válido.');
    }

    // Validar fortaleza de contraseña
    if (strlen($password) < 8) {
        die('La contraseña debe tener al menos 8 caracteres.');
    }

    // Hash seguro de la contraseña (compatible con password_verify)
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    if ($password_hash === false) {
        die('Error al generar el hash de la contraseña');
    }

    // Verificar si el usuario o correo ya existen
    $stmt_check = $conexion->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ? OR correo = ?");
    $stmt_check->bind_param("ss", $usuario, $correo);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        die('El nombre de usuario o correo electrónico ya está registrado.');
    }
    $stmt_check->close();

    // Insertar usuario de forma segura con rol
    $stmt = $conexion->prepare("INSERT INTO usuario 
        (nombre, apellido, nombre_usuario, cedula, correo, telefono, password, rol) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $nombres, $apellidos, $usuario, $cedula, $correo, $telefono, $password_hash, $rol);
    
    if ($stmt->execute()) {
        // Registro exitoso
        $stmt->close();
        $conexion->close();
        header("Location: /Sistema_Central_de_Vivienda-main/index.php?registro=exitoso");
        exit();
    } else {
        // Manejo de errores de SQL
        if (strpos($stmt->error, 'Duplicate entry') !== false) {
            die('El nombre de usuario o correo electrónico ya está registrado.');
        } else {
            die('Error al registrar: ' . $stmt->error);
        }
    }
} else {
    // Método no permitido
    http_response_code(405);
    die('Método no permitido');
}
?>