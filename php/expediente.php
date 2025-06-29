<?php
session_start();
require_once '../php/conf/conexion.php';

// Verificación robusta de sesión y rol
$esAdmin = isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'root';

if (!isset($_GET['id'])) {
    echo "ID de beneficiario no especificado.";
    exit;
}

$id = intval($_GET['id']); // Asegurarse de que el ID es un número entero

// Consulta SQL actualizada para coincidir con la estructura de la base de datos
$sql = "SELECT 
    b.id_beneficiario,
    b.cedula,
    b.nombre_beneficiario,
    b.telefono,
    b.status,
    b.cod_obra,
    b.metodo_constructivo,
    b.modelo_constructivo,
    b.fiscalizador,
    u.direccion_exacta,
    u.utm_norte,
    u.utm_este,
    p.parroquia as nombre_parroquia,
    m.municipio as nombre_municipio,
    c.comunidad as nombre_comunidad,
    mc.metodo as nombre_metodo,
    mo.modelo as nombre_modelo,
    co.cod_obra as codigo_obra_nombre,
    f.Fiscalizador as nombre_fiscalizador,
    dc.avance_fisico,
    dc.fecha_culminacion,
    dc.fecha_protocolizacion,
    dc.observaciones_responsables_control,
    dc.observaciones_fiscalizadores
