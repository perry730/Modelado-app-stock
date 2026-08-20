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

$resultado = $conexion->query("SELECT id, nombre, precio, stock FROM productos ORDER BY nombre");
$productos = [];

while ($fila = $resultado->fetch_assoc()) {
    $productos[] = $fila;
}

echo json_encode($productos);
?>
