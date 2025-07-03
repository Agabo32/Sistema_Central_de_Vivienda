<?php
session_start();
require_once '../php/conf/conexion.php';
require_once '../php/conf/session_helper.php';

// Verificación de autenticación
verificar_autenticacion();

// Todos los usuarios tienen acceso total
$esAdmin = true;

// Obtener el rol del usuario actual
$usuario_actual = $_SESSION['user'] ?? null;
$rol_usuario = $usuario_actual['rol'] ?? '';

// Retrieve filter parameters
$estadoLara = $conexion->query("SELECT id_estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
$id_lara = $estadoLara['id_estado'];
$id_municipio = $_GET['municipios'] ?? null;
$id_parroquia = $_GET['parroquias'] ?? null;
$id_comunidad = $_GET['comunidad'] ?? null;
$estado_beneficiario = $_GET['estado'] ?? null;
$codigo_obra = $_GET['cod_obra'] ?? null;
$tipo_avance = $_GET['tipo_avance'] ?? 'avance_fisico';

function getProgressClass($valor) {
    $valor = floatval($valor ?? 0);
    if ($valor >= 100) return 'complete';
    if ($valor > 0) return 'in-progress';
    return 'not-started';
}

// Consulta SQL base
$sql = "SELECT 
    e.id_estado,
    e.estado,
    m.id_municipio,
    m.municipio,
    p.id_parroquia,
    p.parroquia,
    c.id_comunidad,
    c.comunidad,
    co.cod_obra as codigo_obra,
    COUNT(DISTINCT b.id_beneficiario) as total_viviendas,
    COUNT(DISTINCT CASE WHEN dc.$tipo_avance = 100 THEN b.id_beneficiario END) as completadas,
    COUNT(DISTINCT CASE WHEN dc.$tipo_avance > 0 AND dc.$tipo_avance < 100 THEN b.id_beneficiario END) as en_progreso,
    COUNT(DISTINCT CASE WHEN dc.$tipo_avance = 0 OR dc.$tipo_avance IS NULL THEN b.id_beneficiario END) as no_iniciadas,
    ROUND(AVG(COALESCE(dc.$tipo_avance, 0)), 2) as avance_promedio
FROM estados e
JOIN municipios m ON e.id_estado = m.id_estado
JOIN parroquias p ON m.id_municipio = p.id_municipio
JOIN ubicaciones u ON p.id_parroquia = u.parroquia
JOIN comunidades c ON u.comunidad = c.id_comunidad
LEFT JOIN beneficiarios b ON u.id_ubicacion = b.id_ubicacion
LEFT JOIN cod_obra co ON b.cod_obra = co.id_cod_obra
LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
WHERE e.id_estado = ?";

$types = "i";
$params = [$id_lara];

// Aplicar filtros
if ($id_municipio) {
    $sql .= " AND m.id_municipio = ?";
    $types .= "i";
    $params[] = $id_municipio;
}

if ($id_parroquia) {
    $sql .= " AND p.id_parroquia = ?";
    $types .= "i";
    $params[] = $id_parroquia;
}

if ($id_comunidad) {
    $sql .= " AND c.id_comunidad = ?";
    $types .= "i";
    $params[] = $id_comunidad;
}

if ($codigo_obra) {
    $sql .= " AND b.cod_obra = ?";
    $types .= "s";
    $params[] = $codigo_obra;
}

// Filtro por estado del beneficiario
if ($estado_beneficiario && $estado_beneficiario !== 'todos') {
    $sql .= " AND b.status = ?";
    $types .= "s";
    $params[] = $estado_beneficiario;
} else {
    $sql .= " AND b.status = 'activo'";
}

// Agrupar y ordenar
$sql .= " GROUP BY e.id_estado, m.id_municipio, p.id_parroquia, c.id_comunidad
          HAVING total_viviendas > 0
          ORDER BY m.municipio, p.parroquia, c.comunidad";

// Preparar y ejecutar la consulta
$stmt = $conexion->prepare($sql);
if ($stmt === false) {
    die("Error en la preparación de la consulta: " . $conexion->error);
}

// Vincular parámetros
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Ejecutar consulta
if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$result = $stmt->get_result();
$reportes = $result->fetch_all(MYSQLI_ASSOC);

// Calcular totales generales
$total_viviendas_general = 0;
$total_completadas_general = 0;
$total_en_progreso_general = 0;
$total_no_iniciadas_general = 0;
$suma_avances = 0;
$count_reportes = 0;

foreach ($reportes as $reporte) {
    $total_viviendas_general += $reporte['total_viviendas'];
    $total_completadas_general += $reporte['completadas'];
    $total_en_progreso_general += $reporte['en_progreso'];
    $total_no_iniciadas_general += $reporte['no_iniciadas'];
    $suma_avances += $reporte['avance_promedio'] * $reporte['total_viviendas'];
    $count_reportes++;
}

$avance_promedio_general = $total_viviendas_general > 0 ? round($suma_avances / $total_viviendas_general, 2) : 0;

// Contar protocolizados y no protocolizados
$sql_protocolizados = "SELECT 
    SUM(CASE WHEN b.protocolizacion = 1 THEN 1 ELSE 0 END) AS protocolizados,
    SUM(CASE WHEN b.protocolizacion = 0 OR b.protocolizacion IS NULL THEN 1 ELSE 0 END) AS no_protocolizados
FROM beneficiarios b
JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
WHERE 1=1";
if ($id_municipio) {
    $sql_protocolizados .= " AND u.municipio = " . intval($id_municipio);
}
if ($id_parroquia) {
    $sql_protocolizados .= " AND u.parroquia = " . intval($id_parroquia);
}
if ($id_comunidad) {
    $sql_protocolizados .= " AND u.comunidad = " . intval($id_comunidad);
}
if ($estado_beneficiario && $estado_beneficiario !== 'todos') {
    $sql_protocolizados .= " AND b.status = '" . $conexion->real_escape_string($estado_beneficiario) . "'";
} else {
    $sql_protocolizados .= " AND b.status = 'activo'";
}
$result_protocolizados = $conexion->query($sql_protocolizados);
$protocolizados_data = $result_protocolizados->fetch_assoc();
$total_protocolizados = $protocolizados_data['protocolizados'] ?? 0;
$total_no_protocolizados = $protocolizados_data['no_protocolizados'] ?? 0;

// Consulta para obtener datos por comunidad
$sql_comunidades = "SELECT 
    c.comunidad,
    COUNT(DISTINCT b.id_beneficiario) as total_viviendas,
    ROUND(AVG(COALESCE(dc.$tipo_avance, 0)), 2) as avance_promedio
FROM comunidades c
LEFT JOIN ubicaciones u ON c.id_comunidad = u.comunidad
LEFT JOIN beneficiarios b ON u.id_ubicacion = b.id_ubicacion
LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
WHERE 1=1";

// Consulta para obtener datos por municipio
$sql_municipios = "SELECT 
    m.municipio,
    COUNT(DISTINCT b.id_beneficiario) as total_viviendas,
    ROUND(AVG(COALESCE(dc.$tipo_avance, 0)), 2) as avance_promedio
FROM municipios m
LEFT JOIN parroquias p ON m.id_municipio = p.id_municipio
LEFT JOIN ubicaciones u ON p.id_parroquia = u.parroquia
LEFT JOIN beneficiarios b ON u.id_ubicacion = b.id_ubicacion
LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
WHERE m.id_estado = ?";

// Consulta para obtener datos por parroquia
$sql_parroquias = "SELECT 
    p.parroquia,
    COUNT(DISTINCT b.id_beneficiario) as total_viviendas,
    ROUND(AVG(COALESCE(dc.$tipo_avance, 0)), 2) as avance_promedio
FROM parroquias p
LEFT JOIN ubicaciones u ON p.id_parroquia = u.parroquia
LEFT JOIN beneficiarios b ON u.id_ubicacion = b.id_ubicacion
LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
WHERE p.id_municipio = ?";

// Aplicar filtros a la consulta de comunidades
if ($id_municipio) {
    $sql_comunidades .= " AND u.municipio = ?";
}
if ($id_parroquia) {
    $sql_comunidades .= " AND u.parroquia = ?";
}
if ($id_comunidad) {
    $sql_comunidades .= " AND c.id_comunidad = ?";
}
if ($estado_beneficiario && $estado_beneficiario !== 'todos') {
    $sql_comunidades .= " AND b.status = ?";
}

$sql_comunidades .= " GROUP BY c.id_comunidad ORDER BY c.comunidad";

// Preparar y ejecutar consultas
$stmt_comunidades = $conexion->prepare($sql_comunidades);
$stmt_municipios = $conexion->prepare($sql_municipios);
$stmt_parroquias = $conexion->prepare($sql_parroquias);

if ($stmt_comunidades === false || $stmt_municipios === false || $stmt_parroquias === false) {
    die("Error en la preparación de las consultas: " . $conexion->error);
}

// Vincular parámetros para la consulta de comunidades
$types_comunidades = "";
$params_comunidades = [];

if ($id_municipio) {
    $types_comunidades .= "i";
    $params_comunidades[] = $id_municipio;
}
if ($id_parroquia) {
    $types_comunidades .= "i";
    $params_comunidades[] = $id_parroquia;
}
if ($id_comunidad) {
    $types_comunidades .= "i";
    $params_comunidades[] = $id_comunidad;
}
if ($estado_beneficiario && $estado_beneficiario !== 'todos') {
    $types_comunidades .= "s";
    $params_comunidades[] = $estado_beneficiario;
}

if (!empty($params_comunidades)) {
    $stmt_comunidades->bind_param($types_comunidades, ...$params_comunidades);
}

// Ejecutar consultas
$stmt_municipios->bind_param("i", $id_lara);
$stmt_municipios->execute();
$result_municipios = $stmt_municipios->get_result();
$municipios = $result_municipios->fetch_all(MYSQLI_ASSOC);

if ($id_municipio) {
    $stmt_parroquias->bind_param("i", $id_municipio);
    $stmt_parroquias->execute();
    $result_parroquias = $stmt_parroquias->get_result();
    $parroquias = $result_parroquias->fetch_all(MYSQLI_ASSOC);
}

$stmt_comunidades->execute();
$result_comunidades = $stmt_comunidades->get_result();
$comunidades = $result_comunidades->fetch_all(MYSQLI_ASSOC);

// Consulta para obtener códigos de obra únicos
$sql_codigos_obra = "SELECT DISTINCT b.cod_obra, cod.cod_obra as codigo 
                     FROM beneficiarios b 
                     JOIN cod_obra cod ON b.cod_obra = cod.id_cod_obra 
                     WHERE b.cod_obra IS NOT NULL 
                     ORDER BY cod.cod_obra";
$result_codigos_obra = $conexion->query($sql_codigos_obra);
$codigos_obra = $result_codigos_obra->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Avance - SIGEVU</title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/reportes.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        
        .table th {
            white-space: nowrap;
            background-color: #f8f9fa;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .progress-container {
            min-width: 100px;
        }
        
        .badge {
            min-width: 40px;
        }
        
        .table td .badge.bg-info {
            background-color: rgba(13, 202, 240, 0.1) !important;
            color: #0dcaf0 !important;
            padding: 8px 12px;
            font-weight: 500;
        }
        
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            
            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
            }
            
            .table th {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
            
            .table {
                width: 100% !important;
                font-size: 10pt;
            }
            
            .table td, .table th {
                padding: 0.3rem;
            }
            
            .container {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .navbar, .btn, .form-control, .input-group {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark glass-navbar fixed-top no-print">
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
                        <a class="nav-link" href="../php/beneficiarios.php">
                            <i class="fas fa-users me-1"></i> Beneficiarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                </ul>
                <div class="d-flex ms-3">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <a class="nav-link ms-2" href="../php/conf/logout.php" style="color: #f8f9fa">
                        <i class="fas fa-sign-out-alt me-1" style="color: #f8f9fa"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <h1 class="page-title text-center">Reportes de Avance Constructivo</h1>
        
        <!-- Resumen General -->
        <?php if (!empty($reportes)): ?>
        <div class="summary-card">
            <div class="row">
                <div class="col-md-3 summary-item">
                    <span class="summary-number"><?= $total_viviendas_general ?></span>
                    <span class="summary-label">Total Viviendas</span>
                </div>
                <div class="col-md-3 summary-item">
                    <span class="summary-number"><?= $avance_promedio_general ?>%</span>
                    <span class="summary-label">Avance Promedio</span>
                </div>
                <div class="col-md-2 summary-item">
                    <span class="summary-number"><?= $total_completadas_general ?></span>
                    <span class="summary-label">Completadas</span>
                </div>
                <div class="col-md-2 summary-item">
                    <span class="summary-number"><?= $total_en_progreso_general ?></span>
                    <span class="summary-label">En Progreso</span>
                </div>
                <div class="col-md-2 summary-item">
                    <span class="summary-number"><?= $total_no_iniciadas_general ?></span>
                    <span class="summary-label">Sin Iniciar</span>
                </div>
                <div class="col-md-3 summary-item">
                    <span class="summary-number"><?= $total_protocolizados ?></span>
                    <span class="summary-label">Protocolizados</span>
                </div>
                <div class="col-md-3 summary-item">
                    <span class="summary-number"><?= $total_no_protocolizados ?></span>
                    <span class="summary-label">No Protocolizados</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="card">
            <div class="card-header bg-primary text-white text-center">
                <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
            </div>
            <div class="card-body">
                <form id="filterForm" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estados" id="estadoSelect" class="form-select" disabled>
                            <?php
                            $estadoLara = $conexion->query("SELECT id_estado, estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
                            echo "<option value='{$estadoLara['id_estado']}' selected>{$estadoLara['estado']}</option>";
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Municipio</label>
                        <select name="municipios" id="municipioSelect" class="form-select">
                            <option value="">Todos</option>
                            <?php
                            if ($estadoLara) {
                                $municipios = $conexion->query("SELECT id_municipio, municipio FROM municipios WHERE id_estado = {$estadoLara['id_estado']} ORDER BY municipio");
                                while ($row = $municipios->fetch_assoc()) {
                                    echo "<option value='{$row['id_municipio']}' " . ($row['id_municipio'] == ($_GET['municipios'] ?? '') ? 'selected' : '') . ">{$row['municipio']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Parroquia</label>
                        <select name="parroquias" id="parroquiaSelect" class="form-select" <?= !isset($_GET['municipios']) ? 'disabled' : '' ?>>
                            <option value="">Todas</option>
                            <?php
                            if (isset($_GET['municipios'])) {
                                $parroquias = $conexion->query("SELECT id_parroquia, parroquia FROM parroquias WHERE id_municipio = " . intval($_GET['municipios']) . " ORDER BY parroquia");
                                while ($row = $parroquias->fetch_assoc()) {
                                    echo "<option value='{$row['id_parroquia']}' " . ($row['id_parroquia'] == ($_GET['parroquias'] ?? '') ? 'selected' : '') . ">{$row['parroquia']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Estado Beneficiario</label>
                        <select name="estado" class="form-select">
                            <option value="activo" <?= (!isset($_GET['estado']) || $_GET['estado'] === 'activo') ? 'selected' : '' ?>>Activos</option>
                            <option value="inactivo" <?= (isset($_GET['estado']) && $_GET['estado'] === 'inactivo') ? 'selected' : '' ?>>Inactivos</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Comunidad</label>
                        <select name="comunidad" id="comunidadSelect" class="form-select" <?= !isset($_GET['parroquias']) ? 'disabled' : '' ?>>
                            <option value="">Todas</option>
                            <?php
                            if (isset($_GET['parroquias'])) {
                                $comunidades = $conexion->query("SELECT id_comunidad, comunidad FROM comunidades WHERE id_parroquia = " . intval($_GET['parroquias']) . " ORDER BY comunidad");
                                while ($row = $comunidades->fetch_assoc()) {
                                    echo "<option value='{$row['id_comunidad']}' " . ($row['id_comunidad'] == ($_GET['comunidad'] ?? '') ? 'selected' : '') . ">{$row['comunidad']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Código de Obra</label>
                        <select name="cod_obra" class="form-select">
                            <option value="">Todos los códigos</option>
                            <?php
                            foreach ($codigos_obra as $codigo) {
                                $selected = (isset($_GET['cod_obra']) && $_GET['cod_obra'] === $codigo['cod_obra']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($codigo['cod_obra']) . "' $selected>" . htmlspecialchars($codigo['codigo']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Avance</label>
                        <select name="tipo_avance" class="form-select" required>
                            <option value="avance_fisico" <?= $tipo_avance == 'avance_fisico' ? 'selected' : '' ?>>Avance Físico General</option>
                            <option value="acondicionamiento" <?= $tipo_avance == 'acondicionamiento' ? 'selected' : '' ?>>Acondicionamiento</option>
                            <option value="limpieza" <?= $tipo_avance == 'limpieza' ? 'selected' : '' ?>>Limpieza</option>
                            <option value="replanteo" <?= $tipo_avance == 'replanteo' ? 'selected' : '' ?>>Replanteo</option>
                            <option value="fundacion" <?= $tipo_avance == 'fundacion' ? 'selected' : '' ?>>Fundación</option>
                            <option value="excavacion" <?= $tipo_avance == 'excavacion' ? 'selected' : '' ?>>Excavación</option>
                            <option value="acero_vigas_riostra" <?= $tipo_avance == 'acero_vigas_riostra' ? 'selected' : '' ?>>Acero Vigas Riostra</option>
                            <option value="encofrado_malla" <?= $tipo_avance == 'encofrado_malla' ? 'selected' : '' ?>>Encofrado Malla</option>
                            <option value="instalaciones_electricas_sanitarias" <?= $tipo_avance == 'instalaciones_electricas_sanitarias' ? 'selected' : '' ?>>Instalaciones Eléctricas/Sanitarias</option>
                            <option value="vaciado_losa_anclajes" <?= $tipo_avance == 'vaciado_losa_anclajes' ? 'selected' : '' ?>>Vaciado Losa/Anclajes</option>
                            <option value="estructura" <?= $tipo_avance == 'estructura' ? 'selected' : '' ?>>Estructura</option>
                            <option value="armado_columnas" <?= $tipo_avance == 'armado_columnas' ? 'selected' : '' ?>>Armado Columnas</option>
                            <option value="vaciado_columnas" <?= $tipo_avance == 'vaciado_columnas' ? 'selected' : '' ?>>Vaciado Columnas</option>
                            <option value="armado_vigas" <?= $tipo_avance == 'armado_vigas' ? 'selected' : '' ?>>Armado Vigas</option>
                            <option value="vaciado_vigas" <?= $tipo_avance == 'vaciado_vigas' ? 'selected' : '' ?>>Vaciado Vigas</option>
                            <option value="cerramiento" <?= $tipo_avance == 'cerramiento' ? 'selected' : '' ?>>Cerramiento</option>
                            <option value="bloqueado" <?= $tipo_avance == 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                            <option value="colocacion_correas" <?= $tipo_avance == 'colocacion_correas' ? 'selected' : '' ?>>Colocación Correas</option>
                            <option value="colocacion_techo" <?= $tipo_avance == 'colocacion_techo' ? 'selected' : '' ?>>Colocación Techo</option>
                            <option value="acabado" <?= $tipo_avance == 'acabado' ? 'selected' : '' ?>>Acabado</option>
                            <option value="colocacion_ventanas" <?= $tipo_avance == 'colocacion_ventanas' ? 'selected' : '' ?>>Colocación Ventanas</option>
                            <option value="colocacion_puertas_principales" <?= $tipo_avance == 'colocacion_puertas_principales' ? 'selected' : '' ?>>Colocación Puertas Principales</option>
                            <option value="instalaciones_electricas_sanitarias_paredes" <?= $tipo_avance == 'instalaciones_electricas_sanitarias_paredes' ? 'selected' : '' ?>>Instalaciones Eléctricas/Sanitarias Paredes</option>
                            <option value="frisos" <?= $tipo_avance == 'frisos' ? 'selected' : '' ?>>Frisos</option>
                            <option value="sobrepiso" <?= $tipo_avance == 'sobrepiso' ? 'selected' : '' ?>>Sobrepiso</option>
                            <option value="ceramica_bano" <?= $tipo_avance == 'ceramica_bano' ? 'selected' : '' ?>>Cerámica Baño</option>
                            <option value="colocacion_puertas_internas" <?= $tipo_avance == 'colocacion_puertas_internas' ? 'selected' : '' ?>>Colocación Puertas Internas</option>
                            <option value="equipos_accesorios_electricos" <?= $tipo_avance == 'equipos_accesorios_electricos' ? 'selected' : '' ?>>Equipos/Accesorios Eléctricos</option>
                            <option value="equipos_accesorios_sanitarios" <?= $tipo_avance == 'equipos_accesorios_sanitarios' ? 'selected' : '' ?>>Equipos/Accesorios Sanitarios</option>
                            <option value="colocacion_lavaplatos" <?= $tipo_avance == 'colocacion_lavaplatos' ? 'selected' : '' ?>>Colocación Lavaplatos</option>
                            <option value="pintura" <?= $tipo_avance == 'pintura' ? 'selected' : '' ?>>Pintura</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Generar Reporte
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='reportes.php'">
                                <i class="fas fa-times me-1"></i> Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resultados Detallados</h3>
                <?php if ($rol_usuario !== 'usuario'): ?>
                <button class="btn btn-light btn-action" onclick="imprimirReporte()">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Gráfico de Avance -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <canvas id="avanceChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="estadoChart"></canvas>
                    </div>
                </div>
                <?php if (!empty($reportes)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="color: #000000;">Estado</th>
                                    <th style="color: #000000;">Municipio</th>
                                    <th style="color: #000000;">Parroquia</th>
                                    <th style="color: #000000;">Comunidad</th>
                                    <th style="color: #000000;">Código de Obra</th>
                                    <th style="color: #000000;">Total Viviendas</th>
                                    <th style="color: #000000;">Avance Promedio</th>
                                    <th style="color: #000000;">Completadas</th>
                                    <th style="color: #000000;">En Progreso</th>
                                    <th style="color: #000000;">Sin Iniciar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportes as $reporte): ?>
                                <tr>
                                    <td><?= htmlspecialchars($reporte['estado']) ?></td>
                                    <td><?= htmlspecialchars($reporte['municipio'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($reporte['parroquia'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?= htmlspecialchars($reporte['comunidad'] ?? 'No especificada') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($reporte['codigo_obra'] ?? 'N/A') ?></td>
                                    <td><strong><?= $reporte['total_viviendas'] ?? 0 ?></strong></td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-bar <?= getProgressClass($reporte['avance_promedio']) ?>" 
                                                 style="width: <?= max(0, min(100, floatval($reporte['avance_promedio'] ?? 0))) ?>%">
                                                <?= number_format(floatval($reporte['avance_promedio'] ?? 0), 2) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success"><?= $reporte['completadas'] ?? 0 ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?= $reporte['en_progreso'] ?? 0 ?></span></td>
                                    <td><span class="badge bg-danger"><?= $reporte['no_iniciadas'] ?? 0 ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se encontraron resultados con los filtros seleccionados.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Datos para los gráficos existentes
            const avanceData = {
                labels: ['Completadas', 'En Progreso', 'Sin Iniciar'],
                datasets: [{
                    data: [
                        <?= $total_completadas_general ?>,
                        <?= $total_en_progreso_general ?>,
                        <?= $total_no_iniciadas_general ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            };

            const estadoData = {
                labels: ['Avance Promedio'],
                datasets: [{
                    data: [<?= $avance_promedio_general ?>],
                    backgroundColor: '#0dcaf0',
                    borderColor: '#0dcaf0',
                    borderWidth: 1
                }]
            };

            // Configuración del gráfico de distribución
            new Chart(document.getElementById('avanceChart'), {
                type: 'pie',
                data: avanceData,
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribución de Viviendas por Estado',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Configuración del gráfico de estado
            new Chart(document.getElementById('estadoChart'), {
                type: 'bar',
                data: estadoData,
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Avance Promedio General',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Porcentaje (%)'
                            }
                        }
                    }
                }
            });

            const municipioSelect = document.getElementById('municipioSelect');
            const parroquiaSelect = document.getElementById('parroquiaSelect');
            const comunidadSelect = document.getElementById('comunidadSelect');

            // Función para cargar parroquias
            async function cargarParroquias(municipioId) {
                if (!municipioId) {
                    parroquiaSelect.innerHTML = '<option value="">Todas</option>';
                    parroquiaSelect.disabled = true;
                    comunidadSelect.innerHTML = '<option value="">Todas</option>';
                    comunidadSelect.disabled = true;
                    return;
                }

                try {
                    const response = await fetch(`../php/conf/get_parroquias.php?municipio_id=${municipioId}`);
                    const data = await response.json();
                    
                    parroquiaSelect.innerHTML = '<option value="">Todas</option>';
                    data.forEach(parroquia => {
                        const option = document.createElement('option');
                        option.value = parroquia.id_parroquia;
                        option.textContent = parroquia.parroquia;
                        parroquiaSelect.appendChild(option);
                    });
                    parroquiaSelect.disabled = false;
                } catch (error) {
                    console.error('Error cargando parroquias:', error);
                    parroquiaSelect.innerHTML = '<option value="">Error al cargar parroquias</option>';
                }
            }

            // Función para cargar comunidades
            async function cargarComunidades(parroquiaId) {
                if (!parroquiaId) {
                    comunidadSelect.innerHTML = '<option value="">Todas</option>';
                    comunidadSelect.disabled = true;
                    return;
                }

                try {
                    const response = await fetch(`../php/conf/obtener_comunidades.php?id_parroquia=${parroquiaId}`);
                    const data = await response.json();
                    
                    comunidadSelect.innerHTML = '<option value="">Todas</option>';
                    data.forEach(comunidad => {
                        const option = document.createElement('option');
                        option.value = comunidad.id_comunidad;
                        option.textContent = comunidad.nombre;
                        comunidadSelect.appendChild(option);
                    });
                    comunidadSelect.disabled = false;
                } catch (error) {
                    console.error('Error cargando comunidades:', error);
                    comunidadSelect.innerHTML = '<option value="">Error al cargar comunidades</option>';
                }
            }

            // Eventos para los selects
            municipioSelect.addEventListener('change', function() {
                cargarParroquias(this.value);
            });

            parroquiaSelect.addEventListener('change', function() {
                cargarComunidades(this.value);
            });

            // Cargar datos iniciales si hay valores seleccionados
            if (municipioSelect.value) {
                cargarParroquias(municipioSelect.value);
            }
            if (parroquiaSelect.value) {
                cargarComunidades(parroquiaSelect.value);
            }
        });

        function imprimirReporte() {
            // Clonar solo la tabla de resultados y el resumen general
            const card = document.querySelector('.card:last-child');
            const tabla = card.querySelector('.table-responsive').cloneNode(true);
            const resumen = document.querySelector('.summary-card')?.cloneNode(true);

            const ventanaImpresion = window.open('', '_blank');
            ventanaImpresion.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Reporte de Avance Constructivo - SIGEVU</title>
                    <style>
                        @page {
                            size: A4;
                            margin: 1cm;
                        }
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 0; 
                            padding: 20px;
                            width: 210mm;
                            height: 297mm;
                        }
                        h1 { 
                            color: #1565C0; 
                            text-align: center; 
                            margin-bottom: 30px;
                            font-size: 24px;
                        }
                        h3 { 
                            color: #333; 
                            margin-bottom: 20px;
                            font-size: 18px;
                        }
                        table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin-top: 20px;
                            font-size: 12px;
                        }
                        th, td { 
                            border: 1px solid #ddd; 
                            padding: 8px; 
                            text-align: left;
                            vertical-align: middle;
                        }
                        th { 
                            background-color: #f2f2f2; 
                            font-weight: bold;
                            white-space: nowrap;
                        }
                        .progress-container { 
                            border: 1px solid #ddd; 
                            height: 20px; 
                            background: #f8f9fa;
                            min-width: 100px;
                        }
                        .progress-bar { 
                            height: 100%; 
                            color: white; 
                            text-align: center; 
                            line-height: 20px;
                            font-size: 11px;
                        }
                        .complete { background-color: #28a745; }
                        .in-progress { background-color: #ffc107; }
                        .not-started { background-color: #dc3545; }
                        .badge { 
                            padding: 4px 8px; 
                            border-radius: 4px; 
                            color: white; 
                            font-size: 11px;
                            display: inline-block;
                            min-width: 30px;
                            text-align: center;
                        }
                        .bg-success { background-color: #28a745; }
                        .bg-warning { background-color: #ffc107; color: #000; }
                        .bg-danger { background-color: #dc3545; }
                        .text-center { text-align: center; }
                        .summary { 
                            background: #f8f9fa; 
                            padding: 15px; 
                            margin-bottom: 20px; 
                            border-radius: 5px;
                            font-size: 14px;
                        }
                        .comunidad-badge {
                            background-color: rgba(13, 202, 240, 0.1);
                            color: #0dcaf0;
                            padding: 4px 8px;
                            border-radius: 4px;
                            font-size: 11px;
                            display: inline-block;
                        }
                    </style>
                </head>
                <body>
                    <h1>Reporte de Avance Constructivo</h1>
                    ${resumen ? resumen.outerHTML : ''}
                    ${tabla.outerHTML}
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() {
                                window.close();
                            }, 100);
                        };
                    <\/script>
                </body>
                </html>
            `);
            ventanaImpresion.document.close();
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$stmt_comunidades->close();
$stmt_municipios->close();
$stmt_parroquias->close();
$conexion->close();
?>