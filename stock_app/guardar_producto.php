<?php
session_start();
require "conexion.php";
$conexion = Conexion::obtenerInstancia();
header("Content-Type: application/json");

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

if ($_SESSION["usuario_rol"] !== "admin") {
    http_response_code(403);
    echo json_encode(["error" => "Solo el administrador puede hacer esto"]);
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$precio = floatval($_POST["precio"] ?? 0);
$stock = intval($_POST["stock"] ?? 0);

if ($nombre === "" || $precio <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}

$stmt = $conexion->prepare("INSERT INTO productos (nombre, precio, stock) VALUES (?, ?, ?)");
$stmt->bind_param("sdi", $nombre, $precio, $stock);
$stmt->execute();

echo json_encode(["success" => true]);
?>
