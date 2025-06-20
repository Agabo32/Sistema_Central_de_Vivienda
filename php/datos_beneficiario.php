<?php
session_start();
require_once '../php/conf/conexion.php';

// Verificación robusta de sesión y rol
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'root';

// Obtener el rol del usuario actual
$usuario_actual = $_SESSION['user'] ?? null;
$rol_usuario = $usuario_actual['rol'] ?? '';

// Obtener fiscalizadores
$query_fiscalizadores = "SELECT id_fiscalizador, Fiscalizador FROM fiscalizadores ORDER BY Fiscalizador ASC";
$resultado_fiscalizadores = mysqli_query($conexion, $query_fiscalizadores);

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
    b.status,
    b.cod_obra,
    b.metodo_constructivo,
    b.modelo_constructivo,
    b.fiscalizador,
    c.id_comunidad,
    c.comunidad, 
    u.direccion_exacta, 
    u.utm_norte, 
    u.utm_este,
    m.id_municipio,
    m.municipio AS municipio,  
    p.id_parroquia,
    p.parroquia AS parroquia,
    mc.metodo AS metodo_constructivo_nombre, 
    mo.modelo AS modelo_constructivo_nombre,
    co.cod_obra as codigo_obra_nombre,
    f.Fiscalizador AS nombre_fiscalizador,
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
    dc.fecha_protocolizacion,
    dc.acta_entregada, 
    dc.observaciones_responsables_control, 
    dc.observaciones_fiscalizadores
