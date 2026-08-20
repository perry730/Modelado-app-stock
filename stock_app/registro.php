<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(401);
    echo "Acceso no autorizado.";
    exit;
}

if (($_SESSION["usuario_rol"] ?? "") !== "admin") {
    http_response_code(403);
    echo "No tenés permiso para acceder a esta página.";
    exit;
}

require "conexion.php";
$conexion = Conexion::obtenerInstancia();
$error = "";
$rolesValidos = ["admin", "vendedor", "cliente"];

if (!isset($_SESSION["csrf_crear_usuario"])) {
    $_SESSION["csrf_crear_usuario"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $dni = trim($_POST["dni"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $password = $_POST["password"] ?? "";
    $rol = $_POST["rol"] ?? "";

    if (!hash_equals($_SESSION["csrf_crear_usuario"], $token)) {
        http_response_code(400);
        $error = "La sesión del formulario venció. Actualizá la página e intentá nuevamente.";
    } elseif ($dni === "" || $nombre === "" || $apellido === "" || $password === "") {
        $error = "Completá todos los campos obligatorios.";
    } elseif (mb_strlen($dni) > 20 || mb_strlen($nombre) > 100 || mb_strlen($apellido) > 100) {
        $error = "Uno de los datos supera la longitud permitida.";
    } elseif (!in_array($rol, $rolesValidos, true)) {
        $error = "Seleccioná un rol válido.";
    } else {
        $check = $conexion->prepare("SELECT id FROM usuarios WHERE dni = ? LIMIT 1");
        $check->bind_param("s", $dni);
        $check->execute();

        if ($check->get_result()->fetch_assoc()) {
            $error = "Ya existe una cuenta con ese DNI.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conexion->prepare(
                "INSERT INTO usuarios (dni, nombre, apellido, password, rol) VALUES (?, ?, ?, ?, ?)"
            );
            $insert->bind_param("sssss", $dni, $nombre, $apellido, $hash, $rol);
            $insert->execute();

            $_SESSION["csrf_crear_usuario"] = bin2hex(random_bytes(32));
            header("Location: registro.php?creado=1");
            exit;
        }
    }
}

$resultadoUsuarios = $conexion->query(
    "SELECT id, dni, nombre, apellido, rol, fecha_registro FROM usuarios ORDER BY fecha_registro DESC, id DESC"
);
$usuarios = $resultadoUsuarios->fetch_all(MYSQLI_ASSOC);

function e(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios | Control Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/estilos.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar app-navbar sticky-top py-3">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="admin.php">
                <span class="marca-icono" aria-hidden="true">CS</span>
                <span class="fw-bold">Control Stock</span>
            </a>
            <div class="d-flex gap-2">
                <a href="admin.php" class="btn btn-outline-secondary btn-sm">Volver al panel</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-md-5">
        <div class="mb-4">
            <p class="etiqueta text-primary mb-1">Administración</p>
            <h1 class="h2 fw-bold mb-2">Gestión de usuarios</h1>
            <p class="texto-secundario mb-0">Creá cuentas y asigná el nivel de acceso correspondiente.</p>
        </div>

        <?php if (isset($_GET["creado"])): ?>
            <div class="alert alert-success" role="status">El usuario fue creado correctamente.</div>
        <?php endif; ?>
        <?php if ($error !== ""): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="row g-4 align-items-start">
            <div class="col-12 col-lg-5">
                <section class="seccion-card" aria-labelledby="titulo-crear-usuario">
                    <h2 id="titulo-crear-usuario" class="h5 fw-bold mb-1">Crear usuario</h2>
                    <p class="texto-secundario small mb-4">Todos los campos son obligatorios.</p>
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_crear_usuario"]) ?>">
                        <div class="mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" id="dni" name="dni" class="form-control" inputmode="numeric" maxlength="20" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" maxlength="100" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" id="apellido" name="apellido" class="form-control" maxlength="100" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" required>
                        </div>
                        <div class="mb-4">
                            <label for="rol" class="form-label">Rol</label>
                            <select id="rol" name="rol" class="form-select" required>
                                <option value="cliente">Cliente</option>
                                <option value="vendedor">Vendedor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">+ Crear usuario</button>
                    </form>
                </section>
            </div>

            <div class="col-12 col-lg-7">
                <section class="seccion-card" aria-labelledby="titulo-listado-usuarios">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 id="titulo-listado-usuarios" class="h5 fw-bold mb-0">Usuarios existentes</h2>
                        <span class="badge text-bg-light border"><?= count($usuarios) ?> usuarios</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Usuario</th><th>DNI</th><th>Rol</th></tr></thead>
                            <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($usuario["nombre"], 0, 1))) ?></span>
                                            <div>
                                                <div class="fw-semibold"><?= e(trim($usuario["nombre"] . " " . ($usuario["apellido"] ?? ""))) ?></div>
                                                <?php if ($usuario["apellido"] === null): ?><small class="text-warning-emphasis">Apellido pendiente</small><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($usuario["dni"]) ?></td>
                                    <td><span class="badge text-bg-primary"><?= e(ucfirst($usuario["rol"])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
