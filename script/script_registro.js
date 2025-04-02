$(document).ready(function() {
    $("#registroForm").submit(function(event) {
        event.preventDefault(); // Evita que la página se recargue al enviar el formulario

        // Aquí puedes agregar la lógica para enviar los datos al servidor si lo necesitas

        // Muestra la alerta de registro exitoso
        Swal.fire({
            icon: "success",
            title: "Registro Exitoso",
            text: "Tu cuenta ha sido creada correctamente",
            confirmButtonText: "Aceptar"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href ="../index.html"; // Redirige al inicio de sesión
            }
        });
    });
});

const showPwd = () => {
    let input = document.querySelector("contraseña")
    if (input.type === "password") {
        input.type = "text"
    }
    else {
        input.type = "password"
    }   
}

document.addEventListener("DOMContentLoaded", function () {
    const togglePwd = document.getElementById("button");
    const inputPwd = document.getElementById("contraseña");

    if (togglePwd && inputPwd) {
        togglePwd.addEventListener("click", function () {
            if (inputPwd.type === "password") {
                inputPwd.type = "text";
                togglePwd.textContent = "Ocultar Contraseña"; // Cambia el texto del botón
            } else {
                inputPwd.type = "password";
                togglePwd.textContent = "Mostrar Contraseña"; // Cambia el texto del botón
            }
        });
    }
});