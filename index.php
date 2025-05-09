<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'pasantias';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Login processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($usuario) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['id_usuario'] = $user['id'];
                $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
                $_SESSION['rol'] = $user['rol'];
                
                // Redirect to main menu based on role
                if ($user['rol'] == 'administrador') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: usuario/dashboard.php");
                }
                exit();
            } else {
                $error = "Usuario o contraseña incorrectos";
            }
        } catch(PDOException $e) {
            $error = "Error en el sistema. Por favor intente nuevamente.";
        }
    } else {
        $error = "Por favor complete todos los campos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEVU</title>
    <link rel="icon" type="image/x-icon" href="imagenes/favicon.ico">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Tipografías personalizadas */
        h4 {
            font-weight: 400;
            font-style: normal;
            color: #ff1504;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        h5 { 
            font-weight: 700;
            font-style: normal;
            font-size: 1.8rem;
            color: #fff;
        }
        
        .institucion {
            font-weight: 400;
            font-style: normal;
            font-size: 1.4rem;
            color: #fff;
        }
        
        p, label, input::placeholder {
            font-family: "Winky Sans", sans-serif;
            font-weight: 400;
            font-style: normal;
            color: #333;
        }
        
        /* Fondo general */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('imagenes/fondo1.jpg') no-repeat center center/cover;
            box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.5);
            position: relative;
            background-attachment: fixed;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1;
        }
        
        /* Contenedor creativo para login */
        .login-container {
            position: relative;
            z-index: 2;
            width: 90%;
            max-width: 1000px;
            margin: 2rem auto;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out;
            backdrop-filter: blur(10px);
            background: rgba(34, 7, 7, 0.7);
            border: 1px solid rgba(255, 21, 4, 0.3);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-left {
            background: linear-gradient(135deg, rgba(34, 7, 7, 0.9), rgba(102, 8, 8, 0.9));
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,21,4,0.1) 0%, rgba(255,21,4,0) 70%);
            animation: pulse 8s infinite alternate;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(1.2); opacity: 0.8; }
        }
        
        .login-right {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
        }
        
        .logo-container {
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .logo-container:hover {
            transform: scale(1.05);
        }
        
        .logo-img {
            width: 120px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }
        
        /* Inputs modernos */
        .modern-input {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            transition: all 0.3s ease-in-out;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .modern-input:focus {
            border-color: #ff0000;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.3), inset 0 1px 3px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        
        .input-group-text {
            background-color: #ff1504;
            color: white;
            border: none;
            border-radius: 10px 0 0 10px !important;
        }
        
        /* Botón con efecto */
        .btn-animado {
            background: linear-gradient(45deg, #ff1504, #ff4d4d);
            border: none;
            color: white;
            padding: 12px 25px;
            font-size: 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 21, 4, 0.4);
            position: relative;
            overflow: hidden;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            width: 100%;
        }
        
        .btn-animado:hover {
            background: linear-gradient(45deg, #ff4d4d, #ff1504);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 21, 4, 0.6);
        }
        
        .btn-animado:active {
            transform: scale(0.98);
            box-shadow: 0 3px 10px rgba(255, 21, 4, 0.4);
        }
        
        .btn-animado::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        .btn-animado:focus:not(:active)::after {
            animation: ripple 0.6s ease-out;
        }
        
        @keyframes ripple {
            0% { transform: scale(0, 0); opacity: 0.5; }
            100% { transform: scale(20, 20); opacity: 0; }
        }
        
        /* Enlaces */
        .text-muted {
            color: #6c757d !important;
            transition: color 0.3s ease;
        }
        
        .text-muted:hover {
            color: #ff1504 !important;
        }
        
        /* Efecto de olas decorativo */
        .waves {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="%23ff1504" opacity=".25"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" fill="%23ff1504" opacity=".5"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%23ff1504"/></svg>');
            background-size: cover;
            background-repeat: no-repeat;
            opacity: 0.7;
            z-index: -1;
        }
        
        /* Responsive design */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                width: 95%;
            }
            
            .login-left, .login-right {
                padding: 2rem;
            }
            
            .logo-img {
                width: 100px;
            }
        }
        
        @media (max-width: 768px) {
            .login-container {
                border-radius: 15px;
            }
            
            h4 {
                font-size: 1.5rem;
            }
            
            h5 {
                font-size: 1.5rem;
            }
            
            .institucion {
                font-size: 1.2rem;
            }
            
            .modern-input, .btn-animado {
                padding: 10px 15px;
            }
        }
        
        @media (max-width: 576px) {
            .login-left, .login-right {
                padding: 1.5rem;
            }
            
            .logo-img {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container row g-0">
        <!-- Sección izquierda (información) -->
        <div class="col-lg-6 login-left">
            <div class="logo-container">
                <img src="imagenes/logo_menu.png.ico" alt="Logo" class="logo-img">
            </div>
            <h4 class="mb-4">SIGEVU</h4>
            <h5>Bienvenido</h5>
            <p class="institucion mb-4">Gerencia de Vivienda y Urbanismo</p>
            
        </div>
        
        <!-- Sección derecha (formulario) -->
        <div class="col-lg-6 login-right">
            <form>
                <div class="mb-4">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control modern-input" id="usuario" placeholder="Nombre de Usuario" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control modern-input" id="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label" for="remember">Recordarme</label>
                    </div>
                    <a href="#!" class="text-muted">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn btn-animado mb-4">Iniciar Sesión</button>
                
                <div class="text-center">
                    <p class="mb-0">¿No tienes una cuenta? <a href="php/registro.php" class="text-muted">Regístrate</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Efecto de partículas opcional -->
    <script src="../Sistema_Central_de_Vivienda-main/script/script.js"></script>
</body>
</html>