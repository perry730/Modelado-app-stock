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

if (!in_array($_SESSION["usuario_rol"], ["admin", "vendedor"])) {
    http_response_code(403);
    echo json_encode(["error" => "No tenés permiso para esta acción"]);
    exit;
}

$sql = "SELECT v.id, v.producto_nombre, v.cantidad, v.precio_unitario, v.total, v.fecha, u.nombre AS vendedor
        FROM ventas v
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        ORDER BY v.fecha DESC";

$resultado = $conexion->query($sql);
$ventas = [];

while ($fila = $resultado->fetch_assoc()) {
    $ventas[] = $fila;
}

echo json_encode($ventas);
?>
