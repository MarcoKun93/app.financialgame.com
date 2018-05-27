<?php

class jugadorOnline
{
    // Datos de la tabla "jugadoronline"
    const NOMBRE_TABLA = "jugadoronline";
    const ID_JUGADOR_ONLINE = "idJugadorOnline";
    const ID_USUARIO = "idUsuario";
    const PUNTUACION = "puntuacion";
    // Ponemos la cantidad de cada tipo de producto financiero
    const NUM_ACCIONES_EUROPEAS = "numAccionesEuropeas";
    const NUM_ACCIONES_AMERICANAS = "numAccionesAmericanas";
    const NUM_ACCIONES_ASIATICAS = "numAccionesAsiaticas";
    const NUM_BONOS_DEL_ESTADO = "numBonosDelEstado";
    const NUM_PLAN_DE__PENSIONES = "numPlanDePensiones";
    const LIQUIDEZ = "liquidez";
    const FINANCIACION = "financiacion";
    // Ponemos el estado del usuario
    const IS_ANFITRION = "isAnfitrion";	// Boolean, para saber si es anfitrion, interesante por si sale de la partida.
    const ESTADO = "estado";	// Entera, para conocer el estado de un jugador
    
    // Estados de un jugador    
    const ESPERANDO = 1;
    const INTRODUCIENDO_DATOS = 2;
    const SIN_ESTADO = 0;
    
    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CONEXION_ACTUALIZADA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;


    /*
        Los distintos servicios ofrecidos seran:
        - post jugadorOnline
        - post puntuaciones
        - get jugadorOnline
        - get claveApi
        - get puntuaciones
        - delete jugadorOnline
    */
    // DA ERROR DE BBDD CON LA CONEXION!!

