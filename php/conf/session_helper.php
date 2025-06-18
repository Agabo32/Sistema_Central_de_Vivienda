<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica si el usuario está autenticado
 * @return bool
 */
function esta_autenticado(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id_usuario']);
}

/**
 * Verifica si el usuario es administrador
 * @return bool
 */
function es_admin(): bool {
    return esta_autenticado() && isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'admin';
}

/**
 * Obtiene los datos del usuario actual
 * @return array|null
 */
function obtener_usuario_actual(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Verifica la autenticación y redirige si no está autenticado
 */
function verificar_autenticacion() {
    // Verificar si la sesión está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id_usuario'])) {
        // Guardar la URL actual para redireccionar después del login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirigir al login
        header('Location: ../../index.php?error=sesion_expirada');
        exit();
    }

    // Verificar si la sesión ha expirado (30 minutos)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Limpiar la sesión
        session_unset();
        session_destroy();
        
        // Redirigir al login
        header('Location: ../../index.php?error=sesion_expirada');
        exit();
    }

    // Actualizar el tiempo de última actividad
    $_SESSION['last_activity'] = time();
}

/**
 * Verifica si es administrador y redirige si no lo es
 */
function verificar_admin(): void {
    verificar_autenticacion();
    if (!es_admin()) {
        header('Location: /Sistema_Central_de_Vivienda-main/php/menu_principal.php');
        exit;
    }
}

// Función para verificar si el usuario tiene un rol específico
function verificar_rol($roles_permitidos) {
    if (!isset($_SESSION['user']['rol']) || !in_array($_SESSION['user']['rol'], $roles_permitidos)) {
        header('Location: ../../index.php?error=acceso_denegado');
        exit();
    }
} 