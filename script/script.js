document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("loginForm");
    const registerBtn = document.getElementById("registerBtn");

    if (loginForm) {
        loginForm.addEventListener("submit", function (event) {
            event.preventDefault(); // Evita que el formulario recargue la página

            let usuario = document.getElementById("usuario").value;
            let password = document.getElementById("password").value;

            if (usuario === "admin" && password === "1234") {
                window.location.href = "dashboard.html"; // Redirigir a otra página (cambia la URL según necesites)
            } else {
                alert("❌ Usuario o contraseña incorrectos");
            }
        });
    }

    if (registerBtn) {
        registerBtn.addEventListener("click", function (event) {
            event.preventDefault(); // Evita cualquier acción predeterminada
            // Redirige a la página de registro
            window.location.href = "/php/registro.html"; // Cambia esto por la URL real de la página de registro
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const togglePwd = document.getElementById("button");
    const inputPwd = document.getElementById("password");

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