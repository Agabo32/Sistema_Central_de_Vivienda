<?php
session_start();

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destruir la sesión
session_destroy();

// Limpiar cualquier cookie de sesión que pudiera quedar
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/');
}

// Redirigir al login con un mensaje de sesión cerrada
header('Location: ../../index.php?msg=sesion_cerrada');
exit();
?> 