<?php

namespace Drupal\module_name\lib\redsys\Utils;

/**
 * Clase Utils.
 *
 * Contiene funciones de encriptado y conversión.
 */
class Utils {

  /**
   * Función arrayToJson().
   *
   * Convierte un Array a formato Json.
   *
   * @param array $data
   *   Array a convertir.
   *
   * @return string
   *   Cadena en formato Json.
   */
  public static function arrayToJson(array $data) {
    return json_encode($data);
  }

  /**
   * Función jsonToArray().
   *
   * Convierte una cadena Json en array.
   *
   * @param string $data
   *   Cadena en formato Json.
   *
   * @return array
   *   Array con los datos del Json.
   */
  public static function jsonToArray(string $data) {
    return json_decode($data, TRUE);
  }

  /**
   * Función xmlToArray().
   *
   * Convierte una cadena XML en array.
   *
   * @param string $xml
   *   Cadena a convertir en array.
   *
   * @return array
   *   Array de la cadena pasada como parámetro.
   */
  public static function xmlToArray(string $xml) {
    $xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
    $json = json_encode($xml);
    $response = json_decode($json, TRUE);
    return $response;
  }

  /* ***************************************************************************
   *
   * - FUNCIONES DE ENCRIPTADO DE DATOS.
   *
   * **************************************************************************/

  /**
   * Función hmac256().
   *
   * Genera encriptación en sha256.
   *
   * @param string $data
   *   Datos a encriptar.
   * @param string $key
   *   Clave pública del comercio.
   *
   * @return string
   *   Cadena encriptada en sha256.
   */
  public static function hmac256(string $data, string $key) {
    return hash_hmac('sha256', $data, $key, TRUE);
  }

  /**
   * Función encrypt3des().
   *
   * Genera encriptación en 3DES.
   *
   * @param string $data
   *   Datos a encriptar.
   * @param string $key
   *   Clave pública del comercio.
   *
   * @return string
   *   Cadena encriptada en 3DES.
   */
  public static function encrypt3des(string $data, string $key) {
    $iv = "\0\0\0\0\0\0\0\0";
    $data_padded = $data;

    if (strlen($data_padded) % 8) {
      $data_padded = str_pad($data_padded, strlen($data_padded) + 8 - strlen($data_padded) % 8, "\0");
    }

    return openssl_encrypt($data_padded, "DES-EDE3-CBC", $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
  }

  /**
   * Función base64UrlEncode().
   *
   * Codifica una URL en base64.
   *
   * @param string $input
   *   Cadena a encriptar.
   *
   * @return string
   *   Cadena encriptada.
   */
  public static function base64UrlEncode(string $input) {
    return strtr(base64_encode($input), '+/', '-_');
  }

  /**
   * Función base64UrlDecode().
   *
   * Decodifica una URL encriptada en base64.
   *
   * @param string $input
   *   Cadena encriptada.
   *
   * @return string
   *   Cadena sin encriptación.
   */
  public static function base64UrlDecode(string $input) {
    return base64_decode(strtr($input, '-_', '+/'));
  }

  /**
   * Función encodeBase64().
   *
   * Codifica una cadena en base64.
   *
   * @param string $data
   *   Cadena a encriptar.
   *
   * @return string
   *   Cadena encriptada.
   */
  public static function encodeBase64(string $data) {
    return base64_encode($data);
  }

  /**
   * Función decodeBase64().
   *
   * Decodifica una cadena encriptada en base64.
   *
   * @param string $data
   *   Cadena encriptada.
   *
   * @return string
   *   Cadena sin encriptación.
   */
  public static function decodeBase64(string $data) {
    return base64_decode($data);
  }

  /**
   * Función decodeParameters().
   *
   * Decodifica los parámetros recibidos.
   *
   * @param string $data
   *   Cadena codificada con los parámetros.
   *
   * @return bool|string
   *   Parámetros decodificados.
   */
  public static function decodeParameters(string $data) {
    return base64_decode(strtr($data, '-_', '+/'));
  }

}
