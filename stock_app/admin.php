<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION["usuario_rol"] ?? "") !== "admin") {
    http_response_code(403);
    echo "No tenés permiso para acceder al panel administrativo.";
    exit;
}
require "conexion.php";
require "seguridad.php";
$conexion = Conexion::obtenerInstancia();
$cantidadUsuarios = (int) $conexion->query("SELECT COUNT(*) AS total FROM usuarios")->fetch_assoc()["total"];
$nombreCompleto = trim(($_SESSION["usuario_nombre"] ?? "Administrador") . " " . ($_SESSION["usuario_apellido"] ?? ""));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administrativo | Control Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/estilos.css" rel="stylesheet">
</head>
<body data-rol="admin" data-csrf="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, "UTF-8") ?>">
    <nav class="navbar navbar-expand-lg app-navbar sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#inicio"><span class="marca-icono" aria-hidden="true">CS</span><span class="fw-bold">Control Stock</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuAdmin" aria-controls="menuAdmin" aria-expanded="false" aria-label="Abrir menú"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="menuAdmin">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-1 pt-3 pt-lg-0">
                    <a class="nav-link nav-link-app active" href="#inicio">Inicio</a>
                    <a class="nav-link nav-link-app" href="#productos">Productos</a>
                    <a class="nav-link nav-link-app" href="#ventas">Ventas</a>
                    <a class="nav-link nav-link-app" href="registro.php">Usuarios</a>
                    <a class="btn btn-outline-danger btn-sm ms-lg-2" href="logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-md-5">
        <section id="inicio" class="hero-panel p-4 p-md-5 mb-4">
            <div class="hero-contenido">
                <p class="etiqueta text-white-50 mb-2">Panel administrativo</p>
                <h1 class="display-6 fw-bold mb-2">Hola, <?= htmlspecialchars($nombreCompleto, ENT_QUOTES, "UTF-8") ?></h1>
                <p class="lead text-white-50 mb-0">Controlá productos, ventas y accesos desde un mismo lugar.</p>
            </div>
        </section>

        <section id="resumen" class="row g-3 mb-5" aria-label="Resumen general">
            <div class="col-12 col-sm-4"><div class="stat-card"><span class="texto-secundario small">Productos</span><div id="resumenProductos" class="stat-valor">—</div></div></div>
            <div class="col-12 col-sm-4"><div class="stat-card"><span class="texto-secundario small">Unidades en stock</span><div id="resumenStock" class="stat-valor">—</div></div></div>
            <div class="col-12 col-sm-4"><div class="stat-card"><span class="texto-secundario small">Usuarios</span><div class="stat-valor"><?= $cantidadUsuarios ?></div></div></div>
        </section>

        <section id="productos" class="seccion-card mb-4" aria-labelledby="titulo-productos">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3">
                <div><p class="etiqueta text-primary mb-1">Inventario</p><h2 id="titulo-productos" class="h4 fw-bold mb-0">Productos</h2></div>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalProducto">+ Agregar producto</button>
            </div>
            <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th class="text-end">Acción</th></tr></thead><tbody id="productosBody"></tbody></table></div>
        </section>

        <section id="ventas" class="seccion-card" aria-labelledby="titulo-ventas">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3">
                <div><p class="etiqueta text-primary mb-1">Actividad</p><h2 id="titulo-ventas" class="h4 fw-bold mb-0">Ventas <span id="resumenVentas" class="badge text-bg-light border ms-1">—</span></h2></div>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalVenta">+ Registrar venta</button>
            </div>
            <form id="formFiltrosVentas" class="row g-3 align-items-end mb-4">
                <div class="col-12 col-sm-6 col-lg-3"><label for="filtroDesde" class="form-label">Desde</label><input type="date" id="filtroDesde" name="desde" class="form-control"></div>
                <div class="col-12 col-sm-6 col-lg-3"><label for="filtroHasta" class="form-label">Hasta</label><input type="date" id="filtroHasta" name="hasta" class="form-control"></div>
                <div class="col-12 col-sm-6 col-lg-3"><label for="filtroProducto" class="form-label">Producto</label><select id="filtroProducto" name="producto_id" class="form-select"><option value="">Todos los productos</option></select></div>
                <div class="col-12 col-sm-6 col-lg-3"><label for="filtroEstado" class="form-label">Estado</label><select id="filtroEstado" name="estado" class="form-select"><option value="">Todos</option><option value="ACTIVA">Activa</option><option value="MODIFICADA">Modificada</option><option value="CANCELADA">Cancelada</option></select></div>
                <div class="col-12 d-flex flex-wrap gap-2"><button type="submit" class="btn btn-primary">Filtrar</button><button type="button" id="limpiarFiltros" class="btn btn-outline-secondary">Limpiar filtros</button><span id="errorFiltros" class="text-danger small align-self-center"></span></div>
            </form>
            <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>ID</th><th>Fecha</th><th>Cliente</th><th>Producto</th><th>Cantidad</th><th>Precio unit.</th><th>Total</th><th>Vendedor</th><th>Estado</th><th>Última modificación</th><th>Acciones</th></tr></thead><tbody id="ventasBody"></tbody></table></div>
        </section>
    </main>

    <div class="modal fade" id="modalProducto" tabindex="-1" aria-labelledby="tituloModalProducto" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="formProducto"><div class="modal-header"><h2 id="tituloModalProducto" class="modal-title fs-5">Agregar producto</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div id="errorProducto" class="alert alert-danger d-none"></div><div class="mb-3"><label class="form-label" for="productoNombre">Nombre</label><input class="form-control" id="productoNombre" name="nombre" maxlength="150" required></div><div class="row g-3"><div class="col-6"><label class="form-label" for="productoPrecio">Precio</label><input class="form-control" id="productoPrecio" name="precio" type="number" min="0.01" step="0.01" required></div><div class="col-6"><label class="form-label" for="productoStock">Stock</label><input class="form-control" id="productoStock" name="stock" type="number" min="0" required></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar producto</button></div></form></div></div></div>

    <div class="modal fade" id="modalVenta" tabindex="-1" aria-labelledby="tituloModalVenta" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="formVenta"><div class="modal-header"><h2 id="tituloModalVenta" class="modal-title fs-5">Registrar venta</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div id="errorVenta" class="alert alert-danger d-none"></div><div class="mb-3"><label class="form-label" for="ventaCliente">Cliente</label><select class="form-select" id="ventaCliente" name="cliente_id" required></select></div><div class="mb-3"><label class="form-label" for="ventaProducto">Producto</label><select class="form-select" id="ventaProducto" name="producto_id" required></select></div><div><label class="form-label" for="ventaCantidad">Cantidad</label><input class="form-control" id="ventaCantidad" name="cantidad" type="number" min="1" required></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Registrar venta</button></div></form></div></div></div>

    <div class="modal fade" id="modalModificarVenta" tabindex="-1" aria-labelledby="tituloModificarVenta" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="formModificarVenta"><div class="modal-header"><h2 id="tituloModificarVenta" class="modal-title fs-5">Modificar cantidad</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div id="errorModificarVenta" class="alert alert-danger d-none"></div><input type="hidden" name="venta_id" id="modificarVentaId"><input type="hidden" name="accion" value="modificar_cantidad"><label for="modificarCantidad" class="form-label">Nueva cantidad</label><input type="number" min="1" id="modificarCantidad" name="cantidad" class="form-control" required><label for="motivoModificacion" class="form-label mt-3">Motivo (opcional)</label><textarea id="motivoModificacion" name="motivo" class="form-control" maxlength="500" rows="3"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button><button type="submit" class="btn btn-primary">Guardar cambio</button></div></form></div></div></div>

    <div class="modal fade" id="modalCancelarVenta" tabindex="-1" aria-labelledby="tituloCancelarVenta" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="formCancelarVenta"><div class="modal-header"><h2 id="tituloCancelarVenta" class="modal-title fs-5">Cancelar venta</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div id="errorCancelarVenta" class="alert alert-danger d-none"></div><input type="hidden" name="venta_id" id="cancelarVentaId"><input type="hidden" name="accion" value="cancelar"><p class="texto-secundario">La venta permanecerá en el historial y el stock será devuelto una sola vez.</p><label for="motivoCancelacion" class="form-label">Motivo (opcional)</label><textarea id="motivoCancelacion" name="motivo" class="form-control" maxlength="500" rows="3"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button><button type="submit" class="btn btn-danger">Confirmar cancelación</button></div></form></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/panel.js"></script>
</body>
</html>
