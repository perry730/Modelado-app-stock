<?php
require "seguridad.php";
require "conexion.php";
requerirUsuarioJson(["admin"]);
requerirCsrfJson();
$conexion = Conexion::obtenerInstancia();

$id = intval($_POST["id"] ?? 0);

$stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

responderJson(["success" => true]);
?>
