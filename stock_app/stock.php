<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

// Nivel de acceso del usuario logueado: admin, vendedor o cliente
$rol = $_SESSION["usuario_rol"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCK</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <style>
        h1 {
            color: black;
        }
    body {
        background-color:  #a5a4a4;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    table, td, th{
        border: 3px solid #0a0a0a;
        border-collapse: collapse;
    }
    table{
        width: 70%;
        margin: auto;
    }
    td, th{
        padding: 20px;
    }
</style>
</head>
<body onload="carga()">

<div class="d-flex justify-content-between align-items-center px-4 pt-3">
    <span class="text-light fw-semibold">Hola, <?= htmlspecialchars($_SESSION["usuario_nombre"]) ?> (<?= htmlspecialchars($rol) ?>)</span>
    <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
</div>

<h1 class="text-center text-light fw-bold mt-2 mb-3">STOCK</h1>

<!-- Productos -->
<h2 class="text-center text-info fw-semibold mb-4">LISTA DE PRODUCTOS</h2>

<div class="text-center">
  <div id="listadoProductos" class="d-inline-block w-75"></div>
  <?php if ($rol === "admin"): ?>
  <button onclick="addProducto()" class="btn btn-primary btn-sm mt-3 w-75">Agregar Producto</button>
  <?php endif; ?>
</div>

<br><br>

<?php if ($rol !== "cliente"): ?>
<!-- Ventas: no visible para clientes -->
<h2 class="text-center text-info fw-semibold mb-4">VENTAS</h2>

<div class="text-center">
  <div id="listadoVentas" class="d-inline-block w-75"></div>
  <button onclick="agregarVenta()" class="btn btn-primary btn-sm mt-3 w-75">Agregar Venta</button>
</div>
<?php endif; ?>


<!-- Dialog Producto -->
<dialog id="DialogoProducto">
    <p>Producto:</p>
    <input type="text" id="inputNombre">

    <p>Precio:</p>
    <input type="number" id="inputPrecio" step="0.01">

    <p>Stock:</p>
    <input type="number" id="inputStock">

    <br><br>
    <button onclick="document.getElementById('DialogoProducto').close()" class="btn btn-danger btn-sm w-100">Cancelar</button>
    <button onclick="addProducto2()" class="btn btn-success btn-sm w-100">Aceptar</button>
</dialog>

<!-- Dialog Venta -->
<dialog id="DialogoVenta">
    <p>Producto:</p>
    <select id="listaProductos"></select>

    <p>Cantidad:</p>
    <input type="number" id="cantidadInput" min="1">

    <br><br>
    <button onclick="document.getElementById('DialogoVenta').close()" class="btn btn-danger btn-sm w-100">Cancelar</button>
    <button onclick="agregarVenta2()" class="btn btn-success btn-sm w-100">Aceptar</button>
</dialog>

<script>
const rol = "<?= $_SESSION['usuario_rol'] ?>"; // admin, vendedor o cliente
let productosCache = [];

async function mostrarProductos(){
    const resp = await fetch("obtener_productos.php");
    productosCache = await resp.json();

    const esAdmin = rol === "admin";
    let tabla = "";
    for(const p of productosCache){
        tabla += `
        <tr>
            <td>${p.nombre}</td>
            <td>$${Number(p.precio).toFixed(2)}</td>
            <td>${p.stock}</td>
            ${esAdmin ? `<td><button onclick="borrarProducto(${p.id})" class="btn btn-danger btn-sm w-100">Eliminar</button></td>` : ""}
        </tr>`;
    }

    document.getElementById("listadoProductos").innerHTML = `
    <table>
        <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock</th>
            ${esAdmin ? "<th>Acción</th>" : ""}
        </tr>
        ${tabla}
    </table>`;
}

async function borrarProducto(id){
    const datos = new FormData();
    datos.append("id", id);
    await fetch("eliminar_producto.php", { method: "POST", body: datos });
    mostrarProductos();
}

function addProducto(){
    document.getElementById("DialogoProducto").showModal();
}

async function addProducto2(){
    const datos = new FormData();
    datos.append("nombre", document.getElementById("inputNombre").value);
    datos.append("precio", document.getElementById("inputPrecio").value);
    datos.append("stock", document.getElementById("inputStock").value);

    const resp = await fetch("guardar_producto.php", { method: "POST", body: datos });
    const data = await resp.json();

    if(data.error){
        alert(data.error);
        return;
    }

    document.getElementById("DialogoProducto").close();
    mostrarProductos();
}

async function mostrarVentas(){
    const resp = await fetch("obtener_ventas.php");
    const ventas = await resp.json();

    let tabla = "";
    for(const v of ventas){
        tabla += `
        <tr>
            <td>${v.producto_nombre}</td>
            <td>${v.cantidad}</td>
            <td>$${Number(v.total).toFixed(2)}</td>
            <td>${v.vendedor ?? "-"}</td>
            <td>${new Date(v.fecha).toLocaleString()}</td>
        </tr>`;
    }

    document.getElementById("listadoVentas").innerHTML = `
    <table>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Total</th>
            <th>Vendedor</th>
            <th>Fecha</th>
        </tr>
        ${tabla}
    </table>`;
}

async function agregarVenta(){
    await mostrarProductos(); // aseguramos tener el stock actualizado

    const select = document.getElementById("listaProductos");
    select.innerHTML = "";

    for(const p of productosCache){
        const op = document.createElement("option");
        op.value = p.id;
        op.textContent = `${p.nombre} (stock: ${p.stock})`;
        select.appendChild(op);
    }

    document.getElementById("DialogoVenta").showModal();
}

async function agregarVenta2(){
    const datos = new FormData();
    datos.append("producto_id", document.getElementById("listaProductos").value);
    datos.append("cantidad", document.getElementById("cantidadInput").value);

    const resp = await fetch("guardar_venta.php", { method: "POST", body: datos });
    const data = await resp.json();

    if(data.error){
        alert(data.error);
        return;
    }

    document.getElementById("DialogoVenta").close();
    mostrarProductos();
    mostrarVentas();
}

function carga(){
    mostrarProductos();
    // Los clientes no tienen la sección de ventas en la página
    if(rol !== "cliente"){
        mostrarVentas();
    }
}
</script>

</body>
</html>
