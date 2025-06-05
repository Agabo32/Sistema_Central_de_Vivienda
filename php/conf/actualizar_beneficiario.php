<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

// Habilitar el reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar la sesión y el rol
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'root') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Obtener y validar el ID del beneficiario
$id_beneficiario = isset($_POST['id_beneficiario']) ? intval($_POST['id_beneficiario']) : 0;
if ($id_beneficiario <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de beneficiario inválido']);
    exit;
}

try {
    // Iniciar transacción
    $conexion->begin_transaction();

    // Actualizar tabla beneficiarios
    $sql_beneficiarios = "UPDATE beneficiarios SET 
        cedula = ?,
        nombre_beneficiario = ?,
        telefono = ?,
        status = ?,
        id_cod_obra = ?,
        id_metodo_constructivo = ?,
        id_modelo_constructivo = ?,
        id_fiscalizador = ?,
        fecha_actualizacion = NOW()
        WHERE id_beneficiario = ?";

    $stmt = $conexion->prepare($sql_beneficiarios);
    $stmt->bind_param("ssssiiiis", 
        $_POST['cedula'],
        $_POST['nombre_beneficiario'],
        $_POST['telefono'],
        $_POST['status'],
        $_POST['codigo_obra'],
        $_POST['metodo_constructivo'],
        $_POST['modelo_constructivo'],
        $_POST['id_fiscalizador'],
        $id_beneficiario
    );
    $stmt->execute();

    // Actualizar tabla datos_de_construccion
    $sql_construccion = "INSERT INTO datos_de_construccion (
        id_beneficiario, limpieza, replanteo, excavacion, 
        acero_vigas_riostra, encofrado_malla, instalaciones_electricas_sanitarias,
        vaciado_losa_anclajes, armado_columnas, vaciado_columnas,
        armado_vigas, vaciado_vigas, bloqueado, colocacion_correas,
        colocacion_techo, colocacion_ventanas, colocacion_puertas_principales,
        instalaciones_electricas_sanitarias_paredes, frisos, sobrepiso,
        ceramica_bano, colocacion_puertas_internas, equipos_accesorios_electricos,
        equipos_accesorios_sanitarios, colocacion_lavaplatos, pintura,
        avance_fisico, fecha_culminacion, fecha_protocolizacion, acta_entregada,
        observaciones_responsables_control, observaciones_fiscalizadores,
        fundacion, estructura, cerramiento
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        limpieza = VALUES(limpieza),
        replanteo = VALUES(replanteo),
        excavacion = VALUES(excavacion),
        acero_vigas_riostra = VALUES(acero_vigas_riostra),
        encofrado_malla = VALUES(encofrado_malla),
        instalaciones_electricas_sanitarias = VALUES(instalaciones_electricas_sanitarias),
        vaciado_losa_anclajes = VALUES(vaciado_losa_anclajes),
        armado_columnas = VALUES(armado_columnas),
        vaciado_columnas = VALUES(vaciado_columnas),
        armado_vigas = VALUES(armado_vigas),
        vaciado_vigas = VALUES(vaciado_vigas),
        bloqueado = VALUES(bloqueado),
        colocacion_correas = VALUES(colocacion_correas),
        colocacion_techo = VALUES(colocacion_techo),
        colocacion_ventanas = VALUES(colocacion_ventanas),
        colocacion_puertas_principales = VALUES(colocacion_puertas_principales),
        instalaciones_electricas_sanitarias_paredes = VALUES(instalaciones_electricas_sanitarias_paredes),
        frisos = VALUES(frisos),
        sobrepiso = VALUES(sobrepiso),
        ceramica_bano = VALUES(ceramica_bano),
        colocacion_puertas_internas = VALUES(colocacion_puertas_internas),
        equipos_accesorios_electricos = VALUES(equipos_accesorios_electricos),
        equipos_accesorios_sanitarios = VALUES(equipos_accesorios_sanitarios),
        colocacion_lavaplatos = VALUES(colocacion_lavaplatos),
        pintura = VALUES(pintura),
        avance_fisico = VALUES(avance_fisico),
        fecha_culminacion = VALUES(fecha_culminacion),
        fecha_protocolizacion = VALUES(fecha_protocolizacion),
        acta_entregada = VALUES(acta_entregada),
        observaciones_responsables_control = VALUES(observaciones_responsables_control),
        observaciones_fiscalizadores = VALUES(observaciones_fiscalizadores),
        fundacion = VALUES(fundacion),
        estructura = VALUES(estructura),
        cerramiento = VALUES(cerramiento)";

    $stmt = $conexion->prepare($sql_construccion);
    
    // Convertir valores vacíos a 0 para campos numéricos
    $campos_numericos = [
        'limpieza', 'replanteo', 'excavacion', 'acero_vigas_riostra',
        'encofrado_malla', 'instalaciones_electricas_sanitarias',
        'vaciado_losa_anclajes', 'armado_columnas', 'vaciado_columnas',
        'armado_vigas', 'vaciado_vigas', 'bloqueado', 'colocacion_correas',
        'colocacion_techo', 'colocacion_ventanas', 'colocacion_puertas_principales',
        'instalaciones_electricas_sanitarias_paredes', 'frisos', 'sobrepiso',
        'ceramica_bano', 'colocacion_puertas_internas', 'equipos_accesorios_electricos',
        'equipos_accesorios_sanitarios', 'colocacion_lavaplatos', 'pintura',
        'avance_fisico', 'fundacion', 'estructura', 'cerramiento'
    ];

    foreach ($campos_numericos as $campo) {
        $_POST[$campo] = empty($_POST[$campo]) ? 0 : floatval($_POST[$campo]);
    }

    $stmt->bind_param("iddddddddddddddddddddddddddssisssdd",
        $id_beneficiario,
        $_POST['limpieza'],
        $_POST['replanteo'],
        $_POST['excavacion'],
        $_POST['acero_vigas_riostra'],
        $_POST['encofrado_malla'],
        $_POST['instalaciones_electricas_sanitarias'],
        $_POST['vaciado_losa_anclajes'],
        $_POST['armado_columnas'],
        $_POST['vaciado_columnas'],
        $_POST['armado_vigas'],
        $_POST['vaciado_vigas'],
        $_POST['bloqueado'],
        $_POST['colocacion_correas'],
        $_POST['colocacion_techo'],
        $_POST['colocacion_ventanas'],
        $_POST['colocacion_puertas_principales'],
        $_POST['instalaciones_electricas_sanitarias_paredes'],
        $_POST['frisos'],
        $_POST['sobrepiso'],
        $_POST['ceramica_bano'],
        $_POST['colocacion_puertas_internas'],
        $_POST['equipos_accesorios_electricos'],
        $_POST['equipos_accesorios_sanitarios'],
        $_POST['colocacion_lavaplatos'],
        $_POST['pintura'],
        $_POST['avance_fisico'],
        $_POST['fecha_culminacion'],
        $_POST['fecha_protocolizacion'],
        $_POST['acta_entregada'],
        $_POST['observaciones_responsables_control'],
        $_POST['observaciones_fiscalizadores'],
        $_POST['fundacion'],
        $_POST['estructura'],
        $_POST['cerramiento']
    );
    $stmt->execute();

    // Confirmar la transacción
    $conexion->commit();
    
    echo json_encode(['success' => true, 'message' => 'Beneficiario actualizado correctamente']);

} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conexion->rollback();
    echo json_encode(['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()]);
}

// Cerrar la conexión
$conexion->close();
?>