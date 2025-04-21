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
    <style>
        :root {
            --primary-color: #1565C0;     /* Naranja brillante */
            --secondary-color: #0074D9;   /* Azul brillante */
            --accent-color:rgba(247, 5, 5, 0.9);      /* Rojo brillante */
            --background-dark: #1A237E;   /* Azul oscuro */
            --background-light: #1565C0;  /* Azul más claro */
            --text-color: #FFFFFF;
            --shadow-color: #FFA500;
            --border-color: #FFA500;
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
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .navbar-brand {
            margin-right: 2rem;
        }

        .nav-link {
            color: white !important;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            border: 1px flex var(--border-color);
            background: rgba(80, 80, 80, 0.9);
        }

        .dropdown-menu {
            background: rgba(34, 7, 7, 0.9);
            border: 1px solid var(--primary-color);
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .dropdown-item {
            color: white;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: rgba(247, 5, 5, 0.9);
            color: var(--secondary-color);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
        }

        .carousel {
            margin-top: 60px; /* Altura del navbar + padding */
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(221, 7, 7, 0.7);
            background: rgba(221, 7, 7, 0.7);
            position: relative;
            z-index: 1;
        }

        .carousel-item img {
            height: 500px;
            object-fit: cover;
        }

        .carousel-caption {
            bottom: 100px;
            background: rgba(221, 7, 7, 0.7);
            padding: 2rem;
            border-radius: 10px;
        }

        .stat-card {
            background: rgba(255, 165, 0, 0.1);
            border-radius: 15px;
            padding: 2rem;
            margin: 1rem;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-color);
            box-shadow: 0 15px 40px var(--shadow-color);
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #FFA500;
        }

        .stat-label {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: white;
        }

        .progress {
            height: 20px;
            background-color: rgba(255, 21, 4, 0.2);
            border-radius: 10px;
            margin-top: 1rem;
        }

        .progress-bar {
            background-color: var(--primary-color);
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .carousel-item img {
            height: 600px;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .carousel-item img {
                height: 400px;
            }
        }

        @media (max-width: 576px) {
            .carousel-item img {
                height: 300px;
            }
        }

        .carousel-caption {
            bottom: 50px;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
        }

        .carousel-caption h5 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .carousel-caption p {
            font-size: 1rem;
        }
            
            .carousel-caption {
                bottom: 50px;
                padding: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
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

    <!-- Carrusel de imágenes -->
    <div class="container-fluid p-0">
        <div id="carouselExample" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="../imagenes/construccion1.jpg" class="d-block w-100" alt="Construcción 1" style="height: 600px; object-fit: cover;">
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
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Total Beneficiarios</div>
                    <div class="stat-number" id="totalBeneficiarios">0</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Casas en Construcción</div>
                    <div class="stat-number" id="casasEnConstruccion">0</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Avance General</div>
                    <div class="stat-number" id="avanceGeneral">0%</div>
                    <div class="progress">
                        <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%"></div>
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
