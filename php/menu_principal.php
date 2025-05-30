<?php
session_start();
require_once '../php/conf/conexion.php';

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
                    <a class="nav-link ms-2" href="../index.php" style="color : #f8f9fa">
                        <i class="fas fa-sign-out-alt me-1" style="color : #f8f9fa"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Carrusel de imágenes -->
    <div class="main-content">
        <div id="carouselExample" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="../imagenes/C0XUWCQWgAAnTeb.jpg" class="d-block w-100" alt="Construcción 1" style="height: 600px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block">
                        <h5 class="text-white">Proyecto Vivienda Digna</h5>
                        <p class="text-white">Avance de construcción: 75%</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="../imagenes/construccion2.jpg" class="d-block w-100" alt="Construcción 2" style="height: 600px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block">
                        <h5 class="text-white">Barrio Nuevo Hogar</h5>
                        <p class="text-white">Etapa de finalización</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="../imagenes/construccion3.jpg" class="d-block w-100" alt="Construcción 3" style="height: 600px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block">
                        <h5 class="text-white">Comunidad Unida</h5>
                        <p class="text-white">Inicio de obras</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="../imagenes/construccion4.jpg" class="d-block w-100" alt="Construcción 4" style="height: 600px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block">
                        <h5 class="text-white">Progreso Habitacional</h5>
                        <p class="text-white">En desarrollo</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="../imagenes/construccion5.jpg" class="d-block w-100" alt="Construcción 5" style="height: 600px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block">
                        <h5 class="text-white">Urbanización Moderna</h5>
                        <p class="text-white">Planificación inicial</p>
                    </div>
                </div>
            </div>
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

    <!-- Tarjetas de estadísticas -->
   
<div class="row mb-4">
    <!-- Tarjeta 1: Total Beneficiarios -->
    <div class="col-md-4 mb-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center" href=../php/beneficiarios.php">
                    <div>
                        <h6 class="card-title text-muted mb-2" >Total Beneficiarios</h6>
                        <h3 class="mb-0 text-primary"><?php echo $total_beneficiarios; ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2: Avance -->
    <div class="col-md-4 mb-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted mb-2">Avance Total</h6>
                        <h3 class="mb-0 text-success"><?php echo $porcentaje_avance; ?>%</h3>
                        <small class="text-muted"><?php echo $avance; ?> de <?php echo $total_beneficiarios; ?></small>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="fas fa-chart-line fa-2x text-success"></i>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $porcentaje_avance; ?>%" 
                         aria-valuenow="<?php echo $porcentaje_avance; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3: Otros datos (ajusta según necesites) -->
    <div class="col-md-4 mb-3">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted mb-2">Otros Datos</h6>
                        <h3 class="mb-0 text-info"><?php echo $otros_datos; ?></h3>
                        <small class="text-muted">Descripción breve</small>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="fas fa-info-circle fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
