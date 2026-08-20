<?php
require "conexion.php";
$conexion = Conexion::obtenerInstancia();
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dni = trim($_POST["dni"]);
    $nombre = trim($_POST["nombre"]);
    $password = $_POST["password"];
    $rol = $_POST["rol"] ?? "cliente";

    // Solo se permiten estos 3 valores, cualquier otra cosa se descarta
    if (!in_array($rol, ["admin", "vendedor", "cliente"])) {
        $rol = "cliente";
    }

    if ($dni === "" || $nombre === "" || $password === "") {
        $error = "Completá todos los campos.";
    } else {
        // Verificar que el dni no exista todavía
        $check = $conexion->prepare("SELECT id FROM usuarios WHERE dni = ?");
        $check->bind_param("s", $dni);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Ese DNI ya está registrado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conexion->prepare("INSERT INTO usuarios (dni, nombre, password, rol) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssss", $dni, $nombre, $hash, $rol);
            $insert->execute();

            header("Location: login.php?registrado=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background-color: #a5a4a4; }
        .caja { max-width: 400px; margin: 80px auto; background: white; padding: 30px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="caja">
        <h2 class="text-center mb-4">Registrarse</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>DNI</label>
            <input type="text" name="dni" class="form-control mb-3" required>

            <label>Nombre</label>
            <input type="text" name="nombre" class="form-control mb-3" required>

            <label>Contraseña</label>
            <input type="password" name="password" class="form-control mb-3" required>

            <label>Nivel de usuario</label>
            <select name="rol" class="form-control mb-3">
                <option value="cliente">Cliente (solo ver catálogo)</option>
                <option value="vendedor">Vendedor (carga ventas)</option>
                <option value="admin">Administrador (control total)</option>
            </select>

            <button type="submit" class="btn btn-success w-100">Registrarme</button>
        </form>

        <p class="text-center mt-3"><a href="login.php">Ya tengo cuenta</a></p>
    </div>
</body>
</html>
