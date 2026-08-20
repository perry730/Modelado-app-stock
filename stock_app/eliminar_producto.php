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

$id = intval($_POST["id"] ?? 0);

$stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(["success" => true]);
?>
