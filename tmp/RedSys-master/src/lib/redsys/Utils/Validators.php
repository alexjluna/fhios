<?php

namespace Drupal\module_name\lib\redsys\Utils;

/**
 * Clase Validators.
 *
 * Contiene funciones de validación.
 */
class Validators {

  /**
   * Función isEmpty().
   *
   * Comprueba si una cadena está vacía.
   *
   * @param string $value
   *   Cadena a comprobar.
   *
   * @return bool
   *   TRUE si está vacía, sino FALSE.
   */
  public static function isEmpty(string $value) {
    return '' === trim($value);
  }

  /**
   * Función isValidLangcode().
   *
   * Comprueba que el idioma seleccionado sea uno válido.
   *
   * @param string $langcode
   *   Código del lenguaje.
   *
   * @return bool
   *   TRUE si se trata de un código válido.
   */
  public static function isValidLangcode(string $langcode) {
    $value = intval($langcode);
    return (($value > 0) and ($value < 14)) ? TRUE : FALSE;
  }

  /**
   * Función isValidUrl().
   *
   * Verifica si la url tiene un formato válido.
   *
   * @param string $url
   *   Url a verificar.
   *
   * @return bool
   *   TRUE si la url es válida.
   */
  public static function isValidUrl(string $url) {
    return filter_var($url, FILTER_VALIDATE_URL);
  }

  /**
   * Función isExpiryDate().
   *
   * Comprueba si la fecha establecida es correcta.
   *
   * @param string $expirydate
   *   Su formato es AAMM, siendo AA los dos últimos dígitos del año
   *   y MM los dos dígitos del mes.
   *
   * @return bool
   *   TRUE si cumple con los parámetros de fecha válida.
   */
  public static function isExpiryDate(string $expirydate) {
    return (strlen(trim($expirydate)) == 4 && is_numeric($expirydate));
  }

  /**
   * Función isValidOrder().
   *
   * Comprueba si el número de pedido es válido..
   * Los 4 primeros dígitos deben ser numéricos, para los dígitos
   * restantes solo utilizar los siguientes caracteres ASCII:
   *   - Del 30 = 0 al 39 = 9
   *   - Del 65 = A al 90 = Z
   *   - Del 97 = a al 122 = z
   * Parámetro OBLIGATORIO.
   *
   * @param string $order
   *   Cadena con el número de pedido.
   *
   * @return bool
   *   TRUE si el valor es válido.
   */
  public static function isValidOrder(string $order) {
    return (strlen($order) >= 4 && strlen($order) <= 12 && is_numeric(substr($order, 0, 4))) ? TRUE : FALSE;
  }

  /**
   * Función validCode().
   *
   * Verifica si el código devuelto es válido.
   *
   * @param array $response
   *   Array - respuesta del servidor.
   *
   * @return bool
   *   TRUE si el código es válido.
   */
  public static function validCode(array $response) {
    $code = $response['CODIGO'];

    if (!is_numeric($code)) {
      return FALSE;
    }

    $code = $response['OPERACION']['Ds_Response'];

    if (!is_numeric($code)) {
      return FALSE;
    }

    if ($code >= 0 && $code < 100) {
      return TRUE;
    }

    if ($code == 900 || $code == 400) {
      return TRUE;
    }

    return FALSE;
  }

}
