<?php
require_once '../php/conf/conexion.php';

// Mostrar mensajes de éxito/error
$mensaje = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        Beneficiario agregado correctamente
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
} elseif (isset($_GET['error']) && $_GET['error'] == 1) {
    $mensaje = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        Error al agregar el beneficiario
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

$sql = "SELECT * FROM beneficiarios";
$resultado = $conexion->query($sql);
?>

<?php
require_once 'conf/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $cedula = $_POST['cedula'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $codigo_obra = $_POST['codigo_obra'] ?? '';

    // Validar datos (puedes agregar más validaciones)
    if (empty($cedula) || empty($nombre) || empty($telefono) || empty($codigo_obra)) {
        die("Todos los campos son obligatorios");
    }

    // Preparar la consulta SQL
    $sql = "INSERT INTO beneficiarios (cedula, nombre_beneficiario, telefono, codigo_obra) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", $cedula, $nombre, $telefono, $codigo_obra);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Redirigir de vuelta a la página de beneficiarios con mensaje de éxito
        header("Location: ../php/beneficiarios.php?success=1");
        exit();
    } else {
        // Redirigir con mensaje de error
        header("Location: ../php/beneficiarios.php?error=1");
        exit();
    }
} else {
    // Si no es POST, redirigir
    header("Location: ../php/beneficiarios.php");
    exit();
}
?>