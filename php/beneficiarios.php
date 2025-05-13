<?php
session_start();
require_once '../php/conf/conexion.php';

// Verificación robusta de sesión y rol
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'admin';
$_SESSION['rol'] === 'admin';
$params = [];
$types = '';
$filtro_condiciones = '';
$lara = $conexion->query("SELECT id_estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
$id_lara = $lara['id_estado'];


// Default to show only active
$show_inactive = $_GET['show_inactive'] ?? false;

$sql = isset($_SESSION['admin']) && $_SESSION['admin'] 
    ? "SELECT * FROM beneficiarios" 
    : "SELECT * FROM beneficiarios WHERE status = 'activo'";

$registros_por_pagina = 10; // Número de registros por página
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Preparar consulta con posibles filtros
$sql_base = "SELECT b.*, 
    p.parroquia, 
    m.municipio, 
    e.estado 
FROM Beneficiarios b
LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
LEFT JOIN parroquias p ON u.id_parroquia = p.id_parroquia
LEFT JOIN municipios m ON p.id_municipio = m.id_municipio
LEFT JOIN estados e ON m.id_estado = e.id_estado
WHERE e.id_estado = $id_lara "; // Forzar filtro por Lara

// Solo administradores pueden ver inactivos si lo solicitan explícitamente
if (!(isset($_SESSION['admin']) && $_SESSION['admin'] && isset($_GET['show_inactive']) && $_GET['show_inactive'])) {
    $sql_base .= " AND b.status = 'activo'";
}

// Aplicar filtros si existen
if (!empty($_GET['estado'])) {
    $sql_base .= " AND e.id_estado = ?";
    $params[] = $_GET['estado'];
    $types .= 'i';
}
if (!empty($_GET['municipio'])) {
    $sql_base .= " AND m.id_municipio = ?";
    $params[] = $_GET['municipio'];
    $types .= 'i';
}

if (!empty($_GET['parroquia'])) {
    $sql_base .= " AND p.id_parroquia = ?";
    $params[] = $_GET['parroquia'];
    $types .= 'i';
}

// Después de los otros filtros, añade:
if (!empty($_GET['codigo_obra'])) {
    $sql_base .= " AND b.codigo_obra LIKE ?";
    $params[] = '%'.$_GET['codigo_obra'].'%';
    $types .= 's';
}

// Consulta para contar total de registros
$total_registros_query = $sql_base;
$total_stmt = $conexion->prepare($total_registros_query);
if (!empty($params)) {
    $total_stmt->bind_param($types, ...$params);
}
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_registros = $total_result->num_rows;

// Calcular total de páginas
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Añadir límite y offset a la consulta principal
$sql_base .= " LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $registros_por_pagina;
$params[] = $offset;

// Ejecutar consulta con paginación
$stmt = $conexion->prepare($sql_base);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$beneficiarios = $result->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiarios - SIGEVU</title>
    <link rel="icon" type="image/x-icon" href="/imagenes/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- CSS personalizado -->
    <style>
        :root {
            --primary-color: #0D36EEFF;
            --primary-hover: #0523AAFF;
            --secondary-color: #0523AAFF;
            --accent-color: #0523AAFF;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #ef233c;
            --card-bg: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            font-family: 'Poppins', sans-serif;
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
            min-height: 100vh;
            color: #333;
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

        .content-wrapper {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            margin-top: 80px;
            transition: var(--transition);
        }

        .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-weight: 500;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .table {
            --bs-table-bg: transparent;
        }

        .table th {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .table-hover tbody tr {
            transition: var(--transition);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
            transform: scale(1.005);
        }

        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 40px;
            border-radius: 50px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
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

        .modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Efecto de carga */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
            min-height: 20px;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
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
                    <a class="nav-link ms-2" href="../index.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <div class="container-fluid py-5">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <i class="fas fa-filter me-2"></i> Filtros de Búsqueda
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select name="estado" id="estadoSelect" class="form-select" disabled>
                                <?php
                                // Forzar solo el estado Lara
                                $lara = $conexion->query("SELECT id_estado, estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
                                echo "<option value='{$lara['id_estado']}' selected>{$lara['estado']}</option>";
                                ?>
                            </select>
                        </div>
<div class="col-md-4">
    <label class="form-label">Municipio</label>
    <select name="municipio" id="municipioSelect" class="form-select">
        <option value="">Todos</option>
        <?php
        // Cargar solo municipios de Lara
        $municipios = $conexion->query("SELECT id_municipio, municipio FROM municipios WHERE id_estado = {$lara['id_estado']}");
        while ($row = $municipios->fetch_assoc()) {
            $selected = (isset($_GET['municipio']) && $_GET['municipio'] == $row['id_municipio']) ? 'selected' : '';
            echo "<option value='{$row['id_municipio']}' $selected>{$row['municipio']}</option>";
        }
        ?>
    </select>
</div>
            <div class="col-md-4">
                <label class="form-label">Parroquia</label>
                <select name="parroquia" id="parroquiaSelect" class="form-select" <?= !isset($_GET['municipio']) ? 'disabled' : '' ?>>
                    <option value="">Todas</option>
                    <?php
                    if (isset($_GET['municipio'])) {
                        $parroquias = $conexion->query("SELECT id_parroquia, parroquia FROM parroquias WHERE id_municipio = ".intval($_GET['municipio']));
                        while ($row = $parroquias->fetch_assoc()) {
                            echo "<option value='{$row['id_parroquia']}' ".(isset($_GET['parroquia']) && $_GET['parroquia'] == $row['id_parroquia'] ? 'selected' : '').">{$row['parroquia']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-1"></i> Filtrar
                </button>
                <a href="beneficiarios.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Limpiar
                </a>
            </div>

        </form>
    </div>
</div>
    <div class="content-wrapper animated">
            <div class="container-fluid p-4">
                <!-- Título y botones de acción -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-users me-2"></i> Listado de Beneficiarios
                        </h2>
                    </div>
                    <div class="col-md-6 text-end">
    <?php if ($esAdmin): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoBeneficiario">
            <i class="fas fa-plus me-2"></i> Nuevo Beneficiario
        </button>
    <?php endif; ?>
</div>
                </div>

                <!-- Tabla de beneficiarios -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">
                            <i class="fas fa-list me-2"></i> Beneficiarios Registrados
                        </h5>
                        <div class="search-box" style="max-width: 300px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="form-control" id="buscar" placeholder="Buscar beneficiario...">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th>Cédula</th>
                                        <th>Nombre Completo</th>
                                        <th>Teléfono</th>
                                        <th>Código Obra</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaBeneficiarios">
                                    <?php if ($beneficiarios && count($beneficiarios) > 0): ?>
                                        <?php foreach ($beneficiarios as $beneficiario): ?>
                                            <tr>
                                                <td class="text-center fw-bold"><?= htmlspecialchars($beneficiario['id_beneficiario']) ?></td>
                                                <td><?= htmlspecialchars($beneficiario['cedula']) ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium"><?= htmlspecialchars($beneficiario['nombre_beneficiario']) ?></div>
                                                            <small class="text-muted">ID: <?= htmlspecialchars($beneficiario['id_beneficiario']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($beneficiario['telefono']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                        <?= htmlspecialchars($beneficiario['codigo_obra']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="datos_beneficiario.php?id=<?= $beneficiario['id_beneficiario'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye me-1"></i> Detalles
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-users-slash text-muted mb-2" style="font-size: 2rem;"></i>
                                                    <h5 class="text-muted">No hay beneficiarios registrados</h5>
                                                    <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalNuevoBeneficiario">
                                                        <i class="fas fa-plus me-1"></i> Agregar Beneficiario
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <nav aria-label="Paginación de Beneficiarios">
    <ul class="pagination justify-content-center">
        <?php if ($pagina_actual > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= !empty($_GET['estado']) ? '&estado='.$_GET['estado'] : '' ?>">Anterior</a>
            </li>
        <?php endif; ?>

        <?php 
        // Mostrar páginas cercanas
        $rango = 2; // Número de páginas a mostrar antes y después de la página actual
        $inicio = max(1, $pagina_actual - $rango);
        $fin = min($total_paginas, $pagina_actual + $rango);

        // Mostrar primera página si no está en el rango inicial
        if ($inicio > 1) {
            echo '<li class="page-item"><a class="page-link" href="?pagina=1">1</a></li>';
            if ($inicio > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Mostrar páginas en el rango
        for ($i = $inicio; $i <= $fin; $i++): 
            $active = $i == $pagina_actual ? 'active' : '';
        ?>
            <li class="page-item <?= $active ?>">
                <a class="page-link" href="?pagina=<?= $i ?><?= !empty($_GET['estado']) ? '&estado='.$_GET['estado'] : '' ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>

<?php if ($fin < $total_paginas): ?>
    <li class="page-item disabled"><span class="page-link">...</span></li>
    
    <li class="page-item">
        <a class="page-link" href="?pagina=<?= $total_paginas ?><?= !empty($_GET['estado']) ? '&estado='.$_GET['estado'] : '' ?>">
            <?= $total_paginas ?>
        </a>
    </li>
<?php endif; ?>

<?php if ($pagina_actual < $total_paginas): ?>
    <li class="page-item">
        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?><?= !empty($_GET['estado']) ? '&estado='.$_GET['estado'] : '' ?>">Siguiente</a>
    </li>
<?php endif; ?>
    </ul>
</nav>          
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal Nuevo Beneficiario -->
    <div class="modal fade" id="modalNuevoBeneficiario" tabindex="-1" aria-labelledby="modalNuevoBeneficiarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="col-md-6 text-end">
    <?php if ($esAdmin): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoBeneficiario">
            <i class="fas fa-plus me-2"></i> Nuevo Beneficiario
        </button>
    <?php endif; ?>
</div>
            <form id="agregarBeneficiarioForm" method="POST" action="../php/conf/guardar_beneficiario.php">
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="cedula" class="form-label">Cédula</label>
                <input type="text" class="form-control" id="cedula" name="cedula" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="tel" class="form-control" id="telefono" name="telefono" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="codigo_obra" class="form-label">Código de Obra</label>
                <input type="text" class="form-control" id="codigo_obra" name="codigo_obra" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="comunidad" class="form-label">Comunidad</label>
                <input type="text" class="form-control" id="comunidad" name="comunidad" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="activo" selected>Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>
        <!-- Nuevos campos para municipio y parroquia -->
        <div class="row">
        <div class="col-md-6 mb-3">
        <label for="modalEstado" class="form-label">Estado</label>
        <select name="estado" id="modalEstado" class="form-select" required>
            <option value="">Seleccione un estado</option>
            <?php
            // Obtener solo el estado Lara
            $estado = $conexion->query("SELECT id_estado, estado FROM estados WHERE estado = 'Lara'");
            if ($row = $estado->fetch_assoc()) {
                echo "<option value='{$row['id_estado']}' selected>{$row['estado']}</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="modalMunicipio" class="form-label">Municipio</label>
        <select name="municipio" id="modalMunicipio" class="form-select" required>
            <option value="">Seleccione un municipio</option>
            <?php
            // Cargar municipios de Lara
            $municipios = $conexion->query("SELECT id_municipio, municipio FROM municipios WHERE id_estado = {$row['id_estado']} ORDER BY municipio ASC");
            while ($mun = $municipios->fetch_assoc()) {
                echo "<option value='{$mun['id_municipio']}'>{$mun['municipio']}</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="modalParroquia" class="form-label">Parroquia</label>
        <select name="parroquia" id="modalParroquia" class="form-select" required disabled>
            <option value="">Primero seleccione un municipio</option>
        </select>
    </div>
</div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> Guardar
        </button>
    </div>
</form>
        </div>
    </div>
</div>

    <!-- Bootstrap JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       document.addEventListener('DOMContentLoaded', function() {
    const estadoSelect = document.getElementById('estadoSelect');
    const municipioSelect = document.getElementById('municipioSelect');
    const parroquiaSelect = document.getElementById('parroquiaSelect');
    
    municipioSelect.addEventListener('change', function() {
        const municipioId = this.value;
        
        if (municipioId) {
            parroquiaSelect.disabled = false;
            fetch(`../php/ajax/get_parroquias.php?municipio_id=${municipioId}`)
                .then(response => response.json())
                .then(data => {
                    parroquiaSelect.innerHTML = '<option value="">Todas</option>';
                    data.forEach(parroquia => {
                        const option = document.createElement('option');
                        option.value = parroquia.id_parroquia;
                        option.textContent = parroquia.parroquia;
                        parroquiaSelect.appendChild(option);
                    });
                });
        } else {
            parroquiaSelect.innerHTML = '<option value="">Todas</option>';
            parroquiaSelect.disabled = true;
        }
    });
    
    // Cargar parroquias si ya hay un municipio seleccionado
    if (municipioSelect.value) {
        municipioSelect.dispatchEvent(new Event('change'));
    }
});
    // Manejar el scroll del navbar
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 10) {
            navbar.classList.add('shadow-sm');
        } else {
            navbar.classList.remove('shadow-sm');
        }
    });

    // Funcionalidad de búsqueda
    document.addEventListener('DOMContentLoaded', function() {
    const inputBuscar = document.getElementById('buscar');
    const tablaBeneficiarios = document.getElementById('tablaBeneficiarios');
    const filas = tablaBeneficiarios.querySelectorAll('tbody tr');
    const paginationContainer = document.querySelector('.pagination');

    // Función de búsqueda avanzada con mejoras en búsqueda de cédulas
    function busquedaAvanzada(termino) {
        termino = termino.trim().toLowerCase();
        let resultadosEncontrados = 0;
        
        filas.forEach(fila => {
            const celdas = fila.getElementsByTagName('td');
            const cedula = celdas[1].textContent.toLowerCase().replace(/\D/g, ''); // Eliminar caracteres no numéricos
            const nombre = celdas[2].textContent.toLowerCase();
            const codigoObra = celdas[4].textContent.toLowerCase();
            
            // Búsqueda más flexible
            const terminoLimpio = termino.replace(/\D/g, ''); // Eliminar caracteres no numéricos del término de búsqueda
            
            const coincide = 
                cedula.includes(terminoLimpio) || // Búsqueda parcial de cédula
                nombre.includes(termino) || 
                codigoObra.includes(termino);
            
            if (coincide) {
                fila.style.display = '';
                resultadosEncontrados++;
            } else {
                fila.style.display = 'none';
            }
        });

        // Gestionar visibilidad de paginación
        gestionarPaginacion(resultadosEncontrados);
    }

    // Función para gestionar la paginación
    function gestionarPaginacion(resultadosEncontrados) {
        if (paginationContainer) {
            if (resultadosEncontrados === 0) {
                paginationContainer.style.display = 'none';
            } else {
                paginationContainer.style.display = 'flex';
            }
        }
    }

    // Implementación de debounce
    function debounce(func, timeout = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    }

    const busquedaOptimizada = debounce(busquedaAvanzada);

    // Evento de búsqueda
    inputBuscar.addEventListener('input', function() {
        busquedaOptimizada(this.value);
    });

    // Función para limpiar búsqueda
    function limpiarBusqueda() {
        inputBuscar.value = '';
        filas.forEach(fila => {
            fila.style.display = '';
        });

        // Restaurar paginación
        if (paginationContainer) {
            paginationContainer.style.display = 'flex';
        }
    }

    // Botón de limpieza
    const botonLimpiar = document.createElement('button');
    botonLimpiar.innerHTML = '<i class="fas fa-times"></i>';
    botonLimpiar.classList.add('btn', 'btn-link', 'position-absolute', 'end-0', 'top-50', 'translate-middle-y');
    botonLimpiar.style.zIndex = '10';
    
    inputBuscar.parentNode.style.position = 'relative';
    inputBuscar.parentNode.appendChild(botonLimpiar);
    
    botonLimpiar.addEventListener('click', limpiarBusqueda);

    // Manejar paginación
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Restablecer búsqueda al cambiar de página
            inputBuscar.value = '';
            filas.forEach(fila => {
                fila.style.display = '';
            });
        });
    });
});
    document.addEventListener('DOMContentLoaded', function() {
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Capturar los parámetros de filtro actuales
            const filtros = new URLSearchParams(window.location.search);
            const nuevaUrl = new URL(this.href);
            
            // Copiar todos los filtros a la nueva URL
            filtros.forEach((valor, clave) => {
                if (clave !== 'pagina') {
                    nuevaUrl.searchParams.set(clave, valor);
                }
            });

            window.location.href = nuevaUrl.toString();
            e.preventDefault();
        });
    });
});

document.getElementById('agregarBeneficiarioForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        // Verificar rol primero
        const rolResponse = await fetch('../php/conf/verificar_rol.php');
        const rolData = await rolResponse.json();
        
        if (!rolData.autorizado) {
            alert('Error: Solo administradores pueden agregar beneficiarios');
            window.location.reload();
            return;
        }

        // Si es admin, proceder con el envío
        const formData = new FormData(this);
        const response = await fetch('../php/conf/guardar_beneficiario.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'ok') {
            alert(`Beneficiario agregado:\n${result.beneficiario.nombre_beneficiario}`);
            window.location.reload();
        } else {
            throw new Error(result.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(`Error al guardar: ${error.message}`);
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const modalEstado = document.getElementById('modalEstado');
    const modalMunicipio = document.getElementById('modalMunicipio');
    const modalParroquia = document.getElementById('modalParroquia');
    
    // Deshabilitar cambios en el estado (siempre será Lara)
    modalEstado.disabled = true;

    // Cargar parroquias al seleccionar municipio
    modalMunicipio.addEventListener('change', function() {
        const municipioId = this.value;
        modalParroquia.innerHTML = '<option value="">Cargando...</option>';
        
        if (municipioId) {
            fetch(`../php/conf/get_parroquias.php?municipio_id=${municipioId}`)
                .then(response => response.json())
                .then(data => {
                    modalParroquia.innerHTML = '<option value="">Seleccione una parroquia</option>';
                    data.forEach(parroquia => {
                        const option = new Option(parroquia.parroquia, parroquia.id_parroquia);
                        modalParroquia.add(option);
                    });
                    modalParroquia.disabled = false;
                });
        } else {
            modalParroquia.innerHTML = '<option value="">Seleccione un municipio primero</option>';
            modalParroquia.disabled = true;
        }
    });
});
document.getElementById('formularioBeneficiario').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('../php/conf/guardar_beneficiario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            // Limpiar el formulario
            this.reset();
            
            // Crear una nueva fila para la tabla de beneficiarios
            const tabla = document.getElementById('tablaBeneficiarios').getElementsByTagName('tbody')[0];
            const nuevaFila = tabla.insertRow(0);
            
            // Insertar celdas con los datos del nuevo beneficiario
            const celdas = [
                data.beneficiario.id_beneficiario,
                data.beneficiario.cedula,
                data.beneficiario.nombre_beneficiario,
                data.beneficiario.telefono,
                data.beneficiario.codigo_obra,
                data.ubicacion.municipio,
                data.ubicacion.parroquia,
                data.beneficiario.status
            ];
            
            celdas.forEach((valor, index) => {
                const celda = nuevaFila.insertCell(index);
                celda.textContent = valor || 'N/A';
            });
            
            // Agregar botones de acciones
            const celdaAcciones = nuevaFila.insertCell(celdas.length);
            celdaAcciones.innerHTML = `
                <div class="btn-group" role="group">
                    <a href="datos_beneficiario.php?id=${data.beneficiario.id_beneficiario}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="#" class="btn btn-warning btn-sm editar-beneficiario" data-id="${data.beneficiario.id_beneficiario}">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            `;
            
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: 'Beneficiario Agregado',
                text: data.message
            });
        } else {
            // Mostrar mensaje de error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo agregar el beneficiario'
        });
    });
});

    </script>
</body>
</html>
<?php
// Cerrar conexión al final del documento
if (isset($conexion)) {
    $conexion->close();
}
?>