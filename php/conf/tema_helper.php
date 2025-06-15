<?php
function obtener_tema_actual() {
    // Primero intentamos obtener el tema de la sesión
    if (isset($_SESSION['tema'])) {
        return $_SESSION['tema'];
    }
    
    // Si no hay tema en la sesión, devolvemos el tema por defecto
    return 'default';
}

function cargar_tema() {
    $tema = obtener_tema_actual();
    return "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.documentElement.setAttribute('data-tema', '$tema');
        });
    </script>";
} 