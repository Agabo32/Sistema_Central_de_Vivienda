<?php
include 'conexion.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all incoming POST data
file_put_contents('debug_update.log', print_r($_POST, true), FILE_APPEND);

// Check if all required fields are present
$required_fields = ['id_beneficiario', 'nombre_beneficiario', 'cedula', 'comunidad', 'status'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if(!isset($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if(!empty($missing_fields)) {
    echo 'error: Missing fields: ' . implode(', ', $missing_fields);
    exit;
}

// Sanitize and validate input
$id = intval($_POST['id_beneficiario']);
$nombre = mysqli_real_escape_string($conexion, $_POST['nombre_beneficiario']);
$cedula = mysqli_real_escape_string($conexion, $_POST['cedula']);
$comunidad = mysqli_real_escape_string($conexion, $_POST['comunidad']);
$status = in_array($_POST['status'], ['activo', 'inactivo']) ? $_POST['status'] : 'activo';

// Start a transaction to ensure all updates happen or none
mysqli_begin_transaction($conexion);

try {
    // Update beneficiarios table with status
    $query_beneficiario = "UPDATE beneficiarios SET 
        nombre_beneficiario = '$nombre', 
        cedula = '$cedula',
        status = '$status'
        WHERE id_beneficiario = $id";
    
    // Update ubicaciones table
    $query_ubicacion = "UPDATE ubicaciones SET 
        comunidad = '$comunidad' 
        WHERE id_ubicacion = $id";

    // Execute both queries
    $result_beneficiario = mysqli_query($conexion, $query_beneficiario);
    $result_ubicacion = mysqli_query($conexion, $query_ubicacion);

    // If both queries are successful, commit the transaction
    if ($result_beneficiario && $result_ubicacion) {
        mysqli_commit($conexion);
        echo 'ok';
    } else {
        // If any query fails, rollback the transaction
        mysqli_rollback($conexion);
        
        // Log the specific MySQL errors
        $error_log = "Beneficiario Query Error: " . mysqli_error($conexion) . "\n" .
                     "Ubicacion Query Error: " . mysqli_error($conexion) . "\n" .
                     "Beneficiario Query: $query_beneficiario\n" .
                     "Ubicacion Query: $query_ubicacion\n";
        
        file_put_contents('debug_update.log', $error_log, FILE_APPEND);
        
        echo 'error: No se pudieron actualizar todos los datos';
    }
} catch (Exception $e) {
    // Rollback the transaction in case of any error
    mysqli_rollback($conexion);
    
    file_put_contents('debug_update.log', "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo 'error: Excepción durante la actualización';
}

// Close the connection
mysqli_close($conexion);
?>