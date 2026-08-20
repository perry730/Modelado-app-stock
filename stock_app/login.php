<?php
session_start();
require "conexion.php";
$conexion = Conexion::obtenerInstancia();
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dni = trim($_POST["dni"]);
    $password = $_POST["password"];

    $stmt = $conexion->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE dni = ?");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if (password_verify($password, $usuario["password"])) {
            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["usuario_nombre"] = $usuario["nombre"];
            $_SESSION["usuario_rol"] = $usuario["rol"];

            header("Location: stock.php");
            exit;
        } else {
            $error = "DNI o contraseña incorrectos.";
        }
    } else {
        $error = "DNI o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background-color: #a5a4a4; }
        .caja { max-width: 400px; margin: 80px auto; background: white; padding: 30px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="caja">
        <h2 class="text-center mb-4">Iniciar sesión</h2>

        <?php if (isset($_GET["registrado"])): ?>
            <div class="alert alert-success">Cuenta creada. Ya podés iniciar sesión.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>DNI</label>
            <input type="text" name="dni" class="form-control mb-3" required>

            <label>Contraseña</label>
            <input type="password" name="password" class="form-control mb-3" required>

            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>

        <p class="text-center mt-3"><a href="registro.php">Crear cuenta nueva</a></p>
    </div>
</body>
</html>
