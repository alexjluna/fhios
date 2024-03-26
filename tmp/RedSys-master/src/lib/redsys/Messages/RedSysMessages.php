<?php

namespace Drupal\module_name\lib\redsys\Messages;

/**
 * Clase RedSysMessages.
 *
 * Contiene funcionalidades que permiten identificar el código de
 * error enviado por el banco.
 */
class RedSysMessages {

  /**
   * Almacena el contenido de los archivos con los datos de mensajes.
   *
   * @var array
   */
  private static $messages = [];

  /**
   * Función getAll().
   *
   * Devuelve todos los datos de mensajes de la carpeta "data".
   *
   * @return array
   *   Array con los datos de todos los archivos.
   */
  public static function getAll() {
    return self::load();
  }

  /**
   * Función getByCode().
   *
   * Obtiene toda la información de un código.
   *
   * @param string $code
   *   Código a obtener la información.
   *
   * @return array|null
   *   Array con los datos almacenados sobre el código dado.
   */
  public static function getByCode($code) {
    self::load();

    if (preg_match('/^[0-9]+$/', $code)) {
      $code = (int) $code;
    }

    if (isset(self::$messages[$code])) {
      return self::$messages[$code];
    }
  }

  /**
   * Función load().
   *
   * Carga todos los datos de mensajes de la carpeta "data".
   *
   * @return array
   *   Array con los datos de todos los archivos.
   */
  private static function load() {
    if (self::$messages) {
      return self::$messages;
    }

    foreach (glob(__DIR__ . '/data/*.inc') as $file) {
      self::$messages += require $file;
    }

    return self::$messages;
  }

}
