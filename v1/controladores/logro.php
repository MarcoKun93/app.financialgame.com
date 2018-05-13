<?php

class logro
{
    // Atributos de la tabla logro y usuariologro
    const NOMBRE_TABLA_LOGRO = "logro";
    const NOMBRE_TABLA_USUARIO_LOGRO = "usuariologro";
    const ID_USUARIO = "idUsuario";
    const ID_LOGRO = "idLogro";
    const NOMBRE = "nombre";
    const DESCRIPCION = "descripcion";
    const TIPO = "tipo";
    const VARIABLE_AFECTADA = "variableafectada";
    const VALOR = "valor";

    // Constantes de los valores de los logros
    const VALOR_MILLONARIO = 1000000;
    const VALOR_PARTIDA_LARGA = 4;

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

    // Para el caso de los logros, le tenemos que enviar ese idUsuario.

    // El usuario enviara las peticiones de servicio por cada tipo de logro registrado en el sistema.
    // En caso de que lo cumpla, se añadira la relacion idUsuario-idLogro en la tabla correspondiente, y se enviara una confirmacion del logro o no.
    public static function post($peticion)
    {
        $idUsuario = usuario::autorizar();

        // Procesar post
        if ($peticion[0] == 'logroPrimeraPartida') {
            return self::logroTuPrimeraPartida($idUsuario);
        } else if ($peticion[0] == 'logroMillonario') {
            return self::logroMillonario($idUsuario);
        } else if ($peticion[0] == 'logroPartidaLarga'){
                return self::logroPartidaLarga($idUsuario);
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    // En este caso, pedirán la lista completa, o sólo un logro en concreto (le pasamos el idLogro).
    public static function get($peticion)
    {
        $idUsuario = usuario::autorizar();

        // Procesar get
        if ($peticion[0] == 'obtenerLogros') {
            if (empty($peticion[1])) {
                return self::obtenerLogros($idUsuario);
            } else {
                return self::obtenerLogros($idUsuario, $peticion[1]);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    private function obtenerLogros($idUsuario, $idLogro = NULL)
    {
        try {
            if (!$idLogro) {
                $comando = "SELECT a.idLogro, a.nombre, a.descripcion, a.tipo, a.variableafectada, a.valor FROM logro as a, usuariologro as b 
                            WHERE b.idUsuario = ? AND b.idLogro = a.idLogro";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idContacto e idUsuario
                $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);

            } else {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA_LOGRO .
                    " WHERE " . self::ID_LOGRO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idContacto e idUsuario
                $sentencia->bindParam(1, $idLogro, PDO::PARAM_INT);
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

    /* A continuacion se muestran los distintos metodos de POST que comprueban logros. Antes se debe comprobar que no se haya obtenido ya.
     *
     * 1º: Comprobamos que no se haya cumplido ya.
     * 2º: Añadimos la relacion usuario-logro en la tabla correspondiente.
     */

    // Obtenemos los datos de interes y los pasamos por los distintos filtros.
    // idLogro = 1
    private function logroTuPrimeraPartida($idUsuario) {
        $idLogro = 1;

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA_USUARIO_LOGRO . " ( " .
                self::ID_USUARIO . "," .
                self::ID_LOGRO . ")" .
                " VALUES(?,?)";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $idUsuario);
            $sentencia->bindParam(2, $idLogro);
            $resultado = $sentencia->execute();

            if ($resultado) {
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("Logro TuPrimeraPartida conseguido!")
                    ];
            } else {
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
            }

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }

    }

    // idLogro = 2
    private function logroMillonario($idUsuario) {
        $cuerpo = file_get_contents('php://input');
        $partida = json_decode($cuerpo);
        $idLogro = 2;

        $puntuacionganador = $partida->puntuacionganador;

        try {

            if($puntuacionganador >= self::VALOR_MILLONARIO) {
                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA_USUARIO_LOGRO . " ( " .
                    self::ID_USUARIO . "," .
                    self::ID_LOGRO . ")" .
                    " VALUES(?,?)";

                $sentencia = $pdo->prepare($comando);
                $sentencia->bindParam(1, $idUsuario);
                $sentencia->bindParam(2, $idLogro);
                $resultado = $sentencia->execute();

                if ($resultado) {
                    http_response_code(201);
                    return
                        [
                            "estado" => self::ESTADO_CREACION_EXITOSA,
                            "mensaje" => utf8_encode("Logro Millonario conseguido!")
                        ];
                } else {
                    throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                }
            } else {
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_FALLIDA,
                        "mensaje" => utf8_encode("No cumple los requisitos del logro")
                    ];
            }

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    // idLogro = 3
    private function logroPartidaLarga($idUsuario) {
        $cuerpo = file_get_contents('php://input');
        $partida = json_decode($cuerpo);
        $idLogro = 3;

        $numrondas = $partida->numrondas;

        try {

            if($numrondas >= self::VALOR_PARTIDA_LARGA) {
                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA_USUARIO_LOGRO . " ( " .
                    self::ID_USUARIO . "," .
                    self::ID_LOGRO . ")" .
                    " VALUES(?,?)";

                $sentencia = $pdo->prepare($comando);
                $sentencia->bindParam(1, $idUsuario);
                $sentencia->bindParam(2, $idLogro);
                $resultado = $sentencia->execute();

                if ($resultado) {
                    http_response_code(201);
                    return
                        [
                            "estado" => self::ESTADO_CREACION_EXITOSA,
                            "mensaje" => utf8_encode("Logro PartidaLarga conseguido!")
                        ];
                } else {
                    throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                }
            } else {
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_FALLIDA,
                        "mensaje" => utf8_encode("No cumple los requisitos del logro")
                    ];
            }

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }

    }
}