<?php
require "seguridad.php";
require "conexion.php";
requerirUsuarioJson(["admin", "vendedor"]);
requerirCsrfJson();

$conexion = Conexion::obtenerInstancia();
$productoId = filter_input(INPUT_POST, "producto_id", FILTER_VALIDATE_INT) ?: 0;
$clienteId = filter_input(INPUT_POST, "cliente_id", FILTER_VALIDATE_INT) ?: 0;
$cantidad = filter_input(INPUT_POST, "cantidad", FILTER_VALIDATE_INT) ?: 0;

if ($productoId <= 0 || $clienteId <= 0 || $cantidad <= 0) {
    responderJson(["error" => "Seleccioná un producto, un cliente y una cantidad válida."], 400);
}

try {
    $conexion->begin_transaction();

    $clienteStmt = $conexion->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'cliente' FOR UPDATE");
    $clienteStmt->execute([$clienteId]);
    if (!$clienteStmt->get_result()->fetch_assoc()) {
        throw new DomainException("El cliente seleccionado no existe.");
    }

    $productoStmt = $conexion->prepare("SELECT nombre, precio, stock FROM productos WHERE id = ? FOR UPDATE");
    $productoStmt->execute([$productoId]);
    $producto = $productoStmt->get_result()->fetch_assoc();
    if (!$producto) {
        throw new DomainException("Producto no encontrado.");
    }
    if ((int) $producto["stock"] < $cantidad) {
        throw new DomainException("No hay stock suficiente. Disponible: " . $producto["stock"]);
    }

    $total = (float) $producto["precio"] * $cantidad;
    $usuarioId = (int) $_SESSION["usuario_id"];
    $insert = $conexion->prepare(
        "INSERT INTO ventas
         (producto_id, producto_nombre, cantidad, precio_unitario, total, usuario_id, cliente_id, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVA')"
    );
    $insert->bind_param("isiddii", $productoId, $producto["nombre"], $cantidad, $producto["precio"], $total, $usuarioId, $clienteId);
    $insert->execute();
    $ventaId = $conexion->insert_id;

    $update = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
    $update->execute([$cantidad, $productoId]);

    $historial = $conexion->prepare(
        "INSERT INTO venta_historial
         (venta_id, usuario_id, tipo, cantidad_nueva, total_nuevo, estado_anterior, estado_nuevo)
         VALUES (?, ?, 'VENTA_CREADA', ?, ?, 'NUEVA', 'ACTIVA')"
    );
    $historial->execute([$ventaId, $usuarioId, $cantidad, $total]);

    $conexion->commit();
    responderJson(["success" => true, "venta_id" => $ventaId], 201);
} catch (DomainException $e) {
    $conexion->rollback();
    responderJson(["error" => $e->getMessage()], 400);
} catch (Throwable $e) {
    $conexion->rollback();
    responderJson(["error" => "No se pudo registrar la venta."], 500);
}
