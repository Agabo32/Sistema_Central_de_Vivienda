<?php
session_start();
require_once 'conf/conexion.php';

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'El correo electrónico no es válido']);
        exit;
    }

    try {
        // Verificar si el correo existe en la base de datos
        $stmt = $conexion->prepare("SELECT id_usuario, nombre FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'No existe una cuenta con este correo electrónico']);
            exit;
        }

        $usuario = $result->fetch_assoc();
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Guardar token en la base de datos
        $stmt = $conexion->prepare("INSERT INTO password_resets (id_usuario, token, expiracion) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $usuario['id_usuario'], $token, $expiracion);
        $stmt->execute();
        
        // Enviar correo electrónico
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/Sistema_Central_de_Vivienda-main/Sistema_Central_de_Vivienda-main/reset_password.php?token=" . $token;
        
        $to = $email;
        $subject = "Recuperación de Contraseña - Sistema Central de Vivienda";
        $message = "Hola " . $usuario['nombre'] . ",\n\n";
        $message .= "Has solicitado restablecer tu contraseña. Por favor, haz clic en el siguiente enlace para crear una nueva contraseña:\n\n";
        $message .= $reset_link . "\n\n";
        $message .= "Este enlace expirará en 1 hora.\n\n";
        $message .= "Si no solicitaste este cambio, por favor ignora este correo.\n\n";
        $message .= "Saludos,\nSistema Central de Vivienda";
        
        $headers = "From: noreply@sistemavivienda.com\r\n";
        $headers .= "Reply-To: noreply@sistemavivienda.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if (mail($to, $subject, $message, $headers)) {
            echo json_encode(['success' => true, 'message' => 'Se ha enviado un correo con instrucciones para restablecer tu contraseña']);
        } else {
            throw new Exception("Error al enviar el correo electrónico");
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
    }
    
    exit;
}

// Si no es POST, mostrar el formulario
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema Central de Vivienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Recuperar Contraseña</h2>
                        <div id="mensaje" class="alert d-none"></div>
                        <form id="recuperarForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Enviar Instrucciones</button>
                                <a href="index.php" class="btn btn-link">Volver al inicio de sesión</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('recuperarForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const mensaje = document.getElementById('mensaje');
            mensaje.className = 'alert d-none';
            
            const formData = new FormData(this);
            
            fetch('php/recuperar_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                mensaje.className = 'alert ' + (data.error ? 'alert-danger' : 'alert-success');
                mensaje.textContent = data.error || data.message;
                mensaje.classList.remove('d-none');
                
                if (!data.error) {
                    document.getElementById('recuperarForm').reset();
                }
            })
            .catch(error => {
                mensaje.className = 'alert alert-danger';
                mensaje.textContent = 'Error al procesar la solicitud';
                mensaje.classList.remove('d-none');
            });
        });
    </script>
</body>
</html> 