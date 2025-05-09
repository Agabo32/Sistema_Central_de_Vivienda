<?php
require_once __DIR__ . '/conexion.php';



error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Campos requeridos
$required_fields = [
    'id_beneficiario', 
    'nombre_beneficiario', 
    'cedula', 
    'comunidad', 
    'status',
    'telefono',
    'codigo_obra',
    'id_municipio',
    'id_parroquia'
];

// Verificar campos faltantes
$missing_fields = [];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Faltan campos requeridos: ' . implode(', ', $missing_fields)]);
    exit;
}

// Validar ID de beneficiario
$id = intval($_POST['id_beneficiario']);
if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID de beneficiario no válido']);
    exit;
}

// Validar IDs de municipio y parroquia
$id_municipio = intval($_POST['id_municipio']);
$id_parroquia = intval($_POST['id_parroquia']);
if ($id_municipio <= 0 || $id_parroquia <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Municipio o parroquia no válidos']);
    exit;
}

// Iniciar transacción
mysqli_begin_transaction($conexion);

try {
    // 1. Actualizar tabla Beneficiarios
    $query_beneficiario = "UPDATE Beneficiarios SET 
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
    
    // Sanitizar y validar datos básicos
    $nombre = trim($_POST['nombre_beneficiario']);
    $cedula = trim($_POST['cedula']);
    $telefono = trim($_POST['telefono']);
    $codigo_obra = trim($_POST['codigo_obra']);
    $status = trim($_POST['status']);
    
    $stmt->bind_param("sssssi", $nombre, $cedula, $telefono, $codigo_obra, $status, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar beneficiario: " . $stmt->error);
    }
    $stmt->close();
    
    // 2. Actualizar Ubicación con municipio y parroquia
    $query_ubicacion = "UPDATE Ubicaciones u
    INNER JOIN Beneficiarios b ON u.id_ubicacion = b.id_ubicacion
    SET u.comunidad = ?, 
        u.direccion_exacta = ?, 
        u.utm_norte = ?, 
        u.utm_este = ?, 
        u.id_municipio = ?, 
        u.id_parroquia = ?
    WHERE b.id_beneficiario = ?";
    
    $stmt = $conexion->prepare($query_ubicacion);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta de ubicación: " . $conexion->error);
    }
    
    // Sanitizar datos de ubicación
    $comunidad = trim($_POST['comunidad']);
    $direccion_exacta = isset($_POST['direccion_exacta']) ? trim($_POST['direccion_exacta']) : '';
    $utm_norte = isset($_POST['utm_norte']) ? trim($_POST['utm_norte']) : '';
    $utm_este = isset($_POST['utm_este']) ? trim($_POST['utm_este']) : '';
    
    $stmt->bind_param("ssssiii", $comunidad, $direccion_exacta, $utm_norte, $utm_este, $id_municipio, $id_parroquia, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar ubicación: " . $stmt->error);
    }
    $stmt->close();
    // 3. Actualizar Datos de Construcción (si existen)
$query_construccion = "UPDATE Datos_de_Construccion SET 
limpieza = ?,
replanteo = ?,
avance_fisico = ?,
excavacion = ?,
acero_vigas_riostra = ?,
encofrado_malla = ?,
instalaciones_electricas_sanitarias = ?,
vaciado_losa_anclajes = ?,
estructura = ?,
armado_columnas = ?,
vaciado_columnas = ?,
armado_vigas = ?,
vaciado_vigas = ?,
cerramiento = ?,
bloqueado = ?,
colocacion_correas = ?,
colocacion_techo = ?,
acabado = ?,
colocacion_ventanas = ?,
colocacion_puertas_principales = ?, 
instalaciones_electricas_sanitarias_paredes = ?,
frisos = ?,
sobrepiso = ?,
ceramica_bano = ?,
colocacion_puertas_internas = ?,
equipos_accesorios_electricos = ?,
equipos_accesorios_sanitarios = ?,
colocacion_lavaplatos = ?,
pintura = ?,
observaciones_responsables_control = ?,
observaciones_fiscalizadores = ?
WHERE id_beneficiario = ?";

$stmt = $conexion->prepare($query_construccion);

if (!$stmt) {
throw new Exception("Error al preparar consulta de construcción: " . $conexion->error);
}

