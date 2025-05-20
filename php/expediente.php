<?php
session_start();
require_once '../php/conf/conexion.php';

// Verificación robusta de sesión y rol
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'admin';

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
    <title>Expediente del Beneficiario - SIGEVU</title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="..//css/expediente.css">
</head>
<body>
    <!-- Barra de navegación (manteniendo la existente) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
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

    <div class="container" style="margin-top: 80px;">
        <!-- Encabezado institucional -->
        <div class="header-institution text-center">
            <h4>22 CORPOLARA - Corporación de Desarrollo Jacinto Lara</h4>
            <h5>AÑO: 2013</h5>
        </div>

        <!-- Contenedor principal del expediente -->
        <div class="expediente-container">
            <!-- Encabezado del expediente -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Expediente del Beneficiario</h2>
                    <h3 class="mb-0 text-primary"><?php echo htmlspecialchars($data['nombre_beneficiario']); ?></h3>
                </div>
                <div>
                <?php if ($esAdmin): ?>
    <button class="btn btn-primary btn-action no-print" onclick="imprimirExpediente()">
        <i class="fas fa-print me-1"></i> Imprimir
    </button>
<?php endif; ?>
                    
                </div>
            </div>

            <!-- Datos básicos -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="info-label">N° Expediente</div>
                        <div class="info-value"><?php echo !empty($data['id_beneficiario']) ? htmlspecialchars($data['id_beneficiario']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="info-label">Código de obra</div>
                        <div class="info-value"><?php echo !empty($data['codigo_obra']) ? htmlspecialchars($data['codigo_obra']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Información del beneficiario -->
            <h4 class="section-title">Información del Beneficiario</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="info-label">Nombre</div>
                        <div class="info-value"><?php echo !empty($data['nombre_beneficiario']) ? htmlspecialchars($data['nombre_beneficiario']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <div class="info-label">Cédula</div>
                        <div class="info-value"><?php echo !empty($data['cedula']) ? htmlspecialchars($data['cedula']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <div class="info-label">Teléfono</div>
                        <div class="info-value"><?php echo !empty($data['telefono']) ? htmlspecialchars($data['telefono']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Ubicación -->
            <h4 class="section-title">Ubicación</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="info-label">Comunidad</div>
                        <div class="info-value"><?php echo !empty($data['comunidad']) ? htmlspecialchars($data['comunidad']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="info-label">Parroquia</div>
                        <div class="info-value"><?php echo !empty($data['parroquia']) ? htmlspecialchars($data['parroquia']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="info-label">Municipio</div>
                        <div class="info-value"><?php echo !empty($data['municipio']) ? htmlspecialchars($data['municipio']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <div class="info-label">Dirección Exacta</div>
                        <div class="info-value"><?php echo !empty($data['direccion_exacta']) ? htmlspecialchars($data['direccion_exacta']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <div class="info-label">UTM Norte</div>
                        <div class="info-value"><?php echo !empty($data['utm_norte']) ? htmlspecialchars($data['utm_norte']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <div class="info-label">UTM Este</div>
                        <div class="info-value"><?php echo !empty($data['utm_este']) ? htmlspecialchars($data['utm_este']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Datos de construcción -->
            <h4 class="section-title">Datos de Construcción</h4>
            <table class="table-details">
                <tr>
                    <td width="30%"><div class="info-label">Método Constructivo</div></td>
                    <td width="70%"><?php echo !empty($data['metodo_constructivo']) ? htmlspecialchars($data['metodo_constructivo']) : '<span class="ref-badge">¡REF!</span>'; ?></td>
                </tr>
                <tr>
                    <td><div class="info-label">Modelo Constructivo</div></td>
                    <td><?php echo !empty($data['modelo_constructivo']) ? htmlspecialchars($data['modelo_constructivo']) : '<span class="ref-badge">¡REF!</span>'; ?></td>
                </tr>
                <tr>
                    <td><div class="info-label">Proyecto</div></td>
                    <td>IMVI´S</td>
                </tr>
                <tr>
                    <td><div class="info-label">Avance Físico</div></td>
                    <td>
                        <div class="progress-container">
                            <div class="progress-bar <?php echo getProgressClass($data['avance_fisico']); ?>" style="width: <?php echo getProgressWidth($data['avance_fisico']); ?>%">
                                <?php echo getProgressWidth($data['avance_fisico']); ?>%
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><div class="info-label">Estado</div></td>
                    <td>
                        <?php if($data['status'] == 'activo'): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><div class="info-label">Culminado</div></td>
                    <td><?php echo !empty($data['fecha_culminacion']) ? 'Sí (' . htmlspecialchars($data['fecha_culminacion']) . ')' : 'No'; ?></td>
                </tr>
            </table>
            <!-- Observaciones -->
            <h4 class="section-title">Observaciones</h4>
            <div class="alert alert-light border">
                <?php echo !empty($data['observaciones_responsables_control']) ? nl2br(htmlspecialchars($data['observaciones_responsables_control'])) : 'No hay observaciones registradas'; ?>
            </div>

            <!-- Pie de página institucional -->
            <div class="text-center mt-4 pt-3 border-top">
                <h5 class="text-primary">¡Impulsando el Desarrollo!</h5>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para manejar la impresión
        function prepareForPrint() {
            // Aquí puedes agregar lógica adicional para preparar la página antes de imprimir
            console.log("Preparando para imprimir...");
        }

        // Asignar el evento de antes de imprimir
        window.addEventListener('beforeprint', prepareForPrint);

        
        function imprimirExpediente() {
    // Función auxiliar para obtener contenido seguro
    function getSafeContent(selector, defaultValue = 'N/A') {
        const element = document.querySelector(selector);
        return element ? element.innerHTML : defaultValue;
    }

    // Función auxiliar para obtener progreso seguro
    function getSafeProgress(selector, defaultValue = '0%') {
        const container = document.querySelector(selector);
        if (!container) return `<div class="progress-container"><div class="progress-bar low" style="width: ${defaultValue}">${defaultValue}</div></div>`;
        return container.outerHTML;
    }

    // Datos para la impresión
    const expedienteData = {
    titulo: "<?php echo htmlspecialchars($data['nombre_beneficiario']); ?>",
    nExpediente: "<?php echo !empty($data['id_beneficiario']) ? htmlspecialchars($data['id_beneficiario']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    codigoObra: "<?php echo !empty($data['codigo_obra']) ? htmlspecialchars($data['codigo_obra']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    nombre: "<?php echo !empty($data['nombre_beneficiario']) ? htmlspecialchars($data['nombre_beneficiario']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    cedula: "<?php echo !empty($data['cedula']) ? htmlspecialchars($data['cedula']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    telefono: "<?php echo !empty($data['telefono']) ? htmlspecialchars($data['telefono']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    estado: "<?php echo ($data['status'] == 'activo') ? '<span class=\"badge bg-success\">Activo</span>' : '<span class=\"badge bg-secondary\">Inactivo</span>'; ?>",
    comunidad: "<?php echo !empty($data['comunidad']) ? htmlspecialchars($data['comunidad']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    parroquia: "<?php echo !empty($data['parroquia']) ? htmlspecialchars($data['parroquia']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    municipio: "<?php echo !empty($data['municipio']) ? htmlspecialchars($data['municipio']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    direccion: "<?php echo !empty($data['direccion_exacta']) ? htmlspecialchars($data['direccion_exacta']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    utmNorte: "<?php echo !empty($data['utm_norte']) ? htmlspecialchars($data['utm_norte']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    utmEste: "<?php echo !empty($data['utm_este']) ? htmlspecialchars($data['utm_este']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    metodoConstructivo: "<?php echo !empty($data['metodo_constructivo']) ? htmlspecialchars($data['metodo_constructivo']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    modeloConstructivo: "<?php echo !empty($data['modelo_constructivo']) ? htmlspecialchars($data['modelo_constructivo']) : '<span class=\"ref-badge\">¡REF!</span>'; ?>",
    fiscalizador: "<?php echo !empty($data['nombre_fiscalizador']) ? htmlspecialchars($data['nombre_fiscalizador']) : 'No asignado'; ?>",
    avanceFisico: "<?php echo getProgressWidth($data['avance_fisico']); ?>%",
    avanceFisicoClass: "<?php echo getProgressClass($data['avance_fisico']); ?>",
    culminado: "<?php echo !empty($data['fecha_culminacion']) ? 'Sí (' . htmlspecialchars($data['fecha_culminacion']) . ')' : 'No'; ?>",
    observaciones: "<?php echo !empty($data['observaciones_responsables_control']) ? addslashes(nl2br(htmlspecialchars($data['observaciones_responsables_control']))) : 'No hay observaciones registradas'; ?>"
};


    // Crear ventana de impresión
    const ventanaImpresion = window.open('', '_blank');
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Expediente del Beneficiario - SIGEVU</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @page {
                    size: A4 portrait;
                    margin: 5mm;
                }
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 10px;
                    line-height: 1.2;
                    padding: 5mm;
                    margin: 0;
                    background-color: white;
                }
                .print-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 5px;
                }
                .print-table th {
                    background-color: #f8f9fa;
                    text-align: left;
                    padding: 3px 5px;
                    border: 1px solid #ddd;
                    font-weight: bold;
                }
                .print-table td {
                    padding: 3px 5px;
                    border: 1px solid #ddd;
                    vertical-align: top;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 10px;
                }
                .print-title {
                    font-size: 14px;
                    font-weight: bold;
                    margin: 5px 0;
                }
                .print-subtitle {
                    font-size: 12px;
                    margin: 3px 0 8px;
                }
                .progress-container {
                    width: 100%;
                    height: 12px;
                    background-color: #e9ecef;
                    border-radius: 2px;
                    margin-top: 2px;
                }
                .progress-bar {
                    height: 100%;
                    font-size: 8px;
                    line-height: 12px;
                    text-align: center;
                    color: white;
                }
                .complete {
                    background-color: #4CAF50;
                }
                .medium {
                    background-color: #FFC107;
                }
                .low {
                    background-color: #E53935;
                }
                .section-title {
                    background-color: #f0f0f0;
                    font-weight: bold;
                    padding: 3px 5px;
                    margin: 8px 0 5px;
                    font-size: 11px;
                }
                .badge {
                    font-size: 10px;
                    padding: 2px 5px;
                }
                .ref-badge {
                    background-color: #f8d7da;
                    color: #721c24;
                    padding: 2px 5px;
                    border-radius: 3px;
                    font-size: 9px;
                }
                @media print {
                    body {
                        zoom: 85%;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <div class="print-title">22 CORPOLARA - Corporación de Desarrollo Jacinto Lara</div>
                <div class="print-subtitle">AÑO: 2013 | Expediente del Beneficiario: ${expedienteData.titulo}</div>
            </div>

            <table class="print-table">
                <tr>
                    <th colspan="4" class="section-title">INFORMACIÓN BÁSICA</th>
                </tr>
                <tr>
                    <td width="15%"><strong>N° Expediente</strong></td>
                    <td width="35%">${expedienteData.nExpediente}</td>
                    <td width="15%"><strong>Código Obra</strong></td>
                    <td width="35%">${expedienteData.codigoObra}</td>
                </tr>
                <tr>
                    <td><strong>Nombre</strong></td>
                    <td>${expedienteData.nombre}</td>
                    <td><strong>Cédula</strong></td>
                    <td>${expedienteData.cedula}</td>
                </tr>
                <tr>
                    <td><strong>Teléfono</strong></td>
                    <td>${expedienteData.telefono}</td>
                    <td><strong>Estado</strong></td>
                    <td>${expedienteData.estado}</td>
                </tr>
            </table>

            <table class="print-table">
                <tr>
                    <th colspan="4" class="section-title">UBICACIÓN</th>
                </tr>
                <tr>
                    <td width="15%"><strong>Comunidad</strong></td>
                    <td width="35%">${expedienteData.comunidad}</td>
                    <td width="15%"><strong>Parroquia</strong></td>
                    <td width="35%">${expedienteData.parroquia}</td>
                </tr>
                <tr>
                    <td><strong>Municipio</strong></td>
                    <td>${expedienteData.municipio}</td>
                    <td><strong>Dirección</strong></td>
                    <td>${expedienteData.direccion}</td>
                </tr>
                <tr>
                    <td><strong>UTM Norte</strong></td>
                    <td>${expedienteData.utmNorte}</td>
                    <td><strong>UTM Este</strong></td>
                    <td>${expedienteData.utmEste}</td>
                </tr>
            </table>

            <table class="print-table">
                <tr>
                    <th colspan="4" class="section-title">DATOS DE CONSTRUCCIÓN</th>
                </tr>
                <tr>
                    <td width="15%"><strong>Método Constructivo</strong></td>
                    <td width="35%">${expedienteData.metodoConstructivo}</td>
                    <td width="15%"><strong>Modelo Constructivo</strong></td>
                    <td width="35%">${expedienteData.modeloConstructivo}</td>
                </tr>
                <tr>
                    <td><strong>Proyecto</strong></td>
                    <td>IMVI´S</td>
                    <td><strong>Fiscalizador</strong></td>
                    <td>${expedienteData.fiscalizador}</td>
                </tr>
                <tr>
                    <td><strong>Avance Físico</strong></td>
                    <td colspan="3">${expedienteData.avanceFisico}</td>
                </tr>
                <tr>
                    <td><strong>Culminado</strong></td>
                    <td colspan="3">${expedienteData.culminado}</td>
                </tr>
            </table>
            <table class="print-table">
                <tr>
                    <th class="section-title">OBSERVACIONES</th>
                </tr>
                <tr>
                    <td>${expedienteData.observaciones}</td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 10px; font-size: 9px;">
                Documento generado el ${new Date().toLocaleDateString()} - ¡Impulsando el Desarrollo!
            </div>
        </body>
        </html>
    `);
    
    ventanaImpresion.document.close();
    
    // Esperar a que los estilos se carguen antes de imprimir
    setTimeout(() => {
        ventanaImpresion.print();
        ventanaImpresion.close();
    }, 500);
}

    </script>
</body>
</html>
<?php
// Cerrar conexiones de base de datos si las hay
if (isset($conexion)) {
    $conexion->close();
}
?>
