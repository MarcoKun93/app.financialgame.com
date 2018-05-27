<?php

class partidaOnline {
    // Datos de la tabla "partidaOnline"
    const NOMBRE_TABLA = "partidaOnline";
    const ID_PARTIDA = "idPartidaOnline";
    const NOMBRE_ANFITRION = "nombreanfitrion";
    const ID_ANFITRION = "idAnfitrion";
    const FECHA = "fecha";
    const NUM_RONDAS = "numrondas";
    const NUM_JUGADORES = "numjugadores";
    const NUM_JUGADORES_MAX = "numjugadoresmax";
    const NOMBRE_GANADOR = "nombreganador";
    const PUNTUACION_GANADOR = "puntuacionganador";
    const ID_EVENTO = "idEvento";
    const TEMPORIZADOR = "temporizador";
    const ESTADO = "estado";

	 // COnstantes del control de errores
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

    // Estados de una partida
    const SIN_ESTADO = 0;
    const INICIALIZADA = 1;
    const INICIADA = 2;


	 // La relación PartidaOnline-JugadorOnline será creada en otra tabla, que me relaciona ids

    public static function post($peticion)
    {
        $idUsuario = usuario::autorizar();
        
        // Procesar post
        if ($peticion[0] == 'crearPartidaOnline') {
            return self::postPartidaOnline($idUsuario);
        } else if($peticion[0] == 'postEstadoPartidaOnline'){
            return self::postEstadoPartidaOnline($idUsuario);
        } else if($peticion[0] == 'postnumeroJugadores'){
            return self::postnumeroJugadores();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function get($peticion)
    {
        $idUsuario = usuario::autorizar();
        
        // Procesar get
        if ($peticion[0] == 'partidasOnline') {
            if(empty($peticion[1])) {
                return self::getPartidasOnline();
            } else {
                return self::getPartidasOnline($peticion[1]);
            }
        } else if ($peticion[0] == 'getEstadoPartidaOnline') {
            return self::getEstadoPartidaOnline($idUsuario);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }
    
    public static function delete($peticion)
    {
        $idUsuario = usuario::autorizar();
        
        if (empty($peticion[0])) {
            if (self::eliminarPartidaOnline($idUsuario) > 0) {
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

    private function getEstadoPartidaOnline($idUsuario)
    {
        try {

            $comando = "SELECT a.estado FROM partidaonline as a, jugadoronline as b, partidaonlinejugadoresonline as c 
                        WHERE a.idPartidaOnline = c.idPartidaOnline AND c.idJugadorOnline = b.idJugadorOnline AND b.idUsuario = ?";

            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia->bindParam(1, $idUsuario);

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

    private function postEstadoPartidaOnline($idUsuario) {
        $cuerpo = file_get_contents('php://input');
        $partida = json_decode($cuerpo);

        // 1º: Subir partida en el servidor
        // 2º: Imprimir respuesta
        $resultado = self::cambiarEstadoPartidaOnline($partida);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Estado partida cambiada con exito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Fallo desconocido", 400);
        }
    }

    private function cambiarEstadoPartidaOnline($partida) {
        $estado = $partida->estado;
        $idPartida = $partida->idPartidaOnline;

        try {
            $comando = "UPDATE " .
                self::NOMBRE_TABLA .
                " SET " . self::ESTADO . "=?" .
                " WHERE " . self::ID_PARTIDA . "=?";
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia->bindParam(1, $estado);
            $sentencia->bindParam(2, $idPartida);

            $sentencia->execute();

            return true;

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    // Modificamos el numero de jugadores unidos, la informacion la recibimos del cliente.
    private function postnumeroJugadores() {
        $cuerpo = file_get_contents('php://input');
        $partida = json_decode($cuerpo);

        // 1º: Subir partida en el servidor
        // 2º: Imprimir respuesta
        $resultado = self::modificarNumeroJugadores($partida);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Numero jugadores cambiado con exito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Fallo desconocido", 400);
        }
    }

    private function modificarNumeroJugadores($partida) {
        $numJugadores = $partida->numJugadores;
        $idPartida = $partida->idPartidaOnline;

        try {
            $comando = "UPDATE " .
                self::NOMBRE_TABLA .
                " SET " . self::NUM_JUGADORES . "=?" .
                " WHERE " . self::ID_PARTIDA . "=?";
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia->bindParam(1, $numJugadores);
            $sentencia->bindParam(2, $idPartida);

            $sentencia->execute();

            return true;

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    // Obtenemos las partidas creadas disponible (aquellas con estado 0 y que no esten completas)
    private function getPartidasOnline($idPartida = NULL)
    {
        try {
            if (!$idPartida) {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ESTADO . " = " . self::SIN_ESTADO .
                    " AND " . self::NUM_JUGADORES . " < " . self::NUM_JUGADORES_MAX;

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            } else {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_PARTIDA . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idContacto e idUsuario
                $sentencia->bindParam(1, $idPartida, PDO::PARAM_INT);
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

    // Si ha sido exitosa la creación de la partida, debemos crear al JugadorOnline, haciendo uso del idUsuario.
    // Tambien creamos el jugadorOnline del anfitrion.
    private function postPartidaOnline($idUsuario) {
        $cuerpo = file_get_contents('php://input');
        $partida = json_decode($cuerpo);

        // 1º: Subir partida en el servidor
        // 2º: Imprimir respuesta
        $resultado = self::crearPartidaOnline($partida, $idUsuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                jugadorOnline::inicializarJugadorOnline($idUsuario);
                
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Partida creada con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Fallo desconocido", 400);
        }
    }

    private function crearPartidaOnline($datosPartida, $idUsuario)
    {
        $nombreanfitrion = $datosPartida->nombreanfitrion;
        $fecha = $datosPartida->fecha;
        $numrondas = $datosPartida->numrondas;
        $numjugadores = $datosPartida->numjugadores;
        $numjugadoresmax = $datosPartida->numjugadoresmax;
        $nombreganador = $datosPartida->nombreganador;
        $puntuacionganador = $datosPartida->puntuacionganador;
        $idEvento = $datosPartida->idEvento;
        $temporizador = $datosPartida->temporizador;
        $estado = $datosPartida->estado;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE_ANFITRION . "," .
                self::ID_ANFITRION . "," .
                self::FECHA . "," .
                self::NUM_RONDAS . "," .
                self::NUM_JUGADORES . "," .
                self::NUM_JUGADORES_MAX . "," .
                self::NOMBRE_GANADOR . "," .
                self::PUNTUACION_GANADOR . "," .
                self::ID_EVENTO . "," .
                self::TEMPORIZADOR . "," .
                self::ESTADO . ")" .
                " VALUES(?,?,?,?,?,?,?,?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombreanfitrion);
            $sentencia->bindParam(2, $idUsuario);
            $sentencia->bindParam(3, $fecha);
            $sentencia->bindParam(4, $numrondas);
            $sentencia->bindParam(5, $numjugadores);
            $sentencia->bindParam(6, $numjugadoresmax);
            $sentencia->bindParam(7, $nombreganador);
            $sentencia->bindParam(8, $puntuacionganador);
            $sentencia->bindParam(9, $idEvento);
            $sentencia->bindParam(10, $temporizador);
            $sentencia->bindParam(11, $estado);

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

    private function eliminarPartidaOnline($idUsuario) {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_ANFITRION . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idUsuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}