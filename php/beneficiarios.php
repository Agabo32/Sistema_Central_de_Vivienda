<?php
session_start();
require_once '../php/conf/conexion.php';

// Verificación robusta de sesión y rol
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'admin';
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
.modal-body .form-label[for="utm_este"]:after {
    content: "";
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
                    <a class="nav-link ms-2" href="../index.php" style="color : #f8f9fa">
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
                                                            <div class="d-flex align-items-center" >
                                                                <div class="me-3">
                                                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: #000000; color: #ffffff;" >
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
                                                            <span class="badge bg-primary bg-opacity-10 text-primary " style="color: #e30016;">
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
        </div>
    </div>

    <!-- Modal Nuevo Beneficiario -->
    <div class="modal fade" id="modalNuevoBeneficiario" tabindex="-1" aria-labelledby="modalNuevoBeneficiarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalNuevoBeneficiarioLabel">
                        <i class="fas fa-user-plus me-2"></i> Nuevo Beneficiario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevoBeneficiario" method="POST" action="../php/conf/guardar_beneficiario.php">
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
                                <input type="text" class="form-control" id="codigo_obra" name="codigo_obra" required>
                            </div>
                        </div>

                        <!-- Información de Ubicación -->
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Información de Ubicación</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="comunidad" class="form-label">Comunidad *</label>
                                <input type="text" class="form-control" id="comunidad" name="comunidad" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="direccion_exacta" class="form-label">Dirección Exacta</label>
                                <input type="text" class="form-control" id="direccion_exacta" name="direccion_exacta">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="modalEstado" class="form-label">Estado *</label>
                                <select name="estado" id="modalEstado" class="form-select" required>
                                    <?php
                                    // Forzar solo el estado Lara
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
                                    // Cargar municipios de Lara
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidad de filtros en la página principal
            const estadoSelect = document.getElementById('estadoSelect');
            const municipioSelect = document.getElementById('municipioSelect');
            const parroquiaSelect = document.getElementById('parroquiaSelect');
            
            municipioSelect.addEventListener('change', function() {
                const municipioId = this.value;
                
                if (municipioId) {
                    parroquiaSelect.disabled = false;
                    fetch(`../php/conf/get_parroquias.php?municipio_id=${municipioId}`)
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

            // Funcionalidad del modal
            const modalEstado = document.getElementById('modalEstado');
            const modalMunicipio = document.getElementById('modalMunicipio');
            const modalParroquia = document.getElementById('modalParroquia');
            
            // Deshabilitar cambios en el estado (siempre será Lara)
            modalEstado.disabled = true;

            // Cargar parroquias al seleccionar municipio en el modal
            modalMunicipio.addEventListener('change', function() {
                const municipioId = this.value;
                modalParroquia.innerHTML = '<option value="">Cargando...</option>';
                modalParroquia.disabled = true;
                
                if (municipioId) {
                    fetch(`../php/conf/get_parroquias.php?municipio_id=${municipioId}`)
                        .then(response => response.json())
                        .then(data => {
                            modalParroquia.innerHTML = '<option value="">Seleccione una parroquia</option>';
                            data.forEach(parroquia => {
                                const option = document.createElement('option');
                                option.value = parroquia.id_parroquia;
                                option.textContent = parroquia.parroquia;
                                modalParroquia.appendChild(option);
                            });
                            modalParroquia.disabled = false;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            modalParroquia.innerHTML = '<option value="">Error al cargar parroquias</option>';
                        });
                } else {
                    modalParroquia.innerHTML = '<option value="">Seleccione un municipio primero</option>';
                }
            });

            // Manejar envío del formulario
            const formNuevoBeneficiario = document.getElementById('formNuevoBeneficiario');
formNuevoBeneficiario.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validar campos requeridos con los IDs correctos del modal
    const camposRequeridos = [
        { id: 'nombre_beneficiario', nombre: 'Nombre Completo' },
        { id: 'cedula', nombre: 'Cédula' },
        { id: 'telefono', nombre: 'Teléfono' },
        { id: 'codigo_obra', nombre: 'Código de Obra' },
        { id: 'comunidad', nombre: 'Comunidad' },
        { id: 'modalMunicipio', nombre: 'Municipio' },
        { id: 'modalParroquia', nombre: 'Parroquia' }
    ];
    
    let camposFaltantes = [];
    
    camposRequeridos.forEach(campo => {
        const elemento = document.getElementById(campo.id);
        if (!elemento || !elemento.value.trim()) {
            camposFaltantes.push(campo.nombre);
        }
    });
    
    if (camposFaltantes.length > 0) {
        alert('Por favor complete todos los campos requeridos:\n• ' + camposFaltantes.join('\n• '));
        
        // Enfocar el primer campo faltante
        const primerCampoFaltante = camposRequeridos.find(campo => {
            const elemento = document.getElementById(campo.id);
            return !elemento || !elemento.value.trim();
        });
        
        if (primerCampoFaltante) {
            const elemento = document.getElementById(primerCampoFaltante.id);
            if (elemento) {
                elemento.focus();
                elemento.classList.add('is-invalid');
                setTimeout(() => elemento.classList.remove('is-invalid'), 3000);
            }
        }
        return;
    }
    
    // Obtener referencia al botón antes del try
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    try {
        const formData = new FormData(this);
        
        // Mostrar indicador de carga
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
        submitBtn.disabled = true;
        
        const response = await fetch('../php/conf/guardar_beneficiario.php', {
            method: 'POST',
            body: formData
        });
        
        let result;
        
        try {
            const text = await response.text();
            try {
                result = JSON.parse(text);
            } catch (jsonError) {
                console.error('Error al parsear JSON:', text);
                throw new Error('Respuesta del servidor no es JSON válido. Revise los logs para más detalles.');
            }
        } catch (textError) {
            console.error('Error al obtener texto de respuesta:', textError);
            throw new Error('No se pudo leer la respuesta del servidor');
        }
        
        if (result.status === 'success' || result.status === 'ok') {
            alert('✅ Beneficiario agregado exitosamente:\n' + (result.beneficiario?.nombre_beneficiario || 'Beneficiario'));
            
            // Cerrar modal y recargar página
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoBeneficiario'));
            if (modal) {
                modal.hide();
            }
            
            // Limpiar formulario
            this.reset();
            
            // Recargar página después de un breve delay
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            throw new Error(result.message || 'Error desconocido al guardar');
        }
    } catch (error) {
        console.error('Error completo:', error);
        alert('❌ Error al guardar beneficiario:\n' + error.message);
    } finally {
        // Restaurar botón
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    }
});

            // Funcionalidad de búsqueda
            const inputBuscar = document.getElementById('buscar');
            const tablaBeneficiarios = document.getElementById('tablaBeneficiarios');
            const filas = tablaBeneficiarios.querySelectorAll('tr');
            const paginationContainer = document.querySelector('.pagination');

            function busquedaAvanzada(termino) {
                termino = termino.trim().toLowerCase();
                let resultadosEncontrados = 0;
                
                filas.forEach(fila => {
                    const celdas = fila.getElementsByTagName('td');
                    if (celdas.length > 0) {
                        const cedula = celdas[1].textContent.toLowerCase().replace(/\D/g, '');
                        const nombre = celdas[2].textContent.toLowerCase();
                        const codigoObra = celdas[4].textContent.toLowerCase();
                        
                        const terminoLimpio = termino.replace(/\D/g, '');
                        
                        const coincide = 
                            cedula.includes(terminoLimpio) || 
                            nombre.includes(termino) || 
                            codigoObra.includes(termino);
                        
                        if (coincide) {
                            fila.style.display = '';
                            resultadosEncontrados++;
                        } else {
                            fila = 'none';
                        }
                    }
                });

                if (paginationContainer) {
                    paginationContainer.style.display = resultadosEncontrados === 0 ? 'none' : 'flex';
                }
            }

            function debounce(func, timeout = 300) {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => { func.apply(this, args); }, timeout);
                };
            }

            const busquedaOptimizada = debounce(busquedaAvanzada);
            inputBuscar.addEventListener('input', function() {
                busquedaOptimizada(this.value);
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