FROM beneficiarios b
LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
LEFT JOIN comunidades c ON u.comunidad = c.id_comunidad
LEFT JOIN parroquias p ON u.parroquia = p.id_parroquia
LEFT JOIN municipios m ON p.id_municipio = m.id_municipio
LEFT JOIN cod_obra co ON b.cod_obra = co.id_cod_obra
LEFT JOIN metodos_constructivos mc ON b.metodo_constructivo = mc.id_metodo
LEFT JOIN modelos_constructivos mo ON b.modelo_constructivo = mo.id_modelo
LEFT JOIN fiscalizadores f ON b.fiscalizador = f.id_fiscalizador
LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
WHERE b.id_beneficiario = ?";

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente - SIGEVU</title>
    <link rel="icon" type="image/x-icon" href="../imagenes/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/expediente.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 1rem 0;
            font-family: Arial, sans-serif;
        }
        
        .header-card {
            background-color: #28a745;
            color: white;
            padding: 0.75rem;
        }
        
        .logo-badge {
            background-color: white;
            color: #28a745;
            padding: 0.3rem 0.6rem;
            border-radius: 0.25rem;
            font-weight: bold;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .year-badge {
            background-color: white;
            color: black;
            padding: 0.3rem 0.6rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .year-badge:hover {
            background-color: #f8f9fa;
        }
        
        .year-input {
            background: transparent;
            border: none;
            text-align: center;
            font-weight: bold;
            width: 60px;
            color: black;
        }
        
        .year-input:focus {
            outline: 1px solid #667eea;
            background-color: white;
        }
        
        .content-card {
            background-color: #f0f9ff;
            padding: 1rem;
        }
        
        .field-label {
            background-color: #bbf7d0;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.7rem;
            text-align: center;
            display: block;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .form-input {
            text-align: center;
            background-color: white;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            padding: 0.25rem;
            height: auto;
            min-height: 32px;
        }
        
        .form-input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.1rem rgba(40, 167, 69, 0.25);
        }
        
        .form-input.readonly {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .form-input.modified {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        
        .footer-section {
            background-color: white;
            padding: 0.5rem;
            border-radius: 0.25rem;
            margin-top: 1rem;
            text-align: center;
        }
        
        .venezuela-flag {
            display: inline-flex;
            margin-left: 0.5rem;
        }
        
        .flag-stripe {
            width: 16px;
            height: 12px;
        }
        
        .yellow { background-color: #fbbf24; }
        .blue { background-color: #2563eb; }
        .red { background-color: #dc2626; }
        
        .btn-save {
            background-color: #28a745;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
            font-size: 0.8rem;
        }
        
        .btn-save:hover {
            background-color: #218838;
            color: white;
        }
        
        .btn-print {
            background-color: #17a2b8;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }
        
        .btn-print:hover {
            background-color: #138496;
            color: white;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.25rem;
        }
        
        .status-active { background-color: #28a745; }
        .status-inactive { background-color: #dc3545; }
        
        .compact-row {
            margin-bottom: 0.5rem;
        }
        
        .compact-col {
            margin-bottom: 0.25rem;
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
        
        /* Clases específicas para diferentes anchos de columnas */
        .col-1-5 { flex: 0 0 12.5%; max-width: 12.5%; }
        .col-2-5 { flex: 0 0 20%; max-width: 20%; }
        .col-3-5 { flex: 0 0 30%; max-width: 30%; }
        .col-4-5 { flex: 0 0 40%; max-width: 40%; }
        
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Estilos específicos para impresión - Una sola hoja */
        @media print {
            @page {
                size: A4;
                margin: 0.5cm;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            body {
                background: white !important;
                font-size: 10px !important;
                line-height: 1.2 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .header-card {
                padding: 0.5cm !important;
                margin-bottom: 0.3cm !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                background-color: #28a745 !important;
                border-bottom: 1px solid #000 !important;
            }
            
            .header-card h1 {
                font-size: 14px !important;
                margin: 0 !important;
                color: white !important;
                font-weight: bold !important;
            }
            
            .header-card p {
                font-size: 10px !important;
                margin: 0 !important;
                color: white !important;
            }
            
            .logo-badge {
                padding: 0.2cm !important;
                font-size: 10px !important;
                margin-right: 0.3cm !important;
                background-color: white !important;
                color: #28a745 !important;
                border: 1px solid #28a745 !important;
                border-radius: 2px !important;
            }
            
            .year-badge {
                padding: 0.2cm !important;
                font-size: 9px !important;
                background-color: white !important;
                color: black !important;
                border: 1px solid #000 !important;
                border-radius: 2px !important;
            }
            
            .year-badge .small {
                font-size: 8px !important;
                font-weight: bold !important;
            }
            
            .year-input {
                font-size: 9px !important;
                background: transparent !important;
                border: none !important;
                font-weight: bold !important;
            }
            
            .content-card {
                background: white !important;
                padding: 0.3cm !important;
                margin: 0 !important;
            }
            
            .field-label {
                padding: 0.1cm 0.2cm !important;
                font-size: 8px !important;
                margin-bottom: 0.1cm !important;
                background-color: #bbf7d0 !important;
                border: 0.5px solid #22c55e !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                font-weight: bold !important;
                text-align: center !important;
            }
            
            .form-input, .form-control {
                font-size: 9px !important;
                padding: 0.1cm !important;
                border: 0.5px solid #000 !important;
                background: white !important;
                height: auto !important;
                min-height: 0.5cm !important;
                line-height: 1.2 !important;
                text-align: center !important;
            }
            
            textarea.form-input {
                min-height: 1cm !important;
                resize: none !important;
                text-align: left !important;
                padding: 0.1cm !important;
            }
            
            .compact-row {
                margin-bottom: 0.2cm !important;
                display: flex !important;
                flex-wrap: wrap !important;
            }
            
            .compact-col {
                margin-bottom: 0.1cm !important;
                padding-left: 0.1cm !important;
                padding-right: 0.1cm !important;
            }
            
            .row {
                margin: 0 !important;
            }
            
            /* Anchos específicos para impresión optimizados */
            .col-1-5 { 
                flex: 0 0 12.5% !important; 
                max-width: 12.5% !important; 
            }
            .col-2-5 { 
                flex: 0 0 20% !important; 
                max-width: 20% !important; 
            }
            .col-3-5 { 
                flex: 0 0 30% !important; 
                max-width: 30% !important; 
            }
            .col-4-5 { 
                flex: 0 0 40% !important; 
                max-width: 40% !important; 
            }
            
            .col-md-2 { 
                flex: 0 0 16.666667% !important; 
                max-width: 16.666667% !important; 
            }
            .col-md-3 { 
                flex: 0 0 25% !important; 
                max-width: 25% !important; 
            }
            .col-md-4 { 
                flex: 0 0 33.333333% !important; 
                max-width: 33.333333% !important; 
            }
            .col-md-6 { 
                flex: 0 0 50% !important; 
                max-width: 50% !important; 
            }
            .col-md-8 { 
                flex: 0 0 66.666667% !important; 
                max-width: 66.666667% !important; 
            }
            .col-12 { 
                flex: 0 0 100% !important; 
                max-width: 100% !important; 
            }
            
            .footer-section {
                padding: 0.2cm !important;
                margin-top: 0.3cm !important;
                font-size: 9px !important;
                background: white !important;
                text-align: center !important;
                border-top: 1px solid #000 !important;
            }
            
            .footer-section .fw-bold {
                font-size: 10px !important;
                margin: 0 !important;
                font-weight: bold !important;
            }
            
            .flag-stripe {
                width: 1cm !important;
                height: 0.6cm !important;
            }
            
            .venezuela-flag {
                margin-left: 0.3cm !important;
            }
            
            .status-indicator {
                width: 0.5cm !important;
                height: 0.5cm !important;
                margin-right: 0.2cm !important;
            }
            
            /* Optimización del contenedor */
            .expediente-container {
                transform: scale(1) !important;
                transform-origin: top left !important;
                width: 100% !important;
            }
            
            /* Asegurar que los textos largos se ajusten */
            .form-input[readonly] {
                background-color: #f9f9f9 !important;
            }
            
            /* Ajustes para espaciado */
            .d-flex {
                margin-bottom: 0.2cm !important;
            }
            
            /* Mejorar la legibilidad */
            input[type="text"], input[type="number"], input[type="date"], textarea {
                font-family: Arial, sans-serif !important;
                font-weight: normal !important;
            }
            
            /* Evitar cortes de página */
            .card-body, .content-card {
                page-break-inside: avoid !important;
            }
            
            /* Espaciado final */
            .compact-row:last-child {
                margin-bottom: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="container expediente-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg">
                    <!-- Header -->
                    <div class="header-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="logo-badge me-2">
                                    <i class="fas fa-home"></i>
                                    <span>SIGEVU</span>
                                </div>
                                <div>
                                    <h1 class="h6 mb-0">Sistema Integral de Gestión de Viviendas</h1>
                                    <p class="small mb-0">Expediente de Beneficiario</p>
                                </div>
                            </div>
                            <div class="year-badge">
                                <div class="small fw-bold">AÑO</div>
                                <input type="number" class="year-input" id="yearInput" value="2024">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="content-card">
                        <!-- Botones de Acción -->
                        <div class="d-flex justify-content-end mb-2 no-print">
                            <?php if ($esAdmin): ?>
                                <button type="button" class="btn btn-save" onclick="guardarCambios()">
                                    <i class="fas fa-save me-1"></i>Guardar
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-print" onclick="imprimirExpediente()">
                                <i class="fas fa-print me-1"></i>Imprimir
                            </button>
                        </div>
                        
                        <form id="sigevu-form">
                            <input type="hidden" id="id_beneficiario" value="<?php echo $data['id_beneficiario']; ?>">
                            
                            <!-- Fila 1: Datos básicos de identificación -->
                            <div class="row compact-row">
                                <div class="col-2-5 compact-col">
                                    <label class="field-label">N° Expediente</label>
                                    <input type="text" class="form-control form-input" id="expediente" 
                                           value="<?php echo !empty($data['id_beneficiario']) ? str_pad($data['id_beneficiario'], 6, '0', STR_PAD_LEFT) : ''; ?>"
                                           placeholder="<?php echo empty($data['id_beneficiario']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-2-5 compact-col">
                                    <label class="field-label">Código de obra</label>
                                    <input type="text" class="form-control form-input" id="codigoObra" 
                                           value="<?php echo !empty($data['codigo_obra_nombre']) ? htmlspecialchars($data['codigo_obra_nombre']) : ''; ?>"
                                           placeholder="<?php echo empty($data['codigo_obra_nombre']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-3-5 compact-col">
                                    <label class="field-label">Cédula</label>
                                    <input type="text" class="form-control form-input" id="cedula" 
                                           value="<?php echo !empty($data['cedula']) ? htmlspecialchars($data['cedula']) : ''; ?>"
                                           placeholder="<?php echo empty($data['cedula']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-2-5 compact-col">
                                    <label class="field-label">Proyecto</label>
                                    <input type="text" class="form-control form-input" id="proyecto" value="SIGEVU">
                                </div>
                            </div>
                            
                            <!-- Fila 2: Beneficiario y Teléfono -->
                            <div class="row compact-row">
                                <div class="col-md-8 compact-col">
                                    <label class="field-label">Beneficiario (a)</label>
                                    <input type="text" class="form-control form-input" id="beneficiario" 
                                           value="<?php echo !empty($data['nombre_beneficiario']) ? htmlspecialchars($data['nombre_beneficiario']) : ''; ?>"
                                           placeholder="<?php echo empty($data['nombre_beneficiario']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-4 compact-col">
                                    <label class="field-label">Teléfono</label>
                                    <input type="text" class="form-control form-input" id="telefono" 
                                           value="<?php echo !empty($data['telefono']) ? htmlspecialchars($data['telefono']) : ''; ?>"
                                           placeholder="<?php echo empty($data['telefono']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Fila 3: Ubicación geográfica (sin Estado) -->
                            <div class="row compact-row">
                                <div class="col-md-4 compact-col">
                                    <label class="field-label">COMUNIDAD</label>
                                    <input type="text" class="form-control form-input" id="comunidad" 
                                           value="<?php echo !empty($data['nombre_comunidad']) ? htmlspecialchars($data['nombre_comunidad']) : ''; ?>"
                                           placeholder="<?php echo empty($data['nombre_comunidad']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-4 compact-col">
                                    <label class="field-label">Parroquia</label>
                                    <input type="text" class="form-control form-input" id="parroquia" 
                                           value="<?php echo !empty($data['nombre_parroquia']) ? htmlspecialchars($data['nombre_parroquia']) : ''; ?>"
                                           placeholder="<?php echo empty($data['nombre_parroquia']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-4 compact-col">
                                    <label class="field-label">Municipio</label>
                                    <input type="text" class="form-control form-input" id="municipio" 
                                           value="<?php echo !empty($data['nombre_municipio']) ? htmlspecialchars($data['nombre_municipio']) : ''; ?>"
                                           placeholder="<?php echo empty($data['nombre_municipio']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Fila 4: Dirección Exacta -->
                            <div class="row compact-row">
                                <div class="col-12 compact-col">
                                    <label class="field-label">Dirección Exacta</label>
                                    <input type="text" class="form-control form-input" id="direccionExacta" 
                                           value="<?php echo !empty($data['direccion_exacta']) ? htmlspecialchars($data['direccion_exacta']) : ''; ?>"
                                           placeholder="<?php echo empty($data['direccion_exacta']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Fila 5: Coordenadas UTM y Métodos Constructivos -->
                            <div class="row compact-row">
                                <div class="col-md-3 compact-col">
                                    <label class="field-label">UTM Norte</label>
                                    <input type="text" class="form-control form-input" id="utmNorte" 
                                           value="<?php echo !empty($data['utm_norte']) ? htmlspecialchars($data['utm_norte']) : ''; ?>"
                                           placeholder="<?php echo empty($data['utm_norte']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-3 compact-col">
                                    <label class="field-label">UTM Este</label>
                                    <input type="text" class="form-control form-input" id="utmEste" 
                                           value="<?php echo !empty($data['utm_este']) ? htmlspecialchars($data['utm_este']) : ''; ?>"
                                           placeholder="<?php echo empty($data['utm_este']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-3 compact-col">
                                    <label class="field-label">Método Constructivo</label>
                                    <input type="text" class="form-control form-input" id="metodoConstructivo" 
                                           value="<?php echo !empty($data['nombre_metodo']) ? htmlspecialchars($data['nombre_metodo']) : ''; ?>"
                                           placeholder="<?php echo empty($data['nombre_metodo']) ? '#REF!' : ''; ?>">
                                </div>
                                <div class="col-md-3 compact-col">
                                    <label class="field-label">Modelo Constructivo</label>
                                    <input type="text" class="form-control form-input" id="modeloConstructivo" 
                                           value="<?php echo !empty($data['nombre_modelo']) ? htmlspecialchars($data['nombre_modelo']) : ''; ?>"
                                           placeholder="<?php echo empty($data['nombre_modelo']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Fila 6: Estado del proyecto -->
                            <div class="row compact-row">
                                <div class="col-md-3 compact-col">
                                    <label class="field-label">Avance Físico (%)</label>
                                    <input type="number" class="form-control form-input" id="avanceFisico" 
                                           value="<?php echo !empty($data['avance_fisico']) ? number_format($data['avance_fisico'], 2) : '0.00'; ?>"
                                           placeholder="<?php echo empty($data['avance_fisico']) ? '#REF!' : ''; ?>"
                                           min="0" max="100" step="0.01">
                                </div>
                                <div class="col-md-3 compact-col">
                                    <label class="field-label">Fecha Culminación</label>
                                    <input type="date" class="form-control form-input" id="fechaCulminacion" 
                                           value="<?php echo !empty($data['fecha_culminacion']) ? htmlspecialchars($data['fecha_culminacion']) : ''; ?>">
                                </div>
                                <div class="col-md-3 compact-col">
                                    <label class="field-label">Fecha Protocolización</label>
                                    <input type="date" class="form-control form-input" id="fechaProtocolizacion" 
                                           value="<?php echo !empty($data['fecha_protocolizacion']) ? htmlspecialchars($data['fecha_protocolizacion']) : ''; ?>">
                                </div>
                                <div class="col-md-3 compact-col">
                                    <label class="field-label">Fiscalizador</label>
                                    <input type="text" class="form-control form-input" id="fiscalizador" 
                                           value="<?php echo !empty($data['nombre_fiscalizador']) ? htmlspecialchars($data['nombre_fiscalizador']) : ''; ?>"
                                           placeholder="<?php echo empty($data['nombre_fiscalizador']) ? '#REF!' : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Fila 7: Observaciones -->
                            <div class="row compact-row">
                                <div class="col-md-6 compact-col">
                                    <label class="field-label">Observaciones Responsables Control</label>
                                    <textarea class="form-control form-input" id="observacionesControl" rows="2" 
                                              placeholder="<?php echo empty($data['observaciones_responsables_control']) ? '#REF!' : ''; ?>"><?php echo !empty($data['observaciones_responsables_control']) ? htmlspecialchars($data['observaciones_responsables_control']) : ''; ?></textarea>
                                </div>
                                <div class="col-md-6 compact-col">
                                    <label class="field-label">Observaciones Fiscalizadores</label>
                                    <textarea class="form-control form-input" id="observacionesFiscalizadores" rows="2" 
                                              placeholder="<?php echo empty($data['observaciones_fiscalizadores']) ? '#REF!' : ''; ?>"><?php echo !empty($data['observaciones_fiscalizadores']) ? htmlspecialchars($data['observaciones_fiscalizadores']) : ''; ?></textarea>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Footer -->
                        <div class="footer-section">
                            <span class="fw-bold fst-italic">¡Impulsando el Desarrollo Habitacional!</span>
                            <div class="venezuela-flag">
                                <div class="flag-stripe yellow"></div>
                                <div class="flag-stripe blue"></div>
                                <div class="flag-stripe red"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript para manejar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('sigevu-form');
            
            // Agregar event listeners para cada campo
            const campos = [
                'expediente', 'codigoObra', 'beneficiario', 'cedula', 'telefono',
                'comunidad', 'parroquia', 'municipio', 'direccionExacta',
                'utmNorte', 'utmEste', 'metodoConstructivo', 'modeloConstructivo',
                'proyecto', 'avanceFisico', 'fechaCulminacion', 'fechaProtocolizacion',
                'fiscalizador', 'observacionesControl', 'observacionesFiscalizadores'
            ];
            
            campos.forEach(campo => {
                const input = document.getElementById(campo);
                if (input) {
                    input.addEventListener('input', function() {
                        console.log(`Campo ${campo} actualizado: ${this.value}`);
                        // Marcar el campo como modificado
                        this.classList.add('modified');
                    });
                }
            });
            
            // Event listener para el campo de año
            const yearInput = document.getElementById('yearInput');
            if (yearInput) {
                yearInput.addEventListener('input', function() {
                    console.log(`Año actualizado: ${this.value}`);
                    this.classList.add('modified');
                });
            }
            
            // Función para obtener todos los datos del formulario
            function obtenerDatosFormulario() {
                const datos = {};
                campos.forEach(campo => {
                    const input = document.getElementById(campo);
                    if (input) {
                        datos[campo] = input.value;
                    }
                });
                datos.id_beneficiario = document.getElementById('id_beneficiario').value;
                datos.year = document.getElementById('yearInput').value;
                return datos;
            }
            
            // Función para establecer datos en el formulario
            function establecerDatosFormulario(datos) {
                Object.keys(datos).forEach(campo => {
                    const input = document.getElementById(campo);
                    if (input) {
                        input.value = datos[campo];
                    }
                });
            }
            
            // Exponer funciones globalmente para uso externo
            window.sigevu = {
                obtenerDatos: obtenerDatosFormulario,
                establecerDatos: establecerDatosFormulario
            };
        });
        
        // Función para mostrar toast
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
        
        // Función para guardar cambios
        function guardarCambios() {
            const datos = window.sigevu.obtenerDatos();
            
            // Mostrar indicador de carga
            const btnSave = document.querySelector('.btn-save');
            const originalText = btnSave.innerHTML;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
            btnSave.disabled = true;
            
            // Enviar datos al servidor
            fetch('../php/conf/actualizar_expediente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datos)
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
                    showToast('success', 'Expediente actualizado correctamente');
                    // Remover clase de modificado de todos los campos
                    document.querySelectorAll('.modified').forEach(el => {
                        el.classList.remove('modified');
                    });
                } else {
                    throw new Error(data.error || 'Error al actualizar el expediente');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', error.message || 'Error al guardar los cambios');
            })
            .finally(() => {
                btnSave.innerHTML = originalText;
                btnSave.disabled = false;
            });
        }
        
        // Función para imprimir expediente
        function imprimirExpediente() {
            // Configurar título para impresión
            const originalTitle = document.title;
            document.title = 'Expediente - <?php echo htmlspecialchars($data['nombre_beneficiario']); ?>';
            
            // Imprimir
            window.print();
            
            // Restaurar título
            document.title = originalTitle;
        }
    </script>
</body>
</html>
<?php
// Cerrar conexiones de base de datos si las hay
if (isset($stmt)) {
    $stmt->close();
}
if (isset($conexion)) {
    $conexion->close();
}
?>
