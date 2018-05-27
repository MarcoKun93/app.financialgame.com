<?php

class partidaonlinejugadoresonline {

    const NOMBRE_TABLA = "partidaonlinejugadoresonline";

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

    // Metodo que relaciona los identificadores de jugadorOnline con la PartidaOnline que esta interesado.
    // Tiene dos modos de funcionamiento:
    // 1º: Cuando queremos unir a un jugador que es el anfitrion, solo hace falta su idUsuario.
    // 2º: Cuando queremos unir a un jugador cualquiera, necesitamos su idUsuario y el idPartida interesada.
    public static function unirPartidaJugador($idUsuario, $idPartidaOnline = NULL) {
        try {
            if (!$idPartidaOnline) {
                $comando = "INSERT INTO partidaonlinejugadoresonline (idPartidaOnline, idJugadorOnline) 
                            SELECT (SELECT idPartidaOnline FROM partidaonline WHERE idAnfitrion = ?), 
                            (SELECT idJugadorOnline FROM jugadoronline WHERE jugadoronline.idUsuario = ?)";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idUsuario
                $sentencia->bindParam(1, $idUsuario);
                $sentencia->bindParam(2, $idUsuario);

            } else {
                $comando = "INSERT INTO partidaonlinejugadoresonline (idPartidaOnline, idJugadorOnline) 
                            SELECT ?, (SELECT idJugadorOnline FROM jugadoronline WHERE jugadoronline.idUsuario = ?)";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idContacto e idUsuario
                $sentencia->bindParam(1, $idPartidaOnline);
                $sentencia->bindParam(2, $idUsuario);
            }

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

}