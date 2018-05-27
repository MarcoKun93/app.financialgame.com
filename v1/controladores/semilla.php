<?php

class semilla
{
    // Atributos de la tabla semilla
    const NOMBRE_TABLA = "semilla";
    const ID_SEMILLA = "idSemilla";
    const TITULO = "titulo";
    const DESCRIPCION = "descripcion";
    const EFECTO = "efecto";
    const VALOR_BONOS = "valorbonos";
    const VALOR_PENSIONES = "valorpensiones";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;

// Para el caso de la semilla, lo usuarios sólo podrán hacer get.
// En este caso, pedirán la lista completa, o sólo una semilla en concreto (le pasamos el idSemilla)
    public static function get($peticion)
    {
        $idUsuario = usuario::autorizar();

        // Procesar get
        if ($peticion[0] == 'obtenerSemillas') {
            if (empty($peticion[1])) {
                return self::obtenerSemillas();
            } else {
                return self::obtenerSemillas($peticion[1]);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    // En nuestro sistema. la semilla con id 0 es la semilla vacia.
    private function obtenerSemillas($idSemilla = NULL) {
        try {
            if (!$idSemilla) {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                            " WHERE " .self::ID_SEMILLA ." != 0";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            } else {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_SEMILLA . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idContacto e idUsuario
                $sentencia->bindParam(1, $idSemilla, PDO::PARAM_INT);
            }

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}