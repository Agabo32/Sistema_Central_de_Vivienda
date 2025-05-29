<?php
session_start();
require_once __DIR__ . '/conexion.php';

// Verificar permisos de administrador
if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Acceso no autorizado'
    ]);
    exit;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Validar ID de beneficiario
if (!isset($_POST['id_beneficiario']) || !is_numeric($_POST['id_beneficiario'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID de beneficiario no válido']);
    exit;
}

$id_beneficiario = intval($_POST['id_beneficiario']);

// Iniciar transacción
mysqli_begin_transaction($conexion);

try {
    // 1. Actualizar tabla beneficiarios
    $query_beneficiario = "UPDATE beneficiarios SET 
        nombre_beneficiario = ?,
        cedula = ?,
        telefono = ?,
        codigo_obra = ?,
        status = ?,
        fecha_actualizacion = NOW()
        WHERE id_beneficiario = ?";
    
    $stmt = $conexion->prepare($query_beneficiario);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta de beneficiario: " . $conexion->error);
    }
    
    $nombre = trim($_POST['nombre_beneficiario'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $codigo_obra = trim($_POST['codigo_obra'] ?? '');
    $status = trim($_POST['status'] ?? 'activo');
    
    $stmt->bind_param("sssssi", $nombre, $cedula, $telefono, $codigo_obra, $status, $id_beneficiario);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar beneficiario: " . $stmt->error);
    }
    $stmt->close();
    
    // 2. Obtener ID de ubicación del beneficiario
    $query_get_ubicacion = "SELECT id_ubicacion FROM beneficiarios WHERE id_beneficiario = ?";
    $stmt = $conexion->prepare($query_get_ubicacion);
    if (!$stmt) {
        throw new Exception("Error preparando consulta de ubicación: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $id_beneficiario);
    if (!$stmt->execute()) {
        throw new Exception("Error obteniendo ubicación: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $ubicacion_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($ubicacion_data && $ubicacion_data['id_ubicacion']) {
        // 3. Actualizar ubicación existente
        $id_ubicacion = $ubicacion_data['id_ubicacion'];
        
        $query_ubicacion = "UPDATE ubicaciones SET 
            comunidad = ?,
            direccion_exacta = ?,
            utm_norte = ?,
            utm_este = ?,
            id_municipio = ?,
            id_parroquia = ?
            WHERE id_ubicacion = ?";
        
        $stmt = $conexion->prepare($query_ubicacion);
        if (!$stmt) {
            throw new Exception("Error preparando actualización de ubicación: " . $conexion->error);
        }
        
        $comunidad = trim($_POST['comunidad'] ?? '');
        $direccion_exacta = trim($_POST['direccion_exacta'] ?? '');
        $utm_norte = !empty($_POST['utm_norte']) ? floatval($_POST['utm_norte']) : null;
        $utm_este = !empty($_POST['utm_este']) ? floatval($_POST['utm_este']) : null;
        $id_municipio = !empty($_POST['id_municipio']) ? intval($_POST['id_municipio']) : null;
        $id_parroquia = !empty($_POST['id_parroquia']) ? intval($_POST['id_parroquia']) : null;
        
        $stmt->bind_param("ssddiii", $comunidad, $direccion_exacta, $utm_norte, $utm_este, $id_municipio, $id_parroquia, $id_ubicacion);
        
        if (!$stmt->execute()) {
            throw new Exception("Error actualizando ubicación: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // 4. Crear nueva ubicación si no existe
        $query_nueva_ubicacion = "INSERT INTO ubicaciones (comunidad, direccion_exacta, utm_norte, utm_este, id_municipio, id_parroquia) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($query_nueva_ubicacion);
        if (!$stmt) {
            throw new Exception("Error preparando nueva ubicación: " . $conexion->error);
        }
        
        $comunidad = trim($_POST['comunidad'] ?? '');
        $direccion_exacta = trim($_POST['direccion_exacta'] ?? '');
        $utm_norte = !empty($_POST['utm_norte']) ? floatval($_POST['utm_norte']) : null;
        $utm_este = !empty($_POST['utm_este']) ? floatval($_POST['utm_este']) : null;
        $id_municipio = !empty($_POST['id_municipio']) ? intval($_POST['id_municipio']) : null;
        $id_parroquia = !empty($_POST['id_parroquia']) ? intval($_POST['id_parroquia']) : null;
        
        $stmt->bind_param("ssddii", $comunidad, $direccion_exacta, $utm_norte, $utm_este, $id_municipio, $id_parroquia);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creando nueva ubicación: " . $stmt->error);
        }
        
        $nueva_id_ubicacion = $conexion->insert_id;
        $stmt->close();
        
        // Actualizar beneficiario con la nueva ubicación
        $query_update_beneficiario_ubicacion = "UPDATE beneficiarios SET id_ubicacion = ? WHERE id_beneficiario = ?";
        $stmt = $conexion->prepare($query_update_beneficiario_ubicacion);
        if (!$stmt) {
            throw new Exception("Error preparando actualización de beneficiario con ubicación: " . $conexion->error);
        }
        
        $stmt->bind_param("ii", $nueva_id_ubicacion, $id_beneficiario);
        if (!$stmt->execute()) {
            throw new Exception("Error actualizando beneficiario con nueva ubicación: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // 5. Verificar si existen datos de construcción
    $query_check_construccion = "SELECT id_construccion FROM datos_de_construccion WHERE id_beneficiario = ?";
    $stmt = $conexion->prepare($query_check_construccion);
    if (!$stmt) {
        throw new Exception("Error verificando datos de construcción: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $id_beneficiario);
    $stmt->execute();
    $result = $stmt->get_result();
    $construccion_exists = $result->fetch_assoc();
    $stmt->close();
    
    // Preparar datos de construcción
    $construccion_fields = [
        'acondicionamiento', 'limpieza', 'replanteo', 'fundacion', 'excavacion',
        'acero_vigas_riostra', 'encofrado_malla', 'instalaciones_electricas_sanitarias',
        'vaciado_losa_anclajes', 'estructura', 'armado_columnas', 'vaciado_columnas',
        'armado_vigas', 'vaciado_vigas', 'cerramiento', 'bloqueado', 'colocacion_correas',
        'colocacion_techo', 'acabado', 'colocacion_ventanas', 'colocacion_puertas_principales',
        'instalaciones_electricas_sanitarias_paredes', 'frisos', 'sobrepiso', 'ceramica_bano',
        'colocacion_puertas_internas', 'equipos_accesorios_electricos', 'equipos_accesorios_sanitarios',
        'colocacion_lavaplatos', 'pintura', 'avance_fisico'
    ];
    
    $construccion_values = [];
    foreach ($construccion_fields as $field) {
        $construccion_values[] = isset($_POST[$field]) && is_numeric($_POST[$field]) ? floatval($_POST[$field]) : 0;
    }
    
    $observaciones_responsables = trim($_POST['observaciones_responsables_control'] ?? '');
    $observaciones_fiscalizadores = trim($_POST['observaciones_fiscalizadores'] ?? '');
    $fecha_culminacion = !empty($_POST['fecha_culminacion']) ? $_POST['fecha_culminacion'] : null;
    $acta_entregada = isset($_POST['acta_entregada']) ? intval($_POST['acta_entregada']) : 0;
    
    if ($construccion_exists) {
        // 6. Actualizar datos de construcción existentes
        $set_clause = implode(' = ?, ', $construccion_fields) . ' = ?';
        $query_construccion = "UPDATE datos_de_construccion SET 
            $set_clause,
            fecha_culminacion = ?,
            acta_entregada = ?,
            observaciones_responsables_control = ?,
            observaciones_fiscalizadores = ?
            WHERE id_beneficiario = ?";
        
        $stmt = $conexion->prepare($query_construccion);
        if (!$stmt) {
            throw new Exception("Error preparando actualización de construcción: " . $conexion->error);
        }
        
        $types = str_repeat('d', count($construccion_fields)) . 'sissi';
        $params = array_merge($construccion_values, [$fecha_culminacion, $acta_entregada, $observaciones_responsables, $observaciones_fiscalizadores, $id_beneficiario]);
        
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            throw new Exception("Error actualizando datos de construcción: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // 7. Crear nuevos datos de construcción
        $fields_clause = implode(', ', $construccion_fields);
        $placeholders = str_repeat('?, ', count($construccion_fields) - 1) . '?';
        
        $query_nueva_construccion = "INSERT INTO datos_de_construccion 
            (id_beneficiario, $fields_clause, fecha_culminacion, acta_entregada, observaciones_responsables_control, observaciones_fiscalizadores) 
            VALUES (?, $placeholders, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($query_nueva_construccion);
        if (!$stmt) {
            throw new Exception("Error preparando nueva construcción: " . $conexion->error);
        }
        
        $types = 'i' . str_repeat('d', count($construccion_fields)) . 'siss';
        $params = array_merge([$id_beneficiario], $construccion_values, [$fecha_culminacion, $acta_entregada, $observaciones_responsables, $observaciones_fiscalizadores]);
        
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creando nuevos datos de construcción: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // Confirmar transacción
    mysqli_commit($conexion);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Beneficiario actualizado correctamente']);
    
} catch (Exception $e) {
    // Rollback en caso de error
    mysqli_rollback($conexion);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    
    error_log("Error actualizando beneficiario: " . $e->getMessage());
}

mysqli_close($conexion);
?>
