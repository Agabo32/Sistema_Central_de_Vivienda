<?php
session_start();
require_once '../php/conf/conexion.php';

// Verificación robusta de sesión y rol
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'admin';

if (!isset($_GET['id'])) {
    echo "ID de beneficiario no especificado.";
    exit;
}

$id = intval($_GET['id']); // Asegurarse de que el ID es un número entero

// Consulta SQL corregida con nombres de tablas y campos actualizados
$sql = "
SELECT
    b.id_beneficiario, 
    b.cedula, 
    b.nombre_beneficiario, 
    b.telefono, 
    b.fecha_actualizacion,
    b.status,
    co.cod_obra AS codigo_obra,
    u.comunidad, 
    u.direccion_exacta, 
    u.utm_norte, 
    u.utm_este,
    m.id_municipio,
    m.municipio AS municipio,  
    p.id_parroquia,
    p.parroquia AS parroquia,
    e.estado AS estado,
    mc.nomb_metodo AS metodo_constructivo, 
    mo.nomb_modelo AS modelo_constructivo,
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
FROM beneficiarios b
LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
LEFT JOIN municipios m ON u.id_municipio = m.id_municipio
LEFT JOIN parroquias p ON u.id_parroquia = p.id_parroquia
LEFT JOIN estados e ON m.id_estado = e.id_estado
LEFT JOIN metodos_constructivos mc ON b.id_metodo_constructivo = mc.id_metodo
LEFT JOIN modelos_constructivos mo ON b.id_modelo_constructivo = mo.id_modelo
LEFT JOIN cod_obra co ON b.id_cod_obra = co.id_cod_obra
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
        if ($percentage >= 75) return 'high';
        if ($percentage >= 50) return 'medium';
        if ($percentage > 0) return 'low';
        return 'none';
    }
    
    switch(strtolower($value)) {
        case 'completo': return 'complete';
        case 'avanzado': return 'high';
        case 'en progreso': return 'medium';
        case 'pendiente': return 'low';
        case 'no iniciado': return 'none';
        default: return 'none';
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-red: #e30016;
            --primary-dark: #1b1918;
            --light-gray: #f8f9fa;
            --medium-gray: #6c757d;
            --border-color: #dee2e6;
            --shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow-lg: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--primary-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .expediente-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Header Institucional */
        .header-institution {
            background: linear-gradient(135deg, var(--primary-red) 0%, #c8001a 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .header-institution::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }

        .header-institution .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }

        .header-institution .logo-container img {
            height: 60px;
            margin-right: 1rem;
            filter: brightness(0) invert(1);
        }

        .header-institution h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }

        .header-institution h2 {
            font-size: 1.2rem;
            font-weight: 400;
            margin: 0.5rem 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        /* Contenedor Principal */
        .main-content {
            background: white;
            border-radius: 0 0 20px 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* Header del Expediente */
        .expediente-header {
            background: var(--primary-dark);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .expediente-header h3 {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        .expediente-header h4 {
            font-size: 1.1rem;
            font-weight: 400;
            margin: 0.5rem 0 0;
            opacity: 0.8;
        }

        .btn-print {
            background: var(--primary-red);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .btn-print:hover {
            background: #c8001a;
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(227, 0, 22, 0.3);
            color: white;
        }

        /* Cards de Información */
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .section-title {
            color: var(--primary-red);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--primary-red);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            font-size: 1.2rem;
        }

        /* Información Básica */
        .basic-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid var(--primary-red);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .info-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--medium-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--primary-dark);
            word-break: break-word;
        }

        /* Tabla de Detalles */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .details-table th {
            background: var(--primary-dark);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .details-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            background: white;
            transition: background-color 0.3s ease;
        }

        .details-table tr:hover td {
            background: var(--light-gray);
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        /* Barras de Progreso */
        .progress-container {
            background: #e9ecef;
            border-radius: 50px;
            height: 25px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .progress-bar.complete {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .progress-bar.high {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
        }

        .progress-bar.medium {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .progress-bar.low {
            background: linear-gradient(135deg, #dc3545, var(--primary-red));
        }

        .progress-bar.none {
            background: linear-gradient(135deg, #6c757d, #495057);
        }

        /* Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-active {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .badge-inactive {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .ref-badge {
            background: linear-gradient(135deg, var(--primary-red), #c8001a);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Observaciones */
        .observations-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 1rem;
            border-left: 5px solid var(--primary-red);
        }

        /* Footer */
        .footer-institution {
            background: var(--primary-dark);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
        }

        .footer-institution h5 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }

        /* Estilos de Impresión */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 15mm;
            }

            body {
                background: white !important;
                font-size: 11px !important;
                line-height: 1.4 !important;
                color: #333 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .expediente-container {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Ocultar elementos no necesarios para impresión */
            .no-print,
            .btn-print,
            .navbar,
            .fixed-top {
                display: none !important;
            }

            /* Header institucional */
            .header-institution {
                background: linear-gradient(135deg, var(--primary-red) 0%, #c8001a 100%) !important;
                color: white !important;
                border-radius: 0 !important;
                page-break-inside: avoid !important;
                margin-bottom: 0 !important;
                padding: 15mm !important;
            }

            .header-institution::before {
                display: none !important;
            }

            .header-institution .logo-container img {
                height: 40px !important;
                filter: brightness(0) invert(1) !important;
            }

            .header-institution h1 {
                font-size: 24px !important;
                margin: 5px 0 !important;
            }

            .header-institution h2 {
                font-size: 14px !important;
                margin: 0 !important;
            }

            /* Contenido principal */
            .main-content {
                border-radius: 0 !important;
                box-shadow: none !important;
                background: white !important;
            }

            .expediente-header {
                background: var(--primary-dark) !important;
                color: white !important;
                page-break-inside: avoid !important;
                padding: 15px !important;
                margin-bottom: 0 !important;
            }

            .expediente-header h3 {
                font-size: 20px !important;
                margin: 0 !important;
            }

            .expediente-header h4 {
                font-size: 14px !important;
                margin: 5px 0 0 !important;
            }

            /* Cards de información */
            .info-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid !important;
                margin-bottom: 15px !important;
                padding: 15px !important;
                background: white !important;
            }

            .section-title {
                color: var(--primary-red) !important;
                page-break-after: avoid !important;
                font-size: 16px !important;
                margin-bottom: 10px !important;
                border-bottom: 2px solid var(--primary-red) !important;
                padding-bottom: 5px !important;
            }

            .section-title i {
                color: var(--primary-red) !important;
            }

            /* Grid de información básica */
            .basic-info {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
                margin-bottom: 15px !important;
            }

            .info-item {
                background: #f8f9fa !important;
                border: 1px solid #dee2e6 !important;
                border-left: 3px solid var(--primary-red) !important;
                padding: 10px !important;
                border-radius: 5px !important;
                page-break-inside: avoid !important;
            }

            .info-item:hover {
                transform: none !important;
            }

            .info-label {
                font-size: 9px !important;
                font-weight: 600 !important;
                color: #666 !important;
                text-transform: uppercase !important;
                margin-bottom: 3px !important;
            }

            .info-value {
                font-size: 11px !important;
                font-weight: 500 !important;
                color: #333 !important;
            }

            /* Tabla de detalles */
            .details-table {
                box-shadow: none !important;
                page-break-inside: avoid !important;
                border: 1px solid #ddd !important;
                margin-top: 10px !important;
            }

            .details-table th {
                background: var(--primary-dark) !important;
                color: white !important;
                padding: 8px !important;
                font-size: 10px !important;
                border: 1px solid #ddd !important;
            }

            .details-table td {
                padding: 8px !important;
                border: 1px solid #ddd !important;
                font-size: 10px !important;
                background: white !important;
            }

            .details-table tr:hover td {
                background: white !important;
            }

            /* Barras de progreso */
            .progress-container {
                background: #e9ecef !important;
                border: 1px solid #ddd !important;
                height: 20px !important;
                border-radius: 10px !important;
            }

            .progress-bar {
                color: white !important;
                font-weight: 600 !important;
                font-size: 10px !important;
                line-height: 20px !important;
            }

            .progress-bar::before {
                display: none !important;
            }

            .progress-bar.complete {
                background: #28a745 !important;
            }

            .progress-bar.high {
                background: #17a2b8 !important;
            }

            .progress-bar.medium {
                background: #ffc107 !important;
                color: #333 !important;
            }

            .progress-bar.low {
                background: #dc3545 !important;
            }

            .progress-bar.none {
                background: #6c757d !important;
            }

            /* Badges */
            .status-badge {
                padding: 3px 8px !important;
                border-radius: 12px !important;
                font-size: 9px !important;
                font-weight: 600 !important;
            }

            .badge-active {
                background: #28a745 !important;
                color: white !important;
            }

            .badge-inactive {
                background: #6c757d !important;
                color: white !important;
            }

            .ref-badge {
                background: var(--primary-red) !important;
                color: white !important;
                padding: 2px 6px !important;
                border-radius: 8px !important;
                font-size: 8px !important;
                font-weight: 600 !important;
            }

            /* Observaciones */
            .observations-card {
                background: #f8f9fa !important;
                border: 1px solid #dee2e6 !important;
                border-left: 4px solid var(--primary-red) !important;
                padding: 12px !important;
                border-radius: 5px !important;
                page-break-inside: avoid !important;
            }

            /* Footer */
            .footer-institution {
                background: var(--primary-dark) !important;
                color: white !important;
                page-break-inside: avoid !important;
                margin-top: 20px !important;
                padding: 15px !important;
                border-radius: 8px !important;
            }

            .footer-institution h5 {
                font-size: 14px !important;
                margin: 0 !important;
            }

            /* Ajustes específicos para impresión */
            .printing .expediente-container {
                transform: scale(0.95);
                transform-origin: top left;
            }

            /* Evitar saltos de página en elementos importantes */
            .info-card,
            .basic-info,
            .details-table,
            .observations-card {
                page-break-inside: avoid !important;
            }

            /* Asegurar que los colores se impriman */
            .header-institution,
            .expediente-header,
            .section-title,
            .progress-bar,
            .status-badge,
            .ref-badge,
            .footer-institution {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        /* Estilos adicionales para mejorar la impresión */
        @media print and (max-width: 210mm) {
            .basic-info {
                grid-template-columns: 1fr !important;
            }
            
            .info-item {
                margin-bottom: 8px !important;
            }
        }
    </style>
</head>
<body>
    <div class="expediente-container">
        <!-- Header Institucional -->
        <div class="header-institution">
            <div class="logo-container">
                <img src="../imagenes/logo_menu.png.ico" alt="SIGEVU Logo">
                <div>
                    <h1>CORPOLARA</h1>
                    <h2>Corporación de Desarrollo Jacinto Lara</h2>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="main-content">
            <!-- Header del Expediente -->
            <div class="expediente-header">
                <div>
                    <h3>Expediente del Beneficiario</h3>
                    <h4><?php echo htmlspecialchars($data['nombre_beneficiario']); ?></h4>
                </div>
                <div>
                    <?php if ($esAdmin): ?>
                        <button class="btn btn-print no-print" onclick="imprimirExpediente()">
                            <i class="fas fa-print me-2"></i>Imprimir Expediente
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información Básica -->
            <div class="info-card">
                <h4 class="section-title">
                    <i class="fas fa-id-card"></i>
                    Información Básica
                </h4>
                <div class="basic-info">
                    <div class="info-item">
                        <div class="info-label">N° Expediente</div>
                        <div class="info-value"><?php echo !empty($data['id_beneficiario']) ? htmlspecialchars($data['id_beneficiario']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Código de Obra</div>
                        <div class="info-value"><?php echo !empty($data['codigo_obra']) ? htmlspecialchars($data['codigo_obra']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Nombre Completo</div>
                        <div class="info-value"><?php echo !empty($data['nombre_beneficiario']) ? htmlspecialchars($data['nombre_beneficiario']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Cédula</div>
                        <div class="info-value"><?php echo !empty($data['cedula']) ? htmlspecialchars($data['cedula']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Teléfono</div>
                        <div class="info-value"><?php echo !empty($data['telefono']) ? htmlspecialchars($data['telefono']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Estado</div>
                        <div class="info-value">
                            <?php if($data['status'] == 'activo'): ?>
                                <span class="status-badge badge-active">Activo</span>
                            <?php else: ?>
                                <span class="status-badge badge-inactive">Inactivo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ubicación -->
            <div class="info-card">
                <h4 class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Ubicación
                </h4>
                <div class="basic-info">
                    <div class="info-item">
                        <div class="info-label">Estado</div>
                        <div class="info-value"><?php echo !empty($data['estado']) ? htmlspecialchars($data['estado']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Municipio</div>
                        <div class="info-value"><?php echo !empty($data['municipio']) ? htmlspecialchars($data['municipio']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Parroquia</div>
                        <div class="info-value"><?php echo !empty($data['parroquia']) ? htmlspecialchars($data['parroquia']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Comunidad</div>
                        <div class="info-value"><?php echo !empty($data['comunidad']) ? htmlspecialchars($data['comunidad']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">UTM Norte</div>
                        <div class="info-value"><?php echo !empty($data['utm_norte']) ? htmlspecialchars($data['utm_norte']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">UTM Este</div>
                        <div class="info-value"><?php echo !empty($data['utm_este']) ? htmlspecialchars($data['utm_este']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
                <div style="margin-top: 1.5rem;">
                    <div class="info-item">
                        <div class="info-label">Dirección Exacta</div>
                        <div class="info-value"><?php echo !empty($data['direccion_exacta']) ? htmlspecialchars($data['direccion_exacta']) : '<span class="ref-badge">¡REF!</span>'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Datos de Construcción -->
            <div class="info-card">
                <h4 class="section-title">
                    <i class="fas fa-hammer"></i>
                    Datos de Construcción
                </h4>
                <table class="details-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Concepto</th>
                            <th style="width: 70%;">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Método Constructivo</strong></td>
                            <td><?php echo !empty($data['metodo_constructivo']) ? htmlspecialchars($data['metodo_constructivo']) : '<span class="ref-badge">¡REF!</span>'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Modelo Constructivo</strong></td>
                            <td><?php echo !empty($data['modelo_constructivo']) ? htmlspecialchars($data['modelo_constructivo']) : '<span class="ref-badge">¡REF!</span>'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Proyecto</strong></td>
                            <td>IMVI´S</td>
                        </tr>
                        <tr>
                            <td><strong>Avance Físico</strong></td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-bar <?php echo getProgressClass($data['avance_fisico']); ?>" style="width: <?php echo getProgressWidth($data['avance_fisico']); ?>%">
                                        <?php echo getProgressWidth($data['avance_fisico']); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Fecha de Culminación</strong></td>
                            <td><?php echo !empty($data['fecha_culminacion']) ? 'Sí (' . htmlspecialchars($data['fecha_culminacion']) . ')' : 'No culminado'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Acta Entregada</strong></td>
                            <td>
                                <?php if($data['acta_entregada'] == 1): ?>
                                    <span class="status-badge badge-active">Sí</span>
                                <?php else: ?>
                                    <span class="status-badge badge-inactive">No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Observaciones -->
            <div class="info-card">
                <h4 class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Observaciones
                </h4>
                <div class="observations-card">
                    <?php echo !empty($data['observaciones_responsables_control']) ? nl2br(htmlspecialchars($data['observaciones_responsables_control'])) : 'No hay observaciones registradas para este beneficiario.'; ?>
                </div>
                
                <?php if (!empty($data['observaciones_fiscalizadores'])): ?>
                <div style="margin-top: 1rem;">
                    <h5 style="color: var(--primary-red); margin-bottom: 1rem;">
                        <i class="fas fa-eye me-2"></i>Observaciones de Fiscalizadores
                    </h5>
                    <div class="observations-card">
                        <?php echo nl2br(htmlspecialchars($data['observaciones_fiscalizadores'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Institucional -->
        <div class="footer-institution">
            <h5><i class="fas fa-rocket me-2"></i>¡Impulsando el Desarrollo!</h5>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function imprimirExpediente() {
            // Ocultar elementos que no deben imprimirse
            const elementsToHide = document.querySelectorAll('.no-print, .btn-print');
            elementsToHide.forEach(element => {
                element.style.display = 'none';
            });
            
            // Agregar clase para impresión al body
            document.body.classList.add('printing');
            
            // Configurar el título de la página para la impresión
            const originalTitle = document.title;
            document.title = `Expediente - ${document.querySelector('.expediente-header h4').textContent}`;
            
            // Imprimir
            window.print();
            
            // Restaurar elementos después de la impresión
            setTimeout(() => {
                elementsToHide.forEach(element => {
                    element.style.display = '';
                });
                document.body.classList.remove('printing');
                document.title = originalTitle;
            }, 100);
        }

        // Detectar cuando se cancela la impresión
        window.addEventListener('afterprint', function() {
            const elementsToShow = document.querySelectorAll('.no-print, .btn-print');
            elementsToShow.forEach(element => {
                element.style.display = '';
            });
            document.body.classList.remove('printing');
        });
    </script>
</body>
</html>
<?php
// Cerrar conexiones de base de datos si las hay
if (isset($conexion)) {
    $conexion->close();
}
?>
