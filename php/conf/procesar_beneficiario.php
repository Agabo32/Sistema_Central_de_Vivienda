<?php
session_start();
require_once 'conexion.php';

// Configurar cabeceras
header('Content-Type: application/json; charset=utf-8');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Verificar acción
if (!isset($_POST['action']) || $_POST['action'] !== 'nuevo_beneficiario') {
    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
    exit;
}

// Verificar permisos
if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] !== 'root') {
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción']);
    exit;
}

try {
    $conexion->begin_transaction();
    
    // Validar campos requeridos
    $required_fields = ['nombre_beneficiario', 'cedula', 'telefono', 'codigo_obra', 'municipio', 'parroquia'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("El campo $field es requerido");
        }
    }
    
    // Verificar si la cédula ya existe
    $check_cedula = $conexion->prepare("SELECT id_beneficiario FROM beneficiarios WHERE cedula = ?");
    $check_cedula->bind_param("s", $_POST['cedula']);
    $check_cedula->execute();
    if ($check_cedula->get_result()->num_rows > 0) {
        throw new Exception("Ya existe un beneficiario con esta cédula");
    }
    
    // Crear ubicación
    $stmt_ubicacion = $conexion->prepare("
        INSERT INTO ubicaciones (municipio, parroquia, comunidad, direccion_exacta, utm_norte, utm_este) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $id_municipio = intval($_POST['municipio']);
    $id_parroquia = intval($_POST['parroquia']);
    $id_comunidad = !empty($_POST['comunidad']) ? intval($_POST['comunidad']) : null;
    $direccion_exacta = !empty($_POST['direccion_exacta']) ? $_POST['direccion_exacta'] : '';
    $utm_norte = !empty($_POST['utm_norte']) ? $_POST['utm_norte'] : '';
    $utm_este = !empty($_POST['utm_este']) ? $_POST['utm_este'] : '';
    
    $stmt_ubicacion->bind_param("iiisss", 
        $id_municipio, 
        $id_parroquia, 
        $id_comunidad, 
        $direccion_exacta, 
        $utm_norte, 
        $utm_este
    );
    
    if (!$stmt_ubicacion->execute()) {
        throw new Exception("Error al crear la ubicación: " . $stmt_ubicacion->error);
    }
    
    $id_ubicacion = $conexion->insert_id;
    
    // Crear beneficiario
    $stmt_beneficiario = $conexion->prepare("
        INSERT INTO beneficiarios (
            id_ubicacion,
            cedula,
            nombre_beneficiario,
            telefono,
            cod_obra,
            metodo_constructivo,
            modelo_constructivo,
            status
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre_beneficiario'];
    $telefono = $_POST['telefono'];
    $cod_obra = intval($_POST['codigo_obra']);
    $metodo_constructivo = !empty($_POST['metodo_constructivo']) ? intval($_POST['metodo_constructivo']) : null;
    $modelo_constructivo = !empty($_POST['modelo_constructivo']) ? intval($_POST['modelo_constructivo']) : null;
    $status = $_POST['status'] ?? 'activo';
    
    $stmt_beneficiario->bind_param("isssiiis", 
        $id_ubicacion,
        $cedula,
        $nombre,
        $telefono,
        $cod_obra,
        $metodo_constructivo,
        $modelo_constructivo,
        $status
    );
    
    if (!$stmt_beneficiario->execute()) {
        throw new Exception("Error al crear el beneficiario: " . $stmt_beneficiario->error);
    }
    
    $id_beneficiario = $conexion->insert_id;
    
    // Crear datos de construcción
    $stmt_construccion = $conexion->prepare("
        INSERT INTO datos_de_construccion (id_beneficiario) VALUES (?)
    ");
    
    $stmt_construccion->bind_param("i", $id_beneficiario);
    
    if (!$stmt_construccion->execute()) {
        throw new Exception("Error al crear los datos de construcción: " . $stmt_construccion->error);
    }
    
    $conexion->commit();
    echo json_encode(['status' => 'success', 'message' => 'Beneficiario creado exitosamente']);
    
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
} 