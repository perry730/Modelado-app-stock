<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["usuario_rol"] ?? "") !== "cliente") {
    header("Location: stock.php");
    exit;
}

$nombreUsuario = trim(($_SESSION["usuario_nombre"] ?? "Cliente") . " " . ($_SESSION["usuario_apellido"] ?? ""));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Catálogo de productos disponibles">
    <title>Mi cuenta | Control Stock</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <style>
        :root {
            --color-primary: #2563eb;
            --color-primary-dark: #1d4ed8;
            --color-ink: #172033;
            --color-muted: #68738a;
            --color-surface: #ffffff;
            --color-background: #f4f7fb;
            --color-border: #e5eaf2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--color-ink);
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, .10), transparent 34rem),
                var(--color-background);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .navbar-cliente {
            background: rgba(255, 255, 255, .94);
            border-bottom: 1px solid rgba(229, 234, 242, .9);
            backdrop-filter: blur(12px);
        }

        .marca-icono {
            display: grid;
            width: 2.35rem;
            height: 2.35rem;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, var(--color-primary), #60a5fa);
            border-radius: .75rem;
            box-shadow: 0 .45rem 1rem rgba(37, 99, 235, .23);
        }

        .hero {
            overflow: hidden;
            position: relative;
            color: #fff;
            background: linear-gradient(125deg, #172554, #1d4ed8 58%, #3b82f6);
            border-radius: 1.35rem;
            box-shadow: 0 1rem 2.5rem rgba(30, 64, 175, .18);
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 18rem;
            height: 18rem;
            right: -7rem;
            bottom: -10rem;
            border: 3rem solid rgba(255, 255, 255, .08);
            border-radius: 50%;
        }

        .hero-contenido {
            position: relative;
            z-index: 1;
        }

        .hero-etiqueta {
            color: #bfdbfe;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .texto-secundario {
            color: var(--color-muted);
        }

        .producto-card {
            height: 100%;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            box-shadow: 0 .5rem 1.5rem rgba(23, 32, 51, .06);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .producto-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 .85rem 2rem rgba(23, 32, 51, .10);
        }

        .producto-icono {
            display: grid;
            width: 2.8rem;
            height: 2.8rem;
            place-items: center;
            color: var(--color-primary);
            background: #eff6ff;
            border-radius: .85rem;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .producto-precio {
            color: var(--color-primary-dark);
            font-size: 1.45rem;
            font-weight: 750;
        }

        .estado-disponible {
            color: #047857;
            background: #ecfdf5;
        }

        .estado-agotado {
            color: #b42318;
            background: #fef3f2;
        }

        .estado-stock {
            display: inline-flex;
            align-items: center;
            padding: .38rem .65rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
        }

        .estado-stock::before {
            content: "";
            width: .45rem;
            height: .45rem;
            margin-right: .42rem;
            background: currentColor;
            border-radius: 50%;
        }

        .panel-estado {
            padding: 2.75rem 1.25rem;
            text-align: center;
            background: #fff;
            border: 1px dashed #cfd7e5;
            border-radius: 1rem;
        }

        .spinner-border {
            color: var(--color-primary);
        }

        .btn-salir {
            color: #334155;
            background: #fff;
            border-color: #cbd5e1;
        }

        .btn-salir:hover,
        .btn-salir:focus {
            color: #fff;
            background: var(--color-primary-dark);
            border-color: var(--color-primary-dark);
        }

        @media (max-width: 575.98px) {
            .hero {
                border-radius: 1rem;
            }

            .hero h1 {
                font-size: 1.75rem;
            }

            .producto-card:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-sm navbar-cliente sticky-top py-3" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="#inicio">
                <span class="marca-icono" aria-hidden="true">CS</span>
                <span class="fw-bold">Control Stock</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuCliente" aria-controls="menuCliente" aria-expanded="false" aria-label="Abrir menú"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="menuCliente">
                <div class="navbar-nav ms-auto align-items-sm-center gap-sm-2 pt-3 pt-sm-0">
                    <a class="nav-link" href="#inicio">Inicio</a>
                    <a class="nav-link" href="#productos">Productos</a>
                    <a href="logout.php" class="btn btn-salir btn-sm px-3">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-md-5">
        <section id="inicio" class="hero p-4 p-md-5 mb-5" aria-labelledby="saludo-cliente">
            <div class="hero-contenido">
                <p class="hero-etiqueta mb-2">Mi cuenta</p>
                <h1 id="saludo-cliente" class="display-6 fw-bold mb-2">
                    Hola, <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, "UTF-8") ?>
                </h1>
                <p class="lead mb-0 text-white-50">Bienvenido. Consultá nuestros productos y planes disponibles.</p>
            </div>
        </section>

        <section id="productos" aria-labelledby="titulo-productos">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-2 mb-4">
                <div>
                    <p class="text-primary fw-semibold mb-1">Catálogo</p>
                    <h2 id="titulo-productos" class="h3 fw-bold mb-1">Productos disponibles</h2>
                    <p class="texto-secundario mb-0">Encontrá la opción que mejor se adapta a vos.</p>
                </div>
            </div>

            <div id="estadoCarga" class="panel-estado" role="status">
                <div class="spinner-border spinner-border-sm mb-3" aria-hidden="true"></div>
                <p class="texto-secundario mb-0">Cargando productos...</p>
            </div>

            <div id="listadoProductos" class="row g-3 g-lg-4" aria-live="polite"></div>
        </section>
    </main>

    <footer class="container pb-4 pt-2 text-center">
        <small class="texto-secundario">Control Stock · Tu catálogo siempre disponible</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const estadoCarga = document.getElementById("estadoCarga");
        const listadoProductos = document.getElementById("listadoProductos");
        const formatoMoneda = new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            minimumFractionDigits: 2
        });

        function crearTarjetaProducto(producto) {
            const columna = document.createElement("div");
            columna.className = "col-12 col-md-6 col-xl-4";

            const tarjeta = document.createElement("article");
            tarjeta.className = "producto-card p-4 d-flex flex-column";

            const encabezado = document.createElement("div");
            encabezado.className = "d-flex align-items-start justify-content-between gap-3 mb-4";

            const icono = document.createElement("span");
            icono.className = "producto-icono";
            icono.setAttribute("aria-hidden", "true");
            icono.textContent = "P";

            const stock = Number(producto.stock);
            const disponible = Number.isFinite(stock) && stock > 0;
            const estado = document.createElement("span");
            estado.className = `estado-stock ${disponible ? "estado-disponible" : "estado-agotado"}`;
            estado.textContent = disponible ? "Disponible" : "Sin stock";

            const nombre = document.createElement("h3");
            nombre.className = "h5 fw-bold mb-2";
            nombre.textContent = producto.nombre;

            const descripcion = document.createElement("p");
            descripcion.className = "texto-secundario small mb-4";
            descripcion.textContent = disponible
                ? `${stock} ${stock === 1 ? "unidad disponible" : "unidades disponibles"}`
                : "Consultá próximamente por nueva disponibilidad.";

            const precio = document.createElement("p");
            precio.className = "producto-precio mt-auto mb-0";
            precio.textContent = formatoMoneda.format(Number(producto.precio));

            encabezado.append(icono, estado);
            tarjeta.append(encabezado, nombre, descripcion, precio);
            columna.appendChild(tarjeta);

            return columna;
        }

        function mostrarMensaje(mensaje, esError = false) {
            listadoProductos.replaceChildren();
            estadoCarga.classList.remove("d-none");
            estadoCarga.setAttribute("role", esError ? "alert" : "status");
            estadoCarga.innerHTML = "";

            const titulo = document.createElement("p");
            titulo.className = `${esError ? "text-danger" : "texto-secundario"} fw-semibold mb-1`;
            titulo.textContent = mensaje;

            const detalle = document.createElement("small");
            detalle.className = "texto-secundario";
            detalle.textContent = esError
                ? "Actualizá la página para volver a intentarlo."
                : "Volvé a consultar más tarde.";

            estadoCarga.append(titulo, detalle);
        }

        async function cargarProductos() {
            try {
                const respuesta = await fetch("obtener_productos.php", {
                    headers: { "Accept": "application/json" }
                });

                if (respuesta.status === 401) {
                    window.location.href = "login.php";
                    return;
                }

                if (!respuesta.ok) {
                    throw new Error("No fue posible obtener el catálogo.");
                }

                const productos = await respuesta.json();
                estadoCarga.classList.add("d-none");
                listadoProductos.replaceChildren();

                if (!Array.isArray(productos) || productos.length === 0) {
                    mostrarMensaje("Todavía no hay productos disponibles.");
                    return;
                }

                productos.forEach((producto) => {
                    listadoProductos.appendChild(crearTarjetaProducto(producto));
                });
            } catch (error) {
                mostrarMensaje("No pudimos cargar los productos.", true);
            }
        }

        cargarProductos();
    </script>
</body>
</html>
