<?php
require_once '../php/conf/session_helper.php';
require_once '../php/conf/conexion.php';
require_once '../php/conf/db_config.php';

// Verificación de autenticación
verificar_autenticacion();

// Obtener usuarios
$sql_usuarios = "SELECT id_usuario, nombre, correo, rol FROM usuario";
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
    // Aquí guardarías la preferencia del tema en la base de datos
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
    <style>
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
            <div class="row mb-3">
                <div class="col-md-4">
                    <button class="btn btn-config w-100" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </button>
                </div>
            </div>
            <div class="row">
                <?php while($usuario = $result_usuarios->fetch_assoc()): ?>
                <div class="col-md-4 mb-3">
                    <div class="user-card">
                        <h5><?php echo htmlspecialchars($usuario['nombre']); ?></h5>
                        <p class="mb-2"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($usuario['correo']); ?></p>
                        <p class="mb-2"><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($usuario['rol']); ?></p>
                        <div class="btn-group">
                            <button class="btn btn-config btn-sm" onclick="editarUsuario(<?php echo $usuario['id_usuario']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="eliminarUsuario(<?php echo $usuario['id_usuario']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
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
    <div class="modal fade" id="nuevoUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoUsuario">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-control" name="rol" required>
                                <option value="admin">Administrador</option>
                                <option value="usuario">Usuario</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-config" onclick="guardarNuevoUsuario()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

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
        }

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

        // Función para guardar nuevo usuario
        function guardarNuevoUsuario() {
            // Implementar lógica para guardar nuevo usuario
            alert('Guardando nuevo usuario...');
            $('#nuevoUsuarioModal').modal('hide');
        }
    </script>
</body>
</html> 