<?php

/*
 * GET tfgserviceses.000webhostapp.com/v1/partida/partidasSubidas
 * GET tfgserviceses.000webhostapp.com/v1/partida/partidasSubidas/:id
 * GET tfgserviceses.000webhostapp.com/v1/partida/ranking
 * POST tfgserviceses.000webhostapp.com/v1/partida/subirPartida
 * PUT tfgserviceses.000webhostapp.com/v1/partida/:id
 * DELETE tfgserviceses.000webhostapp.com/v1/partida/:id
 */

class partida {
    // Datos de la tabla "partida"
    const NOMBRE_TABLA = "partida";
    const ID_PARTIDA = "idPartida";
    const FECHA = "fecha";
    const NUM_RONDAS = "numrondas";
    const NUM_JUGADORES = "numjugadores";
    const NOMBRE_GANADOR = "nombreganador";
    const PUNTUACION_GANADOR = "puntuacionganador";
    const ID_USUARIO = "idUsuario";
    const ID_SEMILLA = "idSemilla";

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

    /*
     * Con post solo creamos la partida deseada en el servidor, para ello debemos autorizar antes al usuario
     */
    public static function post($peticion)
    {
        $idUsuario = usuario::autorizar();

        // Procesar post
        if ($peticion[0] == 'subirPartida') {
            return self::subirPartida($idUsuario);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    /*
     * Hacemos varias acciones sobre el recurso partida con metodo get (1º y 2º son el mismo metodo):
     * 1º Pedimos las partidas subidas por el usuario identificado.
     * 2º Pedimos una partida en concreto subida por el usuario identificado.
     * 3º Obtenemos el ranking de partidas segun las puntuaciones de los ganadores.
     */
    public static function get($peticion)
    {
        $idUsuario = usuario::autorizar();

        // Procesar get
        if ($peticion[0] == 'partidasSubidas') {
            if(empty($peticion[1])) {
                return self::obtenerPartidasSubidas($idUsuario);
            } else {
                return self::obtenerPartidasSubidas($idUsuario, $peticion[1]);
            }
        } else if ($peticion[0] == 'ranking') {
            return self::ranking();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function delete($peticion)
    {
        $idUsuario = usuario::autorizar();

        if (!empty($peticion[0])) {
            if (self::eliminar($idUsuario, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "La partida que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    // POST, aprovechamos y si correcto, incrementamos el numero de partidas subidas por el usuario.
    private function subirPartida($idUsuario)
    {
        $cuerpo = file_get_contents('php://input');
        $partida = json_decode($cuerpo);

        // 1º: Subir partida en el servidor
        // 2º: Imprimir respuesta
        $resultado = self::crear($partida, $idUsuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                usuario::incrementarNumPartidasSubidas($idUsuario);
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Partida subida con éxito!")
                    ];  
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Fallo desconocido", 400);
        }
    }

    private function crear($datosPartida, $idUsuario)
    {
        $fecha = $datosPartida->fecha;
        $numrondas = $datosPartida->numrondas;
        $numjugadores = $datosPartida->numjugadores;
        $nombreganador = $datosPartida->nombreganador;
        $puntuacionganador = $datosPartida->puntuacionganador;
        $idSemilla = $datosPartida->idSemilla;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::FECHA . "," .
                self::NUM_RONDAS . "," .
                self::NUM_JUGADORES . "," .
                self::NOMBRE_GANADOR . "," .
                self::PUNTUACION_GANADOR . "," .
                self::ID_USUARIO . "," .
                self::ID_SEMILLA . ")" .
                " VALUES(?,?,?,?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $fecha);
            $sentencia->bindParam(2, $numrondas);
            $sentencia->bindParam(3, $numjugadores);
            $sentencia->bindParam(4, $nombreganador);
            $sentencia->bindParam(5, $puntuacionganador);
            $sentencia->bindParam(6, $idUsuario);
            $sentencia->bindParam(7, $idSemilla);

            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    // GET
    private function obtenerPartidasSubidas($idUsuario, $idPartida = NULL)
    {
        try {
            if (!$idPartida) {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idUsuario
                $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);

            } else {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_PARTIDA . "=? AND " .
                    self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idContacto e idUsuario
                $sentencia->bindParam(1, $idPartida, PDO::PARAM_INT);
                $sentencia->bindParam(2, $idUsuario, PDO::PARAM_INT);
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

    // GET. No es necesario que se le pase ningun parametro. En vez del nombre del jugador ganador, devuelve el nombre del usuario que subio la partida.
    private function ranking()
    {
        try {

            $comando = "SELECT " . self::ID_PARTIDA . ", " . self::FECHA . ", " . self::NUM_RONDAS . ", " . self::NUM_JUGADORES . ", b.nombre as nombreganador, " .
                self::PUNTUACION_GANADOR . ", " . "a." . self::ID_USUARIO . " as idUsuario " . ", " . self::ID_SEMILLA .
                " FROM " . self::NOMBRE_TABLA . " a, usuario b" .
                " WHERE a." . self::ID_USUARIO . " = b.idUsuario" .
                " ORDER BY " . self::PUNTUACION_GANADOR . " DESC" .
                " LIMIT 20";

            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

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

    private function eliminar($idUsuario, $idPartida)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_PARTIDA . "=? AND " .
                self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idPartida);
            $sentencia->bindParam(2, $idUsuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}