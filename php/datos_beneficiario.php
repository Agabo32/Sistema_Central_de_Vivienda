<?php
require_once '../php/conf/conexion.php';

$query = "SELECT * FROM beneficiarios";
$resultado = mysqli_query($conexion, $query);

if (!isset($_GET['id'])) {
    echo "ID de beneficiario no especificado.";
    exit;
}

$id = intval($_GET['id']); // Asegurarse de que el ID es un número entero

$sql = "
SELECT
    b.id_beneficiario, 
    b.cedula, 
    b.nombre_beneficiario, 
    b.telefono, 
    b.codigo_obra, 
    b.fecha_actualizacion,
    b.status,
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
LEFT JOIN Obras o ON b.codigo_obra = b.codigo_obra
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
            --secondary-color: #0523AAFF;
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
            background: url('../imagenes/fondo1.jpg') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            position: relative;
            color: var(--text-color);
            height: 100vh;
            min-height: 100vh;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            overflow-y: auto;
            z-index: -1;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
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
            z-index: -1;
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
                    <h2 class="mb-0" style="color: #ffffff;"><?php echo htmlspecialchars($data['nombre_beneficiario']); ?></h2>
                    <div>
                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#actualizarBeneficiarioModal">
                <i class="fas fa-edit me-2"></i>Actualizar Beneficiario
                    </button>
                    <a href="generar_expediente.php?id=<?= $data['id_beneficiario'] ?>" 
                    class="btn btn-danger btn-action" 
                    target="_blank">
                    <i class="fas fa-file-pdf me-1"></i> Generar Expediente
                    </a>
                    </div>
                </div>
            </div>
        </div>

          <!-- Modal Actualizar Beneficiario -->
