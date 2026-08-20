<?php
function iniciarSesionAplicacion(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function tokenCsrf(): string {
    iniciarSesionAplicacion();
    if (!isset($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function responderJson(array $datos, int $estado = 200): never {
    http_response_code($estado);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function requerirUsuarioJson(array $roles = []): void {
    iniciarSesionAplicacion();
    if (!isset($_SESSION["usuario_id"])) {
        responderJson(["error" => "No autorizado"], 401);
    }
    if ($roles !== [] && !in_array($_SESSION["usuario_rol"] ?? "", $roles, true)) {
        responderJson(["error" => "No tenés permiso para esta acción"], 403);
    }
}

function requerirCsrfJson(): void {
    $recibido = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? ($_POST["csrf_token"] ?? "");
    $esperado = $_SESSION["csrf_token"] ?? "";
    if ($esperado === "" || !is_string($recibido) || !hash_equals($esperado, $recibido)) {
        responderJson(["error" => "La sesión del formulario venció. Actualizá la página."], 403);
    }
}

function fechaIsoValida(string $fecha): bool {
    $objeto = DateTimeImmutable::createFromFormat("!Y-m-d", $fecha);
    $errores = DateTimeImmutable::getLastErrors();
    return $objeto !== false && ($errores === false || ($errores["warning_count"] === 0 && $errores["error_count"] === 0))
        && $objeto->format("Y-m-d") === $fecha;
}
