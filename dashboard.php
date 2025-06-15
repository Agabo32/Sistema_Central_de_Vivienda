<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Central de Vivienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_dashboard.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark glass-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="php/menu_principal.php">
                <img src="imagenes/logo_menu.png.ico" alt="SIGEVU" style="height: 30px;" class="me-2">
                <span class="fw-bold">SIGEVU</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="php/menu_principal.php">
                            <i class="fas fa-home me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="php/beneficiarios.php">
                            <i class="fas fa-users me-1"></i> Beneficiarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="php/reportes.php">
                            <i class="fas fa-chart-bar me-1"></i> Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                </ul>
                <div class="d-flex ms-3">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <a class="nav-link ms-2" href="php/conf/logout.php" style="color: #f8f9fa">
                        <i class="fas fa-sign-out-alt me-1" style="color: #f8f9fa"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        <header>
            <h1>Dashboard del Sistema Central de Vivienda</h1>
        </header>

        <!-- Indicador de carga -->
        <div id="loadingIndicator" class="text-center mb-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando datos del dashboard...</p>
        </div>

        <!-- Mensaje de error -->
        <div id="errorMessage" class="alert alert-danger d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorText">Error al cargar los datos</span>
        </div>

        <!-- Contadores -->
        <div class="row mb-4" id="dashboardContent" style="display: none;">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title">Total de Beneficiarios</h3>
                        <h2 class="display-4 text-primary" id="totalBeneficiarios">0</h2>
                        <i class="fas fa-users fa-3x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title">Viviendas Completadas</h3>
                        <h2 class="display-4 text-success" id="viviendasCompletadas">0</h2>
                        <i class="fas fa-home fa-3x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title">Total de Comunidades</h3>
                        <h2 class="display-4 text-info" id="totalComunidades">0</h2>
                        <i class="fas fa-map-marker-alt fa-3x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid" id="chartsContainer" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-map me-2"></i>Beneficiarios por Municipio</h2>
                </div>
                <div class="card-body">
                    <canvas id="municipiosChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script/dashboard_charts.js"></script>
</body>
</html>
