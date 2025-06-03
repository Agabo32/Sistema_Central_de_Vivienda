<?php
session_start();

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
function verificar_autenticacion(): void {
    if (!esta_autenticado()) {
        header('Location: /Sistema_Central_de_Vivienda-main/index.php');
        exit;
    }
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