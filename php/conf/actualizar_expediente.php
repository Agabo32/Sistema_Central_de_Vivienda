<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario esté autenticado y sea admin
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'root') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No tienes permisos para realizar esta acción']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos JSON del cuerpo de la petición
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos inválidos recibidos');
    }
    
    // Validar ID del beneficiario
    $id_beneficiario = intval($data['id_beneficiario']);
    if ($id_beneficiario <= 0) {
        throw new Exception('ID de beneficiario inválido');
    }
    
    // Extraer datos del expediente
    $nombre_beneficiario = trim($data['beneficiario'] ?? '');
    $cedula = trim($data['cedula'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $direccion_exacta = trim($data['direccionExacta'] ?? '');
    $utm_norte = trim($data['utmNorte'] ?? '');
    $utm_este = trim($data['utmEste'] ?? '');
    $avance_fisico = floatval($data['avanceFisico'] ?? 0);
    $fecha_culminacion = !empty($data['fechaCulminacion']) ? $data['fechaCulminacion'] : null;
    $fecha_protocolizacion = !empty($data['fechaProtocolizacion']) ? $data['fechaProtocolizacion'] : null;
    $observaciones_responsables_control = trim($data['observacionesControl'] ?? '');
    $observaciones_fiscalizadores = trim($data['observacionesFiscalizadores'] ?? '');
    
    // Validaciones básicas
    if (empty($nombre_beneficiario)) {
        throw new Exception('El nombre del beneficiario es requerido');
    }
    
    if (empty($cedula)) {
        throw new Exception('La cédula es requerida');
    }
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    // Obtener la ubicación actual del beneficiario
    $query_ubicacion = "SELECT id_ubicacion FROM beneficiarios WHERE id_beneficiario = ?";
    $stmt_ubicacion = mysqli_prepare($conexion, $query_ubicacion);
    mysqli_stmt_bind_param($stmt_ubicacion, "i", $id_beneficiario);
    mysqli_stmt_execute($stmt_ubicacion);
    $result_ubicacion = mysqli_stmt_get_result($stmt_ubicacion);
    $ubicacion_actual = mysqli_fetch_assoc($result_ubicacion);
    
    if (!$ubicacion_actual) {
        throw new Exception('Beneficiario no encontrado');
    }
    
    $id_ubicacion = $ubicacion_actual['id_ubicacion'];
    
    // Actualizar ubicación si existe
    if ($id_ubicacion) {
        $query_update_ubicacion = "UPDATE ubicaciones SET 
            direccion_exacta = ?, 
            utm_norte = ?, 
            utm_este = ? 
            WHERE id_ubicacion = ?";
        
        $stmt_update_ubicacion = mysqli_prepare($conexion, $query_update_ubicacion);
        mysqli_stmt_bind_param($stmt_update_ubicacion, "sssi", 
            $direccion_exacta, $utm_norte, $utm_este, $id_ubicacion);
        
        if (!mysqli_stmt_execute($stmt_update_ubicacion)) {
            throw new Exception('Error al actualizar la ubicación: ' . mysqli_error($conexion));
        }
    }
    
    // Actualizar beneficiario
    $query_update_beneficiario = "UPDATE beneficiarios SET 
        cedula = ?, 
        nombre_beneficiario = ?, 
        telefono = ?
        WHERE id_beneficiario = ?";
    
    $stmt_update_beneficiario = mysqli_prepare($conexion, $query_update_beneficiario);
    mysqli_stmt_bind_param($stmt_update_beneficiario, "sssi", 
        $cedula, $nombre_beneficiario, $telefono, $id_beneficiario);
    
    if (!mysqli_stmt_execute($stmt_update_beneficiario)) {
        throw new Exception('Error al actualizar el beneficiario: ' . mysqli_error($conexion));
    }
    
    // Verificar si existe registro de construcción
    $query_check_construccion = "SELECT id_construccion FROM datos_de_construccion WHERE id_beneficiario = ?";
    $stmt_check_construccion = mysqli_prepare($conexion, $query_check_construccion);
    mysqli_stmt_bind_param($stmt_check_construccion, "i", $id_beneficiario);
    mysqli_stmt_execute($stmt_check_construccion);
    $result_construccion = mysqli_stmt_get_result($stmt_check_construccion);
    $construccion_existente = mysqli_fetch_assoc($result_construccion);
    
    if ($construccion_existente) {
        // Actualizar datos de construcción existentes
        $query_update_construccion = "UPDATE datos_de_construccion SET 
            avance_fisico = ?, 
            fecha_culminacion = ?, 
            fecha_protocolizacion = ?, 
            observaciones_responsables_control = ?, 
            observaciones_fiscalizadores = ?
            WHERE id_beneficiario = ?";
        
        $stmt_update_construccion = mysqli_prepare($conexion, $query_update_construccion);
        mysqli_stmt_bind_param($stmt_update_construccion, "dssssi",
            $avance_fisico, $fecha_culminacion, $fecha_protocolizacion,
            $observaciones_responsables_control, $observaciones_fiscalizadores, $id_beneficiario);
    } else {
        // Crear nuevo registro de construcción
        $query_insert_construccion = "INSERT INTO datos_de_construccion (
            id_beneficiario, avance_fisico, fecha_culminacion, fecha_protocolizacion,
            observaciones_responsables_control, observaciones_fiscalizadores
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt_insert_construccion = mysqli_prepare($conexion, $query_insert_construccion);
        mysqli_stmt_bind_param($stmt_insert_construccion, "idssss",
            $id_beneficiario, $avance_fisico, $fecha_culminacion, $fecha_protocolizacion,
            $observaciones_responsables_control, $observaciones_fiscalizadores);
        
        $stmt_update_construccion = $stmt_insert_construccion;
    }
    
    if (!mysqli_stmt_execute($stmt_update_construccion)) {
        throw new Exception('Error al actualizar los datos de construcción: ' . mysqli_error($conexion));
    }
    
    // Confirmar transacción
    mysqli_commit($conexion);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Expediente actualizado correctamente',
        'id_beneficiario' => $id_beneficiario
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($conexion);
    
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
} finally {
    // Cerrar statements si existen
    if (isset($stmt_ubicacion)) mysqli_stmt_close($stmt_ubicacion);
    if (isset($stmt_update_ubicacion)) mysqli_stmt_close($stmt_update_ubicacion);
    if (isset($stmt_update_beneficiario)) mysqli_stmt_close($stmt_update_beneficiario);
    if (isset($stmt_check_construccion)) mysqli_stmt_close($stmt_check_construccion);
    if (isset($stmt_update_construccion)) mysqli_stmt_close($stmt_update_construccion);
    
    // Cerrar conexión
    mysqli_close($conexion);
}
?>
