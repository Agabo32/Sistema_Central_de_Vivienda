<?php
require_once '../php/conf/session_helper.php';
require_once '../php/conf/conexion.php';
require_once '../php/conf/db_config.php';

// Verificación de autenticación
verificar_autenticacion();

// Verificar si el usuario es administrador
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'root';

// Obtener usuarios
$sql_usuarios = "SELECT id_usuario, Nombre, Apellido, nombre_usuario, correo, telefono, rol, activo, fecha_registro FROM usuario ORDER BY fecha_registro DESC";
$result_usuarios = $conexion->query($sql_usuarios);

// Procesar respaldo de base de datos si se solicita
if (isset($_POST['crear_respaldo'])) {
    $fecha = date('Y-m-d_H-i-s');
    $nombre_archivo = "backup_" . $fecha . ".sql";
    $ruta_respaldo = "../respaldos/" . $nombre_archivo;
    
    // Asegurarse de que el directorio existe
    if (!file_exists("../respaldos")) {
        mkdir("../respaldos", 0777, true);
    }
    
    // Comando para realizar el respaldo
    $comando = "mysqldump --user=" . DB_USERNAME . " --password=" . DB_PASSWORD . " " . DB_NAME . " > " . $ruta_respaldo;
    
    if (system($comando)) {
        $mensaje_respaldo = "Respaldo creado exitosamente: " . $nombre_archivo;
    } else {
        $mensaje_respaldo = "Error al crear el respaldo";
    }
}

