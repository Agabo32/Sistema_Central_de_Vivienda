<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - SIGEVU</title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="..//css/style_registro.css">
</head>

<body>
    <div class="login-container row g-0">
        <!-- Lado izquierdo -->
        <div class="col-lg-6 login-left">
            <div class="logo-container">
                <img src="../imagenes/logo_menu.png.ico" alt="Logo" class="logo-img">
            </div>
            <h4 class="mb-4">SIGEVU</h4>
            <h5>REGISTRO DE USUARIO</h5>
            <p class="institucion mb-4">Gerencia de Vivienda y Urbanismo</p>
            
        </div>

        <!-- Formulario -->
        <div class="col-lg-6 login-right">
        <form action="conf/registro_usuario.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Nombres</label>
                    <input type="text" name="nombre" class="form-control modern-input" placeholder="Nombres" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellido" class="form-control modern-input" placeholder="Apellidos" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre de usuario</label>
                    <input type="text" name="usuario" class="form-control modern-input" placeholder="Nombre de Usuario" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cédula</label>
                    <input type="text" name="cedula" class="form-control modern-input" placeholder="C.I" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control modern-input" placeholder="Email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" name="telefono" class="form-control modern-input" placeholder="" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="contrasena" class="form-control modern-input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-animado">Registrarse</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