// Sanitizar y validar datos de construcción
$params = [
isset($_POST['limpieza']) ? intval($_POST['limpieza']) : 0,
isset($_POST['replanteo']) ? intval($_POST['replanteo']) : 0,
isset($_POST['avance_fisico']) ? intval($_POST['avance_fisico']) : 0,
isset($_POST['excavacion']) ? intval($_POST['excavacion']) : 0,
isset($_POST['acero_vigas_riostra']) ? intval($_POST['acero_vigas_riostra']) : 0,
isset($_POST['encofrado_malla']) ? intval($_POST['encofrado_malla']) : 0,
isset($_POST['instalaciones_electricas_sanitarias']) ? intval($_POST['instalaciones_electricas_sanitarias']) : 0,
isset($_POST['vaciado_losa_anclajes']) ? intval($_POST['vaciado_losa_anclajes']) : 0,
0, // estructura
isset($_POST['armado_columnas']) ? intval($_POST['armado_columnas']) : 0,
isset($_POST['vaciado_columnas']) ? intval($_POST['vaciado_columnas']) : 0,
isset($_POST['armado_vigas']) ? intval($_POST['armado_vigas']) : 0,
isset($_POST['vaciado_vigas']) ? intval($_POST['vaciado_vigas']) : 0,
0, // cerramiento
isset($_POST['bloqueado']) ? intval($_POST['bloqueado']) : 0,
isset($_POST['colocacion_correas']) ? intval($_POST['colocacion_correas']) : 0,
isset($_POST['colocacion_techo']) ? intval($_POST['colocacion_techo']) : 0,
0, // acabado
isset($_POST['colocacion_ventanas']) ? intval($_POST['colocacion_ventanas']) : 0,
isset($_POST['colocacion_puertas_principales']) ? intval($_POST['colocacion_puertas_principales']) : 0,
isset($_POST['instalaciones_electricas_sanitarias_paredes']) ? intval($_POST['instalaciones_electricas_sanitarias_paredes']) : 0,
isset($_POST['frisos']) ? intval($_POST['frisos']) : 0,
isset($_POST['sobrepiso']) ? intval($_POST['sobrepiso']) : 0,
isset($_POST['ceramica_bano']) ? intval($_POST['ceramica_bano']) : 0,
isset($_POST['colocacion_puertas_internas']) ? intval($_POST['colocacion_puertas_internas']) : 0,
isset($_POST['equipos_accesorios_electricos']) ? intval($_POST['equipos_accesorios_electricos']) : 0,
isset($_POST['equipos_accesorios_sanitarios']) ? intval($_POST['equipos_accesorios_sanitarios']) : 0,
isset($_POST['colocacion_lavaplatos']) ? intval($_POST['colocacion_lavaplatos']) : 0,
isset($_POST['pintura']) ? intval($_POST['pintura']) : 0,
isset($_POST['observaciones_responsables_control']) ? trim($_POST['observaciones_responsables_control']) : '',
isset($_POST['observaciones_fiscalizadores']) ? trim($_POST['observaciones_fiscalizadores']) : '',
$id
];

// Verificar que el número de parámetros coincida con los placeholders en la consulta
$placeholders = substr_count($query_construccion, '?');
if (count($params) !== $placeholders) {
throw new Exception("Número de parámetros (" . count($params) . ") no coincide con placeholders (" . $placeholders . ") en la consulta");
}






// Crear string de tipos dinámicamente
$types = '';
foreach ($params as $param) {
if (is_int($param)) {
    $types .= 'i';
} elseif (is_float($param)) {
    $types .= 'd';
} elseif (is_string($param)) {
    $types .= 's';
} else {
    $types .= 'b'; // blob
}
}

// Agregar referencia a los parámetros para bind_param
$bindParams = [$types];
foreach ($params as &$param) {
$bindParams[] = &$param;
}

// Usar call_user_func_array para bind_param dinámico
call_user_func_array([$stmt, 'bind_param'], $bindParams);

if (!$stmt->execute()) {
throw new Exception("Error al actualizar construcción: " . $stmt->error);
}
$stmt->close();
    
    // Confirmar transacción
    mysqli_commit($conexion);
    
    // Éxito
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Beneficiario actualizado correctamente']);
    
} catch (Exception $e) {
    // Error - hacer rollback
    mysqli_rollback($conexion);
    
    // Mostrar error
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    
    // Log del error
    error_log($e->getMessage());
}

// Cerrar conexión
mysqli_close($conexion);
?>