<div class="modal fade" id="actualizarBeneficiarioModal" tabindex="-1" aria-labelledby="actualizarBeneficiarioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actualizarBeneficiarioModalLabel">Actualizar Beneficiario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="actualizarBeneficiarioForm">
                    <input type="hidden" name="id_beneficiario" value="<?php echo $data['id_beneficiario']; ?>">
                    
                    <div class="row">
                        <!-- Columna 1 -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Estado</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="activo" <?= ($data['status'] ?? 'activo') == 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="inactivo" <?= ($data['status'] ?? 'activo') == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nombre_beneficiario" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre_beneficiario" name="nombre_beneficiario" value="<?php echo $data['nombre_beneficiario']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cedula" class="form-label">Cédula</label>
                                <input type="text" class="form-control" id="cedula" name="cedula" value="<?php echo $data['cedula']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo $data['telefono']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="codigo_obra" class="form-label">Código de Obra</label>
                                <input type="text" class="form-control" id="codigo_obra" name="codigo_obra" value="<?php echo $data['codigo_obra']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="comunidad" class="form-label">Comunidad</label>
                                <input type="text" class="form-control" id="comunidad" name="comunidad" value="<?php echo $data['comunidad']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="direccion_exacta" class="form-label">Dirección Exacta</label>
                                <input type="text" class="form-control" id="direccion_exacta" name="direccion_exacta" value="<?php echo $data['direccion_exacta']; ?>">
                            </div>
                        </div>
                        
                        <!-- Columna 2 -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="utm_norte" class="form-label">UTM Norte</label>
                                <input type="text" class="form-control" id="utm_norte" name="utm_norte" value="<?php echo $data['utm_norte']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="utm_este" class="form-label">UTM Este</label>
                                <input type="text" class="form-control" id="utm_este" name="utm_este" value="<?php echo $data['utm_este']; ?>">
                            </div>
                            
                            <div class="col-md-6">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="municipio" class="form-label">Municipio</label>
                <select class="form-select" id="municipioSelect" name="id_municipio" required>
                    <option value="">Seleccione un municipio</option>
                    <?php
                    $municipios = $conexion->query("SELECT id_municipio, municipio FROM municipios");
                    while ($row = $municipios->fetch_assoc()) {
                        $selected = ($row['id_municipio'] == $data['id_municipio']) ? 'selected' : '';
                        echo "<option value='{$row['id_municipio']}' $selected>{$row['municipio']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="parroquia" class="form-label">Parroquia</label>
                <select class="form-select" id="parroquiaSelect" name="id_parroquia" required>
                    <option value="">Seleccione una parroquia</option>
                    <?php
                    if (!empty($data['id_municipio'])) {
                        $parroquias = $conexion->query("SELECT id_parroquia, parroquia FROM parroquias WHERE id_municipio = {$data['id_municipio']}");
                        while ($row = $parroquias->fetch_assoc()) {
                            $selected = ($row['id_parroquia'] == $data['id_parroquia']) ? 'selected' : '';
                            echo "<option value='{$row['id_parroquia']}' $selected>{$row['parroquia']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
                            
                            <div class="mb-3">
                                <label for="modelo_constructivo" class="form-label">Modelo Constructivo</label>
                                <input type="text" class="form-control" id="modelo_constructivo" name="modelo_constructivo" value="<?php echo $data['modelo_constructivo']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="metodo_constructivo" class="form-label">Método Constructivo</label>
                                <input type="text" class="form-control" id="metodo_constructivo" name="metodo_constructivo" value="<?php echo $data['metodo_constructivo']; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="avance_fisico" class="form-label">Avance Físico (%)</label>
                                <input type="number" class="form-control" id="avance_fisico" name="avance_fisico" min="0" max="100" value="<?php echo $data['avance_fisico'] ?? 0; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Datos de Construcción -->
                    <h5 class="mt-4 mb-3">Datos de Construcción</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="limpieza" class="form-label">Limpieza</label>
                                <input type="number" class="form-control" id="limpieza" name="limpieza" min="0" max="100" value="<?php echo $data['limpieza'] ?? 0; ?>">
                            </div>
                            
                            <!-- Agrega más campos de construcción según sea necesario -->
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="replanteo" class="form-label">Replanteo</label>
                                <input type="number" class="form-control" id="replanteo" name="replanteo" min="0" max="100" value="<?php echo $data['replanteo'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="excavacion" class="form-label">Excavacion</label>
                                <input type="number" class="form-control" id="excavacion" name="excavacion" min="0" max="100" value="<?php echo $data['excavacion'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="acero_vigas_riostra" class="form-label">Acero en Vigas de Riostra</label>
                                <input type="number" class="form-control" id="acero_vigas_riostra" name="acero_vigas_riostra" min="0" max="100" value="<?php echo $data['acero_vigas_riostra'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="encofrado_malla" class="form-label">Encofrado y Colocación de Malla</label>
                                <input type="number" class="form-control" id="encofrado_malla" name="encofrado_malla" min="0" max="100" value="<?php echo $data['encofrado_malla'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="instalaciones_electricas_sanitarias" class="form-label">Instalaciones Eléctricas y Sanitarias</label>
                                <input type="number" class="form-control" id="instalaciones_electricas_sanitarias" name="instalaciones_electricas_sanitarias" min="0" max="100" value="<?php echo $data['instalaciones_electricas_sanitarias'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vaciado_losa_anclajes" class="form-label">Vaciado de Losa y Colocación de Anclajes</label>
                                <input type="number" class="form-control" id="vaciado_losa_anclajes" name="vaciado_losa_anclajes" min="0" max="100" value="<?php echo $data['vaciado_losa_anclajes'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="armado_columnas" class="form-label">Armado de Columnas</label>
                                <input type="number" class="form-control" id="armado_columnas" name="armado_columnas" min="0" max="100" value="<?php echo $data['armado_columnas'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vaciado_columnas" class="form-label">Vaciado de Columnas</label>
                                <input type="number" class="form-control" id="vaciado_columnas" name="vaciado_columnas" min="0" max="100" value="<?php echo $data['vaciado_columnas'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vaciado_columnas" class="form-label">Armado de Vigas</label>
                                <input type="number" class="form-control" id="armado_vigas" name="armado_vigas" min="0" max="100" value="<?php echo $data['armado_vigas'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vaciado_vigas" class="form-label">Vaciado de Vigas</label>
                                <input type="number" class="form-control" id="vaciado_vigas" name="vaciado_vigas" min="0" max="100" value="<?php echo $data['vaciado_vigas'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bloqueado" class="form-label">Bloqueado</label>
                                <input type="number" class="form-control" id="bloqueado" name="bloqueado" min="0" max="100" value="<?php echo $data['bloqueado'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="colocacion_correas" class="form-label">Colocación de Correas</label>
                                <input type="number" class="form-control" id="colocacion_correas" name="colocacion_correas" min="0" max="100" value="<?php echo $data['colocacion_correas'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="colocacion_techo" class="form-label">Colocación de Techo</label>
                                <input type="number" class="form-control" id="colocacion_techo" name="colocacion_techo" min="0" max="100" value="<?php echo $data['colocacion_techo'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="colocacion_techo" class="form-label">Colocación de Ventanas</label>
                                <input type="number" class="form-control" id="colocacion_ventanas" name="colocacion_ventanas" min="0" max="100" value="<?php echo $data['colocacion_ventanas'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="colocacion_techo" class="form-label">Colocación de Puertas Principales</label>
                                <input type="number" class="form-control" id="colocacion_puertas_principales" name="colocacion_puertas_principales" min="0" max="100" value="<?php echo $data['colocacion_puertas_principales'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="colocacion_techo" class="form-label">Instalaciones Eléctricas y Sanitarias en Paredes</label>
                                <input type="number" class="form-control" id="instalaciones_electricas_sanitarias_paredes" name="instalaciones_electricas_sanitarias_paredes" min="0" max="100" value="<?php echo $data['instalaciones_electricas_sanitarias_paredes'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="frisos" class="form-label">Frisos</label>
                                <input type="number" class="form-control" id="frisos" name="frisos" min="0" max="100" value="<?php echo $data['frisos'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sobrepiso" class="form-label">Sobre-piso</label>
                                <input type="number" class="form-control" id="sobrepiso" name="sobrepiso" min="0" max="100" value="<?php echo $data['sobrepiso'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ceramica_bano" class="form-label">Cerámica en Baño</label>
                                <input type="number" class="form-control" id="ceramica_bano" name="ceramica_bano" min="0" max="100" value="<?php echo $data['ceramica_bano'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="colocacion_puertas_internas" class="form-label">Colocación de Puertas Internas</label>
                                <input type="number" class="form-control" id="colocacion_puertas_internas" name="colocacion_puertas_internas" min="0" max="100" value="<?php echo $data['colocacion_puertas_internas'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="equipos_accesorios_electricos" class="form-label">Equipos y Accesorios Eléctricos</label>
                                <input type="number" class="form-control" id="equipos_accesorios_electricos" name="equipos_accesorios_electricos" min="0" max="100" value="<?php echo $data['equipos_accesorios_electricos'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="equipos_accesorios_sanitarios" class="form-label">Equipos y Accesorios Sanitarios</label>
                                <input type="number" class="form-control" id="equipos_accesorios_sanitarios" name="equipos_accesorios_sanitarios" min="0" max="100" value="<?php echo $data['equipos_accesorios_sanitarios'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="colocacion_lavaplatos" class="form-label">Colocación de Lavaplatos</label>
                                <input type="number" class="form-control" id="colocacion_lavaplatos" name="colocacion_lavaplatos" min="0" max="100" value="<?php echo $data['colocacion_lavaplatos'] ?? 0; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pintura" class="form-label">Pintura</label>
                                <input type="number" class="form-control" id="pintura" name="pintura" min="0" max="100" value="<?php echo $data['pintura'] ?? 0; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <div class="mb-3">
                        <label for="observaciones_responsables_control" class="form-label">Observaciones Responsables</label>
                        <textarea class="form-control" id="observaciones_responsables_control" name="observaciones_responsables_control" rows="3"><?php echo $data['observaciones_responsables_control'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones_fiscalizadores" class="form-label">Observaciones Fiscalizadores</label>
                        <textarea class="form-control" id="observaciones_fiscalizadores" name="observaciones_fiscalizadores" rows="3"><?php echo $data['observaciones_fiscalizadores'] ?? ''; ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="actualizarBeneficiario()">Guardar Cambios</button>
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
    <script>
    function actualizarBeneficiario() {
    var form = document.getElementById('actualizarBeneficiarioForm');
    var formData = new FormData(form);
    
    // Mostrar loading
    var submitBtn = document.querySelector('#actualizarBeneficiarioModal .btn-primary');
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
    submitBtn.disabled = true;

    fetch('../php/conf/actualizar_beneficiario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        if (result === 'ok') {
            // Mostrar alerta de éxito
            alert('Beneficiario actualizado exitosamente');
            // Recargar la página para ver cambios
            location.reload();
        } else {
            // Mostrar error
            alert(result);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el beneficiario');
    })
    .finally(() => {
        submitBtn.innerHTML = 'Guardar Cambios';
        submitBtn.disabled = false;
    });
}
function showToast(type, message) {
    const toastContainer = document.createElement('div');
    toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
    toastContainer.style.zIndex = '9999';
    
    const toast = document.createElement('div');
    toast.className = `toast show align-items-center text-white bg-${type}`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    document.body.appendChild(toastContainer);
    
    // Eliminar el toast después de 5 segundos
    setTimeout(() => {
        toastContainer.remove();
    }, 5000);
}
document.getElementById('municipioSelect').addEventListener('change', function() {
    const municipioId = this.value;
    const parroquiaSelect = document.getElementById('parroquiaSelect');
    
    if (municipioId) {
        fetch(`../php/conf/get_parroquias.php?municipio_id=${municipioId}`)
            .then(response => response.json())
            .then(data => {
                parroquiaSelect.innerHTML = '<option value="">Seleccione una parroquia</option>';
                data.forEach(parroquia => {
                    const option = document.createElement('option');
                    option.value = parroquia.id_parroquia;
                    option.textContent = parroquia.parroquia;
                    parroquiaSelect.appendChild(option);
                });
                parroquiaSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error al cargar parroquias:', error);
                parroquiaSelect.innerHTML = '<option value="">Error al cargar parroquias</option>';
            });
    } else {
        parroquiaSelect.innerHTML = '<option value="">Seleccione una parroquia</option>';
        parroquiaSelect.disabled = true;
    }
});


</script>    
</body>
</html>

<?php
$stmt->close();
$conexion->close();
?>