FROM beneficiarios b
LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
LEFT JOIN comunidades c ON u.comunidad = c.id_comunidad
LEFT JOIN parroquias p ON u.parroquia = p.id_parroquia
LEFT JOIN municipios m ON p.id_municipio = m.id_municipio
LEFT JOIN metodos_constructivos mc ON b.metodo_constructivo = mc.id_metodo
LEFT JOIN modelos_constructivos mo ON b.modelo_constructivo = mo.id_modelo
LEFT JOIN cod_obra co ON b.cod_obra = co.id_cod_obra
LEFT JOIN fiscalizadores f ON b.fiscalizador = f.id_fiscalizador
LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
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
    <link rel="stylesheet" href="../css/datos_beneficiarios.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        #avanceChart, #estadoChart {
            width: 100% !important;
            height: 100% !important;
        }
    </style>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark glass-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/Sistema_Central_de_Vivienda-main/php/menu_principal.php">
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
                    <a class="nav-link ms-2" href="../php/conf/logout.php" style="color: #f8f9fa">
                        <i class="fas fa-sign-out-alt me-1" style="color: #f8f9fa"></i> Cerrar Sesión
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
                        <?php if ($esAdmin): ?>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#actualizarBeneficiarioModal">
                            <i class="fas fa-edit me-2"></i>Actualizar Beneficiario
                        </button>
                        <?php endif; ?>
                        <?php if ($rol_usuario !== 'usuario'): ?>
                        <a href="expediente.php?id=<?= $data['id_beneficiario'] ?>" 
                           class="btn btn-danger btn-action" 
                           target="_blank">
                            <i class="fas fa-file-pdf me-1"></i> Generar Expediente
                        </a>
                        <a href="expediente.php?id=<?= $data['id_beneficiario'] ?>" 
                           class="btn btn-info btn-action me-2" 
                           target="_blank">
                            <i class="fas fa-file-alt me-1"></i> Ver Expediente
                        </a>
                        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#reporteIndividualModal">
                            <i class="fas fa-print me-2"></i>Imprimir Reporte
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Actualizar Beneficiario -->
        <?php if ($esAdmin): ?>
        <div class="modal fade" id="actualizarBeneficiarioModal" tabindex="-1" aria-labelledby="actualizarBeneficiarioModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="actualizarBeneficiarioModalLabel">Actualizar Beneficiario</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="actualizarBeneficiarioForm">
                            <input type="hidden" name="id_beneficiario" value="<?php echo $data['id_beneficiario']; ?>">
                            
                            <!-- Pestañas -->
                            <ul class="nav nav-tabs" id="beneficiarioTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="basicos-tab" data-bs-toggle="tab" data-bs-target="#basicos" type="button" role="tab">
                                        <i class="fas fa-user me-1"></i> Datos Básicos
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="ubicacion-tab" data-bs-toggle="tab" data-bs-target="#ubicacion" type="button" role="tab">
                                        <i class="fas fa-map-marker-alt me-1"></i> Ubicación
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="construccion-tab" data-bs-toggle="tab" data-bs-target="#construccion" type="button" role="tab">
                                        <i class="fas fa-tools me-1"></i> Construcción
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content mt-3" id="beneficiarioTabContent">
                                <!-- Pestaña Datos Básicos -->
                                <div class="tab-pane fade show active" id="basicos" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nombre_beneficiario" class="form-label required">Nombre Completo</label>
                                                <input type="text" class="form-control" id="nombre_beneficiario" name="nombre_beneficiario" 
                                                       value="<?php echo htmlspecialchars($data['nombre_beneficiario']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="cedula" class="form-label required">Cédula</label>
                                                <input type="text" class="form-control" id="cedula" name="cedula" 
                                                       value="<?php echo htmlspecialchars($data['cedula']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="telefono" class="form-label">Teléfono</label>
                                                <input type="text" class="form-control" id="telefono" name="telefono" maxlength="50" value="<?php echo htmlspecialchars($data['telefono']); ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="fiscalizador" class="form-label">Fiscalizador</label>
                                                <select class="form-select" id="fiscalizador" name="id_fiscalizador">
                                                    <option value="">Seleccione un fiscalizador</option>
                                                    <?php
                                                    mysqli_data_seek($resultado_fiscalizadores, 0);
                                                    while ($row = mysqli_fetch_assoc($resultado_fiscalizadores)) {
                                                        $selected = ($row['id_fiscalizador'] == $data['fiscalizador']) ? 'selected' : '';
                                                        echo "<option value='{$row['id_fiscalizador']}' $selected>{$row['Fiscalizador']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="fecha_protocolizacion" class="form-label">Fecha de Protocolización</label>
                                                <input type="date" class="form-control" id="fecha_protocolizacion" name="fecha_protocolizacion" 
                                                       value="<?php echo htmlspecialchars($data['fecha_protocolizacion'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="codigo_obra" class="form-label required">Código de Obra</label>
                                                <select class="form-select" id="codigo_obra" name="codigo_obra" required>
                                                    <option value="">Seleccione un código de obra</option>
                                                    <?php
                                                    $codigos_obra = $conexion->query("SELECT id_cod_obra, cod_obra FROM cod_obra ORDER BY cod_obra ASC");
                                                    while ($row = $codigos_obra->fetch_assoc()) {
                                                        $selected = ($row['id_cod_obra'] == $data['cod_obra']) ? 'selected' : '';
                                                        echo "<option value='{$row['id_cod_obra']}' $selected>{$row['cod_obra']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="metodo_constructivo" class="form-label">Método Constructivo</label>
                                                <select class="form-select" id="metodo_constructivo" name="metodo_constructivo">
                                                    <option value="">Seleccione un método constructivo</option>
                                                    <?php
                                                    $metodos = $conexion->query("SELECT id_metodo, metodo FROM metodos_constructivos ORDER BY metodo ASC");
                                                    while ($row = $metodos->fetch_assoc()) {
                                                        $selected = ($row['id_metodo'] == $data['metodo_constructivo']) ? 'selected' : '';
                                                        echo "<option value='{$row['id_metodo']}' $selected>{$row['metodo']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="modelo_constructivo" class="form-label">Modelo Constructivo</label>
                                                <select class="form-select" id="modelo_constructivo" name="modelo_constructivo">
                                                    <option value="">Seleccione un modelo constructivo</option>
                                                    <?php
                                                    $modelos = $conexion->query("SELECT id_modelo, modelo FROM modelos_constructivos ORDER BY modelo ASC");
                                                    while ($row = $modelos->fetch_assoc()) {
                                                        $selected = ($row['id_modelo'] == $data['modelo_constructivo']) ? 'selected' : '';
                                                        echo "<option value='{$row['id_modelo']}' $selected>{$row['modelo']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="status" class="form-label required">Estado</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="activo" <?= ($data['status'] ?? 'activo') == 'activo' ? 'selected' : '' ?>>Activo</option>
                                                    <option value="inactivo" <?= ($data['status'] ?? 'activo') == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Ubicación -->
                                <div class="tab-pane fade" id="ubicacion" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="municipioSelect" class="form-label">Municipio</label>
                                                <select class="form-select" id="municipioSelect" name="id_municipio">
                                                    <option value="">Seleccione un municipio</option>
                                                    <?php
                                                    $municipios = $conexion->query("SELECT id_municipio, municipio FROM municipios ORDER BY municipio");
                                                    while ($row = $municipios->fetch_assoc()) {
                                                        $selected = ($row['id_municipio'] == $data['id_municipio']) ? 'selected' : '';
                                                        echo "<option value='{$row['id_municipio']}' $selected>{$row['municipio']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="parroquiaSelect" class="form-label">Parroquia</label>
                                                <select class="form-select" id="parroquiaSelect" name="id_parroquia">
                                                    <option value="">Seleccione una parroquia</option>
                                                    <?php
                                                    if (!empty($data['id_municipio'])) {
                                                        $parroquias = $conexion->query("SELECT id_parroquia, parroquia FROM parroquias WHERE id_municipio = {$data['id_municipio']} ORDER BY parroquia");
                                                        while ($row = $parroquias->fetch_assoc()) {
                                                            $selected = ($row['id_parroquia'] == $data['id_parroquia']) ? 'selected' : '';
                                                            echo "<option value='{$row['id_parroquia']}' $selected>{$row['parroquia']}</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="comunidadSelect" class="form-label">Comunidad</label>
                                                <select class="form-select" id="comunidadSelect" name="id_comunidad">
                                                    <option value="">Seleccione una comunidad</option>
                                                    <?php
                                                    if (!empty($data['id_parroquia'])) {
                                                        $comunidades = $conexion->query("SELECT id_comunidad, comunidad FROM comunidades WHERE id_parroquia = {$data['id_parroquia']} ORDER BY comunidad");
                                                        while ($row = $comunidades->fetch_assoc()) {
                                                            $selected = ($row['id_comunidad'] == $data['id_comunidad']) ? 'selected' : '';
                                                            echo "<option value='{$row['id_comunidad']}' $selected>{$row['comunidad']}</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="direccion_exacta" class="form-label">Dirección Exacta</label>
                                                <textarea class="form-control" id="direccion_exacta" name="direccion_exacta" rows="3"><?php echo htmlspecialchars($data['direccion_exacta']); ?></textarea>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="utm_norte" class="form-label">UTM Norte</label>
                                                        <input type="number" step="0.000001" class="form-control" id="utm_norte" name="utm_norte" 
                                                               value="<?php echo htmlspecialchars($data['utm_norte']); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="utm_este" class="form-label">UTM Este</label>
                                                        <input type="number" step="0.000001" class="form-control" id="utm_este" name="utm_este" 
                                                               value="<?php echo htmlspecialchars($data['utm_este']); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Construcción -->
                                <div class="tab-pane fade" id="construccion" role="tabpanel">
                                    <div class="row">
                                        <!-- Fundación -->
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-3"><i class="fas fa-hammer me-1"></i> Fundación</h6>
                                            
                                            <div class="mb-3">
                                                <label for="limpieza" class="form-label">Limpieza (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="limpieza" name="limpieza" 
                                                       value="<?php echo $data['limpieza'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="replanteo" class="form-label">Replanteo (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="replanteo" name="replanteo" 
                                                       value="<?php echo $data['replanteo'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="excavacion" class="form-label">Excavación (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="excavacion" name="excavacion" 
                                                       value="<?php echo $data['excavacion'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="fundacion" class="form-label">Fundación (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="fundacion" name="fundacion" 
                                                       value="<?php echo $data['fundacion'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="acero_vigas_riostra" class="form-label">Acero en Vigas de Riostra (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="acero_vigas_riostra" name="acero_vigas_riostra" 
                                                       value="<?php echo $data['acero_vigas_riostra'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="encofrado_malla" class="form-label">Encofrado y Colocación de Malla (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="encofrado_malla" name="encofrado_malla" 
                                                       value="<?php echo $data['encofrado_malla'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="instalaciones_electricas_sanitarias" class="form-label">Instalaciones Eléctricas y Sanitarias (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="instalaciones_electricas_sanitarias" name="instalaciones_electricas_sanitarias" 
                                                       value="<?php echo $data['instalaciones_electricas_sanitarias'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="vaciado_losa_anclajes" class="form-label">Vaciado de Losa y Colocación de Anclajes (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="vaciado_losa_anclajes" name="vaciado_losa_anclajes" 
                                                       value="<?php echo $data['vaciado_losa_anclajes'] ?? 0; ?>">
                                            </div>
                                        </div>
                                        
                                        <!-- Estructura -->
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-3"><i class="fas fa-cubes me-1"></i> Estructura</h6>
                                            
                                            <div class="mb-3">
                                                <label for="estructura" class="form-label">Estructura (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="estructura" name="estructura" 
                                                       value="<?php echo $data['estructura'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="armado_columnas" class="form-label">Armado de Columnas (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="armado_columnas" name="armado_columnas" 
                                                       value="<?php echo $data['armado_columnas'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="vaciado_columnas" class="form-label">Vaciado de Columnas (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="vaciado_columnas" name="vaciado_columnas" 
                                                       value="<?php echo $data['vaciado_columnas'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="armado_vigas" class="form-label">Armado de Vigas (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="armado_vigas" name="armado_vigas" 
                                                       value="<?php echo $data['armado_vigas'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="vaciado_vigas" class="form-label">Vaciado de Vigas (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="vaciado_vigas" name="vaciado_vigas" 
                                                       value="<?php echo $data['vaciado_vigas'] ?? 0; ?>">
                                            </div>

                                            <!-- Cerramiento -->
                                            <h6 class="text-primary mb-3 mt-4"><i class="fas fa-building me-1"></i> Cerramiento</h6>
                                            
                                            <div class="mb-3">
                                                <label for="cerramiento" class="form-label">Cerramiento (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="cerramiento" name="cerramiento" 
                                                       value="<?php echo $data['cerramiento'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="bloqueado" class="form-label">Bloqueado (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="bloqueado" name="bloqueado" 
                                                       value="<?php echo $data['bloqueado'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="colocacion_correas" class="form-label">Colocación de Correas (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="colocacion_correas" name="colocacion_correas" 
                                                       value="<?php echo $data['colocacion_correas'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="colocacion_techo" class="form-label">Colocación de Techo (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="colocacion_techo" name="colocacion_techo" 
                                                       value="<?php echo $data['colocacion_techo'] ?? 0; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <!-- Acabados -->
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-3"><i class="fas fa-paint-roller me-1"></i> Acabados</h6>

                                            <div class="mb-3">
                                                <label for="colocacion_ventanas" class="form-label">Colocación de Ventanas (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="colocacion_ventanas" name="colocacion_ventanas" 
                                                       value="<?php echo $data['colocacion_ventanas'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="colocacion_puertas_principales" class="form-label">Colocación de Puertas Principales (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="colocacion_puertas_principales" name="colocacion_puertas_principales" 
                                                       value="<?php echo $data['colocacion_puertas_principales'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="instalaciones_electricas_sanitarias_paredes" class="form-label">Instalaciones Eléctricas y Sanitarias en Paredes (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="instalaciones_electricas_sanitarias_paredes" name="instalaciones_electricas_sanitarias_paredes" 
                                                       value="<?php echo $data['instalaciones_electricas_sanitarias_paredes'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="frisos" class="form-label">Frisos (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="frisos" name="frisos" 
                                                       value="<?php echo $data['frisos'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="sobrepiso" class="form-label">Sobre-piso (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="sobrepiso" name="sobrepiso" 
                                                       value="<?php echo $data['sobrepiso'] ?? 0; ?>">
                                            </div>        

                                            <div class="mb-3">
                                                <label for="ceramica_bano" class="form-label">Cerámica en Baño (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="ceramica_bano" name="ceramica_bano" 
                                                       value="<?php echo $data['ceramica_bano'] ?? 0; ?>">
                                            </div>        

                                            <div class="mb-3">
                                                <label for="colocacion_puertas_internas" class="form-label">Colocación de Puertas Internas (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="colocacion_puertas_internas" name="colocacion_puertas_internas" 
                                                       value="<?php echo $data['colocacion_puertas_internas'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="equipos_accesorios_electricos" class="form-label">Equipos y Accesorios Eléctricos (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="equipos_accesorios_electricos" name="equipos_accesorios_electricos" 
                                                       value="<?php echo $data['equipos_accesorios_electricos'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="equipos_accesorios_sanitarios" class="form-label">Equipos y Accesorios Sanitarios (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="equipos_accesorios_sanitarios" name="equipos_accesorios_sanitarios" 
                                                       value="<?php echo $data['equipos_accesorios_sanitarios'] ?? 0; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="colocacion_lavaplatos" class="form-label">Colocación de Lavaplatos (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="colocacion_lavaplatos" name="colocacion_lavaplatos" 
                                                       value="<?php echo $data['colocacion_lavaplatos'] ?? 0; ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="pintura" class="form-label">Pintura (%)</label>
                                                <input type="number" min="0" max="100" class="form-control" id="pintura" name="pintura" 
                                                       value="<?php echo $data['pintura'] ?? 0; ?>">
                                            </div>
                                        </div>
                                        
                                        <!-- Avance General -->
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-3"><i class="fas fa-chart-line me-1"></i> Avance General</h6>
                                            
                                            <div class="mb-3">
                                                <label for="avance_fisico" class="form-label">Avance Físico (%)</label>
                                                <input type="number" class="form-control" id="avance_fisico" name="avance_fisico" 
                                                       value="<?php echo htmlspecialchars($data['avance_fisico']); ?>" step="0.01" min="0" max="100">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="acabado" class="form-label">Acabado (%)</label>
                                                <input type="number" class="form-control" id="acabado" name="acabado" 
                                                       value="<?php echo htmlspecialchars($data['acabado']); ?>" step="0.01" min="0" max="100" readonly>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="fecha_culminacion" class="form-label">Fecha de Culminación</label>
                                                <input type="date" class="form-control" id="fecha_culminacion" name="fecha_culminacion" 
                                                       value="<?php echo $data['fecha_culminacion']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="acta_entregada" class="form-label">Acta Entregada</label>
                                                <select class="form-select" id="acta_entregada" name="acta_entregada">
                                                    <option value="0" <?= ($data['acta_entregada'] == 0) ? 'selected' : '' ?>>No</option>
                                                    <option value="1" <?= ($data['acta_entregada'] == 1) ? 'selected' : '' ?>>Sí</option>
                                                </select>
                                            </div>

                                            <!-- Observaciones -->
                                            <h6 class="text-primary mb-3 mt-4"><i class="fas fa-comments me-1"></i> Observaciones</h6>
                                            
                                            <div class="mb-3">
                                                <label for="observaciones_responsables_control" class="form-label">Observaciones Responsables</label>
                                                <textarea class="form-control" id="observaciones_responsables_control" name="observaciones_responsables_control" rows="3"><?php echo htmlspecialchars($data['observaciones_responsables_control'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="observaciones_fiscalizadores" class="form-label">Observaciones Fiscalizadores</label>
                                                <textarea class="form-control" id="observaciones_fiscalizadores" name="observaciones_fiscalizadores" rows="3"><?php echo htmlspecialchars($data['observaciones_fiscalizadores'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="btnGuardarCambios">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modal Reporte Individual -->
        <div class="modal fade" id="reporteIndividualModal" tabindex="-1" aria-labelledby="reporteIndividualModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-gradient-primary text-white print-hide">
                    <h5 class="modal-title" id="reporteIndividualModalLabel">
                        <i class="fas fa-file-alt me-2"></i>Reporte Individual - <?php echo htmlspecialchars($data['nombre_beneficiario']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-0" id="reporteContent">
                    <!-- Encabezado del Reporte -->
                    <div class="reporte-header">
                        <div class="container-fluid">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <img src="../imagenes/logo_menu.png.ico" alt="SIGEVU" class="logo-reporte">
                                </div>
                                <div class="col-md-8 text-center">
                                    <h2 class="reporte-title">SISTEMA INTEGRAL DE GESTIÓN DE VIVIENDAS</h2>
                                    <h3 class="reporte-subtitle">REPORTE INDIVIDUAL DE BENEFICIARIO</h3>
                                    <div class="reporte-date">
                                        Fecha de Generación: <?php echo date('d/m/Y H:i:s'); ?>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div class="reporte-id">
                                        <strong>ID: <?php echo str_pad($data['id_beneficiario'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido del Reporte -->
                    <div class="reporte-body">
                        <div class="container-fluid">
                            
                            <!-- Información Personal -->
                            <div class="reporte-section">
                                <div class="section-header">
                                    <i class="fas fa-user"></i>
                                    <h4>INFORMACIÓN PERSONAL</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <label>Nombre Completo:</label>
                                            <span><?php echo htmlspecialchars($data['nombre_beneficiario']); ?></span>
                                        </div>
                                        <div class="info-box">
                                            <label>Cédula de Identidad:</label>
                                            <span><?php echo htmlspecialchars($data['cedula']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <label>Teléfono:</label>
                                            <span><?php echo htmlspecialchars($data['telefono'] ?: 'No registrado'); ?></span>
                                        </div>
                                        <div class="info-box">
                                            <label>Estado:</label>
                                            <span class="status-badge-print <?php echo ($data['status'] == 'activo') ? 'activo' : 'inactivo'; ?>">
                                                <?php echo ucfirst($data['status'] ?? 'activo'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ubicación -->
                            <div class="reporte-section">
                                <div class="section-header">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <h4>UBICACIÓN</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <label>Municipio:</label>
                                            <span><?php echo htmlspecialchars($data['municipio'] ?: 'No especificado'); ?></span>
                                        </div>
                                        <div class="info-box">
                                            <label>Parroquia:</label>
                                            <span><?php echo htmlspecialchars($data['parroquia'] ?: 'No especificado'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <label>Comunidad:</label>
                                            <span><?php echo htmlspecialchars($data['comunidad'] ?: 'No especificado'); ?></span>
                                        </div>
                                        <div class="info-box">
                                            <label>Dirección Exacta:</label>
                                            <span><?php echo htmlspecialchars($data['direccion_exacta'] ?: 'No especificada'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <label>Coordenadas UTM:</label>
                                            <span>
                                                Norte: <?php echo htmlspecialchars($data['utm_norte'] ?: 'N/A'); ?><br>
                                                Este: <?php echo htmlspecialchars($data['utm_este'] ?: 'N/A'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Proyecto de Construcción -->
                            <div class="reporte-section">
                                <div class="section-header">
                                    <i class="fas fa-home"></i>
                                    <h4>PROYECTO DE CONSTRUCCIÓN</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <label>Código de Obra:</label>
                                            <span><?php echo htmlspecialchars($data['codigo_obra_nombre'] ?: 'No asignado'); ?></span>
                                        </div>
                                        <div class="info-box">
                                            <label>Método Constructivo:</label>
                                            <span><?php echo htmlspecialchars($data['metodo_constructivo_nombre'] ?: 'No especificado'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <label>Modelo Constructivo:</label>
                                            <span><?php echo htmlspecialchars($data['modelo_constructivo_nombre'] ?: 'No especificado'); ?></span>
                                        </div>
                                        <div class="info-box">
                                            <label>Fiscalizador:</label>
                                            <span><?php echo htmlspecialchars($data['nombre_fiscalizador'] ?: 'No asignado'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Avance General -->
                            <div class="reporte-section">
                                <div class="section-header">
                                    <i class="fas fa-chart-line"></i>
                                    <h4>AVANCE GENERAL DEL PROYECTO</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="avance-general">
                                            <div class="avance-circle">
                                                <div class="avance-number"><?php echo number_format($data['avance_fisico'] ?? 0, 1); ?>%</div>
                                                <div class="avance-label">Avance Físico Total</div>
                                            </div>
                                            <div class="avance-details">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="info-box">
                                                            <label>Fecha de Culminación:</label>
                                                            <span><?php echo $data['fecha_culminacion'] ? date('d/m/Y', strtotime($data['fecha_culminacion'])) : 'No especificada'; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="info-box">
                                                            <label>Acta Entregada:</label>
                                                            <span class="status-badge-print <?php echo ($data['acta_entregada'] == 1) ? 'activo' : 'inactivo'; ?>">
                                                                <?php echo ($data['acta_entregada'] == 1) ? 'Sí' : 'No'; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detalle de Construcción -->
                            <div class="reporte-section">
                                <div class="section-header">
                                    <i class="fas fa-tools"></i>
                                    <h4>DETALLE DE AVANCE POR ETAPAS</h4>
                                </div>
                                
                                <!-- Fundación -->
                                <div class="etapa-construccion">
                                    <h5><i class="fas fa-hammer"></i> FUNDACIÓN</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Limpieza</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['limpieza'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['limpieza'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Replanteo</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['replanteo'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['replanteo'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Excavación</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['excavacion'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['excavacion'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Fundación</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['fundacion'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['fundacion'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estructura -->
                                <div class="etapa-construccion">
                                    <h5><i class="fas fa-cubes"></i> ESTRUCTURA</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Armado Columnas</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['armado_columnas'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['armado_columnas'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Vaciado Columnas</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['vaciado_columnas'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['vaciado_columnas'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Armado Vigas</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['armado_vigas'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['armado_vigas'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Vaciado Vigas</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['vaciado_vigas'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['vaciado_vigas'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cerramiento -->
                                <div class="etapa-construccion">
                                    <h5><i class="fas fa-building"></i> CERRAMIENTO</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="progress-item">
                                                <label>Bloqueado</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['bloqueado'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['bloqueado'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="progress-item">
                                                <label>Colocación Correas</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['colocacion_correas'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['colocacion_correas'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="progress-item">
                                                <label>Colocación Techo</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['colocacion_techo'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['colocacion_techo'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acabados -->
                                <div class="etapa-construccion">
                                    <h5><i class="fas fa-paint-roller"></i> ACABADOS</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Frisos</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['frisos'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['frisos'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Pintura</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['pintura'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['pintura'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Ventanas</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['colocacion_ventanas'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['colocacion_ventanas'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="progress-item">
                                                <label>Puertas</label>
                                                <div class="progress-bar-print">
                                                    <div class="progress-fill" style="width: <?php echo $data['colocacion_puertas_principales'] ?? 0; ?>%"></div>
                                                    <span class="progress-text"><?php echo number_format($data['colocacion_puertas_principales'] ?? 0, 1); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <?php if (!empty($data['observaciones_responsables_control']) || !empty($data['observaciones_fiscalizadores'])): ?>
                            <div class="reporte-section">
                                <div class="section-header">
                                    <i class="fas fa-comments"></i>
                                    <h4>OBSERVACIONES</h4>
                                </div>
                                <?php if (!empty($data['observaciones_responsables_control'])): ?>
                                <div class="observacion-box">
                                    <h6>Observaciones de Responsables de Control:</h6>
                                    <p><?php echo nl2br(htmlspecialchars($data['observaciones_responsables_control'])); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($data['observaciones_fiscalizadores'])): ?>
                                <div class="observacion-box">
                                    <h6>Observaciones de Fiscalizadores:</h6>
                                    <p><?php echo nl2br(htmlspecialchars($data['observaciones_fiscalizadores'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Pie del Reporte -->
                            <div class="reporte-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="firma-box">
                                            <div class="firma-line"></div>
                                            <p>Responsable de Control y Seguimiento</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="firma-box">
                                            <div class="firma-line"></div>
                                            <p>Fiscalizador Asignado</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-4">
                                    <small>Este reporte fue generado automáticamente por el Sistema Integral de Gestión de Viviendas (SIGEVU)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer print-hide">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                    <?php if ($rol_usuario !== 'usuario'): ?>
                    <button type="button" class="btn btn-primary" onclick="imprimirReporte()">
                        <i class="fas fa-print me-1"></i> Imprimir Reporte
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

        <!-- Información Personal -->
        <div class="card">
            <div class="card-header" style="color: #ffffff !important">
                <h3 class="mb-0"><i class="fas fa-user me-2" style="color: #ffffff !important"></i>Información Personal</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Cédula</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['cedula']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Teléfono</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['telefono']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ubicación -->
        <div class="card">
            <div class="card-header" style="color: #ffffff !important">
                <h3 class="mb-0"><i class="fas fa-map-marker-alt me-2" style="color: #ffffff !important"></i>Ubicación</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Comunidad</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['comunidad']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Parroquia</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['parroquia']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Municipio</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['municipio']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Dirección Exacta</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['direccion_exacta']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">UTM Norte</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['utm_norte']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">UTM Este</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['utm_este']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Proyecto de Construcción -->
        <div class="card">
            <div class="card-header" style="color: #FFFEFEFF">
                <h3 class="mb-0"><i class="fas fa-home me-2" style="color: #FFFFFFFF"></i>Proyecto de Construcción</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Código de Obra</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['codigo_obra_nombre'] ?? 'No asignado'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Modelo Constructivo</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['modelo_constructivo_nombre']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Método Constructivo</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['metodo_constructivo_nombre']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Fiscalizador</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['nombre_fiscalizador'] ?? 'No asignado'); ?></div>
                    </div>
                </div>

                <h4 class="section-title" style="color: #000000">Avance Físico</h4>
                <div class="progress-container">
                    <div class="progress-bar complete" style="width: <?php echo htmlspecialchars($data['avance_fisico'] ?? 0); ?>%">
                        <?php echo htmlspecialchars($data['avance_fisico'] ?? 0); ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Acondicionamiento y Fundación -->
        <div class="card">
            <div class="card-header" style="color: #FFFFFFFF">
                <h3 class="mb-0"><i class="fas fa-tools me-2" style="color: #FFFFFFFF"></i>Acondicionamiento y Fundación</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Limpieza</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['limpieza']); ?>" style="width: <?php echo getProgressWidth($data['limpieza']); ?>%">
                                <?php echo getProgressWidth($data['limpieza']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Replanteo</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['replanteo']); ?>" style="width: <?php echo getProgressWidth($data['replanteo']); ?>%">
                                <?php echo getProgressWidth($data['replanteo']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Excavación</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['excavacion']); ?>" style="width: <?php echo getProgressWidth($data['excavacion']); ?>%">
                                <?php echo getProgressWidth($data['excavacion']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Acero en Vigas de Riostra</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['acero_vigas_riostra']); ?>" style="width: <?php echo getProgressWidth($data['acero_vigas_riostra']); ?>%">
                                <?php echo getProgressWidth($data['acero_vigas_riostra']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Encofrado y Colocación de Malla</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['encofrado_malla']); ?>" style="width: <?php echo getProgressWidth($data['encofrado_malla']); ?>%">
                                <?php echo getProgressWidth($data['encofrado_malla']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Instalaciones Eléctricas y Sanitarias</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['instalaciones_electricas_sanitarias']); ?>" style="width: <?php echo getProgressWidth($data['instalaciones_electricas_sanitarias']); ?>%">
                                <?php echo getProgressWidth($data['instalaciones_electricas_sanitarias']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Vaciado de Losa y Colocación de Anclajes</div>
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
            <div class="card-header" style="color: #FFFFFFFF">
                <h3 class="mb-0"><i class="fas fa-cubes me-2" style="color: #FFFFFFFF"></i>Estructura</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Armado de Columnas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['armado_columnas']); ?>" style="width: <?php echo getProgressWidth($data['armado_columnas']); ?>%">
                                <?php echo getProgressWidth($data['armado_columnas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Vaciado de Columnas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['vaciado_columnas']); ?>" style="width: <?php echo getProgressWidth($data['vaciado_columnas']); ?>%">
                                <?php echo getProgressWidth($data['vaciado_columnas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Armado de Vigas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['armado_vigas']); ?>" style="width: <?php echo getProgressWidth($data['armado_vigas']); ?>%">
                                <?php echo getProgressWidth($data['armado_vigas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Vaciado de Vigas</div>
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
            <div class="card-header" style="color: #FFFFFFFF">
                <h3 class="mb-0"><i class="fas fa-building me-2" style="color: #FFFFFFFF"></i>Cerramiento</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Bloqueado</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['bloqueado']); ?>" style="width: <?php echo getProgressWidth($data['bloqueado']); ?>%">
                                <?php echo getProgressWidth($data['bloqueado']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Colocación de Correas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_correas']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_correas']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_correas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Colocación de Techo</div>
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
            <div class="card-header" style="color: #FFFFFFFF">
                <h3 class="mb-0"><i class="fas fa-paint-roller me-2" style="color: #FFFFFFFF"></i>Acabado</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Colocación de Ventanas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_ventanas']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_ventanas']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_ventanas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Colocación de Puertas Principales</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_puertas_principales']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_puertas_principales']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_puertas_principales']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Instalaciones Eléctricas y Sanitarias en Paredes</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['instalaciones_electricas_sanitarias_paredes']); ?>" style="width: <?php echo getProgressWidth($data['instalaciones_electricas_sanitarias_paredes']); ?>%">
                                <?php echo getProgressWidth($data['instalaciones_electricas_sanitarias_paredes']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Frisos</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['frisos']); ?>" style="width: <?php echo getProgressWidth($data['frisos']); ?>%">
                                <?php echo getProgressWidth($data['frisos']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Sobre-piso</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['sobrepiso']); ?>" style="width: <?php echo getProgressWidth($data['sobrepiso']); ?>%">
                                <?php echo getProgressWidth($data['sobrepiso']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Cerámica en Baño</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['ceramica_bano']); ?>" style="width: <?php echo getProgressWidth($data['ceramica_bano']); ?>%">
                                <?php echo getProgressWidth($data['ceramica_bano']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Colocación de Puertas Internas</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_puertas_internas']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_puertas_internas']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_puertas_internas']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Equipos y Accesorios Eléctricos</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['equipos_accesorios_electricos']); ?>" style="width: <?php echo getProgressWidth($data['equipos_accesorios_electricos']); ?>%">
                                <?php echo getProgressWidth($data['equipos_accesorios_electricos']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Equipos y Accesorios Sanitarios</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['equipos_accesorios_sanitarios']); ?>" style="width: <?php echo getProgressWidth($data['equipos_accesorios_sanitarios']); ?>%">
                                <?php echo getProgressWidth($data['equipos_accesorios_sanitarios']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Colocación de Lavaplatos</div>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['colocacion_lavaplatos']); ?>" style="width: <?php echo getProgressWidth($data['colocacion_lavaplatos']); ?>%">
                                <?php echo getProgressWidth($data['colocacion_lavaplatos']); ?>%
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Pintura</div>
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
            <div class="card-header" style="color: #FFFFFFFF">
                <h3 class="mb-0"><i class="fas fa-clipboard-check me-2" style="color: #FFFFFFFF"></i>Estado General</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Fecha Culminación</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['fecha_culminacion'] ?? 'No especificada'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Fecha Protocolización</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['fecha_protocolizacion'] ?? 'No especificada'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label" style="color: #000000">Acta Entregada</div>
                        <div class="info-value">
                            <span class="status-badge <?php echo ($data['acta_entregada'] == 1) ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ($data['acta_entregada'] == 1) ? 'Sí' : 'No'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <h4 class="section-title" style="color: #000000">Observaciones Responsables Control y Seguimiento</h4>
                <div class="alert alert-info">
                    <?php echo htmlspecialchars($data['observaciones_responsables_control'] ?? 'No hay observaciones registradas'); ?>
                </div>
                
                <?php if (!empty($data['observaciones_fiscalizadores'])): ?>
                <h4 class="section-title" style="color: #000000">Observaciones de Fiscalizadores</h4>
                <div class="alert alert-warning">
                    <?php echo htmlspecialchars($data['observaciones_fiscalizadores']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">Avance General</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="avanceChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="estadoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Funciones para el modal de actualización de beneficiarios
    function calcularAvanceFisico() {
        // Lista de todos los campos que afectan el avance físico
        const camposAvance = [
            'limpieza', 'replanteo', 'excavacion', 'fundacion',
            'acero_vigas_riostra', 'encofrado_malla',
            'instalaciones_electricas_sanitarias', 'vaciado_losa_anclajes',
            'estructura', 'armado_columnas', 'vaciado_columnas',
            'armado_vigas', 'vaciado_vigas',
            'cerramiento', 'bloqueado', 'colocacion_correas', 'colocacion_techo',
            'colocacion_ventanas', 'colocacion_puertas_principales',
            'instalaciones_electricas_sanitarias_paredes', 'frisos',
            'sobrepiso', 'ceramica_bano', 'colocacion_puertas_internas',
            'equipos_accesorios_electricos', 'equipos_accesorios_sanitarios',
            'colocacion_lavaplatos', 'pintura'
        ];

        let suma = 0;
        let camposConValor = 0;

        // Sumar todos los valores válidos
        camposAvance.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                const valor = parseFloat(elemento.value) || 0;
                if (valor > 0) {
                    suma += valor;
                    camposConValor++;
                }
            }
        });

        // Calcular el promedio solo si hay campos con valor
        const promedio = camposConValor > 0 ? (suma / camposConValor) : 0;
        
        // Actualizar el campo de avance físico
        const avanceFisicoElement = document.getElementById('avance_fisico');
        if (avanceFisicoElement) {
            avanceFisicoElement.value = promedio.toFixed(2);
        }
    }

    function calcularAcondicionamiento() {
        const campos = [
            'limpieza',
            'replanteo',
            'excavacion',
            'fundacion',
            'acero_vigas_riostra',
            'encofrado_malla',
            'instalaciones_electricas_sanitarias',
            'vaciado_losa_anclajes'
        ];

        let suma = 0;
        
        // Sumar todos los campos, incluyendo los que valen 0
        campos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                const valor = parseFloat(elemento.value) || 0;
                suma += valor;
            }
        });

        // Calcular el promedio dividiendo por el total de campos
        const promedio = suma / campos.length;
        
        // Actualizar el campo de acondicionamiento
        const acondicionamientoElement = document.getElementById('acondicionamiento');
        if (acondicionamientoElement) {
            acondicionamientoElement.value = promedio.toFixed(2);
        }
    }

    function calcularCerramiento() {
        const campos = [
            'bloqueado',
            'colocacion_correas',
            'colocacion_techo'
        ];

        let suma = 0;
        
        // Sumar todos los campos, incluyendo los que valen 0
        campos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                const valor = parseFloat(elemento.value) || 0;
                suma += valor;
            }
        });

        // Calcular el promedio dividiendo por el total de campos
        const promedio = suma / campos.length;
        
        // Actualizar el campo de cerramiento
        const cerramientoElement = document.getElementById('cerramiento');
        if (cerramientoElement) {
            cerramientoElement.value = promedio.toFixed(2);
        }
    }

    function calcularAcabado() {
        const campos = [
            'colocacion_ventanas',
            'colocacion_puertas_principales',
            'instalaciones_electricas_sanitarias_paredes',
            'frisos',
            'sobrepiso',
            'ceramica_bano',
            'colocacion_puertas_internas',
            'equipos_accesorios_electricos',
            'equipos_accesorios_sanitarios',
            'colocacion_lavaplatos',
            'pintura'
        ];
        
        let suma = 0;
        let totalCampos = campos.length;
        
        campos.forEach(campo => {
            const valor = parseFloat(document.getElementById(campo).value) || 0;
            suma += valor;
        });
        
        const promedio = suma / totalCampos;
        const acabadoInput = document.getElementById('acabado');
        acabadoInput.value = promedio.toFixed(2);
        
        // Actualizar el valor en el formulario de actualización
        const acabadoUpdateInput = document.querySelector('#actualizarBeneficiarioForm input[name="acabado"]');
        if (acabadoUpdateInput) {
            acabadoUpdateInput.value = promedio.toFixed(2);
        }
    }

    function calcularEstructura() {
        const campos = [
            'armado_columnas',
            'vaciado_columnas',
            'armado_vigas',
            'vaciado_vigas'
        ];

        let suma = 0;
        
        // Sumar todos los campos, incluyendo los que valen 0
        campos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                const valor = parseFloat(elemento.value) || 0;
                suma += valor;
            }
        });

        // Calcular el promedio dividiendo por el total de campos
        const promedio = suma / campos.length;
        
        // Actualizar el campo de estructura
        const estructuraElement = document.getElementById('estructura');
        if (estructuraElement) {
            estructuraElement.value = promedio.toFixed(2);
        }
    }

    function actualizarBeneficiario() {
        console.log('Iniciando actualización del beneficiario...');
        
        // Obtener el formulario
        const form = document.getElementById('actualizarBeneficiarioForm');
        if (!form) {
            console.error('No se encontró el formulario');
            showToast('danger', 'Error: No se encontró el formulario');
            return;
        }

        // Validar campos requeridos
        const camposRequeridos = ['nombre_beneficiario', 'cedula', 'status', 'codigo_obra'];
        for (const campo of camposRequeridos) {
            const elemento = form.elements[campo];
            if (!elemento || !elemento.value.trim()) {
                console.error(`Campo requerido faltante: ${campo}`);
                showToast('danger', `El campo ${campo.replace('_', ' ')} es requerido`);
                return;
            }
        }

        // Calcular valores antes de enviar
        calcularAvanceFisico();
        calcularAcondicionamiento();
        calcularCerramiento();
        calcularAcabado();
        calcularEstructura();

        // Crear FormData
        const formData = new FormData(form);

        // Debug: Mostrar datos a enviar
        console.log('Datos a enviar:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }

        // Mostrar indicador de carga
        const btnGuardar = document.getElementById('btnGuardarCambios');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            btnGuardar.disabled = true;
        }

        // Realizar la petición AJAX
        fetch('../php/conf/actualizar_beneficiario.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log('Respuesta del servidor:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear respuesta:', text);
                throw new Error('Error en la respuesta del servidor: ' + text);
            }
        })
        .then(data => {
            if (data.success) {
                showToast('success', 'Beneficiario actualizado correctamente');
                // Cerrar el modal
                const modalElement = document.getElementById('actualizarBeneficiarioModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }
                // Recargar la página después de un breve retraso
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                throw new Error(data.error || 'Error al actualizar el beneficiario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('danger', error.message || 'Error al procesar la solicitud');
        })
        .finally(() => {
            // Restaurar el botón
            if (btnGuardar) {
                btnGuardar.innerHTML = '<i class="fas fa-save me-1"></i> Guardar Cambios';
                btnGuardar.disabled = false;
            }
        });
    }

    function showToast(type, message) {
        console.log('Mostrando toast:', type, message);
        
        // Crear el contenedor del toast si no existe
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Crear el toast
        const toast = document.createElement('div');
        toast.className = `toast show align-items-center text-white bg-${type} border-0`;
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
        
        // Agregar el toast al contenedor
        toastContainer.appendChild(toast);
        
        // Remover el toast después de 5 segundos
        setTimeout(() => {
            toast.remove();
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        }, 5000);
    }

    function cargarParroquias(municipioId, parroquiaSeleccionada = null) {
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
                        if (parroquiaSeleccionada && parroquia.id_parroquia == parroquiaSeleccionada) {
                            option.selected = true;
                        }
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
    }

    function cargarComunidades(parroquiaId, comunidadSeleccionada = null) {
        const comunidadSelect = document.getElementById('comunidadSelect');
        
        if (parroquiaId) {
            fetch(`../php/conf/get_comunidades.php?parroquia_id=${parroquiaId}`)
                .then(response => response.json())
                .then(data => {
                    comunidadSelect.innerHTML = '<option value="">Seleccione una comunidad</option>';
                    data.forEach(comunidad => {
                        const option = document.createElement('option');
                        option.value = comunidad.id_comunidad;
                        option.textContent = comunidad.comunidad;
                        if (comunidadSeleccionada && comunidad.id_comunidad == comunidadSeleccionada) {
                            option.selected = true;
                        }
                        comunidadSelect.appendChild(option);
                    });
                    comunidadSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error al cargar comunidades:', error);
                    comunidadSelect.innerHTML = '<option value="">Error al cargar comunidades</option>';
                });
        } else {
            comunidadSelect.innerHTML = '<option value="">Seleccione una comunidad</option>';
            comunidadSelect.disabled = true;
        }
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar evento al botón de guardar
        const btnGuardar = document.getElementById('btnGuardarCambios');
        if (btnGuardar) {
            btnGuardar.addEventListener('click', function(e) {
                e.preventDefault();
                actualizarBeneficiario();
            });
        }

        // Agregar evento al formulario
        const form = document.getElementById('actualizarBeneficiarioForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                actualizarBeneficiario();
            });
        }

        // Event listeners para los selectores de ubicación
        const municipioSelect = document.getElementById('municipioSelect');
        if (municipioSelect) {
            municipioSelect.addEventListener('change', function() {
                const municipioId = this.value;
                cargarParroquias(municipioId);
                // Limpiar comunidades
                const comunidadSelect = document.getElementById('comunidadSelect');
                comunidadSelect.innerHTML = '<option value="">Seleccione una comunidad</option>';
                comunidadSelect.disabled = true;
            });
        }

        const parroquiaSelect = document.getElementById('parroquiaSelect');
        if (parroquiaSelect) {
            parroquiaSelect.addEventListener('change', function() {
                const parroquiaId = this.value;
                cargarComunidades(parroquiaId);
            });
        }

        // Agregar event listeners para el cálculo automático del avance físico
        const camposAvance = [
            'limpieza', 'replanteo', 'excavacion', 'fundacion',
            'acero_vigas_riostra', 'encofrado_malla',
            'instalaciones_electricas_sanitarias', 'vaciado_losa_anclajes',
            'armado_columnas', 'vaciado_columnas',
            'armado_vigas', 'vaciado_vigas', 'estructura',
            'bloqueado', 'colocacion_correas', 'colocacion_techo', 'cerramiento',
            'colocacion_ventanas', 'colocacion_puertas_principales',
            'instalaciones_electricas_sanitarias_paredes', 'frisos',
            'sobrepiso', 'ceramica_bano', 'colocacion_puertas_internas',
            'equipos_accesorios_electricos', 'equipos_accesorios_sanitarios',
            'colocacion_lavaplatos', 'pintura'
        ];

        camposAvance.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('change', calcularAvanceFisico);
                elemento.addEventListener('input', calcularAvanceFisico);
            }
        });

        // Event listeners para el cálculo del acondicionamiento
        const camposAcondicionamiento = [
            'limpieza',
            'replanteo',
            'excavacion',
            'fundacion',
            'acero_vigas_riostra',
            'encofrado_malla',
            'instalaciones_electricas_sanitarias',
            'vaciado_losa_anclajes'
        ];

        camposAcondicionamiento.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('change', calcularAcondicionamiento);
                elemento.addEventListener('input', calcularAcondicionamiento);
            }
        });

        // Event listeners para el cálculo del cerramiento
        const camposCerramiento = [
            'bloqueado',
            'colocacion_correas',
            'colocacion_techo'
        ];

        camposCerramiento.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('change', calcularCerramiento);
                elemento.addEventListener('input', calcularCerramiento);
            }
        });

        // Event listeners para el cálculo del acabado
        const camposAcabado = [
            'colocacion_ventanas',
            'colocacion_puertas_principales',
            'instalaciones_electricas_sanitarias_paredes',
            'frisos',
            'sobrepiso',
            'ceramica_bano',
            'colocacion_puertas_internas',
            'equipos_accesorios_electricos',
            'equipos_accesorios_sanitarios',
            'colocacion_lavaplatos',
            'pintura'
        ];

        camposAcabado.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('change', calcularAcabado);
                elemento.addEventListener('input', calcularAcabado);
            }
        });

        // Event listeners para el cálculo de la estructura
        const camposEstructura = [
            'armado_columnas',
            'vaciado_columnas',
            'armado_vigas',
            'vaciado_vigas'
        ];

        camposEstructura.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('change', calcularEstructura);
                elemento.addEventListener('input', calcularEstructura);
            }
        });

        // Calcular valores iniciales
        calcularAvanceFisico();
        calcularAcondicionamiento();
        calcularCerramiento();
        calcularAcabado();
        calcularEstructura();

        // Datos para el gráfico de avance
        const avanceData = {
            labels: [
                'Acondicionamiento',
                'Estructura',
                'Cerramiento',
                'Acabado'
            ],
            datasets: [{
                label: 'Porcentaje de Avance',
                data: [
                    <?php
                    echo ($data['acondicionamiento'] ?? 0) . ",";
                    echo ($data['estructura'] ?? 0) . ",";
                    echo ($data['cerramiento'] ?? 0) . ",";
                    echo ($data['acabado'] ?? 0);
                    ?>
                ],
                backgroundColor: [
                    'rgba(13, 202, 240, 0.2)',
                    'rgba(40, 167, 69, 0.2)',
                    'rgba(255, 193, 7, 0.2)',
                    'rgba(220, 53, 69, 0.2)'
                ],
                borderColor: [
                    'rgba(13, 202, 240, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        };

        // Datos para el gráfico de estado
        const estadoData = {
            labels: ['Completadas', 'En Progreso', 'No Iniciadas'],
            datasets: [{
                data: [
                    <?php
                    $completadas = 0;
                    $enProgreso = 0;
                    $noIniciadas = 0;
                    $campos = ['acondicionamiento', 'estructura', 'cerramiento', 'acabado'];
                    foreach ($campos as $campo) {
                        $valor = $data[$campo] ?? 0;
                        if ($valor == 100) $completadas++;
                        else if ($valor > 0) $enProgreso++;
                        else $noIniciadas++;
                    }
                    echo "$completadas, $enProgreso, $noIniciadas";
                    ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.2)',
                    'rgba(255, 193, 7, 0.2)',
                    'rgba(220, 53, 69, 0.2)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        };

        // Configuración del gráfico de avance
        new Chart(document.getElementById('avanceChart'), {
            type: 'bar',
            data: avanceData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Avance por Etapa de Construcción',
                        font: {
                            size: 16
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Porcentaje de Avance (%)'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        // Configuración del gráfico de estado
        new Chart(document.getElementById('estadoChart'), {
            type: 'pie',
            data: estadoData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Distribución de Estados',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    });

    function imprimirReporte() {
        // Ocultar elementos que no se deben imprimir
        const printHideElements = document.querySelectorAll('.print-hide');
        printHideElements.forEach(element => {
            element.style.display = 'none';
        });
        
        // Configurar estilos para impresión
        const originalTitle = document.title;
        document.title = 'Reporte Individual - <?php echo htmlspecialchars($data['nombre_beneficiario']); ?>';
        
        // Imprimir
        window.print();
        
        // Restaurar elementos ocultos
        printHideElements.forEach(element => {
            element.style.display = '';
        });
        
        // Restaurar título
        document.title = originalTitle;
    }

    // Event listeners para el cálculo del acabado
    document.addEventListener('DOMContentLoaded', function() {
        const camposAcabado = [
            'colocacion_ventanas',
            'colocacion_puertas_principales',
            'instalaciones_electricas_sanitarias_paredes',
            'frisos',
            'sobrepiso',
            'ceramica_bano',
            'colocacion_puertas_internas',
            'equipos_accesorios_electricos',
            'equipos_accesorios_sanitarios',
            'colocacion_lavaplatos',
            'pintura'
        ];

        camposAcabado.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('change', calcularAcabado);
                elemento.addEventListener('input', calcularAcabado);
            }
        });

        // Calcular valores iniciales
        calcularAvanceFisico();
        calcularAcondicionamiento();
        calcularCerramiento();
        calcularEstructura();
        calcularAcabado();
    });

    function actualizarGraficaAvance() {
        if (avanceChartInstance) {
            const valores = [
                parseFloat(document.getElementById('acondicionamiento').value) || 0,
                parseFloat(document.getElementById('estructura').value) || 0,
                parseFloat(document.getElementById('cerramiento').value) || 0,
                parseFloat(document.getElementById('acabado').value) || 0
            ];
            avanceChartInstance.data.datasets[0].data = valores;
            avanceChartInstance.update();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Cargar datos iniciales
        const data = <?php echo json_encode($data); ?>;
        
        // Actualizar campos con los datos cargados
        Object.keys(data).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.value = data[key] || '';
            }
        });
        
        // Calcular valores iniciales
        calcularAcondicionamiento();
        calcularEstructura();
        calcularCerramiento();
        calcularAcabado();
        calcularAvanceFisico();
        
        // Actualizar gráficas
        actualizarGraficaAvance();
        actualizarGraficaEstado();
    });
    </script>
</body>
</html>

<?php
$stmt->close();
$conexion->close();
?>