<?php
session_start();
error_log(print_r($_SESSION, true));
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['autorizado' => false]);
    exit;
}

echo json_encode([
    'autorizado' => ($_SESSION['rol'] === 'admin'),
    'usuario' => $_SESSION['user']['nombre_usuario']
]);
exit;
?>