    public static function post($peticion)
    {
        $idUsuario = usuario::autorizar();

        // Procesar post
        if ($peticion[0] == 'jugadorOnline') {
            return self::postJugadorOnline($idUsuario);
        //} else if ($peticion[0] == 'puntuaciones') {
        //    return self::postPuntuaciones($idUsuario);
        } else if ($peticion[0] == 'postEstadoJugadorOnline') {
            return self::postEstadoJugadorOnline();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

	 public static function get($peticion)
    {
        $idUsuario = usuario::autorizar();

        // Procesar get
        if ($peticion[0] == 'jugadoresOnline') {
            return self::getJugadoresOnline($idUsuario);
        //} else if ($peticion[0] == 'puntuaciones') {
        //    return self::getPuntuaciones($idUsuario);
        } else if ($peticion[0] == 'idsOnline') {
            return self::getIdsOnline($idUsuario);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

   public static function delete($peticion)
   {
       $idUsuario = usuario::autorizar();

       if (empty($peticion[0])) {
           if (self::eliminar($idUsuario)) {
               http_response_code(200);
               return [
                   "estado" => self::ESTADO_EXITO,
                   "mensaje" => "Registro eliminado correctamente"
               ];
           } else {
               throw new ExcepcionApi(self::ESTADO_ERROR,
                   "El jugador que intentas acceder no existe", 404);
           }
       } else {
           throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Fallo", 422);
       }
   }

    private function postJugadorOnline($idUsuario)
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        // 1º: Validar campos (en nuestro servicio de momento no)
        // 2º: Crear usuario
        // 3º: Imprimir respuesta
        $resultado = self::crear($idUsuario, $usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Creado jugadorOnline con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    private function crear($idUsuario, $datosJugadorOnline)
    {
        $isAnfitrion = $datosJugadorOnline->isAnfitrion;
        $estado = $datosJugadorOnline->estado;
        $idPartidaOnline = $datosJugadorOnline->idPartidaOnline; // Lo necesitamos para luego unir el jugador a la partida

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::ID_USUARIO . ", puntuacion, numAccionesEuropeas, numAccionesAmericanas, numAccionesAsiaticas, numBonosDelEstado, numPlanDePensiones, liquidez , financiacion, " .
                self::IS_ANFITRION . "," .
                self::ESTADO . ")" .
                " VALUES(?,0,0,0,0,0,0,0,0,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $idUsuario);
            $sentencia->bindParam(2, $isAnfitrion);
            $sentencia->bindParam(3, $estado);
            $resultado = $sentencia->execute();

            if ($resultado) {
                // Llamamaos a un metodo que me modifique la tabla que me relaciona PartidaOnline con JugadoresOnline
                partidaonlinejugadoresonline::unirPartidaJugador($idUsuario, $idPartidaOnline);
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    // Modifica el estado del jugador online
    private function postEstadoJugadorOnline() {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        // 1º: Validar campos (en nuestro servicio de momento no)
        // 2º: Crear usuario
        // 3º: Imprimir respuesta
        $resultado = self::modificarEstadoJugadorOnline($usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Modificado estado jugadorOnline con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    private function modificarEstadoJugadorOnline($usuario) {
        $estado = $usuario->estado;
        $idJugadorOnline = $usuario->idJugadorOnline;

        try {
            $comando = "UPDATE " .
                self::NOMBRE_TABLA .
                " SET " . self::ESTADO . "=?" .
                " WHERE " . self::ID_JUGADOR_ONLINE . "=?";
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia->bindParam(1, $estado);
            $sentencia->bindParam(2, $idJugadorOnline);

            $sentencia->execute();

            return true;

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    // Devuelve los identificadores online del usuario
    private function getIdsOnline($idUsuario) {
        try {

            $comando = "SELECT jugadoronline.idJugadorOnline, idPartidaOnline FROM partidaonlinejugadoresonline, jugadoronline WHERE jugadoronline.idUsuario = ? 
            AND jugadoronline.idJugadorOnline = partidaonlinejugadoresonline.idJugadorOnline;";

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

    // Es publica porque puede ser llamada desde procesos internos del sistema.
    // Funcion muy parecida a crear pero esta vez llamado dentro del sistema.
    // Si hemos tenido exito,
    public static function inicializarJugadorOnline($idUsuario)
    {
        $isAnfitrion = 1;
        $estado = 0;

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::ID_USUARIO . ", puntuacion, numAccionesEuropeas, numAccionesAmericanas, numAccionesAsiaticas, numBonosDelEstado, numPlanDePensiones, liquidez , financiacion, " .
                self::IS_ANFITRION . "," .
                self::ESTADO . ")" .
                " VALUES(?,0,0,0,0,0,0,0,0,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $idUsuario);
            $sentencia->bindParam(2, $isAnfitrion);
            $sentencia->bindParam(3, $estado);
            $resultado = $sentencia->execute();

            if ($resultado) {
                // Llamamaos a un metodo que me modifique la tabla que me relaciona PartidaOnline con JugadoresOnline
                partidaonlinejugadoresonline::unirPartidaJugador($idUsuario);
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
/*
    private function postPuntuaciones($idUsuario)
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        // 1º: Validar campos (en nuestro servicio de momento no)
        // 2º: Crear usuario
        // 3º: Imprimir respuesta
        $resultado = self::insertarPuntuaciones($idUsuario, $usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Puntuaciones insertadas con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    // El valor totald e la puntuacion lo recibo ya actualizado.
    private function insertarPuntuaciones($idUsuario, $datosJugadorOnline)
    {
        $puntuacion = $datosJugadorOnline->puntuacion;
        $numAccionesEuropeas = $datosJugadorOnline->numAccionesEuropeas;
        $numAccionesAmericanas = $datosJugadorOnline->numAccionesAmericanas;
        $numAccionesAsiaticas = $datosJugadorOnline->numAccionesAsiaticas;
        $numBonosDelEstado = $datosJugadorOnline->numBonosDelEstado;
        $numPlanDePensiones = $datosJugadorOnline->numPlanDePensiones;
        $liquidez = $datosJugadorOnline->liquidez;
        $financiacion = $datosJugadorOnline->financiacion;

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
                self::PUNTUACION . "=? , " .
                self::NUM_ACCIONES_EUROPEAS . "=? , " .
                self::NUM_ACCIONES_AMERICANAS . "=? , " .
                self::NUM_ACCIONES_ASIATICAS . "=? , " .
                self::NUM_BONOS_DEL_ESTADO . "=? , " .
                self::NUM_PLAN_DE__PENSIONES . "=? , " .
                self::LIQUIDEZ . "=? , " .
                self::FINANCIACION . "=?" .
                " WHERE " . self::ID_USUARIO . "=?";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $puntuacion);
            $sentencia->bindParam(2, $numAccionesEuropeas);
            $sentencia->bindParam(3, $numAccionesAmericanas);
            $sentencia->bindParam(4, $numAccionesAsiaticas);
            $sentencia->bindParam(5, $numBonosDelEstado);
            $sentencia->bindParam(6, $numPlanDePensiones);
            $sentencia->bindParam(7, $liquidez);
            $sentencia->bindParam(8, $financiacion);
            $sentencia->bindParam(9, $idUsuario);
            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }*/

    private function getJugadoresOnline($idUsuario)
    {
        try {
            $comando = "SELECT DISTINCT jugadoronline.idJugadorOnline, usuario.nombre as nombreUsuario, puntuacion, numAccionesEuropeas, numAccionesAmericanas, numAccionesAsiaticas,
                         numBonosDelEstado, numPlanDePensiones, liquidez, financiacion, isAnfitrion, estado
            FROM jugadoronline, partidaonlinejugadoresonline, usuario WHERE partidaonlinejugadoresonline.idPartidaOnline = (SELECT idPartidaOnline 
            FROM partidaonlinejugadoresonline, jugadoronline WHERE partidaonlinejugadoresonline.idJugadorOnline = jugadoronline.idJugadorOnline AND jugadoronline.idUsuario = ?) 
            AND partidaonlinejugadoresonline.idJugadorOnline = jugadoronline.idJugadorOnline AND jugadoronline.idUsuario = usuario.idUsuario;";

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
/*
    private function getPuntuaciones($idUsuario)
    {
        try {

            $comando = "SELECT " . self::PUNTUACION . "," .
                self::NUM_ACCIONES_EUROPEAS . "," .
                self::NUM_ACCIONES_AMERICANAS . "," .
                self::NUM_ACCIONES_ASIATICAS . "," .
                self::NUM_BONOS_DEL_ESTADO . "," .
                self::NUM_PLAN_DE__PENSIONES . "," .
                self::LIQUIDEZ . "," .
                self::FINANCIACION . " FROM " .
                self::NOMBRE_TABLA .
                " WHERE " . self::ID_USUARIO . " = ?";

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
*/
    private function eliminar($idUsuario)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_USUARIO . "=?";

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