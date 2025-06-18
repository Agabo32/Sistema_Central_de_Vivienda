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
    // Obtener datos del formulario
    $id_beneficiario = intval($_POST['id_beneficiario']);
    
    // Validar ID del beneficiario
    if ($id_beneficiario <= 0) {
        throw new Exception('ID de beneficiario inválido');
    }
    
    // Datos básicos del beneficiario
    $nombre_beneficiario = trim($_POST['nombre_beneficiario']);
    $cedula = trim($_POST['cedula']);
    $telefono = trim($_POST['telefono']);
    $status = $_POST['status'];
    $codigo_obra = intval($_POST['codigo_obra']);
    $metodo_constructivo = !empty($_POST['metodo_constructivo']) ? intval($_POST['metodo_constructivo']) : null;
    $modelo_constructivo = !empty($_POST['modelo_constructivo']) ? intval($_POST['modelo_constructivo']) : null;
    $id_fiscalizador = !empty($_POST['id_fiscalizador']) ? intval($_POST['id_fiscalizador']) : null;
    
    // Datos de ubicación
    $id_municipio = !empty($_POST['id_municipio']) ? intval($_POST['id_municipio']) : null;
    $id_parroquia = !empty($_POST['id_parroquia']) ? intval($_POST['id_parroquia']) : null;
    $id_comunidad = !empty($_POST['id_comunidad']) ? intval($_POST['id_comunidad']) : null;
    $direccion_exacta = trim($_POST['direccion_exacta']);
    $utm_norte = trim($_POST['utm_norte']);
    $utm_este = trim($_POST['utm_este']);
    
    // Datos de construcción
    $limpieza = floatval($_POST['limpieza']);
    $replanteo = floatval($_POST['replanteo']);
    $excavacion = floatval($_POST['excavacion']);
    $fundacion = floatval($_POST['fundacion']);
    $acero_vigas_riostra = floatval($_POST['acero_vigas_riostra']);
    $encofrado_malla = floatval($_POST['encofrado_malla']);
    $instalaciones_electricas_sanitarias = floatval($_POST['instalaciones_electricas_sanitarias']);
    $vaciado_losa_anclajes = floatval($_POST['vaciado_losa_anclajes']);
    $estructura = floatval($_POST['estructura']);
    $armado_columnas = floatval($_POST['armado_columnas']);
    $vaciado_columnas = floatval($_POST['vaciado_columnas']);
    $armado_vigas = floatval($_POST['armado_vigas']);
    $vaciado_vigas = floatval($_POST['vaciado_vigas']);
    $cerramiento = floatval($_POST['cerramiento']);
    $bloqueado = floatval($_POST['bloqueado']);
    $colocacion_correas = floatval($_POST['colocacion_correas']);
    $colocacion_techo = floatval($_POST['colocacion_techo']);
    $colocacion_ventanas = floatval($_POST['colocacion_ventanas']);
    $colocacion_puertas_principales = floatval($_POST['colocacion_puertas_principales']);
    $instalaciones_electricas_sanitarias_paredes = floatval($_POST['instalaciones_electricas_sanitarias_paredes']);
    $frisos = floatval($_POST['frisos']);
    $sobrepiso = floatval($_POST['sobrepiso']);
    $ceramica_bano = floatval($_POST['ceramica_bano']);
    $colocacion_puertas_internas = floatval($_POST['colocacion_puertas_internas']);
    $equipos_accesorios_electricos = floatval($_POST['equipos_accesorios_electricos']);
    $equipos_accesorios_sanitarios = floatval($_POST['equipos_accesorios_sanitarios']);
    $colocacion_lavaplatos = floatval($_POST['colocacion_lavaplatos']);
    $pintura = floatval($_POST['pintura']);
    $avance_fisico = floatval($_POST['avance_fisico']);
    $fecha_culminacion = !empty($_POST['fecha_culminacion']) ? $_POST['fecha_culminacion'] : null;
    $fecha_protocolizacion = !empty($_POST['fecha_protocolizacion']) ? $_POST['fecha_protocolizacion'] : null;
    $acta_entregada = isset($_POST['acta_entregada']) ? intval($_POST['acta_entregada']) : 0;
    $observaciones_responsables_control = trim($_POST['observaciones_responsables_control'] ?? '');
    $observaciones_fiscalizadores = trim($_POST['observaciones_fiscalizadores'] ?? '');
    
    // Calcular acondicionamiento
    $acondicionamiento = ($limpieza + $replanteo) / 2;
    
    // Validaciones básicas
    if (empty($nombre_beneficiario)) {
        throw new Exception('El nombre del beneficiario es requerido');
    }
    
    if (empty($cedula)) {
        throw new Exception('La cédula es requerida');
    }
    
    if ($codigo_obra <= 0) {
        throw new Exception('El código de obra es requerido');
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
            municipio = ?, 
            parroquia = ?, 
            comunidad = ?, 
            direccion_exacta = ?, 
            utm_norte = ?, 
            utm_este = ? 
            WHERE id_ubicacion = ?";
        
        $stmt_update_ubicacion = mysqli_prepare($conexion, $query_update_ubicacion);
        mysqli_stmt_bind_param($stmt_update_ubicacion, "iiisssi", 
            $id_municipio, $id_parroquia, $id_comunidad, 
            $direccion_exacta, $utm_norte, $utm_este, $id_ubicacion);
        
        if (!mysqli_stmt_execute($stmt_update_ubicacion)) {
            throw new Exception('Error al actualizar la ubicación: ' . mysqli_error($conexion));
        }
    }
    
    // Actualizar beneficiario
    $query_update_beneficiario = "UPDATE beneficiarios SET 
        cedula = ?, 
        nombre_beneficiario = ?, 
        telefono = ?, 
        cod_obra = ?, 
        metodo_constructivo = ?, 
        modelo_constructivo = ?, 
        fiscalizador = ?, 
        status = ? 
        WHERE id_beneficiario = ?";
    
    $stmt_update_beneficiario = mysqli_prepare($conexion, $query_update_beneficiario);
    mysqli_stmt_bind_param($stmt_update_beneficiario, "ssiiiissi", 
        $cedula, $nombre_beneficiario, $telefono, $codigo_obra, 
        $metodo_constructivo, $modelo_constructivo, $id_fiscalizador, 
        $status, $id_beneficiario);
    
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
            acondicionamiento = ?, limpieza = ?, replanteo = ?, fundacion = ?, excavacion = ?,
            acero_vigas_riostra = ?, encofrado_malla = ?, instalaciones_electricas_sanitarias = ?,
            vaciado_losa_anclajes = ?, estructura = ?, armado_columnas = ?, vaciado_columnas = ?,
            armado_vigas = ?, vaciado_vigas = ?, cerramiento = ?, bloqueado = ?, colocacion_correas = ?,
            colocacion_techo = ?, acabado = 0, colocacion_ventanas = ?, colocacion_puertas_principales = ?,
            instalaciones_electricas_sanitarias_paredes = ?, frisos = ?, sobrepiso = ?, ceramica_bano = ?,
            colocacion_puertas_internas = ?, equipos_accesorios_electricos = ?, equipos_accesorios_sanitarios = ?,
            colocacion_lavaplatos = ?, pintura = ?, avance_fisico = ?, fecha_culminacion = ?,
            fecha_protocolizacion = ?, acta_entregada = ?, observaciones_responsables_control = ?,
            observaciones_fiscalizadores = ?
            WHERE id_beneficiario = ?";
        
        $stmt_update_construccion = mysqli_prepare($conexion, $query_update_construccion);
        mysqli_stmt_bind_param($stmt_update_construccion, "ddddddddddddddddddddddddddddddssissi",
            $acondicionamiento, $limpieza, $replanteo, $fundacion, $excavacion,
            $acero_vigas_riostra, $encofrado_malla, $instalaciones_electricas_sanitarias,
            $vaciado_losa_anclajes, $estructura, $armado_columnas, $vaciado_columnas,
            $armado_vigas, $vaciado_vigas, $cerramiento, $bloqueado, $colocacion_correas,
            $colocacion_techo, $colocacion_ventanas, $colocacion_puertas_principales,
            $instalaciones_electricas_sanitarias_paredes, $frisos, $sobrepiso, $ceramica_bano,
            $colocacion_puertas_internas, $equipos_accesorios_electricos, $equipos_accesorios_sanitarios,
            $colocacion_lavaplatos, $pintura, $avance_fisico, $fecha_culminacion,
            $fecha_protocolizacion, $acta_entregada, $observaciones_responsables_control,
            $observaciones_fiscalizadores, $id_beneficiario);
    } else {
        // Crear nuevo registro de construcción
        $query_insert_construccion = "INSERT INTO datos_de_construccion (
            id_beneficiario, acondicionamiento, limpieza, replanteo, fundacion, excavacion,
            acero_vigas_riostra, encofrado_malla, instalaciones_electricas_sanitarias,
            vaciado_losa_anclajes, estructura, armado_columnas, vaciado_columnas,
            armado_vigas, vaciado_vigas, cerramiento, bloqueado, colocacion_correas,
            colocacion_techo, acabado, colocacion_ventanas, colocacion_puertas_principales,
            instalaciones_electricas_sanitarias_paredes, frisos, sobrepiso, ceramica_bano,
            colocacion_puertas_internas, equipos_accesorios_electricos, equipos_accesorios_sanitarios,
            colocacion_lavaplatos, pintura, avance_fisico, fecha_culminacion,
            fecha_protocolizacion, acta_entregada, observaciones_responsables_control,
            observaciones_fiscalizadores
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_insert_construccion = mysqli_prepare($conexion, $query_insert_construccion);
        mysqli_stmt_bind_param($stmt_insert_construccion, "idddddddddddddddddddddddddddddssisss",
            $id_beneficiario, $acondicionamiento, $limpieza, $replanteo, $fundacion, $excavacion,
            $acero_vigas_riostra, $encofrado_malla, $instalaciones_electricas_sanitarias,
            $vaciado_losa_anclajes, $estructura, $armado_columnas, $vaciado_columnas,
            $armado_vigas, $vaciado_vigas, $cerramiento, $bloqueado, $colocacion_correas,
            $colocacion_techo, $colocacion_ventanas, $colocacion_puertas_principales,
            $instalaciones_electricas_sanitarias_paredes, $frisos, $sobrepiso, $ceramica_bano,
            $colocacion_puertas_internas, $equipos_accesorios_electricos, $equipos_accesorios_sanitarios,
            $colocacion_lavaplatos, $pintura, $avance_fisico, $fecha_culminacion,
            $fecha_protocolizacion, $acta_entregada, $observaciones_responsables_control,
            $observaciones_fiscalizadores);
        
        $stmt_update_construccion = $stmt_insert_construccion;
    }
    
    if (!mysqli_stmt_execute($stmt_update_construccion)) {
        throw new Exception('Error al actualizar los datos de construcción: ' . mysqli_error($conexion));
    }
    
    // Confirmar transacción
    mysqli_commit($conexion);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Beneficiario actualizado correctamente',
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