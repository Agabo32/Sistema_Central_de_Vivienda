<?php
include 'conexion.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure log directory exists
$log_dir = dirname(__FILE__) . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Detailed logging function
function detailed_log($message) {
    $log_file = dirname(__FILE__) . '/logs/delete_beneficiario.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Log incoming POST data
detailed_log("Incoming POST data: " . print_r($_POST, true));

// Check if ID is set and valid
if(!isset($_POST['id_beneficiario']) || !is_numeric($_POST['id_beneficiario'])) {
    detailed_log("Error: Invalid or missing ID. Received: " . print_r($_POST, true));
    echo 'error: ID inválido';
    exit;
}

// Sanitize the ID
$id = intval($_POST['id_beneficiario']);
detailed_log("Sanitized ID: $id");



// Start a transaction to ensure data integrity
mysqli_begin_transaction($conexion);

try {
    // First, check if the record exists
    $query_beneficiario = "DELETE    FROM beneficiarios WHERE id_beneficiario = $id";
    $result_beneficiario = mysqli_query($conexion, $query_beneficiario);
    detailed_log("Beneficiarios Query: $query_beneficiario");
    detailed_log("Beneficiarios Result: " . ($result_beneficiario ? 'Success' : 'Failure'));
    
    if (mysqli_num_rows($result_beneficiario) == 0) {
        detailed_log("Error: No beneficiario found with ID $id");
        mysqli_rollback($conexion);
        echo 'error: Beneficiario no encontrado';
        exit;
    }

    // First, delete from ubicaciones table
    $query_ubicacion = "DELETE FROM ubicaciones WHERE id_beneficiario = $id";
    $result_ubicacion = mysqli_query($conexion, $query_ubicacion);
    detailed_log("Ubicaciones Delete Query: $query_ubicacion");
    detailed_log("Ubicaciones Delete Result: " . ($result_ubicacion ? 'Success' : 'Failure'));
    
    // Then, delete from beneficiarios table
    $query_beneficiario = "DELETE FROM beneficiarios WHERE id_beneficiario = $id";
    $result_beneficiario = mysqli_query($conexion, $query_beneficiario);
    detailed_log("Beneficiarios Delete Query: $query_beneficiario");
    detailed_log("Beneficiarios Delete Result: " . ($result_beneficiario ? 'Success' : 'Failure'));

    // Check if both deletions were successful
    if ($result_ubicacion && $result_beneficiario) {
        // Commit the transaction
        mysqli_commit($conexion);
        detailed_log("Transaction committed successfully");
        echo 'ok';
    } else {
        // Rollback the transaction if any deletion fails
        mysqli_rollback($conexion);
        
        // Log specific errors
        $error_ubicacion = mysqli_error($conexion);
        $error_beneficiario = mysqli_error($conexion);
        
        detailed_log("Ubicacion Delete Error: $error_ubicacion");
        detailed_log("Beneficiario Delete Error: $error_beneficiario");
        
        echo 'error: No se pudo eliminar el beneficiario';
    }
} catch (Exception $e) {
    // Rollback the transaction in case of any error
    mysqli_rollback($conexion);
    
    // Log the exception
    detailed_log("Exception: " . $e->getMessage());
    echo 'error: Excepción durante la eliminación';
}

// Close the database connection
mysqli_close($conexion);
?>
