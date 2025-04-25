<?php
require_once '../php/conf/conexion.php';

if (!isset($_GET['id'])) {
    echo "ID de beneficiario no especificado.";
    exit;
}

$id = intval($_GET['id']); // Asegurarse de que el ID es un número entero

$sql = $sql = "
SELECT
    b.id_beneficiario, 
    b.cedula, 
    b.nombre_beneficiario, 
    b.telefono, 
    b.codigo_obra, 
    b.fecha_actualizacion,
    u.comunidad, 
    u.direccion_exacta, 
    u.utm_norte, 
    u.utm_este,
    m.id_municipio,
    m.municipio AS municipio,  
    p.id_parroquia,
    p.parroquia AS parroquia,  
    o.metodo_constructivo, 
    o.modelo_constructivo, 
    f.nombre_fiscalizador,
    dc.acondicionamiento, 
    dc.limpieza, 
    dc.replanteo, 
    dc.fundacion, 
    dc.excavacion, 
    dc.acero_vigas_riostra, 
    dc.encofrado_malla, 
    dc.instalaciones_electricas_sanitarias, 
    dc.vaciado_losa_anclajes, 
    dc.estructura, 
    dc.armado_columnas, 
    dc.vaciado_columnas, 
    dc.armado_vigas, 
    dc.vaciado_vigas, 
    dc.cerramiento, 
    dc.bloqueado, 
    dc.colocacion_correas, 
    dc.colocacion_techo, 
    dc.acabado, 
    dc.colocacion_ventanas, 
    dc.colocacion_puertas_principales, 
    dc.instalaciones_electricas_sanitarias_paredes, 
    dc.frisos, 
    dc.sobrepiso, 
    dc.ceramica_bano, 
    dc.colocacion_puertas_internas, 
    dc.equipos_accesorios_electricos, 
    dc.equipos_accesorios_sanitarios, 
    dc.colocacion_lavaplatos, 
    dc.pintura, 
    dc.avance_fisico, 
    dc.fecha_culminacion, 
    dc.acta_entregada, 
    dc.observaciones_responsables_control, 
    dc.observaciones_fiscalizadores
FROM Beneficiarios b
LEFT JOIN Ubicaciones u ON b.id_ubicacion = u.id_ubicacion
LEFT JOIN municipios m ON u.id_municipio = m.id_municipio
LEFT JOIN parroquias p ON u.id_parroquia = p.id_parroquia
LEFT JOIN Obras o ON b.codigo_obra = o.codigo_obra
LEFT JOIN Fiscalizadores f ON b.id_fiscalizador = f.id_fiscalizador
LEFT JOIN Datos_de_Construccion dc ON b.id_beneficiario = dc.id_beneficiario
WHERE b.id_beneficiario = ?
";

