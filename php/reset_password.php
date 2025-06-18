<?php
session_start();
require_once 'conf/conexion.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = '';

if (empty($token)) {
    header('Location: index.php');
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            // Verificar token
            $stmt = $conexion->prepare("
                SELECT pr.id_usuario, pr.expiracion 
                FROM password_resets pr 
                WHERE pr.token = ? AND pr.expiracion > NOW() 
                AND pr.usado = 0
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = 'El enlace de recuperación no es válido o ha expirado';
            } else {
                $reset = $result->fetch_assoc();
                
                // Actualizar contraseña
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
                $stmt->bind_param("si", $hashed_password, $reset['id_usuario']);
                $stmt->execute();
                
                // Marcar token como usado
                $stmt = $conexion->prepare("UPDATE password_resets SET usado = 1 WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                $success = 'Tu contraseña ha sido actualizada correctamente';
            }
        } catch (Exception $e) {
            $error = 'Error al procesar la solicitud: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Sistema Central de Vivienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Restablecer Contraseña</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="index.php" class="btn btn-primary">Ir al inicio de sesión</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="resetForm">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                                    <a href="index.php" class="btn btn-link">Volver al inicio de sesión</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres');
            } else if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html> 