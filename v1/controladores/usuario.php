<?php

require 'datos/ConexionBD.php';

class usuario
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "usuario";
    const ID_USUARIO = "idUsuario";
    const NOMBRE = "nombre";
    const CONTRASENIA = "contrasenia";
    const CORREO = "correo";
    const CLAVE_API = "claveapi";
    const ULTIMA_CONEXION = "ultimaconexion";
    const NUM_PARTIDAS_SUBIDAS = "numpartidassubidas";

    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CONEXION_ACTUALIZADA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    public static function post($peticion)
    {
        // Procesar post
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::loguear();
        } else if ($peticion[0] == 'actualizarConexion') {
            return self::postActualizarConexion();
        } else if ($peticion[0] == 'cambiarContrasenia') {
            return self::postCambiarContrasenia();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function postActualizarConexion()
    {
        $idUsuario = self::autorizar();

            $body = file_get_contents('php://input');
            $usuario = json_decode($body);
            $ultimaconexion = $usuario->ultimaconexion;

            if (self::actualizarUltimaConexion($idUsuario, $ultimaconexion) == true) {
                http_response_code(200);
                return [
                    "estado" => self::ESTADO_CONEXION_ACTUALIZADA,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "El contacto al que intentas acceder no existe", 404);
            }
    }

    private function actualizarUltimaConexion($idUsuario, $ultimaconexion)
    {
        try {
            $comando = "UPDATE " .
                self::NOMBRE_TABLA .
                " SET " . self::ULTIMA_CONEXION . "=?" .
                " WHERE " . self::ID_USUARIO . "=?";
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia->bindParam(1, $ultimaconexion);
            $sentencia->bindParam(2, $idUsuario);

            $sentencia->execute();

            return true;

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function postCambiarContrasenia() {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        $idUsuario = self::autorizar();
        // 1º: Validar campos (en nuestro servicio de momento no)
        // 2º: Crear contraseña
        // 3º: Imprimir respuesta
        $resultado = self::crearNuevaContrasenia($idUsuario, $usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Contraseña cambiada con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    private function registrar()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        // 1º: Validar campos (en nuestro servicio de momento no)
        // 2º: Crear usuario
        // 3º: Imprimir respuesta
        $resultado = self::crear($usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(201);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Registro con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    private function loguear() {
        $respuesta = array();

        $body = file_get_contents('php://input');
        $usuario = json_decode($body);

        // 1º: Autenticar
        // 2º: Obtener datos del usuario
        // 3º: Imprimir respuesta
        $correo = $usuario->correo;
        $contrasenia = $usuario->contrasenia;


        if (self::autenticar($correo, $contrasenia)) {
            $usuarioBD = self::obtenerUsuarioPorCorreo($correo);

            if ($usuarioBD != NULL) {
                http_response_code(200);
                $respuesta["nombre"] = $usuarioBD["nombre"];
                $respuesta["correo"] = $usuarioBD["correo"];
                $respuesta["claveapi"] = $usuarioBD["claveapi"];
                $respuesta["ultimaconexion"] = $usuarioBD["ultimaconexion"];
                return ["estado" => 1, "usuario" => $respuesta];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Ha ocurrido un error");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, utf8_encode("Correo o contraseña inválidos"));
        }
    }

    private function crearNuevaContrasenia($idUsuario, $datosUsuario)
    {
        $contrasenia = $datosUsuario->contrasenia;
        $contraseniaEncriptada = self::encriptarContrasenia($contrasenia);

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia UPDATE
            $comando = "UPDATE " .
                self::NOMBRE_TABLA .
                " SET " . self::CONTRASENIA. "=?" .
                " WHERE " . self::ID_USUARIO . "=?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $contraseniaEncriptada);
            $sentencia->bindParam(2, $idUsuario);
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

    private function crear($datosUsuario)
    {
        $nombre = $datosUsuario->nombre;
        $contrasenia = $datosUsuario->contrasenia;
        $contraseniaEncriptada = self::encriptarContrasenia($contrasenia);
        $correo = $datosUsuario->correo;
        $claveapi = self::generarClaveApi();

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::CONTRASENIA . "," .
                self::CLAVE_API . "," .
                self::CORREO . ")" .
                " VALUES(?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $contraseniaEncriptada);
            $sentencia->bindParam(3, $claveapi);
            $sentencia->bindParam(4, $correo);

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

    private function encriptarContrasenia($contrasenaPlana)
    {
        if ($contrasenaPlana)
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        else return null;
    }

    private function generarClaveApi()
    {
        return md5(microtime().rand());
    }

    private function autenticar($correo, $contrasenia)
    {
        $comando = "SELECT contrasenia FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";

        try {

            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia->bindParam(1, $correo);
            $sentencia->execute();

            if ($sentencia) {
                $resultado = $sentencia->fetch();

                if (self::validarContrasenia($contrasenia, $resultado['contrasenia'])) {
                    return true;
                } else return false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function validarContrasenia($contraseniaPlana, $contraseniaHash)
    {
        return password_verify($contraseniaPlana, $contraseniaHash);
    }

    private function obtenerUsuarioPorCorreo($correo)
    {
        $comando = "SELECT " .
            self::NOMBRE . "," .
            self::CONTRASENIA . "," .
            self::CORREO . "," .
            self::CLAVE_API . "," .
            self::ULTIMA_CONEXION .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia->bindParam(1, $correo);;

        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }

    // Metodos de autorizacion del usuario.
    public static function autorizar()
    {
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["authorization"])) {

            $claveapi = $cabeceras["authorization"];

            if (usuario::validarClaveApi($claveapi)) {
                return usuario::obtenerIdUsuario($claveapi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticación"));
        }
    }

    // Comprobamos valor claveapi, contaremos registros con clave igual, si mayor que cero es que existe.
    private function validarClaveApi($claveapi)
    {
        $comando = "SELECT COUNT(" . self::ID_USUARIO . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia->bindParam(1, $claveapi);
        $sentencia->execute();

        return $sentencia->fetchColumn(0) > 0;
    }

    // Devolvemos el idUsuario que corresponde a esa claveapi.
    private function obtenerIdUsuario($claveapi)
    {
        $comando = "SELECT " . self::ID_USUARIO .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia->bindParam(1, $claveapi);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado['idUsuario'];
        } else
            return null;
    }

    public static function incrementarNumPartidasSubidas($idUsuario) {
        $numPartidasActual = self::obtenerNumPartidasSubidas($idUsuario);
        $numPartidasActual++;

        $comando = "UPDATE " .
                self::NOMBRE_TABLA .
                " SET " . self::NUM_PARTIDAS_SUBIDAS . "=?" .
                " WHERE " . self::ID_USUARIO . "=?";
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia->bindParam(1, $numPartidasActual);
        $sentencia->bindParam(2, $idUsuario);

        $sentencia->execute();

        return true;
    }

    public static function obtenerNumPartidasSubidas($idUsuario) {
        $comando = "SELECT " .
                self::NUM_PARTIDAS_SUBIDAS .
                " FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_USUARIO . "=?";
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
        $sentencia->bindParam(1, $idUsuario);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado['numpartidassubidas'];
        } else
            return null;
    }
}