const rolPanel = document.body.dataset.rol;
const esAdmin = rolPanel === "admin";
const formatoMoneda = new Intl.NumberFormat("es-AR", { style: "currency", currency: "ARS" });
const formatoFecha = new Intl.DateTimeFormat("es-AR", { dateStyle: "short", timeStyle: "short" });
let productosCache = [];

function celda(texto, clase = "") {
    const td = document.createElement("td");
    td.className = clase;
    td.textContent = texto;
    return td;
}

function mensajeEnTabla(tbody, columnas, mensaje, esError = false) {
    tbody.replaceChildren();
    const fila = document.createElement("tr");
    const td = celda(mensaje, `empty-state ${esError ? "text-danger" : ""}`);
    td.colSpan = columnas;
    fila.appendChild(td);
    tbody.appendChild(fila);
}

async function solicitar(url, opciones = {}) {
    const respuesta = await fetch(url, opciones);
    const datos = await respuesta.json().catch(() => ({}));
    if (respuesta.status === 401) {
        window.location.href = "login.php";
        throw new Error("Sesión finalizada");
    }
    if (!respuesta.ok) throw new Error(datos.error || "No se pudo completar la operación.");
    return datos;
}

async function cargarProductos() {
    const tbody = document.getElementById("productosBody");
    mensajeEnTabla(tbody, esAdmin ? 4 : 3, "Cargando productos...");
    try {
        productosCache = await solicitar("obtener_productos.php");
        tbody.replaceChildren();
        if (productosCache.length === 0) {
            mensajeEnTabla(tbody, esAdmin ? 4 : 3, "Todavía no hay productos cargados.");
            return;
        }

        productosCache.forEach((producto) => {
            const fila = document.createElement("tr");
            fila.append(celda(producto.nombre, "fw-semibold"));
            fila.append(celda(formatoMoneda.format(Number(producto.precio))));

            const stockTd = document.createElement("td");
            const stock = Number(producto.stock);
            const badge = document.createElement("span");
            badge.className = `estado-stock ${stock <= 0 ? "agotado" : ""}`;
            badge.textContent = stock > 0 ? `${stock} disponibles` : "Sin stock";
            stockTd.appendChild(badge);
            fila.appendChild(stockTd);

            if (esAdmin) {
                const accion = document.createElement("td");
                accion.className = "text-end";
                const boton = document.createElement("button");
                boton.type = "button";
                boton.className = "btn btn-outline-danger btn-sm";
                boton.textContent = "Eliminar";
                boton.addEventListener("click", () => eliminarProducto(producto.id, producto.nombre));
                accion.appendChild(boton);
                fila.appendChild(accion);
            }
            tbody.appendChild(fila);
        });

        document.getElementById("resumenProductos").textContent = productosCache.length;
        document.getElementById("resumenStock").textContent = productosCache.reduce((total, p) => total + Number(p.stock), 0);
        llenarSelectorProductos();
    } catch (error) {
        mensajeEnTabla(tbody, esAdmin ? 4 : 3, error.message, true);
    }
}

async function cargarVentas() {
    const tbody = document.getElementById("ventasBody");
    mensajeEnTabla(tbody, 5, "Cargando ventas...");
    try {
        const ventas = await solicitar("obtener_ventas.php");
        tbody.replaceChildren();
        if (ventas.length === 0) {
            mensajeEnTabla(tbody, 5, "Todavía no hay ventas registradas.");
            return;
        }
        ventas.forEach((venta) => {
            const fila = document.createElement("tr");
            fila.append(celda(venta.producto_nombre, "fw-semibold"));
            fila.append(celda(String(venta.cantidad)));
            fila.append(celda(formatoMoneda.format(Number(venta.total))));
            fila.append(celda(venta.vendedor || "-"));
            const fecha = new Date(String(venta.fecha).replace(" ", "T"));
            fila.append(celda(Number.isNaN(fecha.getTime()) ? venta.fecha : formatoFecha.format(fecha)));
            tbody.appendChild(fila);
        });
        document.getElementById("resumenVentas").textContent = ventas.length;
    } catch (error) {
        mensajeEnTabla(tbody, 5, error.message, true);
    }
}

function llenarSelectorProductos() {
    const select = document.getElementById("ventaProducto");
    select.replaceChildren();
    productosCache.filter((p) => Number(p.stock) > 0).forEach((producto) => {
        const option = document.createElement("option");
        option.value = producto.id;
        option.textContent = `${producto.nombre} · stock ${producto.stock}`;
        select.appendChild(option);
    });
}

async function eliminarProducto(id, nombre) {
    if (!window.confirm(`¿Eliminar “${nombre}”?`)) return;
    const datos = new FormData();
    datos.append("id", id);
    try {
        await solicitar("eliminar_producto.php", { method: "POST", body: datos });
        await cargarProductos();
    } catch (error) {
        window.alert(error.message);
    }
}

document.getElementById("formVenta").addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const datos = new FormData(evento.currentTarget);
    try {
        await solicitar("guardar_venta.php", { method: "POST", body: datos });
        bootstrap.Modal.getInstance(document.getElementById("modalVenta")).hide();
        evento.currentTarget.reset();
        await Promise.all([cargarProductos(), cargarVentas()]);
    } catch (error) {
        document.getElementById("errorVenta").textContent = error.message;
        document.getElementById("errorVenta").classList.remove("d-none");
    }
});

if (esAdmin) {
    document.getElementById("formProducto").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        const datos = new FormData(evento.currentTarget);
        try {
            await solicitar("guardar_producto.php", { method: "POST", body: datos });
            bootstrap.Modal.getInstance(document.getElementById("modalProducto")).hide();
            evento.currentTarget.reset();
            await cargarProductos();
        } catch (error) {
            document.getElementById("errorProducto").textContent = error.message;
            document.getElementById("errorProducto").classList.remove("d-none");
        }
    });
}

Promise.all([cargarProductos(), cargarVentas()]);
