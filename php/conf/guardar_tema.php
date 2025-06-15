<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tema'])) {
    $tema = $_POST['tema'];
    
    // Validar que el tema sea uno de los permitidos
    $temas_permitidos = ['default', 'azul', 'verde', 'morado'];
    
    if (in_array($tema, $temas_permitidos)) {
        $_SESSION['tema'] = $tema;
        echo json_encode(['status' => 'success', 'message' => 'Tema guardado correctamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tema no válido']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida']);
} 