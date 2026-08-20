<?php
//Patron singleton
class Conexion {
    private static ?Conexion $instancia = null;
    private mysqli $mysqli;

    private function __construct() {
        $host = "localhost";
        $usuario = "root";
        $clave = "";
        $basedatos = "control_stock";

        $this->mysqli = new mysqli($host, $usuario, $clave, $basedatos);

        if ($this->mysqli->connect_error) {
            die("Error de conexion: " . $this->mysqli->connect_error);
        }

        $this->mysqli->set_charset("utf8mb4");
    }

    public static function obtenerInstancia(): mysqli {
        if (self::$instancia === null) {
            self::$instancia = new Conexion();
        }
        return self::$instancia->mysqli;
    }
}
?>
