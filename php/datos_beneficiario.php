<?php
require_once '../php/conf/conexion.php';

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
    <style>
        :root {
            --primary-color: #1565C0;
            --secondary-color: #0074D9;
            --accent-color: #E53935;
            --background-dark: #1A237E;
            --background-light: #1565C0;
            --text-color: #FFFFFF;
            --card-bg: rgba(255, 255, 255, 0.95);
            --progress-complete: #4CAF50;
            --progress-medium: #FFC107;
            --progress-low: #F44336;
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
            z-index: -1;
            pointer-events: none;
        }

        .navbar {
            background: var(--primary-color);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.25);
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
            border: none;
            overflow: hidden;
            background: var(--card-bg);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            font-weight: bold;
            border-bottom: none;
        }

        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 8px;
            margin: 20px 0 15px;
        }

        .progress-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 20px;
            margin: 10px 0;
            height: 25px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 20px;
            text-align: center;
            line-height: 25px;
            color: white;
            font-weight: bold;
            font-size: 12px;
            transition: width 1s ease-in-out;
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
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            background: rgba(245, 245, 245, 0.9);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 15px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="../imagenes/logo_menu.png.ico" alt="SIGEVU" style="height: 40px;">
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="menu_principal.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Beneficiarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Proyectos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Reportes</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Encabezado -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-center text-white mb-3">Datos del Beneficiario</h1>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="text-white"><?php echo $data['nombre_completo']; ?></h2>
                    <div>
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalActualizar">
                            <i class="fas fa-edit me-1"></i> Actualizar
                        </button>
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar">
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
                        <div class="info-value"><?php echo $data['cedula']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Teléfono</div>
                        <div class="info-value"><?php echo $data['telefono']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Estado Civil</div>
                        <div class="info-value"><?php echo $data['estado_civil'] ?? 'No especificado'; ?></div>
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
                        <div class="info-value"><?php echo $data['comunidad']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Parroquia</div>
                        <div class="info-value"><?php echo $data['parroquia']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Municipio</div>
                        <div class="info-value"><?php echo $data['municipio']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Dirección Exacta</div>
                        <div class="info-value"><?php echo $data['direccion_exacta']; ?></div>
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
                        <div class="info-value"><?php echo $data['codigo_obra']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Modelo Constructivo</div>
                        <div class="info-value"><?php echo $data['modelo_constructivo']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Método Constructivo</div>
                        <div class="info-value"><?php echo $data['metodo_constructivo']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fiscalizador</div>
                        <div class="info-value"><?php echo $data['fiscalizador']; ?></div>
                    </div>
                </div>

                <h4 class="section-title">Avance Físico</h4>
                <div class="progress-container">
                    <div class="progress-bar complete" style="width: <?php echo $data['avance_fisico'] ?? 0; ?>%">
                        <?php echo $data['avance_fisico'] ?? 0; ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Acondicionamiento -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-tools me-2"></i>Acondicionamiento</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Limpieza</div>
                        <div class="info-value"><?php echo $data['limpieza']; ?></div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['limpieza']); ?>" style="width: <?php echo getProgressWidth($data['limpieza']); ?>%">
                                <?php echo getProgressWidth($data['limpieza']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Replanteo</div>
                        <div class="info-value"><?php echo $data['replanteo']; ?></div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['replanteo']); ?>" style="width: <?php echo getProgressWidth($data['replanteo']); ?>%">
                                <?php echo getProgressWidth($data['replanteo']); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cerramiento y Acabados -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-building me-2"></i>Cerramiento y Acabados</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Cerramiento</div>
                        <div class="info-value"><?php echo $data['cerramiento']; ?></div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['cerramiento']); ?>" style="width: <?php echo getProgressWidth($data['cerramiento']); ?>%">
                                <?php echo getProgressWidth($data['cerramiento']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Colocación de Techo</div>
                        <div class="info-value"><?php echo $data['colocacion_techo']; ?></div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_techo']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_techo']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_techo']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Pintura</div>
                        <div class="info-value"><?php echo $data['pintura']; ?></div>
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
                        <div class="info-label">Fecha Inicio</div>
                        <div class="info-value"><?php echo $data['fecha_inicio'] ?? 'No especificada'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fecha Culminación</div>
                        <div class="info-value"><?php echo $data['fecha_culminacion'] ?? 'No especificada'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Acta Entregada</div>
                        <div class="info-value">
                            <span class="status-badge <?php echo ($data['acta_entregada'] == 'Sí') ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $data['acta_entregada'] ?? 'No'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <h4 class="section-title">Observaciones</h4>
                <div class="alert alert-info">
                    <?php echo $data['observaciones'] ?? 'No hay observaciones registradas'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales (se mantienen igual) -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
} else {
    echo "<div class='alert alert-danger'>No se encontraron datos para este beneficiario.</div>";
}

$stmt->close();
$conexion->close();

// Funciones auxiliares para las barras de progreso
function getProgressWidth($status) {
    switch($status) {
        case 'Completo': return 100;
        case 'Avanzado': return 75;
        case 'En progreso': return 50;
        case 'Pendiente': return 25;
        case 'No iniciado': return 0;
        default: return 0;
    }
}

function getProgressClass($status) {
    switch($status) {
        case 'Completo': return 'complete';
        case 'Avanzado': return 'medium';
        case 'En progreso': return 'medium';
        case 'Pendiente': return 'low';
        case 'No iniciado': return 'low';
        default: return 'low';
    }
}
?>