// Procesar cambios de tema si se solicita
if (isset($_POST['guardar_tema'])) {
    $nuevo_tema = $_POST['tema'];
    $_SESSION['tema'] = $nuevo_tema;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEVU - Configuración</title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="../css/menu_principal.css">
    <link rel="stylesheet" href="../css/temas.css">
    <style>
        /* Estilos para las etiquetas del formulario */
        .modal-body .form-label {
            color: #000000 !important;
            font-weight: 500;
        }

        .modal-body h6 {
            color: #000000 !important;
            font-weight: 600;
        }

        .modal-body .text-muted {
            color: #666666 !important;
        }

        .config-section {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .config-section h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .user-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .backup-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .theme-preview {
            width: 100px;
            height: 60px;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .theme-preview:hover {
            transform: scale(1.05);
        }

        .theme-preview.active {
            border-color: var(--primary-color);
        }

        .btn-config {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-config:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark glass-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../php/menu_principal.php">
                <img src="../imagenes/logo_menu.png.ico" alt="SIGEVU" class="me-2">
                SIGEVU
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../php/menu_principal.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../php/conf/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container" style="margin-top: 100px;">
        <h2 class="text-center mb-4">Panel de Configuración</h2>

        <!-- Gestión de Usuarios -->
        <div class="config-section">
            <h3><i class="fas fa-users-cog"></i> Gestión de Usuarios</h3>
            <?php if ($esAdmin): ?>
            <div class="row mb-3">
                <div class="col-md-4">
                    <button class="btn btn-config w-100" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </button>
                </div>
            </div>
            <?php endif; ?>
            <div class="row">
                <?php while($usuario = $result_usuarios->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="user-card">
                        <h5><?php echo htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido']); ?></h5>
                        <p class="mb-1"><i class="fas fa-user"></i> <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></p>
                        <p class="mb-1"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($usuario['correo']); ?></p>
                        <p class="mb-1"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($usuario['telefono']); ?></p>
                        <p class="mb-2">
                            <i class="fas fa-user-tag"></i> 
                            <span class="badge bg-<?php echo $usuario['rol'] === 'root' ? 'danger' : 'primary'; ?>">
                                <?php echo $usuario['rol'] === 'root' ? 'Administrador' : 'Usuario'; ?>
                            </span>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-circle"></i>
                            <span class="status-badge <?php echo $usuario['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> 
                            Registrado: <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                        </small>
                        <?php if ($esAdmin): ?>
                        <div class="btn-group mt-2 w-100">
                            <button class="btn btn-config btn-sm" onclick="editarUsuario(<?php echo $usuario['id_usuario']; ?>)">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="eliminarUsuario(<?php echo $usuario['id_usuario']; ?>)">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Respaldos -->
        <div class="config-section">
            <h3><i class="fas fa-database"></i> Respaldos de Base de Datos</h3>
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="POST" class="d-inline">
                        <button type="submit" name="crear_respaldo" class="btn btn-config">
                            <i class="fas fa-download"></i> Crear Nuevo Respaldo
                        </button>
                    </form>
                </div>
            </div>
            <?php if(isset($mensaje_respaldo)): ?>
            <div class="alert alert-info">
                <?php echo $mensaje_respaldo; ?>
            </div>
            <?php endif; ?>
            <div class="backup-list">
                <?php
                $respaldos = glob("../respaldos/*.sql");
                foreach($respaldos as $respaldo):
                    $nombre = basename($respaldo);
                    $fecha = date("Y-m-d H:i:s", filemtime($respaldo));
                ?>
                <div class="backup-card">
                    <div>
                        <h5><?php echo $nombre; ?></h5>
                        <small><?php echo $fecha; ?></small>
                    </div>
                    <div class="btn-group">
                        <a href="<?php echo $respaldo; ?>" download class="btn btn-config btn-sm">
                            <i class="fas fa-download"></i>
                        </a>
                        <button class="btn btn-danger btn-sm" onclick="eliminarRespaldo('<?php echo $nombre; ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Personalización -->
        <div class="config-section">
            <h3><i class="fas fa-paint-brush"></i> Personalización</h3>
            <form method="POST">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="theme-preview" style="background: linear-gradient(45deg, #ee4242, #1b1918);" 
                             onclick="seleccionarTema('default')">
                        </div>
                        <p>Tema Predeterminado</p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="theme-preview" style="background: linear-gradient(45deg, #2196F3, #311B92);" 
                             onclick="seleccionarTema('azul')">
                        </div>
                        <p>Tema Azul</p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="theme-preview" style="background: linear-gradient(45deg, #4CAF50, #1B5E20);" 
                             onclick="seleccionarTema('verde')">
                        </div>
                        <p>Tema Verde</p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="theme-preview" style="background: linear-gradient(45deg, #9C27B0, #4A148C);" 
                             onclick="seleccionarTema('morado')">
                        </div>
                        <p>Tema Morado</p>
                    </div>
                </div>
                <input type="hidden" name="tema" id="temaSeleccionado">
                <button type="submit" name="guardar_tema" class="btn btn-config">
                    <i class="fas fa-save"></i> Guardar Preferencias
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Nuevo Usuario -->
    <?php if ($esAdmin): ?>
    <div class="modal fade" id="nuevoUsuarioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoUsuario" class="needs-validation" novalidate>
                        <!-- Información Personal -->
                        <div class="row mb-4">
                            <h6 class="mb-3"><i class="fas fa-user me-2"></i>Información Personal</h6>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" required maxlength="50">
                                <div class="invalid-feedback">El nombre es requerido</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellido *</label>
                                <input type="text" class="form-control" name="apellido" required maxlength="50">
                                <div class="invalid-feedback">El apellido es requerido</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cédula *</label>
                                <input type="text" class="form-control" name="cedula" required pattern="[0-9]+" maxlength="20">
                                <div class="invalid-feedback">Ingrese una cédula válida (solo números)</div>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div class="row mb-4">
                            <h6 class="mb-3"><i class="fas fa-address-card me-2"></i>Información de Contacto</h6>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Electrónico *</label>
                                <input type="email" class="form-control" name="correo" required maxlength="100">
                                <div class="invalid-feedback">Ingrese un correo válido</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" name="telefono" required pattern="[0-9]+" maxlength="20">
                                <div class="invalid-feedback">Ingrese un número de teléfono válido</div>
                            </div>
                        </div>

                        <!-- Información de Acceso -->
                        <div class="row mb-4">
                            <h6 class="mb-3"><i class="fas fa-lock me-2"></i>Información de Acceso</h6>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de Usuario *</label>
                                <input type="text" class="form-control" name="nombre_usuario" required maxlength="50">
                                <div class="invalid-feedback">El nombre de usuario es requerido</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contraseña *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">La contraseña debe tener al menos 8 caracteres</div>
                                <small class="text-muted">La contraseña debe tener al menos 8 caracteres</small>
                            </div>
                        </div>

                        <!-- Rol y Estado -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol *</label>
                                <select class="form-select" name="rol" required>
                                    <option value="">Seleccione un rol</option>
                                    <option value="root">Administrador</option>
                                    <option value="usuario">Usuario</option>
                                </select>
                                <div class="invalid-feedback">Seleccione un rol</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="activo">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarNuevoUsuario()">
                        <i class="fas fa-save me-2"></i>Guardar Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para seleccionar tema
        function seleccionarTema(tema) {
            document.getElementById('temaSeleccionado').value = tema;
            document.querySelectorAll('.theme-preview').forEach(preview => {
                preview.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Aplicar el tema inmediatamente
            document.documentElement.setAttribute('data-tema', tema);
            
            // Guardar el tema en localStorage
            localStorage.setItem('tema', tema);
        }

        // Cargar el tema guardado al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            const temaGuardado = localStorage.getItem('tema') || 'default';
            document.documentElement.setAttribute('data-tema', temaGuardado);
            document.getElementById('temaSeleccionado').value = temaGuardado;
            
            // Marcar como activo el tema actual
            const previewActivo = document.querySelector(`.theme-preview[onclick="seleccionarTema('${temaGuardado}')"]`);
            if (previewActivo) {
                previewActivo.classList.add('active');
            }
        });

        // Función para editar usuario
        function editarUsuario(id) {
            // Implementar lógica de edición
            alert('Editando usuario ' + id);
        }

        // Función para eliminar usuario
        function eliminarUsuario(id) {
            if(confirm('¿Está seguro de eliminar este usuario?')) {
                // Implementar lógica de eliminación
                alert('Usuario ' + id + ' eliminado');
            }
        }

        // Función para eliminar respaldo
        function eliminarRespaldo(nombre) {
            if(confirm('¿Está seguro de eliminar este respaldo?')) {
                // Implementar lógica de eliminación
                alert('Respaldo ' + nombre + ' eliminado');
            }
        }

        // Función para mostrar/ocultar contraseña
        function togglePassword(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Función para guardar nuevo usuario
        function guardarNuevoUsuario() {
            const form = document.getElementById('formNuevoUsuario');
            
            // Validar el formulario
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            // Recoger datos del formulario
            const formData = new FormData(form);
            
            // Deshabilitar el botón de guardar y mostrar indicador de carga
            const submitBtn = document.querySelector('#nuevoUsuarioModal .btn-primary');
            const originalBtnContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

            // Enviar datos al servidor
            fetch('conf/guardar_usuario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Mostrar mensaje de éxito
                    alert('Usuario guardado exitosamente');
                    // Cerrar modal y recargar página
                    const modal = bootstrap.Modal.getInstance(document.getElementById('nuevoUsuarioModal'));
                    modal.hide();
                    location.reload();
                } else {
                    // Mostrar mensaje de error
                    alert('Error: ' + data.message);
                    // Restaurar botón
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el usuario');
                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            });
        }

        // Inicializar validación de formularios de Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>
</html>
