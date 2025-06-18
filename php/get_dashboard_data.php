<?php
session_start();
require_once 'conf/conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    // Obtener el período seleccionado
    $periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';
    
    // Calcular fechas según el período
    $fecha_inicio = date('Y-m-d');
    $fecha_fin = date('Y-m-d');
    
    switch ($periodo) {
        case 'hoy':
            $fecha_inicio = date('Y-m-d');
            break;
        case 'semana':
            $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'mes':
            $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'año':
            $fecha_inicio = date('Y-m-d', strtotime('-365 days'));
            break;
    }

    // Consulta para obtener totales generales
    $sql_totales = "SELECT 
        COUNT(*) as total_beneficiarios,
        SUM(CASE WHEN b.status = 'completada' THEN 1 ELSE 0 END) as viviendas_completadas,
        COUNT(DISTINCT u.comunidad) as total_comunidades,
        COALESCE(AVG(dc.avance_fisico), 0) as avance_general
    FROM beneficiarios b
    LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
    LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario";

    $result_totales = $conexion->query($sql_totales);
    if (!$result_totales) {
        throw new Exception("Error en consulta de totales: " . $conexion->error);
    }
    $totales = $result_totales->fetch_assoc();

    // Consulta para obtener cambios porcentuales
    $sql_cambios = "SELECT 
        COALESCE(
            (COUNT(*) - LAG(COUNT(*)) OVER (ORDER BY DATE(b.created_at))) * 100.0 / 
            NULLIF(LAG(COUNT(*)) OVER (ORDER BY DATE(b.created_at)), 0),
            0
        ) as cambio_beneficiarios,
        COALESCE(
            (SUM(CASE WHEN b.status = 'completada' THEN 1 ELSE 0 END) - 
            LAG(SUM(CASE WHEN b.status = 'completada' THEN 1 ELSE 0 END)) OVER (ORDER BY DATE(b.created_at))) * 100.0 / 
            NULLIF(LAG(SUM(CASE WHEN b.status = 'completada' THEN 1 ELSE 0 END)) OVER (ORDER BY DATE(b.created_at)), 0),
            0
        ) as cambio_viviendas,
        COALESCE(
            (COUNT(DISTINCT u.comunidad) - 
            LAG(COUNT(DISTINCT u.comunidad)) OVER (ORDER BY DATE(b.created_at))) * 100.0 / 
            NULLIF(LAG(COUNT(DISTINCT u.comunidad)) OVER (ORDER BY DATE(b.created_at)), 0),
            0
        ) as cambio_comunidades,
        COALESCE(
            (AVG(dc.avance_fisico) - 
            LAG(AVG(dc.avance_fisico)) OVER (ORDER BY DATE(b.created_at))) * 100.0 / 
            NULLIF(LAG(AVG(dc.avance_fisico)) OVER (ORDER BY DATE(b.created_at)), 0),
            0
        ) as cambio_avance
    FROM beneficiarios b
    LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
    LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
    WHERE DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY DATE(b.created_at)
    ORDER BY DATE(b.created_at) DESC
    LIMIT 1";

    $stmt_cambios = $conexion->prepare($sql_cambios);
    if (!$stmt_cambios) {
        throw new Exception("Error en preparación de consulta de cambios: " . $conexion->error);
    }
    $stmt_cambios->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt_cambios->execute();
    $result_cambios = $stmt_cambios->get_result();
    $cambios = $result_cambios->fetch_assoc() ?: [
        'cambio_beneficiarios' => 0,
        'cambio_viviendas' => 0,
        'cambio_comunidades' => 0,
        'cambio_avance' => 0
    ];

    // Consulta para el gráfico de progreso
    $sql_progreso = "SELECT 
        DATE(b.created_at) as fecha,
        COALESCE(AVG(dc.avance_fisico), 0) as avance
    FROM beneficiarios b
    LEFT JOIN datos_de_construccion dc ON b.id_beneficiario = dc.id_beneficiario
    WHERE DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY DATE(b.created_at)
    ORDER BY DATE(b.created_at)";

    $stmt_progreso = $conexion->prepare($sql_progreso);
    if (!$stmt_progreso) {
        throw new Exception("Error en preparación de consulta de progreso: " . $conexion->error);
    }
    $stmt_progreso->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt_progreso->execute();
    $result_progreso = $stmt_progreso->get_result();
    
    $progreso_data = [
        'labels' => [],
        'values' => []
    ];
    
    while ($row = $result_progreso->fetch_assoc()) {
        $progreso_data['labels'][] = date('d/m', strtotime($row['fecha']));
        $progreso_data['values'][] = round($row['avance'], 2);
    }

    // Consulta para el gráfico de estado de viviendas
    $sql_estado = "SELECT 
        COALESCE(b.status, 'sin_estado') as status,
        COUNT(*) as total
    FROM beneficiarios b
    GROUP BY b.status";

    $result_estado = $conexion->query($sql_estado);
    if (!$result_estado) {
        throw new Exception("Error en consulta de estados: " . $conexion->error);
    }
    
    $estado_data = [
        'labels' => [],
        'values' => []
    ];
    
    while ($row = $result_estado->fetch_assoc()) {
        $estado_data['labels'][] = ucfirst(str_replace('_', ' ', $row['status']));
        $estado_data['values'][] = (int)$row['total'];
    }

    // Consulta para el gráfico de beneficiarios por municipio
    $sql_municipios = "SELECT 
        COALESCE(m.municipio, 'Sin Municipio') as municipio,
        COUNT(*) as total
    FROM beneficiarios b
    LEFT JOIN ubicaciones u ON b.id_ubicacion = u.id_ubicacion
    LEFT JOIN parroquias p ON u.parroquia = p.id_parroquia
    LEFT JOIN municipios m ON p.id_municipio = m.id_municipio
    GROUP BY m.id_municipio, m.municipio
    ORDER BY total DESC
    LIMIT 10";

    $result_municipios = $conexion->query($sql_municipios);
    if (!$result_municipios) {
        throw new Exception("Error en consulta de municipios: " . $conexion->error);
    }
    
    $municipios_data = [
        'labels' => [],
        'values' => []
    ];
    
    while ($row = $result_municipios->fetch_assoc()) {
        $municipios_data['labels'][] = $row['municipio'];
        $municipios_data['values'][] = (int)$row['total'];
    }

    // Consulta para el gráfico de métodos constructivos
    $sql_metodos = "SELECT 
        COALESCE(mc.metodo, 'Sin Método') as metodo,
        COUNT(*) as total
    FROM beneficiarios b
    LEFT JOIN metodos_constructivos mc ON b.metodo_constructivo = mc.id_metodo
    GROUP BY mc.id_metodo, mc.metodo
    ORDER BY total DESC";

    $result_metodos = $conexion->query($sql_metodos);
    if (!$result_metodos) {
        throw new Exception("Error en consulta de métodos: " . $conexion->error);
    }
    
    $metodos_data = [
        'labels' => [],
        'values' => []
    ];
    
    while ($row = $result_metodos->fetch_assoc()) {
        $metodos_data['labels'][] = $row['metodo'];
        $metodos_data['values'][] = (int)$row['total'];
    }

    // Preparar respuesta
    $response = [
        'totalBeneficiarios' => (int)$totales['total_beneficiarios'],
        'viviendasCompletadas' => (int)$totales['viviendas_completadas'],
        'totalComunidades' => (int)$totales['total_comunidades'],
        'avanceGeneral' => round($totales['avance_general'], 2),
        'cambioBeneficiarios' => round($cambios['cambio_beneficiarios'], 2),
        'cambioViviendas' => round($cambios['cambio_viviendas'], 2),
        'cambioComunidades' => round($cambios['cambio_comunidades'], 2),
        'cambioAvance' => round($cambios['cambio_avance'], 2),
        'progresoData' => $progreso_data,
        'estadoViviendasData' => $estado_data,
        'municipiosData' => $municipios_data,
        'metodosData' => $metodos_data
    ];

    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener datos: ' . $e->getMessage()]);
}

// Cerrar conexiones
if (isset($stmt_cambios)) $stmt_cambios->close();
if (isset($stmt_progreso)) $stmt_progreso->close();
if (isset($conexion)) $conexion->close();
?>
