<?php

require 'vistas/VistaJson.php';
require 'utilidades/ExcepcionApi.php';
require 'controladores/usuario.php';
require 'controladores/partida.php';
require 'controladores/semilla.php';
require 'controladores/logro.php';
require 'controladores/partidaOnline.php';
require 'controladores/jugadorOnline.php';
require 'controladores/partidaonlinejugadoresonline.php';

// Constantes de estado
const ESTADO_URL_INCORRECTA = 2;
const ESTADO_EXISTENCIA_RECURSO = 3;
const ESTADO_METODO_NO_PERMITIDO = 4;

// Manejador de excepciones de nuestra API
$vista = new VistaJson();
set_exception_handler(function ($exception) use ($vista) {
    $cuerpo = array(
        "estado" => $exception->estado,
        "mensaje" => $exception->getMessage()
    );
    if ($exception->getCode()) {
        $vista->estado = $exception->getCode();
    } else {
        $vista->estado = 500;
    }
    $vista->imprimir($cuerpo);
}
);

// Extraer segmento de la url
if (isset($_GET['PATH_INFO']))
    $peticion = explode('/', $_GET['PATH_INFO']);
else
    throw new ExcepcionApi(ESTADO_URL_INCORRECTA, utf8_encode("No se reconoce la petición"));

// Obtener recurso
$recurso = array_shift($peticion);
$recursos_existentes = array('partida', 'usuario', 'semilla', 'logro', 'partidaonline', 'jugadoronline');

// Comprobar si existe el recurso
if (!in_array($recurso, $recursos_existentes)) {
    // Respuesta error
    throw new ExcepcionApi(ESTADO_EXISTENCIA_RECURSO, "No se reconoce el recurso al que intentas acceder");
}

$metodo = strtolower($_SERVER['REQUEST_METHOD']);

switch ($metodo) {
    case 'get':
        // Procesar método get, antes comprobamos el recurso
        if($recurso == 'usuario') {

        } else if($recurso == 'partida') {
            $vista->imprimir(partida::get($peticion));
        } else if($recurso == 'logro') {
            $vista->imprimir(logro::get($peticion));
        } else if($recurso == 'partidaonline') {
            $vista->imprimir(partidaOnline::get($peticion));
        } else if($recurso == 'jugadoronline'){
            $vista->imprimir(jugadorOnline::get($peticion));
        } else {
            $vista->imprimir(semilla::get($peticion));
        }
        break;

    case 'post':
        // Procesar método post, antes comprobamos el recurso
        if($recurso == 'usuario') {
            $vista->imprimir(usuario::post($peticion));
        } else if($recurso == 'partida'){
            $vista->imprimir(partida::post($peticion));
        } else if($recurso == 'partidaonline'){
            $vista->imprimir(partidaOnline::post($peticion));
        } else if($recurso == 'jugadoronline'){
            $vista->imprimir(jugadorOnline::post($peticion));
        } else {
            $vista->imprimir(logro::post($peticion));
        }
        break;

    case 'put':
        // Procesar método put
        break;

    case 'delete':
        // Procesar método delete
        if($recurso == 'partida') {
            $vista->imprimir(partida::delete($peticion));
        } else if($recurso == 'jugadoronline') {
            $vista->imprimir(jugadorOnline::delete($peticion));
        } else if($recurso == 'partidaonline') {
            $vista->imprimir(partidaOnline::delete($peticion));
        }
        break;

    default:
        // Método no aceptado
        $vista->estado = 405;
        $cuerpo = [
            "estado" => ESTADO_METODO_NO_PERMITIDO,
            "mensaje" => utf8_encode("Método no permitido")
        ];
        $vista->imprimir($cuerpo);
}