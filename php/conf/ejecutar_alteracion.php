<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'root') {
    die('No autorizado');
}

try {
    // SQL para verificar si la columna ya existe
    $check_column = "SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'beneficiarios' 
                    AND COLUMN_NAME = 'fecha_protocolizacion'";
    
    $result = $conexion->query($check_column);
    
    if ($result->num_rows == 0) {
        // La columna no existe, procedemos a crearla
        $sql = "ALTER TABLE beneficiarios 
                ADD COLUMN fecha_protocolizacion DATE NULL DEFAULT NULL 
                AFTER fecha_actualizacion";
        
        if ($conexion->query($sql)) {
            echo "La columna fecha_protocolizacion ha sido agregada exitosamente.";
        } else {
            throw new Exception("Error al agregar la columna: " . $conexion->error);
        }
    } else {
        echo "La columna fecha_protocolizacion ya existe en la tabla.";
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$conexion->close();
?> 