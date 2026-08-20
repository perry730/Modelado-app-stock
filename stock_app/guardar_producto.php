<?php
require "seguridad.php";
require "conexion.php";
requerirUsuarioJson(["admin"]);
requerirCsrfJson();
$conexion = Conexion::obtenerInstancia();

$nombre = trim($_POST["nombre"] ?? "");
$precio = floatval($_POST["precio"] ?? 0);
$stock = intval($_POST["stock"] ?? 0);

if ($nombre === "" || $precio <= 0) {
    responderJson(["error" => "Datos inválidos"], 400);
}

$stmt = $conexion->prepare("INSERT INTO productos (nombre, precio, stock) VALUES (?, ?, ?)");
$stmt->bind_param("sdi", $nombre, $precio, $stock);
$stmt->execute();

responderJson(["success" => true], 201);
?>
