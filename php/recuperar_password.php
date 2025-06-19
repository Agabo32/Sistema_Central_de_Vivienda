<?php
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

// Crear conexión PDO
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("Error en el sistema. Por favor intente más tarde.");
}

// Configurar zona horaria
date_default_timezone_set('America/Caracas');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Configuración compatible con index.php
const APP_NAME = 'SIGEVU';
const MIN_PASSWORD_LENGTH = 8;

// Configuración de la base de datos (igual que index.php)
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

// Crear conexión PDO
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
        exit;
    }
}

// Configurar zona horaria
date_default_timezone_set('America/Caracas');

// Función para limpiar entrada (igual que index.php)
function limpiar_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Función para validar contraseña (compatible con index.php)
function validarPassword($password) {
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        return ['valid' => false, 'message' => 'La contraseña debe tener al menos ' . MIN_PASSWORD_LENGTH . ' caracteres.'];
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe contener al menos una letra mayúscula.'];
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe contener al menos una letra minúscula.'];
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe contener al menos un número.'];
    }
    
    return ['valid' => true];
}

// Procesar peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // Paso 1: Buscar usuario y mostrar pregunta de seguridad
        if (isset($_POST['usuario'])) {
            $usuario = limpiar_input($_POST['usuario']);
            
            if ($usuario === '') {
                echo json_encode(['error' => 'Debes ingresar tu usuario o correo electrónico.']);
                exit;
            }
            
            // Buscar usuario (misma lógica que index.php)
            $stmt = $pdo->prepare("SELECT id_usuario, Nombre, Apellido, pregunta_seguridad, activo 
                                  FROM usuario 
                                  WHERE (nombre_usuario = ? OR correo = ?) AND activo = 1 
                                  LIMIT 1");
            $stmt->execute([$usuario, $usuario]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['error' => 'No existe una cuenta activa con ese usuario o correo electrónico.']);
            } else if (empty(trim($user['pregunta_seguridad']))) {
                echo json_encode(['error' => 'No tienes pregunta de seguridad registrada. Contacta al administrador.']);
            } else {
                echo json_encode([
                    'success' => true,
                    'id_usuario' => $user['id_usuario'],
                    'nombre' => trim($user['Nombre'] . ' ' . $user['Apellido']),
                    'pregunta' => $user['pregunta_seguridad']
                ]);
            }
            exit;
        }
        
        // Paso 2: Validar respuesta y cambiar contraseña
        if (isset($_POST['id_usuario'], $_POST['respuesta'], $_POST['nueva_password'])) {
            $id_usuario = intval($_POST['id_usuario']);
            $respuesta = limpiar_input($_POST['respuesta']);
            $nueva_password = $_POST['nueva_password'];
            
            if ($respuesta === '' || $nueva_password === '') {
                echo json_encode(['error' => 'Debes responder la pregunta y colocar una nueva contraseña.']);
                exit;
            }
            
            // Validar fortaleza de contraseña
            $validacion = validarPassword($nueva_password);
            if (!$validacion['valid']) {
                echo json_encode(['error' => $validacion['message']]);
                exit;
            }
            
            // Obtener respuesta de seguridad del usuario
            $stmt = $pdo->prepare("SELECT respuesta_seguridad, activo 
                                  FROM usuario 
                                  WHERE id_usuario = ? AND activo = 1 
                                  LIMIT 1");
            $stmt->execute([$id_usuario]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['error' => 'Usuario no encontrado.']);
            } else {
                // Comparar respuestas (sin distinción de mayúsculas/minúsculas)
                if (strtolower(trim($user['respuesta_seguridad'])) !== strtolower(trim($respuesta))) {
                    echo json_encode(['error' => 'Respuesta de seguridad incorrecta.']);
                } else {
                    // Hash de la nueva contraseña (compatible con index.php)
                    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                    
                    // Actualizar contraseña
                    $stmt = $pdo->prepare("UPDATE usuario 
                                          SET password = ?, 
                                              token_recuperacion = NULL, 
                                              expiracion_token = NULL 
                                          WHERE id_usuario = ?");
                    
                    if ($stmt->execute([$password_hash, $id_usuario])) {
                        if ($stmt->rowCount() > 0) {
                            // Log de seguridad
                            error_log("Contraseña restablecida para usuario ID: $id_usuario");
                            echo json_encode([
                                'success' => true,
                                'message' => 'Contraseña restablecida correctamente. Ya puedes iniciar sesión con tu nueva contraseña.'
                            ]);
                        } else {
                            echo json_encode(['error' => 'No se pudo actualizar la contraseña.']);
                        }
                    } else {
                        echo json_encode(['error' => 'Error interno al actualizar.']);
                    }
                }
            }
            exit;
        }
        
    } catch (PDOException $e) {
        error_log("Error en recuperar_password: " . $e->getMessage());
        echo json_encode(['error' => 'Error interno del servidor. Intenta más tarde.']);
        exit;
    } catch (Exception $e) {
        error_log("Error general en recuperar_password: " . $e->getMessage());
        echo json_encode(['error' => 'Error interno del servidor. Intenta más tarde.']);
        exit;
    }
}

