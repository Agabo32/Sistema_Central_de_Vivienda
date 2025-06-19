<?php
session_start();
require_once '../php/conf/conexion.php';
require_once '../php/conf/session_helper.php';

// Verificación de autenticación
verificar_autenticacion();

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

// Manejar solicitudes AJAX para crear beneficiario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nuevo_beneficiario') {
    // Limpiar cualquier salida previa
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Establecer headers JSON
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    try {
        $conexion->begin_transaction();
        
        // Validar campos requeridos
        $required_fields = ['nombre_beneficiario', 'cedula', 'telefono', 'codigo_obra', 'municipio', 'parroquia'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }
        
        // Validar formato de cédula
        if (!preg_match('/^\d{7,8}$/', $_POST['cedula'])) {
            throw new Exception("La cédula debe tener entre 7 y 8 dígitos");
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
            INSERT INTO ubicaciones (municipio, parroquia, comunidad, direccion_exacta, utm_norte, utm_este) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $id_municipio = intval($_POST['municipio']);
        $id_parroquia = intval($_POST['parroquia']);
        $id_comunidad = !empty($_POST['comunidad']) ? intval($_POST['comunidad']) : null;
        $direccion_exacta = !empty($_POST['direccion_exacta']) ? $_POST['direccion_exacta'] : '';
        $utm_norte = !empty($_POST['utm_norte']) ? $_POST['utm_norte'] : '';
        $utm_este = !empty($_POST['utm_este']) ? $_POST['utm_este'] : '';
        
        $stmt_ubicacion->bind_param("iiisss", 
            $id_municipio, 
            $id_parroquia, 
            $id_comunidad, 
            $direccion_exacta, 
            $utm_norte, 
            $utm_este
        );
        
        if (!$stmt_ubicacion->execute()) {
            throw new Exception("Error al crear la ubicación: " . $stmt_ubicacion->error);
        }
        
        $id_ubicacion = $conexion->insert_id;
        
        // Crear beneficiario
        $stmt_beneficiario = $conexion->prepare("
            INSERT INTO beneficiarios (
                id_ubicacion,
                cedula,
                nombre_beneficiario,
                telefono,
                cod_obra,
                metodo_constructivo,
                modelo_constructivo,
                fiscalizador,
                status
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $cedula = $_POST['cedula'];
        $nombre = $_POST['nombre_beneficiario'];
        $telefono = $_POST['telefono'];
        $cod_obra = intval($_POST['codigo_obra']);
        $metodo_constructivo = !empty($_POST['metodo_constructivo']) ? intval($_POST['metodo_constructivo']) : null;
        $modelo_constructivo = !empty($_POST['modelo_constructivo']) ? intval($_POST['modelo_constructivo']) : null;
        $fiscalizador = !empty($_POST['fiscalizador']) ? intval($_POST['fiscalizador']) : null;
        $status = $_POST['status'] ?? 'activo';
        
        $stmt_beneficiario->bind_param("isssiiiss", 
            $id_ubicacion,
            $cedula,
            $nombre,
            $telefono,
            $cod_obra,
            $metodo_constructivo,
            $modelo_constructivo,
            $fiscalizador,
            $status
        );
        
        if (!$stmt_beneficiario->execute()) {
            throw new Exception("Error al crear el beneficiario: " . $stmt_beneficiario->error);
        }
        
        $id_beneficiario = $conexion->insert_id;
        
        // Crear registro en datos_de_construccion
        $stmt_construccion = $conexion->prepare("
            INSERT INTO datos_de_construccion (
                id_beneficiario,
                acondicionamiento,
                limpieza,
                replanteo,
                fundacion,
                excavacion,
                acero_vigas_riostra,
                encofrado_malla,
                instalaciones_electricas_sanitarias,
                vaciado_losa_anclajes,
                estructura,
                armado_columnas,
                vaciado_columnas,
                armado_vigas,
                vaciado_vigas,
                cerramiento,
                bloqueado,
                colocacion_correas,
                colocacion_techo,
                acabado,
                colocacion_ventanas,
                colocacion_puertas_principales,
                instalaciones_electricas_sanitarias_paredes,
                frisos,
                sobrepiso,
                ceramica_bano,
                colocacion_puertas_internas,
                equipos_accesorios_electricos,
                equipos_accesorios_sanitarios,
                colocacion_lavaplatos,
                pintura,
                avance_fisico
            ) VALUES (?, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
        ");
        
        $stmt_construccion->bind_param("i", $id_beneficiario);
        
        if (!$stmt_construccion->execute()) {
            throw new Exception("Error al crear los datos de construcción: " . $stmt_construccion->error);
        }
        
        $conexion->commit();
        
        // Respuesta exitosa
        echo json_encode([
            'status' => 'success', 
            'message' => 'Beneficiario creado exitosamente',
            'id_beneficiario' => $id_beneficiario
        ], JSON_UNESCAPED_UNICODE);
        
        exit;
        
    } catch (Exception $e) {
        $conexion->rollback();
        
        // Respuesta de error
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        
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
$sql_base = "SELECT 
    b.*,
    p.parroquia as nombre_parroquia, 
    m.municipio as nombre_municipio,
    c.comunidad as nombre_comunidad,
    mc.metodo as nombre_metodo,
    moc.modelo as nombre_modelo,
    f.fiscalizador as nombre_fiscalizador,
    co.cod_obra as codigo_obra_nombre,
    u.direccion_exacta,
    u.utm_norte,
    u.utm_este
FROM beneficiarios b
LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
LEFT JOIN comunidades c ON u.comunidad = c.id_comunidad
LEFT JOIN parroquias p ON u.parroquia = p.id_parroquia
LEFT JOIN municipios m ON u.municipio = m.id_municipio
LEFT JOIN cod_obra co ON b.cod_obra = co.id_cod_obra
LEFT JOIN metodos_constructivos mc ON b.metodo_constructivo = mc.id_metodo
LEFT JOIN modelos_constructivos moc ON b.modelo_constructivo = moc.id_modelo
LEFT JOIN fiscalizadores f ON b.fiscalizador = f.id_fiscalizador
WHERE 1=1 ";

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

// Filtro de búsqueda por cédula o nombre
if (!empty($_GET['buscar_termino'])) {
    $sql_base .= " AND (b.cedula LIKE ? OR b.nombre_beneficiario LIKE ?)";
    $termino_busqueda = '%'.$_GET['buscar_termino'].'%';
    $params[] = $termino_busqueda;
    $params[] = $termino_busqueda;
    $types .= 'ss';
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
$sql_base .= " ORDER BY b.id_beneficiario ASC LIMIT ? OFFSET ?";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a class="nav-link ms-2" href="conf/logout.php" style="color : #f8f9fa">
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
                                <form class="search-box" style="max-width: 400px;" method="GET" id="formBusqueda">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="buscar_termino" name="buscar_termino" 
                                               placeholder="Buscar por cédula o nombre..." 
                                               value="<?php echo isset($_GET['buscar_termino']) ? htmlspecialchars($_GET['buscar_termino']) : ''; ?>">
                                        <button class="btn btn-primary" type="submit" title="Buscar">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if(isset($_GET['buscar_termino'])): ?>
                                            <a href="beneficiarios.php" class="btn btn-secondary" title="Limpiar búsqueda">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Mantener los filtros actuales -->
                                    <?php if(isset($_GET['municipio'])): ?>
                                        <input type="hidden" name="municipio" value="<?php echo htmlspecialchars($_GET['municipio']); ?>">
                                    <?php endif; ?>
                                    <?php if(isset($_GET['parroquia'])): ?>
                                        <input type="hidden" name="parroquia" value="<?php echo htmlspecialchars($_GET['parroquia']); ?>">
                                    <?php endif; ?>
                                    <?php if(isset($_GET['comunidad'])): ?>
                                        <input type="hidden" name="comunidad" value="<?php echo htmlspecialchars($_GET['comunidad']); ?>">
                                    <?php endif; ?>
                                    <?php if(isset($_GET['status'])): ?>
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($_GET['status']); ?>">
                                    <?php endif; ?>
                                </form>
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
                                                                <?= htmlspecialchars($beneficiario['nombre_comunidad'] ?? 'No especificada') ?>
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
                    <input type="hidden" name="action" value="nuevo_beneficiario">
                    <div class="modal-body">
                        <!-- Información Personal -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">Cédula *</label>
                                <input type="text" class="form-control" id="cedula" name="cedula" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre_beneficiario" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nombre_beneficiario" name="nombre_beneficiario" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="codigo_obra" class="form-label">Código de Obra *</label>
                                <div class="input-group">
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
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleNuevoCodigoObra">
                                        <i class="fas fa-plus-circle me-1"></i> Crear Nuevo Código de Obra
                                    </button>
                                </div>
                            </div>

                            <!-- Sección para crear nuevo código de obra - Inicialmente oculta -->
                            <div class="nueva-codigo-obra-section" id="nuevoCodigoObraSection" style="display: none;">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Nuevo Código de Obra</h6>
                                        <button type="button" class="btn-close btn-close-white" id="cerrarNuevoCodigoObra"></button>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Complete el siguiente campo para registrar un nuevo código de obra
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="nuevo_codigo_obra" class="form-label">Nuevo Código de Obra</label>
                                                <input type="text" class="form-control" id="nuevo_codigo_obra" name="nuevo_codigo_obra">
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success" id="btnGuardarCodigoObra">
                                                <i class="fas fa-save me-1"></i> Guardar Nuevo Código
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
                                <label for="comunidad" class="form-label">Comunidad</label>
                                <div class="input-group">
                                    <select class="form-select" id="comunidad" name="comunidad">
                                        <option value="">Seleccione una comunidad existente</option>
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
                                    <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Nueva Comunidad</h6>
                                    <button type="button" class="btn-close btn-close-white" id="cerrarNuevaComunidad"></button>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Complete los siguientes campos para registrar una nueva comunidad
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nueva_comunidad" class="form-label">Nombre de la Comunidad</label>
                                            <input type="text" class="form-control" id="nueva_comunidad" name="nueva_comunidad">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nueva_comunidad_parroquia" class="form-label">Parroquia</label>
                                            <select class="form-select" id="nueva_comunidad_parroquia" name="nueva_comunidad_parroquia" required>
                                                <option value="">Seleccione una parroquia</option>
                                                <?php
                                                $parroquias = $conexion->query("
                                                    SELECT p.id_parroquia, p.parroquia 
                                                    FROM parroquias p 
                                                    INNER JOIN municipios m ON p.id_municipio = m.id_municipio 
                                                    INNER JOIN estados e ON m.id_estado = e.id_estado 
                                                    WHERE e.estado = 'Lara' 
                                                    ORDER BY p.parroquia ASC
                                                ");
                                                while ($row = $parroquias->fetch_assoc()) {
                                                    echo "<option value='{$row['id_parroquia']}'>{$row['parroquia']}</option>";
                                                }
                                                ?>
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

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="metodo_constructivo" class="form-label">Método Constructivo</label>
                                <div class="input-group">
                                    <select class="form-select" id="metodo_constructivo" name="metodo_constructivo">
                                        <option value="">Seleccione un método constructivo</option>
                                        <?php
                                        $metodos = $conexion->query("SELECT id_metodo, metodo FROM metodos_constructivos ORDER BY metodo ASC");
                                        while ($row = $metodos->fetch_assoc()) {
                                            echo "<option value='{$row['id_metodo']}'>{$row['metodo']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleNuevoMetodo">
                                        <i class="fas fa-plus-circle me-1"></i> Crear Nuevo Método Constructivo
                                    </button>
                                </div>
                            </div>

                            <!-- Sección para crear nuevo método constructivo - Inicialmente oculta -->
                            <div class="nueva-metodo-section" id="nuevoMetodoSection" style="display: none;">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Nuevo Método Constructivo</h6>
                                        <button type="button" class="btn-close btn-close-white" id="cerrarNuevoMetodo"></button>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Complete el siguiente campo para registrar un nuevo método constructivo
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="nuevo_metodo" class="form-label">Nuevo Método Constructivo</label>
                                                <input type="text" class="form-control" id="nuevo_metodo" name="nuevo_metodo">
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success" id="btnGuardarMetodo">
                                                <i class="fas fa-save me-1"></i> Guardar Nuevo Método
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="modelo_constructivo" class="form-label">Modelo Constructivo</label>
                                <div class="input-group">
                                    <select class="form-select" id="modelo_constructivo" name="modelo_constructivo">
                                        <option value="">Seleccione un modelo constructivo</option>
                                        <?php
                                        $modelos = $conexion->query("SELECT id_modelo, modelo FROM modelos_constructivos ORDER BY modelo ASC");
                                        while ($row = $modelos->fetch_assoc()) {
                                            echo "<option value='{$row['id_modelo']}'>{$row['modelo']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleNuevoModelo">
                                        <i class="fas fa-plus-circle me-1"></i> Crear Nuevo Modelo Constructivo
                                    </button>
                                </div>
                            </div>

                            <!-- Sección para crear nuevo modelo constructivo - Inicialmente oculta -->
                            <div class="nueva-modelo-section" id="nuevoModeloSection" style="display: none;">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Nuevo Modelo Constructivo</h6>
                                        <button type="button" class="btn-close btn-close-white" id="cerrarNuevoModelo"></button>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Complete el siguiente campo para registrar un nuevo modelo constructivo
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="nuevo_modelo" class="form-label">Nuevo Modelo Constructivo</label>
                                                <input type="text" class="form-control" id="nuevo_modelo" name="nuevo_modelo">
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success" id="btnGuardarModelo">
                                                <i class="fas fa-save me-1"></i> Guardar Nuevo Modelo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="fiscalizador" class="form-label">Fiscalizador</label>
                                <div class="input-group">
                                    <select class="form-select" id="fiscalizador" name="fiscalizador">
                                        <option value="">Seleccione un fiscalizador</option>
                                        <?php
                                        $fiscalizadores = $conexion->query("SELECT id_fiscalizador, fiscalizador FROM fiscalizadores ORDER BY fiscalizador ASC");
                                        while ($row = $fiscalizadores->fetch_assoc()) {
                                            echo "<option value='{$row['id_fiscalizador']}'>{$row['fiscalizador']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleNuevoFiscalizador">
                                        <i class="fas fa-plus-circle me-1"></i> Crear Nuevo Fiscalizador
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sección para crear nuevo fiscalizador - Inicialmente oculta -->
                        <div class="nueva-fiscalizador-section" id="nuevoFiscalizadorSection" style="display: none;">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Nuevo Fiscalizador</h6>
                                    <button type="button" class="btn-close btn-close-white" id="cerrarNuevoFiscalizador"></button>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Complete el siguiente campo para registrar un nuevo fiscalizador
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="nuevo_fiscalizador" class="form-label">Nuevo Fiscalizador</label>
                                            <input type="text" class="form-control" id="nuevo_fiscalizador" name="nuevo_fiscalizador">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-success" id="btnGuardarFiscalizador">
                                            <i class="fas fa-save me-1"></i> Guardar Nuevo Fiscalizador
                                        </button>
                                    </div>
                                </div>
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

        // Función para validar formulario
        function validateForm(formData) {
            const requiredFields = [
                { field: 'nombre_beneficiario', name: 'Nombre del beneficiario' },
                { field: 'cedula', name: 'Cédula' },
                { field: 'telefono', name: 'Teléfono' },
                { field: 'codigo_obra', name: 'Código de obra' },
                { field: 'municipio', name: 'Municipio' },
                { field: 'parroquia', name: 'Parroquia' }
            ];

            // Verificar campos requeridos
            for (const { field, name } of requiredFields) {
                if (!formData.get(field) || formData.get(field).trim() === '') {
                    showAlert('error', `El campo ${name} es requerido`);
                    return false;
                }
            }

            // Validar formato de cédula
            const cedula = formData.get('cedula').trim();
            if (!/^\d{7,8}$/.test(cedula)) {
                showAlert('error', 'La cédula debe tener entre 7 y 8 dígitos');
                return false;
            }

            // Validar formato de teléfono
            const telefono = formData.get('telefono').replace(/[-\s]/g, '');
            if (!/^\d{10,11}$/.test(telefono)) {
                showAlert('error', 'El teléfono debe tener un formato válido (10-11 dígitos)');
                return false;
            }

            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const municipioSelect = document.getElementById('municipioSelect');
            const parroquiaSelect = document.getElementById('parroquiaSelect');
            const comunidadSelect = document.getElementById('comunidadSelect');
            const modalMunicipio = document.getElementById('modalMunicipio');
            const modalParroquia = document.getElementById('modalParroquia');
            const modalComunidad = document.getElementById('comunidad');
            const toggleNuevaComunidad = document.getElementById('toggleNuevaComunidad');
            const cerrarNuevaComunidad = document.getElementById('cerrarNuevaComunidad');
            const nuevaComunidadSection = document.getElementById('nuevaComunidadSection');
            const btnGuardarComunidad = document.getElementById('btnGuardarComunidad');
            const toggleNuevoCodigoObra = document.getElementById('toggleNuevoCodigoObra');
            const cerrarNuevoCodigoObra = document.getElementById('cerrarNuevoCodigoObra');
            const nuevoCodigoObraSection = document.getElementById('nuevoCodigoObraSection');
            const btnGuardarCodigoObra = document.getElementById('btnGuardarCodigoObra');
            const toggleNuevoFiscalizador = document.getElementById('toggleNuevoFiscalizador');
            const cerrarNuevoFiscalizador = document.getElementById('cerrarNuevoFiscalizador');
            const nuevoFiscalizadorSection = document.getElementById('nuevoFiscalizadorSection');
            const btnGuardarFiscalizador = document.getElementById('btnGuardarFiscalizador');
            const toggleNuevoMetodo = document.getElementById('toggleNuevoMetodo');
            const cerrarNuevoMetodo = document.getElementById('cerrarNuevoMetodo');
            const nuevoMetodoSection = document.getElementById('nuevoMetodoSection');
            const btnGuardarMetodo = document.getElementById('btnGuardarMetodo');
            const toggleNuevoModelo = document.getElementById('toggleNuevoModelo');
            const cerrarNuevoModelo = document.getElementById('cerrarNuevoModelo');
            const nuevoModeloSection = document.getElementById('nuevoModeloSection');
            const btnGuardarModelo = document.getElementById('btnGuardarModelo');

            // Función para cargar parroquias
            async function cargarParroquias(municipioId, targetSelect = parroquiaSelect) {
                if (!municipioId) {
                    targetSelect.innerHTML = '<option value="">Todas</option>';
                    targetSelect.disabled = true;
                    if (targetSelect === parroquiaSelect) {
                        comunidadSelect.innerHTML = '<option value="">Todas</option>';
                        comunidadSelect.disabled = true;
                    } else {
                        modalComunidad.innerHTML = '<option value="">Seleccione una comunidad</option>';
                        modalComunidad.disabled = true;
                    }
                    return;
                }

                try {
                    const response = await fetch(`conf/get_parroquias.php?municipio_id=${municipioId}`);
                    const data = await response.json();
                    
                    targetSelect.innerHTML = '<option value="">Seleccione una parroquia</option>';
                    data.forEach(parroquia => {
                        const option = document.createElement('option');
                        option.value = parroquia.id_parroquia;
                        option.textContent = parroquia.parroquia;
                        targetSelect.appendChild(option);
                    });
                    targetSelect.disabled = false;
                } catch (error) {
                    console.error('Error cargando parroquias:', error);
                    targetSelect.innerHTML = '<option value="">Error al cargar parroquias</option>';
                }
            }

            // Función para cargar comunidades
            async function cargarComunidades(parroquiaId, targetSelect = comunidadSelect) {
                if (!parroquiaId) {
                    targetSelect.innerHTML = '<option value="">Todas</option>';
                    targetSelect.disabled = true;
                    return;
                }

                try {
                    const response = await fetch(`conf/obtener_comunidades.php?id_parroquia=${parroquiaId}`);
                    const data = await response.json();
                    
                    targetSelect.innerHTML = '<option value="">Seleccione una comunidad</option>';
                    data.forEach(comunidad => {
                        const option = document.createElement('option');
                        option.value = comunidad.id_comunidad;
                        option.textContent = comunidad.nombre;
                        targetSelect.appendChild(option);
                    });
                    targetSelect.disabled = false;
                } catch (error) {
                    console.error('Error cargando comunidades:', error);
                    targetSelect.innerHTML = '<option value="">Error al cargar comunidades</option>';
                }
            }

            // Eventos para los selects del formulario principal
            municipioSelect.addEventListener('change', function() {
                cargarParroquias(this.value);
            });

            parroquiaSelect.addEventListener('change', function() {
                cargarComunidades(this.value);
            });

            // Eventos para los selects del modal
            modalMunicipio.addEventListener('change', function() {
                cargarParroquias(this.value, modalParroquia);
                modalComunidad.innerHTML = '<option value="">Seleccione una comunidad</option>';
                modalComunidad.disabled = true;
            });

            modalParroquia.addEventListener('change', function() {
                cargarComunidades(this.value, modalComunidad);
            });

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
                document.getElementById('nueva_comunidad').value = '';
            }

            // Eventos para mostrar/ocultar sección de nueva comunidad
            toggleNuevaComunidad.addEventListener('click', mostrarSeccionNuevaComunidad);
            cerrarNuevaComunidad.addEventListener('click', ocultarSeccionNuevaComunidad);

            // Evento para guardar nueva comunidad
            btnGuardarComunidad.addEventListener('click', async function() {
                const nuevaComunidad = document.getElementById('nueva_comunidad').value.trim();
                const nuevaComunidadParroquia = document.getElementById('nueva_comunidad_parroquia').value;

                if (!nuevaComunidad) {
                    showAlert('error', 'Por favor ingrese el nombre de la comunidad');
                    return;
                }

                if (!nuevaComunidadParroquia) {
                    showAlert('error', 'Por favor seleccione una parroquia');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('nombre_comunidad', nuevaComunidad);
                    formData.append('id_parroquia', nuevaComunidadParroquia);

                    const response = await fetch('conf/guardar_beneficiario.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showAlert('success', 'Comunidad creada exitosamente');
                        
                        // Actualizar el select de comunidades
                        const option = document.createElement('option');
                        option.value = result.id_comunidad;
                        option.textContent = nuevaComunidad;
                        document.getElementById('comunidad').appendChild(option);
                        document.getElementById('comunidad').value = result.id_comunidad;
                        
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

            // Funciones para mostrar/ocultar nuevo código de obra
            function mostrarSeccionNuevoCodigoObra() {
                nuevoCodigoObraSection.style.display = 'block';
                setTimeout(() => {
                    nuevoCodigoObraSection.classList.add('show');
                    nuevoCodigoObraSection.classList.remove('hide');
                }, 10);
                toggleNuevoCodigoObra.style.display = 'none';
            }

            function ocultarSeccionNuevoCodigoObra() {
                nuevoCodigoObraSection.classList.remove('show');
                nuevoCodigoObraSection.classList.add('hide');
                setTimeout(() => {
                    nuevoCodigoObraSection.style.display = 'none';
                }, 300);
                toggleNuevoCodigoObra.style.display = 'inline-block';
                
                // Limpiar campos
                document.getElementById('nuevo_codigo_obra').value = '';
            }

            // Eventos para mostrar/ocultar sección de nuevo código de obra
            toggleNuevoCodigoObra.addEventListener('click', mostrarSeccionNuevoCodigoObra);
            cerrarNuevoCodigoObra.addEventListener('click', ocultarSeccionNuevoCodigoObra);

            // Evento para guardar nuevo código de obra
            btnGuardarCodigoObra.addEventListener('click', async function() {
                const nuevoCodigo = document.getElementById('nuevo_codigo_obra').value.trim();

                if (!nuevoCodigo) {
                    showAlert('error', 'Por favor ingrese el nuevo código de obra');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('codigo_obra', nuevoCodigo);

                    const response = await fetch('conf/guardar_codigo_obra.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showAlert('success', 'Código de obra creado exitosamente');
                        
                        // Actualizar el select de códigos de obra
                        const option = document.createElement('option');
                        option.value = result.id_cod_obra;
                        option.textContent = nuevoCodigo;
                        document.getElementById('codigo_obra').appendChild(option);
                        document.getElementById('codigo_obra').value = result.id_cod_obra;
                        
                        // Ocultar la sección de nuevo código de obra
                        ocultarSeccionNuevoCodigoObra();
                    } else {
                        showAlert('error', result.message || 'Error al crear el código de obra');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('error', 'Error al procesar la solicitud');
                }
            });

            // Funciones para mostrar/ocultar nuevo fiscalizador
            function mostrarSeccionNuevoFiscalizador() {
                document.getElementById('nuevoFiscalizadorSection').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('nuevoFiscalizadorSection').classList.add('show');
                    document.getElementById('nuevoFiscalizadorSection').classList.remove('hide');
                }, 10);
                document.getElementById('toggleNuevoFiscalizador').style.display = 'none';
            }

            function ocultarSeccionNuevoFiscalizador() {
                document.getElementById('nuevoFiscalizadorSection').classList.remove('show');
                document.getElementById('nuevoFiscalizadorSection').classList.add('hide');
                setTimeout(() => {
                    document.getElementById('nuevoFiscalizadorSection').style.display = 'none';
                }, 300);
                document.getElementById('toggleNuevoFiscalizador').style.display = 'inline-block';
                
                // Limpiar campos
                document.getElementById('nuevo_fiscalizador').value = '';
            }

            // Eventos para mostrar/ocultar sección de nuevo fiscalizador
            document.getElementById('toggleNuevoFiscalizador').addEventListener('click', mostrarSeccionNuevoFiscalizador);
            document.getElementById('cerrarNuevoFiscalizador').addEventListener('click', ocultarSeccionNuevoFiscalizador);

            // Evento para guardar nuevo fiscalizador
            document.getElementById('btnGuardarFiscalizador').addEventListener('click', async function() {
                const nuevoFiscalizador = document.getElementById('nuevo_fiscalizador').value.trim();

                if (!nuevoFiscalizador) {
                    showAlert('error', 'Por favor ingrese el nombre del fiscalizador');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('fiscalizador', nuevoFiscalizador);

                    const response = await fetch('conf/guardar_fiscalizador.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showAlert('success', 'Fiscalizador creado exitosamente');
                        
                        // Actualizar el select de fiscalizadores
                        const option = document.createElement('option');
                        option.value = result.id_fiscalizador;
                        option.textContent = nuevoFiscalizador;
                        document.getElementById('fiscalizador').appendChild(option);
                        document.getElementById('fiscalizador').value = result.id_fiscalizador;
                        
                        // Ocultar la sección de nuevo fiscalizador
                        ocultarSeccionNuevoFiscalizador();
                    } else {
                        showAlert('error', result.message || 'Error al crear el fiscalizador');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('error', 'Error al procesar la solicitud');
                }
            });

            // Funciones para mostrar/ocultar nuevo método constructivo
            function mostrarSeccionNuevoMetodo() {
                document.getElementById('nuevoMetodoSection').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('nuevoMetodoSection').classList.add('show');
                    document.getElementById('nuevoMetodoSection').classList.remove('hide');
                }, 10);
                document.getElementById('toggleNuevoMetodo').style.display = 'none';
            }

            function ocultarSeccionNuevoMetodo() {
                document.getElementById('nuevoMetodoSection').classList.remove('show');
                document.getElementById('nuevoMetodoSection').classList.add('hide');
                setTimeout(() => {
                    document.getElementById('nuevoMetodoSection').style.display = 'none';
                }, 300);
                document.getElementById('toggleNuevoMetodo').style.display = 'inline-block';
                
                // Limpiar campos
                document.getElementById('nuevo_metodo').value = '';
            }

            // Eventos para mostrar/ocultar sección de nuevo método constructivo
            document.getElementById('toggleNuevoMetodo').addEventListener('click', mostrarSeccionNuevoMetodo);
            document.getElementById('cerrarNuevoMetodo').addEventListener('click', ocultarSeccionNuevoMetodo);

            // Evento para guardar nuevo método constructivo
            document.getElementById('btnGuardarMetodo').addEventListener('click', async function() {
                const nuevoMetodo = document.getElementById('nuevo_metodo').value.trim();

                if (!nuevoMetodo) {
                    showAlert('error', 'Por favor ingrese el nombre del método constructivo');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('metodo', nuevoMetodo);

                    const response = await fetch('conf/guardar_metodo.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showAlert('success', 'Método constructivo creado exitosamente');
                        
                        // Actualizar el select de métodos constructivos
                        const option = document.createElement('option');
                        option.value = result.id_metodo;
                        option.textContent = nuevoMetodo;
                        document.getElementById('metodo_constructivo').appendChild(option);
                        document.getElementById('metodo_constructivo').value = result.id_metodo;
                        
                        // Ocultar la sección de nuevo método constructivo
                        ocultarSeccionNuevoMetodo();
                    } else {
                        showAlert('error', result.message || 'Error al crear el método constructivo');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('error', 'Error al procesar la solicitud');
                }
            });

            // Funciones para mostrar/ocultar nuevo modelo constructivo
            function mostrarSeccionNuevoModelo() {
                document.getElementById('nuevoModeloSection').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('nuevoModeloSection').classList.add('show');
                    document.getElementById('nuevoModeloSection').classList.remove('hide');
                }, 10);
                document.getElementById('toggleNuevoModelo').style.display = 'none';
            }

            function ocultarSeccionNuevoModelo() {
                document.getElementById('nuevoModeloSection').classList.remove('show');
                document.getElementById('nuevoModeloSection').classList.add('hide');
                setTimeout(() => {
                    document.getElementById('nuevoModeloSection').style.display = 'none';
                }, 300);
                document.getElementById('toggleNuevoModelo').style.display = 'inline-block';
                
                // Limpiar campos
                document.getElementById('nuevo_modelo').value = '';
            }

            // Eventos para mostrar/ocultar sección de nuevo modelo constructivo
            document.getElementById('toggleNuevoModelo').addEventListener('click', mostrarSeccionNuevoModelo);
            document.getElementById('cerrarNuevoModelo').addEventListener('click', ocultarSeccionNuevoModelo);

            // Evento para guardar nuevo modelo constructivo
            document.getElementById('btnGuardarModelo').addEventListener('click', async function() {
                const nuevoModelo = document.getElementById('nuevo_modelo').value.trim();

                if (!nuevoModelo) {
                    showAlert('error', 'Por favor ingrese el nombre del modelo constructivo');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('modelo', nuevoModelo);

                    const response = await fetch('conf/guardar_modelo.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showAlert('success', 'Modelo constructivo creado exitosamente');
                        
                        // Actualizar el select de modelos constructivos
                        const option = document.createElement('option');
                        option.value = result.id_modelo;
                        option.textContent = nuevoModelo;
                        document.getElementById('modelo_constructivo').appendChild(option);
                        document.getElementById('modelo_constructivo').value = result.id_modelo;
                        
                        // Ocultar la sección de nuevo modelo constructivo
                        ocultarSeccionNuevoModelo();
                    } else {
                        showAlert('error', result.message || 'Error al crear el modelo constructivo');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('error', 'Error al procesar la solicitud');
                }
            });

            // Manejar el envío del formulario de nuevo beneficiario - CORREGIDO
            document.getElementById('formNuevoBeneficiario').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const form = this;
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnContent = submitBtn.innerHTML;
                
                try {
                    // Obtener datos del formulario
                    const formData = new FormData(form);
                    
                    // Validar formulario
                    if (!validateForm(formData)) {
                        return;
                    }
                    
                    // Mostrar estado de carga
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
                    submitBtn.disabled = true;
                    
                    // Enviar formulario con manejo de JSON mejorado
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Verificar si la respuesta es ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    // Obtener texto de respuesta primero para verificar contenido
                    const responseText = await response.text();
                    
                    // Intentar parsear como JSON
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('Response text:', responseText);
                        throw new Error('La respuesta del servidor no es un JSON válido');
                    }
                    
                    // Manejar respuesta
                    if (result.status === 'success') {
                        showAlert('success', result.message || 'Beneficiario creado exitosamente');
                        
                        // Resetear formulario
                        form.reset();
                        
                        // Cerrar modal y recargar página después de un delay
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoBeneficiario'));
                            if (modal) {
                                modal.hide();
                            }
                            window.location.reload();
                        }, 1500);
                    } else {
                        throw new Error(result.message || 'Error desconocido al crear el beneficiario');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('error', error.message || 'Error al procesar la solicitud');
                } finally {
                    // Restaurar estado del botón
                    submitBtn.innerHTML = originalBtnContent;
                    submitBtn.disabled = false;
                }
            });

            // Cargar datos iniciales si hay valores seleccionados
            if (municipioSelect.value) {
                cargarParroquias(municipioSelect.value);
            }
            if (parroquiaSelect.value) {
                cargarComunidades(parroquiaSelect.value);
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