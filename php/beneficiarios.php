<?php
session_start();
require_once '../php/conf/conexion.php';

// Dar acceso a todos los usuarios
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'root';
$params = [];
$types = '';
$filtro_condiciones = '';
$lara = $conexion->query("SELECT id_estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
$id_lara = $lara['id_estado'];

// Default to show only active
$show_inactive = $_GET['show_inactive'] ?? false;

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;


// Procesar formulario de nuevo beneficiario ANTES de cualquier salida HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nuevo_beneficiario') {
    // Limpiar cualquier salida previa
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $conexion->begin_transaction();
        
        // Validar campos requeridos
        $required_fields = ['nombre_beneficiario', 'cedula', 'telefono', 'codigo_obra', 'municipio', 'parroquia'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }
        
        // Verificar si la cédula ya existe
        $check_cedula = $conexion->prepare("SELECT id_beneficiario FROM beneficiarios WHERE cedula = ?");
        $check_cedula->bind_param("s", $_POST['cedula']);
        $check_cedula->execute();
        if ($check_cedula->get_result()->num_rows > 0) {
            throw new Exception("Ya existe un beneficiario con esta cédula");
        }
        
        // Crear ubicación primero
        $stmt_ubicacion = $conexion->prepare("
            INSERT INTO ubicaciones (id_estado, id_municipio, id_parroquia, id_comunidad, utm_este, utm_norte, direccion_exacta) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $id_estado = 12; // Lara
        $id_municipio = intval($_POST['municipio']);
        $id_parroquia = intval($_POST['parroquia']);
        $id_comunidad = !empty($_POST['comunidad']) ? intval($_POST['comunidad']) : null;
        $utm_este = !empty($_POST['utm_este']) ? $_POST['utm_este'] : null;
        $utm_norte = !empty($_POST['utm_norte']) ? $_POST['utm_norte'] : null;
        $direccion_exacta = !empty($_POST['direccion_exacta']) ? $_POST['direccion_exacta'] : null;
        
        $stmt_ubicacion->bind_param("iiissss", $id_estado, $id_municipio, $id_parroquia, $id_comunidad, $utm_este, $utm_norte, $direccion_exacta);
        
        if (!$stmt_ubicacion->execute()) {
            throw new Exception("Error al crear la ubicación: " . $stmt_ubicacion->error);
        }
        
        $id_ubicacion = $conexion->insert_id;
        
        // Crear beneficiario
        $stmt_beneficiario = $conexion->prepare("
            INSERT INTO beneficiarios (tipo_de_documento, cedula, nombre_beneficiario, telefono, fecha_actualizacion, id_ubicacion, status, id_metodo_constructivo, id_modelo_constructivo, id_cod_obra) 
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?)
        ");
        
        $tipo_documento = 'V'; // Por defecto venezolano
        $nombre = $_POST['nombre_beneficiario'];
        $cedula = $_POST['cedula'];
        $telefono = $_POST['telefono'];
        $status = $_POST['status'] ?? 'activo';
        $id_metodo = !empty($_POST['metodo_constructivo']) ? intval($_POST['metodo_constructivo']) : null;
        $id_modelo = !empty($_POST['modelo_constructivo']) ? intval($_POST['modelo_constructivo']) : null;
        $id_cod_obra = intval($_POST['codigo_obra']);
        
        $stmt_beneficiario->bind_param("ssssissii", $tipo_documento, $cedula, $nombre, $telefono, $id_ubicacion, $status, $id_metodo, $id_modelo, $id_cod_obra);
        
        if (!$stmt_beneficiario->execute()) {
            throw new Exception("Error al crear el beneficiario: " . $stmt_beneficiario->error);
        }
        
        $conexion->commit();
        echo json_encode(['status' => 'success', 'message' => 'Beneficiario creado exitosamente']);
        exit;
        
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

$params = [];
$types = '';
$lara = $conexion->query("SELECT id_estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
$id_lara = $lara['id_estado'];

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Preparar consulta con posibles filtros - CORREGIDA
$sql_base = "SELECT b.*, 
    p.parroquia, 
    m.municipio, 
    e.estado,
    c.comunidad,
    co.cod_obra as codigo_obra_nombre
FROM beneficiarios b
LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
LEFT JOIN comunidades c ON u.id_comunidad = c.id_comunidad
LEFT JOIN parroquias p ON u.id_parroquia = p.id_parroquia
LEFT JOIN municipios m ON u.id_municipio = m.id_municipio
LEFT JOIN estados e ON u.id_estado = e.id_estado
LEFT JOIN cod_obra co ON b.id_cod_obra = co.id_cod_obra
WHERE e.id_estado = $id_lara ";

// Aplicar filtro de estado
if (!isset($_GET['status']) || $_GET['status'] === 'activo' || $_GET['status'] === '') {
    $sql_base .= " AND b.status = 'activo'";
} elseif ($_GET['status'] === 'inactivo') {
    $sql_base .= " AND b.status = 'inactivo'";
} elseif ($_GET['status'] === 'todos') {
    // No agregar filtro de status
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
if (!empty($_GET['comunidad'])) {
    $sql_base .= " AND c.id_comunidad = ?";
    $params[] = $_GET['comunidad'];
    $types .= 'i';
}
if (!empty($_GET['codigo_obra'])) {
    $sql_base .= " AND co.cod_obra LIKE ?";
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
$sql_base .= " ORDER BY b.id_beneficiario DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $registros_por_pagina;
$params[] = $offset;

// Ejecutar consulta con paginación
$stmt = $conexion->prepare($sql_base);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
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
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="../css/beneficiarios.css">
    <style>
.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.modal-body .form-label {
    font-weight: 500;
}

.modal-body .form-label:after {
    content: " *";
    color: #dc3545;
}

.modal-body .form-label[for="direccion_exacta"]:after,
.modal-body .form-label[for="utm_norte"]:after,
.modal-body .form-label[for="utm_este"]:after,
.modal-body .form-label[for="comunidad"]:after,
.modal-body .form-label[for="metodo_constructivo"]:after,
.modal-body .form-label[for="modelo_constructivo"]:after {
    content: "";
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.nueva-comunidad-section {
    transition: all 0.3s ease-in-out;
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.nueva-comunidad-section.show {
    opacity: 1;
    transform: translateY(0);
}

.nueva-comunidad-section.hide {
    opacity: 0;
    transform: translateY(-20px);
    pointer-events: none;
}

.btn-close:focus {
    box-shadow: none;
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
                    <a class="nav-link ms-2" href="../php/conf/logout.php" style="color : #f8f9fa">
                        <i class="fas fa-sign-out-alt me-1" style="color : #f8f9fa"></i> Cerrar Sesión
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
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" id="estadoSelect" class="form-select" disabled>
                                    <?php
                                    $lara = $conexion->query("SELECT id_estado, estado FROM estados WHERE estado = 'Lara'")->fetch_assoc();
                                    echo "<option value='{$lara['id_estado']}' selected>{$lara['estado']}</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Municipio</label>
                                <select name="municipio" id="municipioSelect" class="form-select">
                                    <option value="">Todos</option>
                                    <?php
                                    $municipios = $conexion->query("SELECT id_municipio, municipio FROM municipios WHERE id_estado = {$lara['id_estado']}");
                                    while ($row = $municipios->fetch_assoc()) {
                                        $selected = (isset($_GET['municipio']) && $_GET['municipio'] == $row['id_municipio']) ? 'selected' : '';
                                        echo "<option value='{$row['id_municipio']}' $selected>{$row['municipio']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-3">
                                <label class="form-label">Comunidad</label>
                                <select name="comunidad" id="comunidadSelect" class="form-select" <?= !isset($_GET['parroquia']) ? 'disabled' : '' ?>>
                                    <option value="">Todas</option>
                                    <?php
                                    if (isset($_GET['parroquia'])) {
                                        $comunidades = $conexion->query("SELECT id_comunidad, comunidad FROM comunidades WHERE id_parroquia = " . intval($_GET['parroquia']) . " ORDER BY comunidad");
                                        while ($row = $comunidades->fetch_assoc()) {
                                            $selected = (isset($_GET['comunidad']) && $_GET['comunidad'] == $row['id_comunidad']) ? 'selected' : '';
                                            echo "<option value='{$row['id_comunidad']}' $selected>{$row['comunidad']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Código de Obra</label>
                                <select name="codigo_obra" class="form-select">
                                    <option value="">Todos los códigos</option>
                                    <?php
                                    $codigos_obra = $conexion->query("SELECT id_cod_obra, cod_obra FROM cod_obra ORDER BY cod_obra ASC");
                                    while ($row = $codigos_obra->fetch_assoc()) {
                                        $selected = (isset($_GET['codigo_obra']) && $_GET['codigo_obra'] == $row['cod_obra']) ? 'selected' : '';
                                        echo "<option value='{$row['cod_obra']}' $selected>{$row['cod_obra']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado del Beneficiario</label>
                                <select name="status" class="form-select">
                                    <option value="todos" <?= (!isset($_GET['status']) || $_GET['status'] === 'todos') ? 'selected' : '' ?>>Todos</option>
                                    <option value="activo" <?= (isset($_GET['status']) && $_GET['status'] === 'activo') ? 'selected' : '' ?>>Activos</option>
                                    <option value="inactivo" <?= (isset($_GET['status']) && $_GET['status'] === 'inactivo') ? 'selected' : '' ?>>Inactivos</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
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
                    <div class="container-fluid p-4" style="color: #000000;">
                        <!-- Título y botones de acción -->
                        <div class="row mb-4 align-items-center" style="color: #000000;">
                            <div class="col-md-6" style="color: #000000;">
                                <h2 class="mb-0 fw-bold" style="color: #000000;">
                                    <i class="fas fa-users me-2" style="color: #000000;"></i> 
                                    Listado de Beneficiarios
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

                        <!-- Alertas -->
                        <div id="alertContainer"></div>

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
                                                <th class="text-center" id="titulos" style="color: #000000;">ID</th>
                                                <th style="color: #000000;">Cédula</th>
                                                <th style="color: #000000;">Nombre Completo</th>
                                                <th style="color: #000000;">Teléfono</th>
                                                <th style="color: #000000;">Comunidad</th>
                                                <th style="color: #000000;">Código Obra</th>
                                                <th class="text-center" style="color: #000000;">Acciones</th>
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
                                                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: #000000; color: #ffffff;">
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
                                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                                <?= htmlspecialchars($beneficiario['comunidad'] ?? 'No especificada') ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                                                <?= htmlspecialchars($beneficiario['codigo_obra_nombre'] ?? $beneficiario['codigo_obra'] ?? 'No asignado') ?>
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
                                                    <td colspan="7" class="text-center py-4">
                                                        <div class="d-flex flex-column align-items-center">
                                                            <i class="fas fa-users-slash text-muted mb-2" style="font-size: 2rem;"></i>
                                                            <h5 class="text-muted">No hay beneficiarios registrados</h5>
                                                            <?php if ($esAdmin): ?>
                                                                <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalNuevoBeneficiario">
                                                                    <i class="fas fa-plus me-1"></i> Agregar Beneficiario
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    
                                    <!-- Paginación -->
                                    <nav aria-label="Paginación de Beneficiarios">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($pagina_actual > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?><?= !empty($_GET['estado']) ? '&estado='.$_GET['estado'] : '' ?>">Anterior</a>
                                                </li>
                                            <?php endif; ?>

                                            <?php 
                                            $rango = 2;
                                            $inicio = max(1, $pagina_actual - $rango);
                                            $fin = min($total_paginas, $pagina_actual + $rango);

                                            if ($inicio > 1) {
                                                echo '<li class="page-item"><a class="page-link" href="?pagina=1">1</a></li>';
                                                if ($inicio > 2) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                            }

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
        </div>
    </div>

    <!-- Modal Nuevo Beneficiario -->
    <div class="modal fade" id="modalNuevoBeneficiario" tabindex="-1" aria-labelledby="modalNuevoBeneficiarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalNuevoBeneficiarioLabel">
                        <i class="fas fa-user-plus me-2"></i> Nuevo Beneficiario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevoBeneficiario" method="POST">
                    <div class="modal-body">
                        <!-- Información Personal -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_beneficiario" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nombre_beneficiario" name="nombre_beneficiario" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">Cédula *</label>
                                <input type="text" class="form-control" id="cedula" name="cedula" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="codigo_obra" class="form-label">Código de Obra *</label>
                                <select class="form-select" id="codigo_obra" name="codigo_obra" required>
                                    <option value="">Seleccione un código de obra</option>
                                    <?php
                                    $codigos_obra = $conexion->query("SELECT id_cod_obra, cod_obra FROM cod_obra ORDER BY cod_obra ASC");
                                    while ($row = $codigos_obra->fetch_assoc()) {
                                        echo "<option value='{$row['id_cod_obra']}'>{$row['cod_obra']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Información de Ubicación -->
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Información de Ubicación</h6>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="modalEstado" class="form-label">Estado *</label>
                                <select name="estado" id="modalEstado" class="form-select" required>
                                    <?php
                                    $estado = $conexion->query("SELECT id_estado, estado FROM estados WHERE estado = 'Lara'");
                                    if ($row = $estado->fetch_assoc()) {
                                        echo "<option value='{$row['id_estado']}' selected>{$row['estado']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="modalMunicipio" class="form-label">Municipio *</label>
                                <select name="municipio" id="modalMunicipio" class="form-select" required>
                                    <option value="">Seleccione un municipio</option>
                                    <?php
                                    $municipios = $conexion->query("SELECT id_municipio, municipio FROM municipios WHERE id_estado = {$lara['id_estado']} ORDER BY municipio ASC");
                                    while ($mun = $municipios->fetch_assoc()) {
                                        echo "<option value='{$mun['id_municipio']}'>{$mun['municipio']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="modalParroquia" class="form-label">Parroquia *</label>
                                <select name="parroquia" id="modalParroquia" class="form-select" required disabled>
                                    <option value="">Primero seleccione un municipio</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="comunidad" class="form-label">Comunidad Existente</label>
                                <div class="input-group">
                                    <select class="form-select" id="comunidad" name="comunidad">
                                        <option value="">Seleccione una comunidad existente</option>
                                        <?php
                                        $comunidades = $conexion->query("SELECT c.ID_COMUNIDAD, c.COMUNIDAD, p.id_parroquia, p.parroquia 
                                                                       FROM comunidades c 
                                                                       JOIN parroquias p ON c.ID_PARROQUIA = p.id_parroquia 
                                                                       ORDER BY c.COMUNIDAD ASC");
                                        while ($com = $comunidades->fetch_assoc()) {
                                            echo "<option value='{$com['ID_COMUNIDAD']}'>{$com['COMUNIDAD']} - {$com['parroquia']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleNuevaComunidad">
                                        <i class="fas fa-plus-circle me-1"></i> Crear Nueva Comunidad
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="direccion_exacta" class="form-label">Dirección Exacta</label>
                                <input type="text" class="form-control" id="direccion_exacta" name="direccion_exacta">
                            </div>
                        </div>

                        <!-- Sección para crear nueva comunidad - Inicialmente oculta -->
                        <div class="nueva-comunidad-section" id="nuevaComunidadSection" style="display: none;">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Registrar Nueva Comunidad</h6>
                                    <button type="button" class="btn-close btn-close-white" id="cerrarNuevaComunidad"></button>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Complete los siguientes campos para registrar una nueva comunidad
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nueva_comunidad_nombre" class="form-label">Nombre de la Nueva Comunidad</label>
                                            <input type="text" class="form-control" id="nueva_comunidad_nombre" name="nueva_comunidad_nombre">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nueva_comunidad_parroquia" class="form-label">Parroquia para la Nueva Comunidad</label>
                                            <select class="form-select" id="nueva_comunidad_parroquia" name="nueva_comunidad_parroquia">
                                                <option value="">Seleccione una parroquia</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-success" id="btnGuardarComunidad">
                                            <i class="fas fa-save me-1"></i> Guardar Nueva Comunidad
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Coordenadas UTM -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="utm_norte" class="form-label">UTM Norte</label>
                                <input type="text" class="form-control" id="utm_norte" name="utm_norte" placeholder="Ej: 1234567.89">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="utm_este" class="form-label">UTM Este</label>
                                <input type="text" class="form-control" id="utm_este" name="utm_este" placeholder="Ej: 987654.32">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Estado *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="activo" selected>Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Beneficiario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para mostrar alertas
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas ${iconClass} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            alertContainer.innerHTML = alertHtml;
            
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

        document.addEventListener('DOMContentLoaded', function() {
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
                    const response = await fetch(`conf/get_parroquias.php?municipio_id=${municipioId}`);
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
                    const response = await fetch(`conf/obtener_comunidades.php?id_parroquia=${parroquiaId}`);
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

            // Elementos del formulario principal de filtros
            const modalMunicipio = document.getElementById('modalMunicipio');
            const modalParroquia = document.getElementById('modalParroquia');
            const modalComunidad = document.getElementById('comunidad');
            const toggleNuevaComunidad = document.getElementById('toggleNuevaComunidad');
            const cerrarNuevaComunidad = document.getElementById('cerrarNuevaComunidad');
            const nuevaComunidadSection = document.getElementById('nuevaComunidadSection');
            const btnGuardarComunidad = document.getElementById('btnGuardarComunidad');

            // Funciones para mostrar/ocultar nueva comunidad
            function mostrarSeccionNuevaComunidad() {
                nuevaComunidadSection.style.display = 'block';
                setTimeout(() => {
                    nuevaComunidadSection.classList.add('show');
                    nuevaComunidadSection.classList.remove('hide');
                }, 10);
                toggleNuevaComunidad.style.display = 'none';
            }

            function ocultarSeccionNuevaComunidad() {
                nuevaComunidadSection.classList.remove('show');
                nuevaComunidadSection.classList.add('hide');
                setTimeout(() => {
                    nuevaComunidadSection.style.display = 'none';
                }, 300);
                toggleNuevaComunidad.style.display = 'inline-block';
                
                // Limpiar campos
                document.getElementById('nueva_comunidad_nombre').value = '';
                document.getElementById('nueva_comunidad_parroquia').value = '';
            }

            toggleNuevaComunidad.addEventListener('click', mostrarSeccionNuevaComunidad);
            cerrarNuevaComunidad.addEventListener('click', ocultarSeccionNuevaComunidad);

            // Evento para guardar nueva comunidad
            btnGuardarComunidad.addEventListener('click', async function() {
                const nombreComunidad = document.getElementById('nueva_comunidad_nombre').value.trim();
                const parroquiaId = document.getElementById('nueva_comunidad_parroquia').value;

                if (!nombreComunidad || !parroquiaId) {
                    showAlert('error', 'Por favor complete el nombre de la comunidad y seleccione una parroquia');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('nombre_comunidad', nombreComunidad);
                    formData.append('id_parroquia', parroquiaId);

                    const response = await fetch('../php/conf/guardar_comunidad.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showAlert('success', 'Comunidad creada exitosamente');
                        
                        // Actualizar el select de comunidades
                        const option = document.createElement('option');
                        option.value = result.id_comunidad;
                        option.textContent = result.nombre_comunidad;
                        modalComunidad.appendChild(option);
                        modalComunidad.value = result.id_comunidad;
                        
                        // Ocultar la sección de nueva comunidad
                        ocultarSeccionNuevaComunidad();
                    } else {
                        showAlert('error', result.message || 'Error al crear la comunidad');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('error', 'Error al procesar la solicitud');
                }
            });

            // Manejar el envío del formulario de nuevo beneficiario
            document.getElementById('formNuevoBeneficiario').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnContent = submitBtn.innerHTML;
                
                try {
                    // Validar campos requeridos
                    const requiredFields = {
                        'nombre_beneficiario': 'Nombre del beneficiario',
                        'cedula': 'Cédula',
                        'telefono': 'Teléfono',
                        'codigo_obra': 'Código de obra',
                        'modalMunicipio': 'Municipio',
                        'modalParroquia': 'Parroquia'
                    };
                    
                    let isValid = true;
                    let missingFields = [];
                    
                    Object.entries(requiredFields).forEach(([fieldId, fieldName]) => {
                        const element = document.getElementById(fieldId);
                        
                        if (!element || !element.value.trim()) {
                            if (element) {
                                element.classList.add('is-invalid');
                            }
                            isValid = false;
                            missingFields.push(fieldName);
                        } else {
                            if (element) {
                                element.classList.remove('is-invalid');
                            }
                        }
                    });
                    
                    if (!isValid) {
                        showAlert('error', `Por favor complete los siguientes campos requeridos: ${missingFields.join(', ')}`);
                        return;
                    }
                    
                    // Mostrar indicador de carga
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
                    submitBtn.disabled = true;
                    
                    const formData = new FormData(this);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Verificar si la respuesta es JSON válida
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Respuesta no es JSON:', text);
                        throw new Error('El servidor no devolvió una respuesta JSON válida');
                    }
                    
                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        showAlert('success', result.message);
                        // Cerrar el modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoBeneficiario'));
                        modal.hide();
                        // Recargar la página después de un breve retraso
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('error', result.message || 'Error al guardar el beneficiario');
                        submitBtn.innerHTML = originalBtnContent;
                        submitBtn.disabled = false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('error', 'Error al procesar la solicitud: ' + error.message);
                    submitBtn.innerHTML = originalBtnContent;
                    submitBtn.disabled = false;
                }
            });

            // Funcionalidad de búsqueda en tiempo real
            const buscarInput = document.getElementById('buscar');
            const tablaBeneficiarios = document.getElementById('tablaBeneficiarios');
            
            if (buscarInput && tablaBeneficiarios) {
                buscarInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = tablaBeneficiarios.querySelectorAll('tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>

<?php
if (isset($conexion)) {
    $conexion->close();
}
?>
