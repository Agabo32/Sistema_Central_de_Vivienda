<?php
$host = 'localhost';
$dbname = 'nueva';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener todos los usuarios
    $usuarios = $pdo->query("SELECT id_usuario, password FROM usuario")->fetchAll();

    foreach ($usuarios as $usuario) {
        // Si la contraseña no parece estar hasheada (menos de 50 caracteres)
        if (strlen($usuario['password']) < 50) {
            $nuevoHash = password_hash($usuario['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuario SET password = ? WHERE id_usuario = ?");
            $stmt->execute([$nuevoHash, $usuario['id_usuario']]);
            echo "Actualizado usuario ID: " . $usuario['id_usuario'] . "<br>";
        }
    }
    echo "¡Proceso completado! Ahora borra este archivo.";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>