// Si no es POST, mostrar el formulario HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?= APP_NAME ?></title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/temas.css">
    <style>
        /* Animaciones y dinamismo conservados */
        .steps-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: 0.5;
            transition: all 0.3s ease;
        }
        .step.active {
            opacity: 1;
        }
        .step.completed {
            opacity: 1;
        }
        .step-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            color: #888;
            transition: all 0.3s ease;
        }
        .step.active .step-number {
            background: #e30016;
            color: white;
        }
        .step.completed .step-number {
            background: #059669;
            color: white;
        }
        .step-label {
            font-size: 12px;
            margin-top: 5px;
            text-align: center;
            color: #888;
            font-weight: 500;
        }
        .step.active .step-label {
            color: #e30016;
        }
        .step-line {
            width: 50px;
            height: 2px;
            background: #eee;
            margin: 0 10px;
        }
        .user-info-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 1rem;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e30016, #a80012);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .user-avatar i {
            color: white;
        }
        .security-question-card {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .security-question-card p {
            color: #92400e;
            font-weight: 500;
            margin: 0;
        }
        .password-strength {
            margin-top: 8px;
        }
        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        .strength-weak { background: #dc2626; }
        .strength-medium { background: #d97706; }
        .strength-strong { background: #059669; }
        .btn-loading .btn-text { display: none; }
        .btn:not(.btn-loading) .btn-loading { display: none !important; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @media (max-width: 768px) {
            .login-container { flex-direction: column; }
            .login-left { min-height: 200px; }
            .steps-indicator { transform: scale(0.8); }
            .step-line { width: 30px; }
        }
        .login-container {
            position: relative;
            z-index: 50;
        }
        .login-right .card {
            position: relative;
            z-index: 20;
        }
    </style>
</head>
<body>
    <div class="login-container row g-0">
        <!-- Sección izquierda (información) -->
        <div class="col-lg-6 login-left d-none d-lg-flex">
            <div z-index="10">
                <div class="logo-container">
                    <img src="../imagenes/logo_menu.png.ico" alt="Logo" class="logo-img">
                </div>
                <h4 class="mb-4"><?= APP_NAME ?></h4>
                <h5>Recuperación Segura</h5>
                <p class="mb-4 institucion">Gerencia de Vivienda y Urbanismo</p>
                <div class="features-list">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Proceso seguro y confiable</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-user-shield me-2"></i>
                        <span>Validación por pregunta de seguridad</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-lock me-2"></i>
                        <span>Contraseña encriptada</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Sección derecha (formulario) -->
        <div class="col-lg-6 login-right">
            <div class="card">
                <div class="card-body p-4">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="d-lg-none mb-3">
                            <img src="../imagenes/logo_menu.png.ico" alt="Logo" style="width: 60px; height: 60px;">
                        </div>
                        <h3 class="fw-bold">Recuperar Contraseña</h3>
                        <p class="text-muted institucion"><?= APP_NAME ?></p>
                    </div>
                    <!-- Alertas -->
                    <div id="mensaje" class="alert d-none"></div>
                    <!-- Indicador de pasos -->
                    <div class="steps-indicator">
                        <div class="step active" id="step1">
                            <div class="step-number">1</div>
                            <div class="step-label">Buscar Usuario</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step" id="step2">
                            <div class="step-number">2</div>
                            <div class="step-label">Pregunta de Seguridad</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step" id="step3">
                            <div class="step-number">3</div>
                            <div class="step-label">Nueva Contraseña</div>
                        </div>
                    </div>
                    <!-- Formulario 1: Buscar usuario -->
                    <form id="usuarioForm">
                        <div class="mb-3">
                            <label for="usuario" class="form-label">
                                <i class="fas fa-user me-2"></i>Usuario o Correo Electrónico
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-at"></i>
                                </span>
                                <input type="text" class="form-control modern-input" id="usuario" 
                                       name="usuario" placeholder="Ingresa tu usuario o correo" required>
                            </div>
                        </div>
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-animado" id="btnBuscar">
                                <span class="btn-text">
                                    <i class="fas fa-search me-2"></i>Buscar Usuario
                                </span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Buscando...
                                </span>
                            </button>
                        </div>
                    </form>
                    <!-- Formulario 2: Pregunta de seguridad y nueva contraseña -->
                    <form id="seguridadForm" class="d-none">
                        <!-- Información del usuario encontrado -->
                        <div class="user-info-card">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <i class="fas fa-user-circle fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold">Usuario encontrado</h6>
                                    <p class="mb-0 text-muted" id="nombreUsuario"></p>
                                </div>
                            </div>
                        </div>
                        <!-- Pregunta de seguridad -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-question-circle me-2 text-warning"></i>
                                Pregunta de Seguridad
                            </label>
                            <div class="security-question-card">
                                <p id="preguntaLabel"></p>
                            </div>
                            <input type="text" class="form-control modern-input" id="respuesta" 
                                   name="respuesta" placeholder="Escribe tu respuesta" required>
                        </div>
                        <!-- Nueva contraseña -->
                        <div class="mb-3">
                            <label for="nueva_password" class="form-label">
                                <i class="fas fa-lock me-2 text-success"></i>Nueva Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control modern-input" 
                                       id="nueva_password" name="nueva_password" 
                                       placeholder="Mínimo 8 caracteres" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthBar"></div>
                                </div>
                                <small class="text-muted" id="strengthText">Ingresa una contraseña</small>
                            </div>
                        </div>
                        <!-- Confirmar contraseña -->
                        <div class="mb-3">
                            <label for="confirmar_password" class="form-label">
                                <i class="fas fa-lock me-2 text-success"></i>Confirmar Nueva Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control modern-input" 
                                       id="confirmar_password" name="confirmar_password" 
                                       placeholder="Repite la contraseña" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword2">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" id="id_usuario" name="id_usuario">
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-success btn-animado" id="btnRestablecer">
                                <span class="btn-text">
                                    <i class="fas fa-key me-2"></i>Restablecer Contraseña
                                </span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Restableciendo...
                                </span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnVolver">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </button>
                        </div>
                    </form>
                    <!-- Enlaces adicionales -->
                    <div class="text-center mt-4">
                        <a href="../index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al inicio de sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Aplicar tema guardado (compatible con index.php)
    document.addEventListener('DOMContentLoaded', function() {
        const temaGuardado = localStorage.getItem('tema') || 'default';
        document.documentElement.setAttribute('data-tema', temaGuardado);
    });

    // --- JS funcionalidad de recuperación ---
    const usuarioForm = document.getElementById('usuarioForm');
    const seguridadForm = document.getElementById('seguridadForm');
    const mensaje = document.getElementById('mensaje');
    let currentStep = 1;

    function updateStep(step) {
        document.querySelectorAll('.step').forEach((el, index) => {
            el.classList.remove('active', 'completed');
            if (index + 1 < step) {
                el.classList.add('completed');
            } else if (index + 1 === step) {
                el.classList.add('active');
            }
        });
        currentStep = step;
    }

    function showMessage(type, text) {
        mensaje.className = `alert alert-${type}`;
        mensaje.textContent = text;
        mensaje.classList.remove('d-none');
        mensaje.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideMessage() {
        mensaje.classList.add('d-none');
    }

    function setButtonLoading(buttonId, loading) {
        const button = document.getElementById(buttonId);
        if (loading) {
            button.classList.add('btn-loading');
            button.disabled = true;
        } else {
            button.classList.remove('btn-loading');
            button.disabled = false;
        }
    }

    function calculatePasswordStrength(password) {
        let score = 0;
        const feedback = [];
        if (password.length >= 8) score += 25;
        else feedback.push("al menos 8 caracteres");
        if (/[a-z]/.test(password)) score += 25;
        else feedback.push("una letra minúscula");
        if (/[A-Z]/.test(password)) score += 25;
        else feedback.push("una letra mayúscula");
        if (/[0-9]/.test(password)) score += 25;
        else feedback.push("un número");
        if (score < 50) {
            return {
                percentage: score,
                class: "strength-weak",
                text: "Débil - Necesita: " + feedback.join(", ")
            };
        } else if (score < 100) {
            return {
                percentage: score,
                class: "strength-medium",
                text: "Media - Necesita: " + feedback.join(", ")
            };
        } else {
            return {
                percentage: 100,
                class: "strength-strong",
                text: "Fuerte - ¡Excelente contraseña!"
            };
        }
    }

    function togglePasswordVisibility(inputId, buttonId) {
        const input = document.getElementById(inputId);
        const button = document.getElementById(buttonId);
        const icon = button.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    usuarioForm.addEventListener('submit', function(e) {
        e.preventDefault();
        hideMessage();
        setButtonLoading('btnBuscar', true);
        const formData = new FormData(usuarioForm);
        fetch('recuperar_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                usuarioForm.classList.add('d-none');
                seguridadForm.classList.remove('d-none');
                seguridadForm.classList.add('fade-in');
                document.getElementById('preguntaLabel').textContent = data.pregunta;
                document.getElementById('nombreUsuario').textContent = data.nombre;
                document.getElementById('id_usuario').value = data.id_usuario;
                updateStep(2);
                showMessage('success', 'Usuario encontrado correctamente. Responde tu pregunta de seguridad.');
            } else {
                showMessage('danger', data.error);
            }
        })
        .catch(() => {
            showMessage('danger', 'Error al procesar la solicitud.');
        })
        .finally(() => {
            setButtonLoading('btnBuscar', false);
        });
    });

    seguridadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const password = document.getElementById('nueva_password').value;
        const confirmPassword = document.getElementById('confirmar_password').value;
        if (password !== confirmPassword) {
            showMessage('danger', 'Las contraseñas no coinciden.');
            return;
        }
        hideMessage();
        setButtonLoading('btnRestablecer', true);
        updateStep(3);
        const formData = new FormData(seguridadForm);
        fetch('recuperar_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.message);
                seguridadForm.reset();
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 3000);
            } else {
                showMessage('danger', data.error);
                updateStep(2);
            }
        })
        .catch(() => {
            showMessage('danger', 'Error al procesar la solicitud.');
            updateStep(2);
        })
        .finally(() => {
            setButtonLoading('btnRestablecer', false);
        });
    });

    document.getElementById('btnVolver').addEventListener('click', function() {
        seguridadForm.classList.add('d-none');
        usuarioForm.classList.remove('d-none');
        usuarioForm.classList.add('fade-in');
        updateStep(1);
        hideMessage();
        seguridadForm.reset();
    });

    document.getElementById('nueva_password').addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        strengthBar.style.width = strength.percentage + '%';
        strengthBar.className = 'strength-fill ' + strength.class;
        strengthText.textContent = strength.text;
    });

    document.getElementById('confirmar_password').addEventListener('input', function() {
        const password = document.getElementById('nueva_password').value;
        const confirmPassword = this.value;
        if (confirmPassword && password !== confirmPassword) {
            this.setCustomValidity('Las contraseñas no coinciden');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
            if (confirmPassword) {
                this.classList.add('is-valid');
            }
        }
    });

    document.getElementById('togglePassword1').addEventListener('click', function() {
        togglePasswordVisibility('nueva_password', 'togglePassword1');
    });
    document.getElementById('togglePassword2').addEventListener('click', function() {
        togglePasswordVisibility('confirmar_password', 'togglePassword2');
    });
    </script>
</body>
</html>
