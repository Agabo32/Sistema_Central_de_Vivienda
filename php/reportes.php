<?php
session_start();
require_once '../php/conf/conexion.php';

// Verificación robusta de sesión y rol
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'admin';

// Retrieve filter parameters
$estadoLara = $conexion->query("SELECT id_estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
$id_lara = $estadoLara['id_estado'];
$id_municipio = $_GET['municipios'] ?? null;
$id_parroquia = $_GET['parroquias'] ?? null;
$estado_beneficiario = $_GET['estado'] ?? null;
$tipo_avance = $_GET['tipo_avance'] ?? 'avance_fisico';

function getProgressClass($valor) {
    if ($valor >= 100) return 'complete';
    if ($valor > 0) return 'in-progress';
    return 'not-started';
}

// Consulta SQL dinámica según filtros
$sql = "SELECT 
    e.id_estado,
    e.estado AS nombre_estado,
    m.id_municipio,
    m.municipio,
    p.id_parroquia,
    p.parroquia,
    COUNT(b.id_beneficiario) AS total_viviendas,
    AVG(dc.$tipo_avance) AS avance_promedio,
    SUM(CASE WHEN dc.$tipo_avance >= 100 THEN 1 ELSE 0 END) AS completadas,
    SUM(CASE WHEN dc.$tipo_avance > 0 AND dc.$tipo_avance < 100 THEN 1 ELSE 0 END) AS en_progreso,
    SUM(CASE WHEN dc.$tipo_avance = 0 OR dc.$tipo_avance IS NULL THEN 1 ELSE 0 END) AS no_iniciadas
FROM estados e
LEFT JOIN municipios m ON e.id_estado = m.id_estado
LEFT JOIN parroquias p ON m.id_municipio = p.id_municipio
LEFT JOIN ubicaciones u ON p.id_parroquia = u.id_parroquia
LEFT JOIN Beneficiarios b ON u.id_ubicacion = b.id_ubicacion
LEFT JOIN Datos_de_Construccion dc ON b.id_beneficiario = dc.id_beneficiario
WHERE e.id_estado = $id_lara";

$types = "";
$params = [];

// Aplicar filtros jerárquicos

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

// Filtro por estado del beneficiario
if ($estado_beneficiario) {
    $sql .= " AND b.status = ?";
    $types .= "s";
    $params[] = $estado_beneficiario;
}

$sql .= " GROUP BY e.id_estado, m.id_municipio, p.id_parroquia";

// Preparar la consulta
$stmt = $conexion->prepare($sql);
if ($stmt === false) {
    die("Error en la consulta: " . $conexion->error);
}

// Vincular parámetros si existen
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Ejecutar consulta
$stmt->execute();
$result = $stmt->get_result();
$reportes = $result->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Avance - SIGEVU</title>
    <!-- Mismos estilos que datos_beneficiario.php -->
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="..//css/reportes.css">
</head>
<body>
    <!-- Barra de navegación (igual que en datos_beneficiario.php) -->
    <nav class="navbar navbar-expand-lg navbar-dark glass-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
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
                        <a class="nav-link" href="#">
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
        <h1 class="page-title text-center">Reportes de Avance Constructivo</h1>
        
        <!-- Filtros actualizados -->
        <form id="filterForm" method="GET" class="row g-3">
        <div class="col-md-3">
    <label class="form-label">Estado</label>
    <select name="estados" id="estadoSelect" class="form-select">
        <?php
     
        $estadoLara = $conexion->query("SELECT id_estado, estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
        echo "<option value='{$estadoLara['id_estado']}' selected>{$estadoLara['estado']}</option>";
        ?>
    </select>
</div>

<div class="col-md-3">
    <label class="form-label">Municipio</label>
    <select name="municipios" id="municipioSelect" class="form-select"> <!-- Quitamos disabled -->
        <option value="">Todos</option>
        <?php
        // Cargar municipios de Lara automáticamente (evita depender solo de JS)
        if ($estadoLara) {
            $municipios = $conexion->query("SELECT id_municipio, municipio FROM municipios WHERE id_estado = {$estadoLara['id_estado']}");
            while ($row = $municipios->fetch_assoc()) {
                echo "<option value='{$row['id_municipio']}' " . ($row['id_municipio'] == ($_GET['municipios'] ?? '') ? 'selected' : '') . ">{$row['municipio']}</option>";
            }
        }
        ?>
    </select>
</div>
            <div class="col-md-3">
                <label class="form-label">Parroquia</label>
                <select name="parroquias" id="parroquiaSelect" class="form-select" <?= !$id_municipio ? 'disabled' : '' ?>>
                    <option value="">Todas</option>
                    <?php
                    if ($id_municipio) {
                        $parroquias = $conexion->query("SELECT id_parroquia, parroquia FROM parroquias WHERE id_municipio = $id_municipio");
                        while ($row = $parroquias->fetch_assoc()) {
                            echo "<option value='{$row['id_parroquia']}' " . ($row['id_parroquia'] == $id_parroquia ? 'selected' : '') . ">{$row['parroquia']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado Beneficiario</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="activo" <?= $estado_beneficiario == 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= $estado_beneficiario == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo de Avance</label>
                <select name="tipo_avance" class="form-select" required>
                    <option value="cerramiento" <?= $tipo_avance == 'cerramiento' ? 'selected' : '' ?>>Cerramiento</option>
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
        <option value="avance_fisico" <?= $tipo_avance == 'avance_fisico' ? 'selected' : '' ?>>Avance Físico General</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-action">
                    <i class="fas fa-search me-1"></i> Generar Reporte
                </button>
            </div>
        </form>

        <!-- Resultados -->
    <div class="card">  <!-- Este div falta -->
        <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resultados</h3>
        <?php if ($esAdmin): ?>
            <button class="btn btn-primary btn-action" onclick="imprimirReporte()">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
        <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($reportes)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Municipio</th>
                                <th>Parroquia</th>
                                <th>Total Viviendas</th>
                                <th>Avance Promedio</th>
                                <th>Completadas</th>
                                <th>En Progreso</th>
                                <th>No Iniciadas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportes as $reporte): ?>
                <tr>
                    <td><?= htmlspecialchars($reporte['nombre_estado']) ?></td>
                    <td><?= htmlspecialchars($reporte['municipio'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($reporte['parroquia'] ?? 'N/A') ?></td>
                    <td><?= $reporte['total_viviendas'] ?? 0 ?></td>
                    <td>
                        <div class="progress-container">
                            <div class="progress-bar <?= getProgressClass($reporte['avance_promedio']) ?>" 
                                 style="width: <?= $reporte['avance_promedio'] ?>%">
                                <?= number_format($reporte['avance_promedio'], 2) ?>%
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-success"><?= $reporte['completadas'] ?? 0 ?></span></td>
                    <td><span class="badge bg-warning"><?= $reporte['en_progreso'] ?? 0 ?></span></td>
                    <td><span class="badge bg-danger"><?= $reporte['no_iniciadas'] ?? 0 ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">No se encontraron resultados con los filtros seleccionados.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
   

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

   document.addEventListener('DOMContentLoaded', function() {
    const estadoSelect = document.getElementById('estadoSelect');
    const municipioSelect = document.getElementById('municipioSelect');
    const parroquiaSelect = document.getElementById('parroquiaSelect');

    // Cargar municipios de Lara automáticamente al inicio
    loadMunicipios();

    function loadMunicipios() {
        const idEstado = estadoSelect.value;
        if (!idEstado) return;

        fetch(`../php/conf/get_municipios.php?estado_id=${idEstado}`)
            .then(response => response.json())
            .then(data => {
                let municipiosHTML = '<option value="">Todos</option>';
                data.forEach(municipio => {
                    municipiosHTML += `<option value="${municipio.id_municipio}">${municipio.municipio}</option>`;
                });
                municipioSelect.innerHTML = municipiosHTML;
            });
    }

    // Cargar parroquias al seleccionar municipio (opcional)
    municipioSelect.addEventListener('change', function() {
        const idMunicipio = this.value;
        if (!idMunicipio) {
            parroquiaSelect.innerHTML = '<option value="">Todas</option>';
            parroquiaSelect.disabled = true;
            return;
        }

        parroquiaSelect.disabled = false;
        fetch(`../php/conf/get_parroquias.php?municipio_id=${idMunicipio}`)
            .then(response => response.json())
            .then(data => {
                let parroquiasHTML = '<option value="">Todas</option>';
                data.forEach(parroquia => {
                    parroquiasHTML += `<option value="${parroquia.id_parroquia}">${parroquia.parroquia}</option>`;
                });
                parroquiaSelect.innerHTML = parroquiasHTML;
            });
    });
});


function imprimirReporte() {
            <?php if (!$esAdmin): ?>
                alert('No tienes permisos para realizar esta acción');
                return false;
            <?php endif; ?>

            // Crear un clon del contenido a imprimir (ahora seleccionando correctamente el .card)
            const contenido = document.querySelector('.card').cloneNode(true);
            
            // Ocultar botones en el clon
            const botones = contenido.querySelectorAll('.btn-action');
            botones.forEach(boton => boton.style.display = 'none');
            
            // Abrir una nueva ventana para imprimir
            const ventanaImpresion = window.open('', '_blank');
            ventanaImpresion.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Reporte de Avance Constructivo</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                        h1 { color: #1565C0; text-align: center; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .progress-container { border: 1px solid #ddd; height: 20px; }
                        .progress-bar { height: 100%; background-color: #4CAF50; }
                        .badge { padding: 3px 8px; border-radius: 4px; color: white; }
                        .bg-success { background-color: #4CAF50; }
                        .bg-warning { background-color: #FFC107; }
                        .bg-danger { background-color: #F44336; }
                    </style>
                </head>
                <body>
                    <h1>Reporte de Avance Constructivo</h1>
                    ${contenido.innerHTML}
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
<?

$stmt->close();
$conexion->close();
?>