<?php
require_once '../php/conf/conexion.php';

// Obtener parámetros de filtro (si existen)
$id_municipio = $_GET['municipios'] ?? null;
$id_parroquia = $_GET['parroquias'] ?? null;
$tipo_avance = $_GET['tipo_avance'] ?? 'avance_fisico'; // Valor por defecto
$columnas_validas = ['avance_fisico', 'cerramiento', 'pintura']; // agrega las que tengas

function getProgressClass($valor) {
    if ($valor >= 100) return 'complete';
    if ($valor >= 50) return 'medium';
    return 'low';
}

// Consulta SQL dinámica según filtros
$sql = "
    SELECT 
        m.id_municipio,
    m.municipio AS municipio,  
    p.id_parroquia,
    p.parroquia AS parroquia, 
        COUNT(b.id_beneficiario) AS total_viviendas,
        AVG(dc.$tipo_avance) AS avance_promedio,
        SUM(CASE WHEN dc.$tipo_avance >= 100 THEN 1 ELSE 0 END) AS completadas,
        SUM(CASE WHEN dc.$tipo_avance > 0 AND dc.$tipo_avance < 100 THEN 1 ELSE 0 END) AS en_progreso,
        SUM(CASE WHEN dc.$tipo_avance = 0 OR dc.$tipo_avance IS NULL THEN 1 ELSE 0 END) AS no_iniciadas
    FROM Beneficiarios b
    LEFT JOIN Ubicaciones u ON b.id_ubicacion = u.id_ubicacion
    LEFT JOIN municipios m ON u.id_municipio = m.id_municipio
    LEFT JOIN parroquias p ON u.id_parroquia = p.id_parroquia
    LEFT JOIN Datos_de_Construccion dc ON b.id_beneficiario = dc.id_beneficiario
    WHERE 1=1
";

$types = "";
$params = [];

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

$sql .= " GROUP BY m.id_municipio, p.id_parroquia";

// Preparar la consulta
$stmt = $conexion->prepare($sql);
if ($stmt === false) {
    die("Error en la consulta: " . $conexion->error);
}

// Vincular parámetros si existen
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!in_array($tipo_avance, $columnas_validas)) {
    $tipo_avance = 'avance_fisico'; // fallback seguro
}

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
    <style>
        /* Copia exacta del CSS de datos_beneficiario.php */
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
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-filter me-2"></i>Filtros</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Municipio</label>
                    <select name="municipio" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $municipios = $conexion->query("SELECT id_municipio, nombre FROM municipio");
                        while ($row = $municipios->fetch_assoc()) {
            echo "<option value='{$row['municipio']}' " . ($row['municipio'] == $id_municipio ? 'selected' : '') . ">{$row['id_municipio']}</option>";
        }
        ?>
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">Parroquia</label>
    <select name="parroquia" class="form-select">
        <option value="">Todas</option>
        <?php
        $where = $id_municipio ? "WHERE id_municipio = $id_municipio" : "";
        $parroquias = $conexion->query("SELECT id_parroquia, nombre FROM parroquia $where");
        while ($row = $parroquias->fetch_assoc()) {
            echo "<option value='{$row['id_parroquia']}' " . ($row['id_parroquia'] == $id_parroquia ? 'selected' : '') . ">{$row['id_parroquia']}</option>";
        }
        ?>
    </select>
</div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Avance</label>
                        <select name="tipo_avance" class="form-select" required>
                            <option value="avance_fisico" <?= $tipo_avance == 'avance_fisico' ? 'selected' : '' ?>>Avance Físico General</option>
                            <option value="cerramiento" <?= $tipo_avance == 'cerramiento' ? 'selected' : '' ?>>Cerramiento</option>
                            <option value="pintura" <?= $tipo_avance == 'pintura' ? 'selected' : '' ?>>Pintura</option>
                            <!-- Agrega más opciones según las columnas de Datos_de_Construccion -->
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-action">
                            <i class="fas fa-search me-1"></i> Generar Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resultados</h3>
                <button class="btn btn-primary btn-action" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($reportes)): ?>
                    <div class="alert alert-info">No hay datos con los filtros seleccionados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
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
                                    <td><?= htmlspecialchars($reporte['municipio']) ?></td>
                                    <td><?= htmlspecialchars($reporte['parroquia']) ?></td>
                                    <td><?= $reporte['total_viviendas'] ?></td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-bar <?= getProgressClass($reporte['avance_promedio']) ?>" 
                                                 style="width: <?= $reporte['avance_promedio'] ?>%">
                                                <?= number_format($reporte['avance_promedio'], 2) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success"><?= $reporte['completadas'] ?></span></td>
                                    <td><span class="badge bg-warning"><?= $reporte['en_progreso'] ?></span></td>
                                    <td><span class="badge bg-danger"><?= $reporte['no_iniciadas'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar opciones de parroquias según municipio seleccionado
document.querySelector('select[name="municipio"]').addEventListener('change', function() {
    const id_municipio = this.value;
    fetch(`get_parroquias.php?id_municipio=${id_municipio}`)
        .then(response => response.json())
        .then(data => {
            const parroquiaSelect = document.querySelector('select[name="parroquia"]');
            parroquiaSelect.innerHTML = '<option value="">Todas</option>';
            data.forEach(parroquia => {
                parroquiaSelect.innerHTML += `<option value="${parroquia.id_parroquia}">${parroquia.parroquia}</option>`;
            });
        });
});
    </script>
</body>
</html>
<?php
$stmt->close();
$conexion->close();
?>