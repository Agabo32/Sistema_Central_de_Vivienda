<?php
session_start();

// 1. Configuración de la aplicación
const APP_NAME = 'SIGEVU';
const DEFAULT_ROLE = 'usuario';
const ROLES_PERMITIDOS = ['admin', 'root']; // Ajustar según necesidades

// 2. Configuración de la base de datos
$db_config = [
    'host' => 'localhost',
    'dbname' => '18/06/2025',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
];

// 3. Conexión a la base de datos
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("Error en el sistema. Por favor intente más tarde.");
}

// 4. Funciones auxiliares
function login_exitoso(array $user): void {
    // Validar y asignar rol
    $rol = in_array($user['rol'] ?? '', ROLES_PERMITIDOS) ? $user['rol'] : DEFAULT_ROLE;
    
    // Establecer datos de sesión
    $_SESSION['user'] = [
        'id_usuario' => $user['id_usuario'],
        'nombre' => $user['nombre'],
        'apellido' => $user['apellido'],
        'nombre_usuario' => $user['nombre_usuario'],
        'correo' => $user['correo'],
        'rol' => $rol
    ];
    
    // Redirección según rol
    $redirect = ($rol === 'root') 
        ? 'php/beneficiarios.php' 
        : 'php/menu_principal.php';
    
    header("Location: $redirect");
    exit();
}

function mostrar_error(string $mensaje): void {
    $_SESSION['login_error'] = htmlspecialchars($mensaje, ENT_QUOTES);
    header('Location: index.php');
    exit();
}

function limpiar_input(string $data): string {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// 5. Procesamiento del formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Limpiar inputs
        $input_usuario = limpiar_input($_POST['nombre_usuario'] ?? '');
        $input_password = $_POST['password'] ?? '';
        
        // Validación básica
        if (empty($input_usuario)) {
            throw new Exception('El nombre de usuario es obligatorio');
        }
        
        if (empty($input_password)) {
            throw new Exception('La contraseña es obligatoria');
        }
        
        // Buscar usuario en la base de datos
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE nombre_usuario = ? OR correo = ? LIMIT 1");
        $stmt->execute([$input_usuario, $input_usuario]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Usuario no encontrado');
        }
        
        // Verificación de contraseña
        if (password_verify($input_password, $user['password'])) {
            login_exitoso($user);
        } 
        // Migración de contraseñas en texto plano (solo para desarrollo)
        elseif ($input_password === $user['password']) {
            $nuevoHash = password_hash($input_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?")
               ->execute([$nuevoHash, $user['id_usuario']]);
            login_exitoso($user);
        } else {
            throw new Exception('Contraseña incorrecta');
        }
        
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        mostrar_error('Error en el sistema. Por favor intente más tarde.');
    } catch (Exception $e) {
        mostrar_error($e->getMessage());
    }
}

// 6. Mostrar errores de sesión si existen
$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']); // Limpiar el error después de mostrarlo
$input_usuario = $_POST['nombre_usuario'] ?? '';

// Manejar mensaje de sesión cerrada
$sesion_cerrada = isset($_GET['msg']) && $_GET['msg'] === 'sesion_cerrada';
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
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/temas.css">
</head>

<body>
<div class="login-container row g-0">
        <!-- Sección izquierda (información) -->
        <div class="col-lg-6 login-left">
            <div class="logo-container">
                <img src="imagenes/logo_menu.png.ico" alt="Logo" class="logo-img">
            </div>
            <h4 class="mb-4"><?= APP_NAME ?></h4>
            <h5>Bienvenido</h5>
            <p class="institucion mb-4">Gerencia de Vivienda y Urbanismo</p>
        </div>
        
        <!-- Sección derecha (formulario) -->
        <div class="col-lg-6 login-right">
            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $login_error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($sesion_cerrada): ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="fas fa-info-circle me-2"></i> Su sesión ha sido cerrada correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control modern-input" id="nombre_usuario" 
                               name="nombre_usuario" placeholder="Nombre de Usuario" 
                               value="<?= htmlspecialchars($input_usuario) ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control modern-input" id="password" 
                               name="password" placeholder="••••••••" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mb-4">
                    
                    <div class="mb-3">
                        <a href="php/recuperar_password.php" class="text-muted">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-animado mb-4">Iniciar Sesión</button>
                
              
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Efecto de partículas opcional -->
    <script src="../Sistema_Central_de_Vivienda-main/script/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const temaGuardado = localStorage.getItem('tema') || 'default';
        document.documentElement.setAttribute('data-tema', temaGuardado);

        // Mostrar/ocultar contraseña
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');
        toggleBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    </script>
</body>
</html>