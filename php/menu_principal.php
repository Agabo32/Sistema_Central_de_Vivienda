<?php
require_once '../php/conf/session_helper.php';
require_once '../php/conf/conexion.php';

// Verificación de autenticación
verificar_autenticacion();

// Consulta para contar el total de beneficiarios
$sql_total = "SELECT COUNT(*) as total FROM beneficiarios";
$result_total = $conexion->query($sql_total);
$total_beneficiarios = $result_total->fetch_assoc()['total'];

// Consulta alternativa para "avance" - usando una columna que sí existe
// Por ejemplo, podemos contar beneficiarios con teléfono registrado
$sql_avance = "SELECT COUNT(*) as avance FROM beneficiarios WHERE telefono IS NOT NULL AND telefono != ''";
$result_avance = $conexion->query($sql_avance);
$avance = $result_avance->fetch_assoc()['avance'];

// Calculamos el porcentaje de avance
$porcentaje_avance = ($total_beneficiarios > 0) ? round(($avance / $total_beneficiarios) * 100) : 0;

// Consulta para otro dato estadístico - por ejemplo, beneficiarios con cédula
$sql_otros = "SELECT COUNT(*) as otros FROM beneficiarios WHERE cedula IS NOT NULL AND cedula != ''";
$result_otros = $conexion->query($sql_otros);
$otros_datos = $result_otros->fetch_assoc()['otros'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEVU - Menú Principal</title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="..//css/menu_principal.css">
</head>
<body>
    <!-- Barra de navegación superior -->
    <nav class="navbar navbar-expand-lg navbar-dark glass-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../php/menu_principal.php">
                <img src="../imagenes/logo_menu.png.ico" alt="SIGEVU" style="height: 30px;" class="me-2">
                <span class="fw-bold">SIGEVU</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../php/menu_principal.php">
                            <i class="fas fa-home me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../php/beneficiarios.php">
                            <i class="fas fa-users me-1"></i> Beneficiarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../php/reportes.php">
                            <i class="fas fa-chart-bar me-1"></i> Reportes
                        </a>
                    </li>
                </ul>
                <div class="d-flex ms-3">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <a class="nav-link ms-2" href="../php/conf/logout.php" style="color : #f8f9fa">
                        <i class="fas fa-sign-out-alt me-1" style="color : #f8f9fa"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Carrusel de imágenes -->
    <div class="main-content">
        <div id="carouselExample" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <!-- Indicadores -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="2" aria-label="Slide 3"></button>
                <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="3" aria-label="Slide 4"></button>
                <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="4" aria-label="Slide 5"></button>
            </div>

            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="carousel-overlay"></div>
                    <img src="../imagenes/C0XUWCQWgAAnTeb.jpg" class="d-block w-100" alt="Construcción 1">
                    <div class="carousel-caption">
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="carousel-overlay"></div>
                    <img src="../imagenes/construccion2.jpg" class="d-block w-100" alt="Construcción 2">
                    <div class="carousel-caption">
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="carousel-overlay"></div>
                    <img src="../imagenes/construccion3.jpg" class="d-block w-100" alt="Construcción 3">
                    <div class="carousel-caption">
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="carousel-overlay"></div>
                    <img src="../imagenes/construccion4.jpg" class="d-block w-100" alt="Construcción 4">
                    <div class="carousel-caption">
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="carousel-overlay"></div>
                    <img src="../imagenes/construccion5.jpg" class="d-block w-100" alt="Construcción 5">
                    <div class="carousel-caption">
                    </div>
                </div>
            </div>

            <!-- Controles modernos -->
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    </div>

    <!-- Sección de Módulos -->
    <div class="container modules-section">
        <h2 class="text-center mb-5">Módulos del Sistema</h2>
        <div class="row g-4">
            <!-- Módulo de Beneficiarios -->
            <div class="col-md-4">
                <div class="module-card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Beneficiarios</h3>
                    <p>Gestión completa de beneficiarios, actualización de datos y seguimiento de casos.</p>
                    <a href="../php/beneficiarios.php" class="module-btn">
                        Acceder <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Módulo de Reportes -->
            <div class="col-md-4">
                <div class="module-card">
                    <div class="card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Reportes</h3>
                    <p>Generación de informes, estadísticas y análisis de datos del programa.</p>
                    <a href="../php/reportes.php" class="module-btn">
                        Acceder <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Módulo de Configuración -->
            <div class="col-md-4">
                <div class="module-card">
                    <div class="card-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h3>Configuración</h3>
                    <p>Gestión de usuarios, roles y permisos. Configuración de parámetros del sistema, respaldo de datos y personalización de la interfaz.</p>
                    <a href="../php/configuracion.php" class="module-btn">
                        Acceder <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
    .config-features {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .feature-tag {
        background: rgba(238, 66, 66, 0.2);
        padding: 0.3rem 0.8rem;
        border-radius: 15px;
        font-size: 0.85rem;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid rgba(238, 66, 66, 0.3);
    }

    .feature-tag i {
        font-size: 0.8rem;
    }
    </style>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animación de números
        function animateNumber(element, target) {
            let current = 0;
            const step = target / 100;
            
            const interval = setInterval(() => {
                current += step;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(interval);
                } else {
                    element.textContent = Math.round(current);
                }
            }, 16);
        }

        // Datos de ejemplo (en producción estos vendrían de la base de datos)
        const totalBeneficiarios = 1250;
        const casasEnConstruccion = 250;
        const avanceGeneral = 75;

        // Inicializar animaciones
        document.addEventListener('DOMContentLoaded', () => {
            // Animar números
            animateNumber(document.getElementById('totalBeneficiarios'), totalBeneficiarios);
            animateNumber(document.getElementById('casasEnConstruccion'), casasEnConstruccion);
            
            // Animar barra de progreso
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = avanceGeneral + '%';
            document.getElementById('avanceGeneral').textContent = avanceGeneral + '%';
        });
    </script>

</body>
</html>
