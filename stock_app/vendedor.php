<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION["usuario_rol"] ?? "") !== "vendedor") {
    http_response_code(403);
    echo "No tenés permiso para acceder al panel de ventas.";
    exit;
}
$nombreCompleto = trim(($_SESSION["usuario_nombre"] ?? "Vendedor") . " " . ($_SESSION["usuario_apellido"] ?? ""));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de ventas | Control Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/estilos.css" rel="stylesheet">
</head>
<body data-rol="vendedor">
    <nav class="navbar navbar-expand-lg app-navbar sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#inicio"><span class="marca-icono" aria-hidden="true">CS</span><span class="fw-bold">Control Stock</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuVendedor" aria-controls="menuVendedor" aria-expanded="false" aria-label="Abrir menú"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="menuVendedor">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-1 pt-3 pt-lg-0">
                    <a class="nav-link nav-link-app active" href="#inicio">Inicio</a>
                    <a class="nav-link nav-link-app" href="#productos">Productos</a>
                    <a class="nav-link nav-link-app" href="#ventas">Ventas</a>
                    <button class="btn btn-primary btn-sm ms-lg-2" type="button" data-bs-toggle="modal" data-bs-target="#modalVenta">Registrar venta</button>
                    <a class="btn btn-outline-danger btn-sm ms-lg-1" href="logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-md-5">
        <section id="inicio" class="hero-panel p-4 p-md-5 mb-4">
            <div class="hero-contenido">
                <p class="etiqueta text-white-50 mb-2">Panel de ventas</p>
                <h1 class="display-6 fw-bold mb-2">Hola, <?= htmlspecialchars($nombreCompleto, ENT_QUOTES, "UTF-8") ?></h1>
                <p class="lead text-white-50 mb-0">Consultá el inventario y registrá las operaciones del día.</p>
            </div>
        </section>

        <section class="row g-3 mb-5" aria-label="Resumen de ventas">
            <div class="col-12 col-sm-4"><div class="stat-card"><span class="texto-secundario small">Productos</span><div id="resumenProductos" class="stat-valor">—</div></div></div>
            <div class="col-12 col-sm-4"><div class="stat-card"><span class="texto-secundario small">Unidades en stock</span><div id="resumenStock" class="stat-valor">—</div></div></div>
            <div class="col-12 col-sm-4"><div class="stat-card"><span class="texto-secundario small">Ventas registradas</span><div id="resumenVentas" class="stat-valor">—</div></div></div>
        </section>

        <section id="productos" class="seccion-card mb-4" aria-labelledby="titulo-productos">
            <div class="mb-3"><p class="etiqueta text-primary mb-1">Inventario</p><h2 id="titulo-productos" class="h4 fw-bold mb-0">Productos disponibles</h2></div>
            <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Producto</th><th>Precio</th><th>Stock</th></tr></thead><tbody id="productosBody"></tbody></table></div>
        </section>

        <section id="ventas" class="seccion-card" aria-labelledby="titulo-ventas">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3"><div><p class="etiqueta text-primary mb-1">Actividad</p><h2 id="titulo-ventas" class="h4 fw-bold mb-0">Historial de ventas</h2></div><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalVenta">+ Registrar venta</button></div>
            <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Producto</th><th>Cantidad</th><th>Total</th><th>Vendedor</th><th>Fecha</th></tr></thead><tbody id="ventasBody"></tbody></table></div>
        </section>
    </main>

    <div class="modal fade" id="modalVenta" tabindex="-1" aria-labelledby="tituloModalVenta" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="formVenta"><div class="modal-header"><h2 id="tituloModalVenta" class="modal-title fs-5">Registrar venta</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div id="errorVenta" class="alert alert-danger d-none"></div><div class="mb-3"><label class="form-label" for="ventaProducto">Producto</label><select class="form-select" id="ventaProducto" name="producto_id" required></select></div><div><label class="form-label" for="ventaCantidad">Cantidad</label><input class="form-control" id="ventaCantidad" name="cantidad" type="number" min="1" required></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Registrar venta</button></div></form></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/panel.js"></script>
</body>
</html>
