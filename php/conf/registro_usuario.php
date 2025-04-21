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

    if (!$nombres || !$apellidos || !$usuario || !$cedula || !$correo || !$telefono || !$password) {
        echo 'Todos los campos son obligatorios.';
        exit();
    }

    $password = md5($password);
    
    // Verificar que el hash se generó correctamente
    if ($password === false) {
        die('Error al generar el hash de la contraseña');
    }

    // Para debug: guardar en un archivo de log
    file_put_contents('debug_log.txt', 
        "Password original: {$password}\n" .
        "Password hash: {$password_hash}\n", 
        FILE_APPEND
    );

    // Insertar usuario de forma segura
    $stmt = $conexion->prepare("INSERT INTO usuario (nombre, apellido, nombre_usuario, cedula, correo, telefono, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nombres, $apellidos, $usuario, $cedula, $correo, $telefono, $password);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conexion->close();
        header("Location: /Sistema_Central_de_Vivienda-main/index.php?registro=exitoso");
        exit();
    } else {
        echo "Error al registrar: " . $stmt->error;
    }
    $stmt->close();
    $conexion->close();
} else {
    echo 'Al finnnnnnnn';
}
?>
