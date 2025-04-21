<?php
require_once '../php/conf/conexion.php'; // Asegúrate que la ruta sea correcta
$sql = "SELECT * FROM beneficiario";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiarios - SIGEVU</title>
    <link rel="icon" type="image/x-icon" href="/imagenes/favicon.ico">
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
            background: url('../imagenes/fondo1.jpg') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            position: relative;
            color: var(--text-color);
            min-height: 100vh;
            height: 100vh;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.5);
            z-index: 1;
            pointer-events: none;
        }

        .main-content {
            position: relative;
            z-index: 2;
            padding: 2rem;
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
    </style>
</head>

<body>
    <!-- Barra de navegación superior -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="height: 56px; padding: 0.5rem 1rem;">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="../imagenes/logo_menu.png.ico" alt="SIGEVU" style="height: 30px; width: auto;" class="img-fluid">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="padding: 0.25rem;">
                <span class="navbar-toggler-icon" style="font-size: 1.2rem;"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="menu_principal.php" >Inicio</a>
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


    <!-- Contenedor principal -->
    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Título y botones de acción -->
            <div class="row mb-4 align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0">Listado de Beneficiarios</h2>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalNuevoBeneficiario">
                        <i class="fas fa-plus me-2"></i>Nuevo Beneficiario
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImportar">
                        <i class="fas fa-file-import me-2"></i>Importar Datos
                    </button>
                </div>
            </div>

            <!-- Tabla de beneficiarios -->
            <div class="card">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Beneficiarios Registrados</h5>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="buscar" placeholder="Buscar beneficiario...">
                            <button class="btn btn-outline-primary btn-sm" type="button" id="btnBuscar">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="border-collapse: separate; border-spacing: 0 8px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 12px; width: 50px;">ID</th>
                                    <th class="text-center" style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 12px; width: 120px;">Cédula</th>
                                    <th class="text-center" style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 12px; width: 300px;">Nombre Completo</th>
                                    <th class="text-center" style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 12px; width: 150px;">Teléfono</th>
                                    <th class="text-center" style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 12px; width: 120px;">Ver más</th>
                                </tr>
                            </thead>
                            <tbody id="tablaBeneficiarios" class="text-center">
                                <!-- Aquí se cargarán los datos de la base de datos -->
                                <?php
                                $sql = "SELECT * FROM beneficiario";
                                $resultado = $conexion->query($sql);

                                if ($resultado && $resultado->num_rows > 0) {
                                    while ($row = $resultado->fetch_assoc()) {
                                        echo "<tr style='border: 1px solid #dee2e6; border-radius: 4px; margin-bottom: 8px;'>";
                                        echo "<td style='padding: 12px; border-right: 1px solid #dee2e6; border-radius: 4px 0 0 4px; width: 50px; text-align: center; word-wrap: break-word; overflow-wrap: break-word; white-space: normal;'>" . htmlspecialchars($row['id_beneficiario']) . "</td>";
                                        echo "<td style='padding: 12px; border-right: 1px solid #dee2e6; width: 120px; text-align: center; word-wrap: break-word; overflow-wrap: break-word; white-space: normal;'>" . htmlspecialchars($row['cedula']) . "</td>";
                                        echo "<td style='padding: 12px; border-right: 1px solid #dee2e6; width: 300px; text-align: left; word-wrap: break-word; overflow-wrap: break-word; white-space: normal;'>" . htmlspecialchars($row['nombre_completo']) . "</td>";                           
                                        echo "<td style='padding: 12px; border-right: 1px solid #dee2e6; width: 150px; text-align: center; word-wrap: break-word; overflow-wrap: break-word; white-space: normal;'>" . htmlspecialchars($row['telefono']) . "</td>";
                                        echo "<td style='padding: 12px; border-radius: 0 4px 4px 0; width: 120px; text-align: center; word-wrap: break-word; overflow-wrap: break-word; white-space: normal;'><a href='datos_beneficiario.php?id=" . $row['id_beneficiario'] . "' class='btn btn-primary btn-sm'><i class='fas fa-eye me-1'></i>Detalles</a></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr style='border: 1px solid #dee2e6; border-radius: 4px; margin-bottom: 8px;'><td colspan='4' style='padding: 12px;'>No hay beneficiarios registrados.</td></tr>";
                                }

        $conexion->close();
        ?> 
                            </tbody>
                            <tr>
                                <td colspan="4" class="text-center py-4" style="border: none !important;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Nuevo Beneficiario -->
        <div class="modal fade" id="modalNuevoBeneficiario" tabindex="-1" aria-labelledby="modalNuevoBeneficiarioLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalNuevoBeneficiarioLabel">Nuevo Beneficiario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formNuevoBeneficiario">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cedula" class="form-label">Cédula</label>
                                    <input type="text" class="form-control" id="cedula" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" id="nombre" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="estado_civil" class="form-label">Estado Civil</label>
                                    <select class="form-select" id="estado_civil" required>
                                        <option value="">Seleccione...</option>
                                        <option value="soltero">Soltero</option>
                                        <option value="casado">Casado</option>
                                        <option value="divorciado">Divorciado</option>
                                        <option value="viudo">Viudo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarBeneficiario()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Importar -->
        <div class="modal fade" id="modalImportar" tabindex="-1" aria-labelledby="modalImportarLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalImportarLabel">Importar Datos</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formImportar" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="archivo" class="form-label">Seleccionar archivo CSV</label>
                                <input type="file" class="form-control" id="archivo" name="archivo" accept=".csv" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" onclick="importarDatos()">Importar</button>
                    </div>
                </div>
            </div>
        
        </div>
    </div>

    <!-- Bootstrap JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS personalizado -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        // Funciones para manejar la búsqueda
        document.getElementById('buscar').addEventListener('input', function() {
            const texto = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaBeneficiarios tr');
            
            filas.forEach(fila => {
                const cedula = fila.cells[0].textContent.toLowerCase();
                const nombre = fila.cells[1].textContent.toLowerCase();
                const estadoCivil = fila.cells[2].textContent.toLowerCase();
                const telefono = fila.cells[3].textContent.toLowerCase();
                
                if (cedula.includes(texto) || 
                    nombre.includes(texto) || 
                    estadoCivil.includes(texto) || 
                    telefono.includes(texto)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });

        // Función para el botón de búsqueda
        document.getElementById('btnBuscar').addEventListener('click', function() {
            document.getElementById('buscar').focus();
        });
    </script>
</body>
</html>