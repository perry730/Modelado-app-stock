const rolPanel = document.body.dataset.rol;
const esAdmin = rolPanel === "admin";
const csrfToken = document.body.dataset.csrf;
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
    const config = { ...opciones, headers: { ...(opciones.headers || {}) } };
    if ((config.method || "GET").toUpperCase() !== "GET") {
        config.headers["X-CSRF-Token"] = csrfToken;
    }
    const respuesta = await fetch(url, config);
    const datos = await respuesta.json().catch(() => ({}));
    if (respuesta.status === 401) {
        window.location.href = "login.php";
        throw new Error("Sesión finalizada");
    }
    if (!respuesta.ok) throw new Error(datos.error || "No se pudo completar la operación.");
    return datos;
}

function fechaLegible(valor) {
    if (!valor) return "—";
    const fecha = new Date(String(valor).replace(" ", "T"));
    return Number.isNaN(fecha.getTime()) ? valor : formatoFecha.format(fecha);
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
        llenarSelectoresProductos();
    } catch (error) {
        mensajeEnTabla(tbody, esAdmin ? 4 : 3, error.message, true);
    }
}

function llenarSelectoresProductos() {
    const venta = document.getElementById("ventaProducto");
    const filtro = document.getElementById("filtroProducto");
    const filtroActual = filtro.value;
    venta.replaceChildren();
    filtro.replaceChildren(new Option("Todos los productos", ""));
    productosCache.forEach((producto) => {
        filtro.appendChild(new Option(producto.nombre, producto.id));
        if (Number(producto.stock) > 0) venta.appendChild(new Option(`${producto.nombre} · stock ${producto.stock}`, producto.id));
    });
    filtro.value = filtroActual;
}

async function cargarClientes() {
    const select = document.getElementById("ventaCliente");
    select.replaceChildren(new Option("Cargando clientes...", ""));
    try {
        const clientes = await solicitar("obtener_clientes.php");
        select.replaceChildren(new Option("Seleccioná un cliente", ""));
        clientes.forEach((cliente) => {
            select.appendChild(new Option(`${cliente.apellido || ""} ${cliente.nombre} · DNI ${cliente.dni}`.trim(), cliente.id));
        });
    } catch (error) {
        select.replaceChildren(new Option("No se pudieron cargar los clientes", ""));
    }
}

function badgeEstado(estado) {
    const badge = document.createElement("span");
    const clases = { ACTIVA: "text-bg-success", MODIFICADA: "text-bg-warning", CANCELADA: "text-bg-danger" };
    badge.className = `badge ${clases[estado] || "text-bg-secondary"}`;
    badge.textContent = estado.charAt(0) + estado.slice(1).toLowerCase();
    return badge;
}

function botonesVenta(venta) {
    const contenedor = document.createElement("div");
    contenedor.className = "d-flex gap-2 flex-nowrap";
    if (venta.estado === "CANCELADA") {
        contenedor.append("—");
        return contenedor;
    }
    const modificar = document.createElement("button");
    modificar.type = "button";
    modificar.className = "btn btn-outline-primary btn-sm";
    modificar.textContent = "Cantidad";
    modificar.addEventListener("click", () => {
        document.getElementById("modificarVentaId").value = venta.id;
        document.getElementById("modificarCantidad").value = venta.cantidad;
        document.getElementById("errorModificarVenta").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalModificarVenta")).show();
    });
    const cancelar = document.createElement("button");
    cancelar.type = "button";
    cancelar.className = "btn btn-outline-danger btn-sm";
    cancelar.textContent = "Cancelar";
    cancelar.addEventListener("click", () => {
        document.getElementById("cancelarVentaId").value = venta.id;
        document.getElementById("errorCancelarVenta").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCancelarVenta")).show();
    });
    contenedor.append(modificar, cancelar);
    return contenedor;
}

function filtrosActuales() {
    return new URLSearchParams(new FormData(document.getElementById("formFiltrosVentas")));
}