$stmt = $conexion->prepare($sql);
if ($stmt === false) {
    die("Error en la preparación de la consulta: " . $conexion->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("No se encontraron registros para el beneficiario con ID: $id");
}

function getProgressWidth($value) {
    if ($value === null || $value === '') {
        return 0;
    }
    if (is_numeric($value)) {
        return min(max(floatval($value), 0), 100);
    }
    
    switch(strtolower($value)) {
        case 'completo': return 100;
        case 'avanzado': return 75;
        case 'en progreso': return 50;
        case 'pendiente': return 25;
        case 'no iniciado': return 0;
        default: return 0;
    }
}

function getProgressClass($value) {
    if (is_numeric($value)) {
        $percentage = floatval($value);
        if ($percentage == 100) return 'complete';
        if ($percentage >= 75) return 'medium';
        if ($percentage >= 50) return 'medium';
        if ($percentage > 0) return 'low';
        return 'low';
    }
    
    switch(strtolower($value)) {
        case 'completo': return 'complete';
        case 'avanzado': return 'medium';
        case 'en progreso': return 'medium';
        case 'pendiente': return 'low';
        case 'no iniciado': return 'low';
        default: return 'low';
    }
}

function formatProgressValue($value) {
    if (is_numeric($value)) {
        return number_format(floatval($value), 2) . '%';
    }
    return $value;
}
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
    <style>
        :root {
            --primary-color: #1565C0;
            --secondary-color: #0074D9;
            --accent-color: #E53935;
            --background-dark: #1A237E;
            --background-light: #1565C0;
            --text-color: #333333;
            --text-light: #FFFFFF;
            --card-bg: rgba(255, 255, 255, 0.95);
            --progress-complete: #4CAF50;
            --progress-medium: #FFC107;
            --progress-low: #F44336;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }

        .glass-navbar {
    background: rgba(67, 97, 238, 0.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}
        .container-main {
            margin-top: 80px;
            padding-bottom: 40px;
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-light);
            font-weight: 600;
            border-bottom: none;
            padding: 15px 20px;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .card-body {
            padding: 25px;
        }

        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 8px;
            margin: 20px 0 15px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .progress-container {
            width: 100%;
            background-color: #e9ecef;
            border-radius: 20px;
            margin-top: 10px;
            height: 20px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 20px;
            text-align: center;
            line-height: 20px;
            color: white;
            font-weight: bold;
            font-size: 0.65rem;
            transition: width 0.6s ease;
        }

        .complete {
            background-color: var(--progress-complete);
        }

        .medium {
            background-color: var(--progress-medium);
        }

        .low {
            background-color: var(--progress-low);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            color: white;
            display: inline-block;
        }

        .bg-success {
            background-color: var(--progress-complete);
        }

        .bg-warning {
            background-color: var(--progress-medium);
        }

        .bg-danger {
            background-color: var(--progress-low);
        }

        .alert {
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-info {
            border-left-color: var(--primary-color);
        }

        .alert-warning {
            border-left-color: var(--progress-medium);
        }

        .btn-action {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #0d47a1;
            border-color: #0d47a1;
            transform: translateY(-2px);
        }

        .btn-outline-danger {
            color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-outline-danger:hover {
            background-color: var(--accent-color);
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .container-main {
                margin-top: 60px;
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
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
                    <a class="nav-link ms-2" href="../index.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <!-- Encabezado -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="page-title text-center">Datos del Beneficiario</h1>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><?php echo htmlspecialchars($data['nombre_beneficiario']); ?></h2>
                    <div>
                        <button class="btn btn-primary btn-action me-2" data-bs-toggle="modal" data-bs-target="#modalActualizar">
                            <i class="fas fa-edit me-1"></i> Actualizar
                        </button>
                        <button class="btn btn-outline-danger btn-action" data-bs-toggle="modal" data-bs-target="#modalEliminar">
                            <i class="fas fa-trash me-1"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Personal -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-user me-2"></i>Información Personal</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Cédula</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['cedula']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Teléfono</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['telefono']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ubicación -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Comunidad</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['comunidad']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Parroquia</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['parroquia']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Municipio</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['municipio']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Dirección Exacta</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['direccion_exacta']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">UTM Norte</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['utm_norte']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">UTM Este</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['utm_este']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Proyecto de Construcción -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-home me-2"></i>Proyecto de Construcción</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Código de Obra</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['codigo_obra']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Modelo Constructivo</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['modelo_constructivo']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Método Constructivo</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['metodo_constructivo']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fiscalizador</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['nombre_fiscalizador']); ?></div>
                    </div>
                </div>

                <h4 class="section-title">Avance Físico</h4>
                <div class="progress-container">
                    <div class="progress-bar complete" style="width: <?php echo htmlspecialchars($data['avance_fisico'] ?? 0); ?>%">
                        <?php echo htmlspecialchars($data['avance_fisico'] ?? 0); ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Acondicionamiento y Fundación -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-tools me-2"></i>Acondicionamiento y Fundación</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Limpieza</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['limpieza']); ?>" style="width: <?php echo getProgressWidth($data['limpieza']); ?>%">
                                <?php echo getProgressWidth($data['limpieza']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Replanteo</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['replanteo']); ?>" style="width: <?php echo getProgressWidth($data['replanteo']); ?>%">
                                <?php echo getProgressWidth($data['replanteo']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Excavación</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['excavacion']); ?>" style="width: <?php echo getProgressWidth($data['excavacion']); ?>%">
                                <?php echo getProgressWidth($data['excavacion']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Acero en Vigas de Riostra</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['acero_vigas_riostra']); ?>" style="width: <?php echo getProgressWidth($data['acero_vigas_riostra']); ?>%">
                                <?php echo getProgressWidth($data['acero_vigas_riostra']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Encofrado y Colocación de Malla</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['encofrado_malla']); ?>" style="width: <?php echo getProgressWidth($data['encofrado_malla']); ?>%">
                                <?php echo getProgressWidth($data['encofrado_malla']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Instalaciones Eléctricas y Sanitarias</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['instalaciones_electricas_sanitarias']); ?>" style="width: <?php echo getProgressWidth($data['instalaciones_electricas_sanitarias']); ?>%">
                                <?php echo getProgressWidth($data['instalaciones_electricas_sanitarias']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Vaciado de Losa y Colocación de Anclajes</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['vaciado_losa_anclajes']); ?>" style="width: <?php echo getProgressWidth($data['vaciado_losa_anclajes']); ?>%">
                                <?php echo getProgressWidth($data['vaciado_losa_anclajes']); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estructura -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-cubes me-2"></i>Estructura</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Armado de Columnas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['armado_columnas']); ?>" style="width: <?php echo getProgressWidth($data['armado_columnas']); ?>%">
                                <?php echo getProgressWidth($data['armado_columnas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Vaciado de Columnas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['vaciado_columnas']); ?>" style="width: <?php echo getProgressWidth($data['vaciado_columnas']); ?>%">
                                <?php echo getProgressWidth($data['vaciado_columnas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Armado de Vigas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['armado_vigas']); ?>" style="width: <?php echo getProgressWidth($data['armado_vigas']); ?>%">
                                <?php echo getProgressWidth($data['armado_vigas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Vaciado de Vigas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['vaciado_vigas']); ?>" style="width: <?php echo getProgressWidth($data['vaciado_vigas']); ?>%">
                                <?php echo getProgressWidth($data['vaciado_vigas']); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cerramiento -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-building me-2"></i>Cerramiento</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Bloqueado</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['bloqueado']); ?>" style="width: <?php echo getProgressWidth($data['bloqueado']); ?>%">
                                <?php echo getProgressWidth($data['bloqueado']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Colocación de Correas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_correas']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_correas']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_correas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Colocación de Techo</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_techo']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_techo']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_techo']); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acabado -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-paint-roller me-2"></i>Acabado</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Colocación de Ventanas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_ventanas']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_ventanas']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_ventanas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Colocación de Puertas Principales</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_puertas_principales']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_puertas_principales']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_puertas_principales']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Instalaciones Eléctricas y Sanitarias en Paredes</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['instalaciones_electricas_sanitarias_paredes']); ?>" style="width: <?php echo getProgressWidth($data['instalaciones_electricas_sanitarias_paredes']); ?>%">
                                <?php echo getProgressWidth($data['instalaciones_electricas_sanitarias_paredes']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Frisos</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['frisos']); ?>" style="width: <?php echo getProgressWidth($data['frisos']); ?>%">
                                <?php echo getProgressWidth($data['frisos']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Sobre-piso</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['sobrepiso']); ?>" style="width: <?php echo getProgressWidth($data['sobrepiso']); ?>%">
                                <?php echo getProgressWidth($data['sobrepiso']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Cerámica en Baño</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['ceramica_bano']); ?>" style="width: <?php echo getProgressWidth($data['ceramica_bano']); ?>%">
                                <?php echo getProgressWidth($data['ceramica_bano']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Colocación de Puertas Internas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_puertas_internas']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_puertas_internas']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_puertas_internas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Equipos y Accesorios Eléctricos</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['equipos_accesorios_electricos']); ?>" style="width: <?php echo getProgressWidth($data['equipos_accesorios_electricos']); ?>%">
                                <?php echo getProgressWidth($data['equipos_accesorios_electricos']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Equipos y Accesorios Sanitarios</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['equipos_accesorios_sanitarios']); ?>" style="width: <?php echo getProgressWidth($data['equipos_accesorios_sanitarios']); ?>%">
                                <?php echo getProgressWidth($data['equipos_accesorios_sanitarios']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Colocación de Lavaplatos</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_lavaplatos']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_lavaplatos']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_lavaplatos']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Pintura</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['pintura']); ?>" style="width: <?php echo getProgressWidth($data['pintura']); ?>%">
                                <?php echo getProgressWidth($data['pintura']); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado General -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Estado General</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Fecha Actualización</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['fecha_actualizacion'] ?? 'No especificada'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fecha Culminación</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['fecha_culminacion'] ?? 'No especificada'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Acta Entregada</div>
                        <div class="info-value">
                            <span class="status-badge <?php echo ($data['acta_entregada'] == 'Sí') ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo htmlspecialchars($data['acta_entregada'] ?? 'No'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <h4 class="section-title">Observaciones Responsables Control y Seguimiento</h4>
                <div class="alert alert-info">
                    <?php echo htmlspecialchars($data['observaciones_responsables_control'] ?? 'No hay observaciones registradas'); ?>
                </div>
                
                <?php if (!empty($data['observaciones_fiscalizadores'])): ?>
                <h4 class="section-title">Observaciones de Fiscalizadores</h4>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($data['observaciones_fiscalizadores']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conexion->close();
?>