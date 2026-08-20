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

$producto_id = intval($_POST["producto_id"] ?? 0);
$cantidad = intval($_POST["cantidad"] ?? 0);

if ($producto_id <= 0 || $cantidad <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}

// Traer el producto para saber precio y stock disponible
$stmt = $conexion->prepare("SELECT nombre, precio, stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();

if (!$producto) {
    http_response_code(404);
    echo json_encode(["error" => "Producto no encontrado"]);
    exit;
}

if ($producto["stock"] < $cantidad) {
    http_response_code(400);
    echo json_encode(["error" => "No hay stock suficiente. Disponible: " . $producto["stock"]]);
    exit;
}

$total = $producto["precio"] * $cantidad;
$usuario_id = $_SESSION["usuario_id"];

// Registrar la venta
$insert = $conexion->prepare("INSERT INTO ventas (producto_id, producto_nombre, cantidad, precio_unitario, total, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
$insert->bind_param("isiddi", $producto_id, $producto["nombre"], $cantidad, $producto["precio"], $total, $usuario_id);
$insert->execute();

// Descontar del stock
$update = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
$update->bind_param("ii", $cantidad, $producto_id);
$update->execute();

echo json_encode(["success" => true]);
?>