async function cargarVentas(parametros = filtrosActuales()) {
    const tbody = document.getElementById("ventasBody");
    const errorFiltros = document.getElementById("errorFiltros");
    errorFiltros.textContent = "";
    mensajeEnTabla(tbody, 11, "Cargando ventas...");
    try {
        const ventas = await solicitar(`obtener_ventas.php?${parametros.toString()}`);
        tbody.replaceChildren();
        if (ventas.length === 0) {
            mensajeEnTabla(tbody, 11, "No encontramos ventas para los filtros seleccionados.");
            document.getElementById("resumenVentas").textContent = "0";
            return;
        }
        ventas.forEach((venta) => {
            const fila = document.createElement("tr");
            fila.append(celda(`#${venta.id}`, "fw-semibold"));
            fila.append(celda(fechaLegible(venta.fecha)));
            fila.append(celda(venta.cliente || "Sin cliente"));
            fila.append(celda(venta.producto_nombre));
            fila.append(celda(String(venta.cantidad)));
            fila.append(celda(formatoMoneda.format(Number(venta.precio_unitario))));
            fila.append(celda(formatoMoneda.format(Number(venta.total)), "fw-semibold"));
            fila.append(celda(venta.vendedor || "—"));
            const estadoTd = document.createElement("td");
            estadoTd.appendChild(badgeEstado(venta.estado));
            fila.appendChild(estadoTd);
            fila.append(celda(fechaLegible(venta.fecha_modificacion)));
            const acciones = document.createElement("td");
            acciones.appendChild(botonesVenta(venta));
            fila.appendChild(acciones);
            tbody.appendChild(fila);
        });
        document.getElementById("resumenVentas").textContent = ventas.length;
    } catch (error) {
        mensajeEnTabla(tbody, 11, error.message, true);
        errorFiltros.textContent = error.message;
    }
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

async function enviarCambioVenta(formulario, modalId, errorId) {
    const errorBox = document.getElementById(errorId);
    errorBox.classList.add("d-none");
    try {
        await solicitar("modificar_venta.php", { method: "POST", body: new FormData(formulario) });
        bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
        formulario.reset();
        await Promise.all([cargarProductos(), cargarVentas()]);
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove("d-none");
    }
}

document.getElementById("formFiltrosVentas").addEventListener("submit", (evento) => {
    evento.preventDefault();
    cargarVentas();
});
document.getElementById("limpiarFiltros").addEventListener("click", () => {
    document.getElementById("formFiltrosVentas").reset();
    cargarVentas(new URLSearchParams());
});
document.getElementById("formModificarVenta").addEventListener("submit", (evento) => {
    evento.preventDefault();
    enviarCambioVenta(evento.currentTarget, "modalModificarVenta", "errorModificarVenta");
});
document.getElementById("formCancelarVenta").addEventListener("submit", (evento) => {
    evento.preventDefault();
    enviarCambioVenta(evento.currentTarget, "modalCancelarVenta", "errorCancelarVenta");
});
document.getElementById("formVenta").addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const errorBox = document.getElementById("errorVenta");
    errorBox.classList.add("d-none");
    try {
        await solicitar("guardar_venta.php", { method: "POST", body: new FormData(evento.currentTarget) });
        bootstrap.Modal.getInstance(document.getElementById("modalVenta")).hide();
        evento.currentTarget.reset();
        await Promise.all([cargarProductos(), cargarVentas()]);
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove("d-none");
    }
});

if (esAdmin) {
    document.getElementById("formProducto").addEventListener("submit", async (evento) => {
        evento.preventDefault();
        const errorBox = document.getElementById("errorProducto");
        errorBox.classList.add("d-none");
        try {
            await solicitar("guardar_producto.php", { method: "POST", body: new FormData(evento.currentTarget) });
            bootstrap.Modal.getInstance(document.getElementById("modalProducto")).hide();
            evento.currentTarget.reset();
            await cargarProductos();
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove("d-none");
        }
    });
}

Promise.all([cargarProductos(), cargarClientes()]).then(() => cargarVentas());
