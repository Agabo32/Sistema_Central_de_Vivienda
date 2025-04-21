<?php
require_once '../php/conf/conexion.php'; // Ajusta si tu ruta es distinta

if (!isset($_GET['id'])) {
    echo "ID de beneficiario no especificado.";
    exit;
}

$id = intval($_GET['id']);

$sql = "
    SELECT 
        b.*, 
        u.comunidad, u.parroquia, u.municipio, u.direccion_exacta, u.utm_norte, u.utm_este,  
        p.codigo_obra, p.modelo_constructivo, p.metodo_constructivo, p.fiscalizador, p.fecha_actualizacion, p.fecha_culminacion, p.avance_fisico, p.acta_entregada, p.observaciones_responsables, p.observaciones_fiscalizadores,
        a.limpieza, a.replanteo,  
        c.cerramiento, c.bloqueado, c.colocacion_correas, c.colocacion_techo, c.colocacion_ventanas, c.colocacion_puertas_principales, c.instalaciones_electricas_sanitarias_paredes, c.frisos, c.sobrepiso, c.ceramica_bano, c.colocacion_puertas_internas, c.equipos_accesorios_electricos, c.equipos_accesorios_sanitarios, c.colocacion_lavaplatos, c.pintura
    FROM vivienda v
    JOIN beneficiario b ON v.id_beneficiario = b.id_beneficiario
    JOIN ubicacion u ON v.id_ubicacion = u.id_ubicacion
    JOIN proyecto_construccion p ON v.id_proyecto = p.id_proyecto
    JOIN acondicionamiento a ON v.id_acondicionamiento = a.id_acondicionamiento
    JOIN cerramiento_techo_acabado c ON v.id_cierre = c.id_cierre
    WHERE b.id_beneficiario = ?
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $data = $resultado->fetch_assoc();

    ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos del Beneficiario - SIGEVU</title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personalizado -->
    <style>
        :root {
            --primary-color: #1565C0;
            --secondary-color: #0074D9;
            --accent-color: rgba(247, 5, 5, 0.9);
            --background-dark: #1A237E;
            --background-light: #1565C0;
            --text-color: #FFFFFF;
            --shadow-color: rgba(221, 7, 7, 0.7);
            --border-color: rgba(221, 7, 7, 0.7);
        }

        body {
            background: linear-gradient(135deg, var(--background-dark), var(--background-light));
            color: var(--text-color);
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }

        .navbar {
            background: var(--primary-color);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--secondary-color);
            padding: 1.2rem 2rem;
            box-shadow: 0 2px 10px var(--shadow-color);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            margin-right: 2rem;
            transition: all 0.3s ease;
        }

        .nav-link {
            color: white !important;
            padding: 0.7rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            border: 1px solid var(--border-color);
            background: rgba(80, 80, 80, 0.9);
        }

        .navbar.scrolled {
            padding: 0.4rem 1.5rem;
            background: rgba(21, 101, 192, 0.95);
            box-shadow: 0 2px 5px var(--shadow-color);
        }

        .navbar.scrolled .navbar-brand {
            margin-right: 1rem;
        }

        .navbar.scrolled .nav-link {
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
        }

        .navbar.scrolled .nav-link i {
            font-size: 0.8rem;
        }

        /* Ajustar el tamaño del logo en el navbar reducido */
        .navbar.scrolled .navbar-brand img {
            height: 25px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .content-wrapper {
            margin-top: 100px;
            padding-top: 20px;
            position: relative;
            z-index: 1;
        }

        .content-wrapper.scrolled {
            margin-top: 60px;
        }

        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
        }

        .card-header {
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid var(--secondary-color);
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }

        .card-body {
            padding: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            width: 20%;
            font-weight: 600;
            color: var(--primary-color);
        }

        .table td {
            width: 80%;
        }

        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
        }

        .btn-link:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }

        .carousel {
            margin-top: 60px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px var(--shadow-color);
            background: var(--shadow-color);
            position: relative;
            z-index: 1;
        }

        .carousel-caption {
            bottom: 100px;
            background: var(--shadow-color);
            padding: 2rem;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación superior -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="../imagenes/logo_menu.png.ico" alt="SIGEVU" style="height: 40px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="menu_principal.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Beneficiarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Proyectos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Reportes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Cerrar Sesión</a>
                    </li>
                </ul>
                <div class="nav-item ms-3">
                    <a class="nav-link" href="#">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container">
        <div class="row">
            <div class="col-12 mb-4">
                <h2 class="text-center text-white mb-4" style="background: var(--primary-color); padding: 1rem; border-radius: 10px;">Datos del Beneficiario</h2>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Información Personal</h3>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalActualizar">
                                <i class="fas fa-edit me-2"></i>Actualizar Datos
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar">
                                <i class="fas fa-trash me-2"></i>Eliminar Registro
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th scope="row">Nombre:</th>
                                    <td><?php echo $data['nombre_completo'] ; ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Cédula:</th>
                                    <td><?php echo $data['cedula']; ?></td>
                                </tr>
                                <tr>
                                    <th>Teléfono:</th>
                                    <td><?php echo $data['telefono']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Información de la Vivienda</h3>
                    </div>
                    <h3>Ubicación</h3>
                    <p><strong>Comunidad:</strong> <?php echo $data['comunidad']; ?></p>
                    <p><strong>Manzana:</strong> <?php echo $data['manzana']; ?></p>
                    <p><strong>Parcela:</strong> <?php echo $data['parcela']; ?></p>
                    <p><strong>Zona:</strong> <?php echo $data['zona']; ?></p>

                    <h3>Proyecto de Construcción</h3>
                    <p><strong>Código de Obra:</strong> <?php echo $data['codigo_obra']; ?></p>
<p><strong>Método Constructivo:</strong> <?php echo $data['metodo_constructivo']; ?></p>
<p><strong>Modelo Constructivo:</strong> <?php echo $data['modelo_constructivo']; ?></p>
<p><strong>Fiscalizador:</strong> <?php echo $data['fiscalizador']; ?></p>

                    <h3>Acondicionamiento</h3>
                    <p><strong>Limpieza:</strong> <?php echo $data['limpieza']; ?></p>
                    <p><strong>Replanteo:</strong> <?php echo $data['replanteo']; ?></p>
                    <p><strong>Relleno:</strong> <?php echo $data['relleno']; ?></p>

                    <h3>Avance Constructivo</h3>
                    <p><strong>Cerramiento:</strong> <?php echo $data['cerramiento']; ?></p>
                    <p><strong>Techo:</strong> <?php echo $data['techo']; ?></p>
                    <p><strong>Acabado:</strong> <?php echo $data['acabado']; ?></p>
                    <p><strong>Bloqueado:</strong> <?php echo $data['bloqueado']; ?></p>
                    <p><strong>Colocación de Correas:</strong> <?php echo $data['colocacion_correas']; ?></p>
                    <p><strong>Colocación de Techo:</strong> <?php echo $data['colocacion_techo']; ?></p>
                    <p><strong>Colocación de Ventanas:</strong> <?php echo $data['colocacion_ventanas']; ?></p>
                    <p><strong>Colocación de Puertas Principales:</strong> <?php echo $data['colocacion_puertas_principales']; ?></p>
                    <p><strong>Intalaciones Electricas, Sanitarias, Paredes:</strong> <?php echo $data['intalaciones_electricas_sanitarias_paredes']; ?></p>
                    <p><strong>Frisos:</strong> <?php echo $data['frisos']; ?></p>
                    <p><strong>Sobrepiso:</strong> <?php echo $data['sobrepiso']; ?></p>
                    <p><strong>Ceramica de Baño:</strong> <?php echo $data['ceramica_de_bano']; ?></p>
                    <p><strong>Colocacion de Puertas Internas:</strong> <?php echo $data['colocacion_puertas_internas']; ?></p>
                    <p><strong>Equipos y Accesorios Electricos:</strong> <?php echo $data['equipos_accesorios_electricos']; ?></p>
                    <p><strong>Equipos y Accesorios Sanitarios:</strong> <?php echo $data['equipos_accesorios_sanitarios']; ?></p>
                    <p><strong>Colocacion de Lavaplatos:</strong> <?php echo $data['colocacion_lavaplatos']; ?></p>
                    <p><strong>Pintura:</strong> <?php echo $data['pintura']; ?></p>

                    <h3>Estado de la Vivienda</h3>
                    <p><strong>Estado:</strong> <?php echo $data['estado_vivienda']; ?></p>
                    <p><strong>Fecha de Inicio:</strong> <?php echo $data['fecha_inicio']; ?></p>
                    <p><strong>Fecha de Culminación:</strong> <?php echo $data['fecha_culminacion']; ?></p>
                    <p><strong>Avances:</strong> <?php echo $data['avances']; ?></p>
                    <p><strong>Observaciones:</strong> <?php echo $data['observaciones']; ?></p>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Modales -->
</ADDITIONAL_METADATA>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Modales -->
    <!-- Modal Actualizar -->
    <div class="modal fade" id="modalActualizar" tabindex="-1" aria-labelledby="modalActualizarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalActualizarLabel">Actualizar Datos del Beneficiario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formActualizar">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" value="Carlos García">
                        </div>
                        <div class="mb-3">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control" id="cedula" value="V-12345678">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" value="1980-01-01">
                        </div>
                        <div class="mb-3">
                            <label for="estado_civil" class="form-label">Estado Civil</label>
                            <select class="form-select" id="estado_civil">
                                <option value="casado">Casado</option>
                                <option value="soltero">Soltero</option>
                                <option value="divorciado">Divorciado</option>
                                <option value="viudo">Viudo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" value="0412-1234567">
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" rows="3">Calle Principal, Barrio Centro, Caracas</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" value="carlos@example.com">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="actualizarDatos()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarLabel">Eliminar Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="eliminarRegistro()">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS personalizado -->
    <script>
        // Manejar el scroll del navbar y el margen del contenido
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            const contentWrapper = document.querySelector('.content-wrapper');
            
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
                contentWrapper.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
                contentWrapper.classList.remove('scrolled');
            }
        });

        // Funciones para manejar los botones
        function actualizarDatos() {
            // Aquí iría la lógica para actualizar los datos
            alert('Funcionalidad de actualizar datos no implementada');
        }

        function eliminarRegistro() {
            // Aquí iría la lógica para eliminar el registro
            alert('Funcionalidad de eliminar datos no implementada');
        }
    </script>
    <script src="../script/datos_benficiarios.js"></script>
</body>
</html>
<?php
} else {
    echo "No se encontraron datos para este beneficiario.";
}

$stmt->close();
$conexion->close();
?>