<?php

namespace Drupal\module_nameys;

use Drupal\module_nameys\Exception\RedSysException;
use Drupal\module_nameys\Messages\RedSysMessages;
use Drupal\module_nameys\Utils\Validators;
use Drupal\module_nameys\Utils\Utils;

use SoapClient;

/**
 * Clase RedSys.
 *
 * Gestiona los pagos a través de esta pasarela de pago.
 */
class RedSys {

  /* ***************************************************************************
   * - VARIABLES PROPIAS DEL TPV.
   * **************************************************************************/
  protected $signature;
  protected $version;
  protected $parameters;
  protected $environment;
  protected $environmentXml;

  /* ***************************************************************************
   * - VARIABLES / PROPIEDADES PROPIAS DEL FORMULARIO.
   * **************************************************************************/
  protected $nameForm;
  protected $idForm;
  protected $nameSubmit;
  protected $idSubmit;
  protected $valueSubmit;
  protected $styleSubmit;
  protected $classSubmit;

  /**
   * Función __construct().
   *
   * Constructor de la clase.
   *
   * @param string $use_test
   *   Si se pasa como parámetro 'test' entra en modo desarrollo.
   *   Por defecto su estado es 'live'.
   */
  public function __construct(string $use_test = 'live') {

    /* Establezco el modo 'live' o 'test' */
    $use_test ? $this->setEnvironment('test') : $this->setEnvironment('live');

    /* Inicializo los parámetros de la pasarela */
    $this->parameters = [];

    /* Parámetros por defecto para la pasarela */
    $this->setMerchantDirectPayment(FALSE);
    $this->setCurrency(978);
    $this->setTransactionType('O');
    $this->setTerminal(1);
    $this->setVersion('HMAC_SHA256_V1');
    $this->setLanguage('001');
    $this->setMethod('C');
    $this->setIdentifier('REQUIRED');

    /* Valores por defecto para el formulario */
    $this->setNameForm('redsys_form');
    $this->setIdForm('redsys_form');
    $this->setAttributesSubmit('btn_submit', 'btn_submit', 'Send', '', '');
  }

  /* ***************************************************************************
   *
   * - FUNCIONES DE ESTABLECIMIENO Y OBTENCIÓN DE PARÁMETROS.
   *
   * **************************************************************************/

  /**
   * Función setIdentifier().
   *
   * Se utiliza para indicar el DS_MERCHANT_IDENTIFIER utilizado para
   * compras recurrentes.
   * Parámetro OBLIGATORIO en pagos recurrentes.
   *
   * @param string $value
   *   Este parámetro se utilizará para manejar la referencia asociada a los
   *   datos de tarjeta. Es un campo alfanumérico de un máximo de 40 posiciones
   *   cuyo valor es generado por el TPV Virtual.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setIdentifier(string $value) {
    if (Validators::isEmpty($value)) {
      throw new RedSysException('Please add value');
    }
    else {
      $this->parameters['DS_MERCHANT_IDENTIFIER'] = $value;
    }
  }

  /**
   * Función setMerchantDirectPayment().
   *
   * Indica si hay que mostrar pantallas adicionales.
   * Parámetro OPCIONAL.
   *
   * @param bool $flat
   *   Si se pasa el valor "TRUE", no se mostrarán pantallas adicionales.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setMerchantDirectPayment(bool $flat) {
    if (!is_bool($flat)) {
      throw new RedSysException('Please set true or false');
    }
    else {
      $this->parameters['DS_MERCHANT_DIRECTPAYMENT'] = $flat;
    }
  }

  /**
   * Función setAmount().
   *
   * Establece la cantidad a cobrar, usando el punto como separador
   * de decimales.
   * Parámetro OBLIGATORIO.
   *
   * @param float $amount
   *   Cantidad que se quiere cobrar.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setAmount(float $amount) {
    if ($amount < 0) {
      throw new RedSysException('Amount must be greater than or equal to 0.');
    }
    else {
      $amount = intval(strval($amount * 100));
      $this->parameters['DS_MERCHANT_AMOUNT'] = $amount;
    }
  }

  /**
   * Función setSumTotal().
   *
   * Establece la cantidad a cobrar en caso de pagos recurrentes.
   * Se debe usar el punto como separador decimal.
   * Parámetro OBLIGATORIO en pagos recurrentes.
   *
   * @param float $sumTotal
   *   Cantidad que se quiere cobrar.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setSumTotal(float $sumTotal) {
    if ($sumTotal < 0) {
      throw new RedSysException('Sum total must be greater than or equal to 0.');
    }
    else {
      $sumTotal = intval(strval($sumTotal * 100));
      $this->parameters['DS_MERCHANT_SUMTOTAL'] = $sumTotal;
    }
  }

  /**
   * Función setOrder().
   *
   * Establece el número de pedido.
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
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setOrder(string $order) {
    $order = trim($order);
    if (!Validators::isValidOrder($order)) {
      throw new RedSysException('Order id must be a 4 digit string at least, maximum 12 characters.');
    }
    else {
      $this->parameters['DS_MERCHANT_ORDER'] = $order;
    }
  }

  /**
   * Función getOrder().
   *
   * Devuelve el número de pedido asignado.
   *
   * @return string
   *   Cadena con el número de pedido.
   */
  public function getOrder() {
    return $this->parameters['DS_MERCHANT_ORDER'];
  }

  /**
   * Función setMerchantCode().
   *
   * Establece el código FUC de la tienda.
   * Parámetro OBLIGATORIO.
   *
   * @param string $fuc
   *   Código FUC del comercio.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setMerchantCode(string $fuc) {
    if (Validators::isEmpty($fuc)) {
      throw new RedSysException('Please add Fuc');
    }
    else {
      $this->parameters['DS_MERCHANT_MERCHANTCODE'] = $fuc;
    }
  }

  /**
   * Función setCurrency().
   *
   * Establece el código ISO-4217 de la moneda a usar.
   * Parámetro OBLIGATORIO.
   *
   * @param int $currency
   *   Código ISO a usar.
   *   Por defecto se establece en 978 => Euro.
   *
   * @see https://en.wikipedia.org/wiki/ISO_4217
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setCurrency(int $currency = 978) {
    if (!preg_match('/^[0-9]{3}$/', $currency)) {
      throw new RedSysException('Currency is not valid');
    }
    else {
      $this->parameters['DS_MERCHANT_CURRENCY'] = $currency;
    }
  }

  /**
   * Función setTransactionType().
   *
   * Para indicar qué tipo de transacción es. Los posibles valores son:
   *   A – Pago tradicional
   *   0 - Autorización
   *   1 – Preautorización
   *   2 – Confirmación
   *   3 – Devolución Automática
   *   6 – Transacción Sucesiva
   *   9 – Anulación de Preautorización
   *   P - Confirmación de autorización en diferido
   *   Q - Anulación de autorización en diferido
   *   S – Autorización recurrente sucesiva diferido
   *   O – Autorización en diferido
   * Parámetro OBLIGATORIO.
   *
   * @param string $transaction
   *   Tipo de transacción que se va a realizar.
   *   Su valor por defecto es 0 - Autorización.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setTransactionType(string $transaction) {
    if (Validators::isEmpty($transaction)) {
      throw new RedSysException('Please add transaction type');
    }
    else {
      $this->parameters['DS_MERCHANT_TRANSACTIONTYPE'] = $transaction;
    }
  }

  /**
   * Función setTerminal().
   *
   * Número de terminal que le asignará su banco. Tres se considera su
   * longitud máxima.
   * Parámetro OBLIGATORIO.
   *
   * @param int $terminal
   *   Número del terminal.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setTerminal(int $terminal) {
    if (intval($terminal) === 0) {
      throw new RedSysException('Terminal is not valid.');
    }
    else {
      $this->parameters['DS_MERCHANT_TERMINAL'] = $terminal;
    }
  }

  /**
   * Función setNotification().
   *
   * Establece la url a la que la pasarella llamará con el resultado de
   * la operación.
   * Parámetro OPCIONAL pero muy necesario.
   *
   * @param string $url
   *   URL completa.
   */
  public function setNotification(string $url = '') {
    if (!Validators::isValidUrl($url)) {
      throw new RedSysException('Invalid notification url.');
    }
    else {
      $this->parameters['DS_MERCHANT_MERCHANTURL'] = $url;
    }
  }

  /**
   * Función setUrlOk().
   *
   * Establece la url a la que será redirigido el usuario en caso de pago
   * realizado correctamente.
   *
   * @param string $url
   *   URL completa de redirección.
   */
  public function setUrlOk(string $url = '') {
    if (!Validators::isValidUrl($url)) {
      throw new RedSysException('Invalid ok url.');
    }
    else {
      $this->parameters['DS_MERCHANT_URLOK'] = $url;
    }
  }

  /**
   * Función setUrlKo().
   *
   * Establece la url a la que será redirigido el usuario en caso de pago
   * erróneo o no realizado.
   *
   * @param string $url
   *   URL completa de redirección.
   */
  public function setUrlKo(string $url = '') {
    if (!Validators::isValidUrl($url)) {
      throw new RedSysException('Invalid ko url.');
    }
    else {
      $this->parameters['DS_MERCHANT_URLKO'] = $url;
    }
  }

  /**
   * Función setVersion().
   *
   * Establece la versión concreta de algoritmo que se está utilizando
   * para la firma.
   * Parámetro OBLIGATORIO.
   *
   * @param string $version
   *   Versión del algoritmo.
   */
  public function setVersion(string $version) {
    if (Validators::isEmpty($version)) {
      throw new RedSysException('Please add version.');
    }
    else {
      $this->version = $version;
    }
  }

  /**
   * Función getVersion().
   *
   * Obtiene la versión del algoritmo establecida.
   *
   * @return string
   *   Versión del algoritmo.
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * Función getMerchantSignature().
   *
   * Obtiene la firma generada en getMerchantSignature().
   *
   * @return string
   *   Firma.
   */
  public function getMerchantSignature() {
    return $this->signature;
  }

  /**
   * Función setEnvironment().
   *
   * Establece si el entorno es el de producción o de desarrollo.
   * Parámetro OBLIGATORIO (pero se establece en el constructor).
   *
   * @param string $environment
   *   Indicar 'test' o 'live' según sea desarrollo o producción.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setEnvironment($environment = 'test') {
    $environment = trim($environment);
    if ($environment === 'live') {
      // Producción.
      $this->environment = 'https://sis.redsys.es/sis/realizarPago';
      $this->environmentXml = 'https://sis.redsys.es/sis/services/SerClsWSEntrada?wsdl';
    }
    elseif ($environment === 'test') {
      // Desarrollo.
      $this->environment = 'https://sis-t.redsys.es:25443/sis/realizarPago';
      $this->environmentXml = 'https://sis-t.redsys.es:25443/sis/services/SerClsWSEntrada?wsdl';
    }
    else {
      throw new RedSysException('Add test or live');
    }
  }

  /**
   * Función getEnvironment().
   *
   * Obtiene el tipo de entorno que estamos usando.
   *
   * @return string
   *   Url del entorno empleado.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Función getEnvironmentXml().
   *
   * Obtiene el tipo de entorno que estamos usando.
   * (Para el servicio SOAP).
   *
   * @return string
   *   Url del entorno empleado.
   */
  public function getEnvironmentXml() {
    return $this->environmentXml;
  }

  /**
   * Función setLanguage().
   *
   * Establece el idioma que usará la pasarela de pago.
   * Parámetro OPCIONAL.
   *
   * @param string $languageCode
   *   Código del idioma empleado. Códigos admitidos:
   *     - 001 - Castellano.
   *     - 002 - Inglés.
   *     - 003 - Catalán.
   *     - 004 - Francés.
   *     - 005 - Alemán.
   *     - 006 - Holandés.
   *     - 007 - Italiano.
   *     - 008 - Sueco.
   *     - 009 - Portugués.
   *     - 010 - Valenciano.
   *     - 011 - Polaco.
   *     - 012 - Gallego.
   *     - 013 - Euskera.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setLanguage(string $languageCode) {
    if (!Validators::isValidLangcode($languageCode)) {
      throw new RedSysException('Invalid language code');
    }
    else {
      $this->parameters['DS_MERCHANT_CONSUMERLANGUAGE'] = trim($languageCode);
    }
  }

  /**
   * Función setMerchantData().
   *
   * Permite enviar datos que serán incluidos en la devolución de la pasarela a
   * la url de Notificación, OK y KO.
   * Parámetro OPCIONAL.
   *
   * @param string $merchantdata
   *   Datos a incluir en la petición.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setMerchantData(string $merchantdata = '') {
    if (Validators::isEmpty($merchantdata)) {
      throw new RedSysException('Add merchant data');
    }
    else {
      $this->parameters['DS_MERCHANT_MERCHANTDATA'] = trim($merchantdata);
    }
  }

  /**
   * Función setProductDescription().
   *
   * Establece el nombre del producto comprado.
   * Parámetro OPCIONAL.
   *
   * @param string $description
   *   Descripción del producto.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setProductDescription(string $description = '') {
    if (Validators::isEmpty($description)) {
      throw new RedSysException('Add product description');
    }
    else {
      $this->parameters['DS_MERCHANT_PRODUCTDESCRIPTION'] = trim($description);
    }
  }

  /**
   * Función setTitular().
   *
   * Establece el nombre del titular de la tienda.
   * Parámetro OBLIGATORIO.
   *
   * @param string $titular
   *   Nombre del titular (ejemplo: Óscar Novás).
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setTitular(string $titular = '') {
    if (Validators::isEmpty($titular)) {
      throw new RedSysException('Add name for the user');
    }
    else {
      $this->parameters['DS_MERCHANT_TITULAR'] = trim($titular);
    }
  }

  /**
   * Función setTradeName().
   *
   * Establece el nombre de la tienda.
   * Parámetro OPCIONAL.
   *
   * @param string $tradename
   *   Nombre de la tienda.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setTradeName(string $tradename = '') {
    if (Validators::isEmpty($tradename)) {
      throw new RedSysException('Add name for Trade name');
    }
    else {
      $this->parameters['DS_MERCHANT_MERCHANTNAME'] = trim($tradename);
    }
  }

  /**
   * Función setMethod().
   *
   * Establece el método de pago.
   *
   * @param string $method
   *   Método de pago a utilizar. Valores:
   *     - T = Pago con Tarjeta + iupay
   *     - R = Pago por Transferencia
   *     - D = Domiciliacion
   *     - C = Sólo Tarjeta (mostrará sólo el formulario para datos de tarjeta)
   *   Por defecto se usará C.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setMethod(string $method) {
    if (Validators::isEmpty($method)) {
      throw new RedSysException('Add pay method');
    }
    else {
      $this->parameters['DS_MERCHANT_PAYMETHODS'] = trim($method);
    }
  }

  /**
   * Función setPan().
   *
   * Establece el número de la tarjeta del cliente.
   * Parámetro OPCIONAL/OBLIGATORIO según el sistema empleado.
   *
   * @param string $pan
   *   Número de Tarjeta. Su longitud depende del tipo de la misma.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setPan(string $pan = '') {
    /* TODO Ver si se puede verificar esta tarjeta de algún modo */
    if (intval($pan) == 0) {
      throw new RedSysException('Pan not valid');
    }
    else {
      $this->parameters['DS_MERCHANT_PAN'] = $pan;
    }
  }

  /**
   * Función setExpiryDate().
   *
   * Establece la fecha de caducidad de la tarjeta de crédito.
   * Parámetro OPCIONAL/OBLIGATORIO según el sistema empleado.
   *
   * @param string $expirydate
   *   Caducidad de la tarjeta.
   *   Su formato es AAMM, siendo AA los dos últimos dígitos del año
   *   y MM los dos dígitos del mes.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setExpiryDate(string $expirydate = '') {
    if (!Validators::isExpiryDate($expirydate)) {
      throw new RedSysException('Expire date is not valid');
    }
    else {
      $this->parameters['DS_MERCHANT_EXPIRYDATE'] = $expirydate;
    }
  }

  /**
   * Función setCvv().
   *
   * Establece el código CVV2 de la tarjeta.
   * Parámetro OPCIONAL/OBLIGATORIO según el sistema empleado.
   *
   * @param int $cvv2
   *   Código CVV2 de la tarjeta.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setCvv(int $cvv2 = 0) {
    if (intval($cvv2) == 0) {
      throw new RedSysException('CVV2 is not valid');
    }
    else {
      $this->parameters['DS_MERCHANT_CVV2'] = $cvv2;
    }
  }

  /**
   * Función getParameters().
   *
   * Devuelve un array con todos los parámetros asignados.
   *
   * @return array
   *   Parámetros asignados hasta el momento.
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * Función getParametersXml().
   *
   * Devuelve una cadena con todos los parámetros en formato XML.
   *
   * @return string
   *   Cadena con los parámetros asignados hasta el momento.
   */
  public function getParametersXml() {
    $xml = '<DATOSENTRADA>';
    foreach ($this->parameters as $key => $value) {
      $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
    }
    $xml .= '</DATOSENTRADA>';
    return $xml;
  }

  /* ***************************************************************************
   *
   * - FUNCIONES APLICADAS AL FORMULARIO DE PETICIÓN DE DATOS.
   *
   * **************************************************************************/

  /**
   * Función setNameForm().
   *
   * Asigna un nommbre al formulario de envío de datos.
   * Parámetro OPCIONAL.
   *
   * @param string $name
   *   Nombre del formulario.
   */
  public function setNameForm(string $name) {
    $this->nameForm = $name;
  }

  /**
   * Función getNameForm().
   *
   * Obtiene el nombre asignado al formulario de envío de datos.
   *
   * @return string
   *   Nombre del formulario.
   */
  public function getNameForm() {
    return $this->nameForm;
  }

  /**
   * Función setIdForm().
   *
   * Establece el Id del formulario de envío de datos.
   * Parámetro OPCIONAL.
   *
   * @param string $id
   *   Valor del id para el formulario.
   */
  public function setIdForm(string $id) {
    $this->idForm = $id;
  }

  /**
   * Función setAttributesSubmit().
   *
   * Asigna diferentes valores al botón de submit del formulario
   * de envío de datos.
   * Parámetro OPCIONAL.
   *
   * @param string $name
   *   Nombre del botón (OBLIGATORIO).
   * @param string $id
   *   Identificador del botón (OBLIGATORIO).
   * @param string $value
   *   Texto mostrado en el botón (OBLIGATORIO).
   * @param string $style
   *   CSS inline.
   * @param string $cssClass
   *   Clase para el botón.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function setAttributesSubmit(string $name, string $id, string $value, string $style, string $cssClass) {
    /* Los 3 primeros parámetros son obligatorios */
    if (Validators::isEmpty($name) or Validators::isEmpty($name) or Validators::isEmpty($name)) {
      throw new RedSysException('Parameters name, id and value are required');
    }
    else {
      $this->nameSubmit = $name;
      $this->idSubmit = $id;
      $this->valueSubmit = $value;
      $this->styleSubmit = $style;
      $this->classSubmit = $cssClass;
    }
  }

  /**
   * Función createForm().
   *
   * Genera el HTML del formulario de envío de datos y lo envía
   * si es necesario.
   *
   * @param bool $auto_submit
   *   Si se establece a TRUE el formulario se envía automáticamente.
   *   Su valor por defecto es FALSE.
   *
   * @return string
   *   HTML del formulario.
   */
  public function createForm(bool $auto_submit = FALSE) {
    $form = '
      <form action="' . $this->environment . '" method="post" id="' . $this->idForm . '" name="' . $this->nameForm . '" >
        <input type="hidden" name="Ds_MerchantParameters" value="' . $this->generateMerchantParameters() . '"/>
        <input type="hidden" name="Ds_Signature" value="' . $this->signature . '"/>
        <input type="hidden" name="Ds_SignatureVersion" value="' . $this->version . '"/>
        <input type="submit" name="' . $this->nameSubmit . '" id="' . $this->idSubmit . '" value="' . $this->valueSubmit . '" ' . ($this->styleSubmit != '' ? ' style="' . $this->styleSubmit . '"' : '') . ' ' . ($this->classSubmit != '' ? ' class="' . $this->classSubmit . '"' : '') . '>
      </form>
    ';

    if ($auto_submit) {
      $form .= '<script>document.forms["' . $this->nameForm . '"].submit();</script>';
    }

    return $form;
  }

  /* ***************************************************************************
   *
   * - FUNCIONES APLICADAS A LA PETICIÓN SOAP.
   *
   * **************************************************************************/

  /**
   * Función firePayment().
   *
   * Ejecuta el pago sobre SOAP.
   *
   * @return array
   *   Array con la respuesta.
   */
  public function firePayment(string $key) {
    $xml = $this->buildXml($key);
    $client = new SoapClient($this->getEnvironmentXml());
    $result = $client->trataPeticion([
      'datoEntrada' => $xml,
    ]);
    $response = Utils::xmlToArray($result->trataPeticionReturn);
    return $this->checkResponse($response, $key);
  }

  /**
   * Función buildXml().
   *
   * Contruye la cadena a enviar en formato Xml.
   *
   * @return string
   *   Cadena formateada.
   */
  private function buildXml(string $key) {
    $datos = $this->getParametersXml();

    $xml = '<REQUEST>';
    $xml .= $datos;
    $xml .= '<DS_SIGNATUREVERSION>' . $this->getVersion() . '</DS_SIGNATUREVERSION>';
    $xml .= '<DS_SIGNATURE>' . $this->generateSignature($datos, $this->getOrder(), $key) . '</DS_SIGNATURE>';
    $xml .= '</REQUEST>';

    return $xml;
  }

  /* ***************************************************************************
   *
   * - FUNCIONES PARA EL CHECK DE LA DEVOLUCIÓN DE LA ENTIDAD.
   *
   * **************************************************************************/

  /**
   * Función checkResponseSignature().
   *
   * Comprueba la firma devuelta por redsys con la generada por nosotros.
   * Se usa para verificar los pagos recibidos.
   *
   * @param array $postData
   *   Datos recibidos del banco.
   * @param string $key
   *   Clave pública del comercio.
   *
   * @return bool
   *   TRUE si los datos coinciden.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  private function checkResponseSignature(array $postData, string $key) {
    if (!isset($postData)) {
      throw new RedSysException("Add data return of bank");
    }

    $cadena_con_tarjeta = $postData['Ds_Amount'] .
                          $postData['Ds_Order'] .
                          $postData['Ds_MerchantCode'] .
                          $postData['Ds_Currency'] .
                          $postData['Ds_Response'] .
                          $postData['Ds_CardNumber'] .
                          $postData['Ds_TransactionType'] .
                          $postData['Ds_SecurePayment'];

    $cadena_sin_tarjeta = $postData['Ds_Amount'] .
                          $postData['Ds_Order'] .
                          $postData['Ds_MerchantCode'] .
                          $postData['Ds_Currency'] .
                          $postData['Ds_Response'] .
                          $postData['Ds_TransactionType'] .
                          $postData['Ds_SecurePayment'];

    if ($this->generateSignature($cadena_con_tarjeta, $postData['Ds_Order'], $key) != $postData['Ds_Signature']) {
      if ($this->generateSignature($cadena_sin_tarjeta, $postData['Ds_Order'], $key) == $postData['Ds_Signature']) {
        return TRUE;
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Función checkPaymentResponse().
   *
   * Decodifica y devuelve los parámetros devueltos por el banco.
   *
   * @param array $postData
   *   Array con la devolución del banco.
   * @param string $key
   *   Clave pública del comercio.
   *
   * @return array
   *   Array con la respuesta del banco.
   *
   * @throws Drupal\module_nameys\Exception\RedSysException
   */
  public function checkPaymentResponse(array $postData, string $key) {
    if (isset($postData)) {
      $parameters = $postData["Ds_MerchantParameters"];
      $signatureReceived = $postData["Ds_Signature"];
      $decodec = json_decode(Utils::decodeParameters($parameters), TRUE);
      $order = $decodec['Ds_Order'];

      $signature = $this->generateSignature($parameters, $order, $key);
      $signature = strtr($signature, '+/', '-_');
      if ($signature === $signatureReceived) {
        return $this->getResponse(0, $decodec);
      }
      else {
        return $this->getResponse('SIS041', $decodec, TRUE);
      }
    }
    else {
      throw new \RedSysException("Error: Redsys response is empty");
    }
  }

  /**
   * Función checkResponse().
   *
   * Devuelve un array con la respuesta de la petición XML.
   *   - 0000 a 0099 Transacción autorizada para pagos y preautorizaciones.
   *   - 900 Transacción autorizada para devoluciones y confirmaciones.
   *   - 400 Transacción autorizada para anulaciones.
   *
   * @param array $response
   *   Array con la respuesta del banco.
   * @param string $key
   *   Clave pública del comercio.
   *
   * @return array
   *   Respuesta de la petición del banco.
   */
  private function checkResponse(array $response, string $key) {

    if (!Validators::validCode($response)) {
      return $this->getResponse($this->getErrorCode($response), $this->getErrorCodeData($response), TRUE);
    }

    if (!$this->checkResponseSignature($response['OPERACION'], $key)) {
      return $this->getResponse('SIS0041', $response['OPERACION'], TRUE);
    }

    return $this->getResponse($response['CODIGO'], $response['OPERACION']);
  }

  /**
   * Función getResponse().
   *
   * Devuelve la respuesta del banco pero formateada con algunos parámetros
   * adicionales.
   *
   * @param string $code
   *   Código de error lanzado por el banco.
   * @param array $response
   *   Array con la respuesta del banco.
   * @param bool $error
   *   Indica si los datos emitios por el banco contienen un error ya
   *   contemplado.
   *
   * @return array
   *   Array con la respuesta y datos adicionales.
   */
  private function getResponse(string $code, array $response, bool $error = FALSE) {
    $response_default = [
      'error' => $error,
      'code'  => $code,
      'error_info' => RedSysMessages::getByCode($code),
    ];

    if (!$response) {
      if (!$response_default['code']) {
        $response_default['code'] = '9998';
      }
      return $response_default;
    }

    return array_merge($response_default, $response);
  }

  /**
   * Función getErrorCode().
   *
   * Devuelve el código de error de la respuesta recibida.
   *
   * @param array $response
   *   Respuesta recibida del banco.
   *
   * @return string
   *   Código del error.
   */
  private function getErrorCode(array $response) {
    $code = $response['CODIGO'];

    if (!is_numeric($code)) {
      return $code;
    }

    return $response['OPERACION']['Ds_Response'];
  }

  /**
   * Función getErrorCodeData().
   *
   * Obtiene todos los datos del posible error de la respuesta recibida.
   *
   * @param array $response
   *   Respuesta recibida del banco.
   *
   * @return array
   *   Datos de la operación.
   */
  private function getErrorCodeData(array $response) {
    $code = $response['CODIGO'];

    if (!is_numeric($code)) {
      return $response['RECIBIDO']['REQUEST']['DATOSENTRADA'];
    }

    return $response['OPERACION'];
  }

  /* ***************************************************************************
   *
   * - FUNCIONES DE GENERACIÓN DE FIRMAS.
   *
   * **************************************************************************/

  /**
   * Función generateMerchantParameters().
   *
   * Codifica los parámetros en Base64.
   *
   * @return string
   *   Cadena codificada.
   */
  private function generateMerchantParameters() {
    // Convierto el Array a Json.
    $json = Utils::arrayToJson($this->parameters);

    // Codifico el Json en Base64.
    return Utils::encodeBase64($json);
  }

  /**
   * Función generateMerchantSignature().
   *
   * Genera la firma de la petición posterior.
   *
   * @param string $key
   *   Clave pública del comercio.
   */
  public function generateMerchantSignature(string $key) {
    $key = Utils::decodeBase64($key);
    $merchant_parameter = $this->generateMerchantParameters();
    $key = Utils::encrypt3des($this->getOrder(), $key);
    $result = Utils::hmac256($merchant_parameter, $key);

    $this->signature = Utils::encodeBase64($result);
  }

  /**
   * Función generateSignature().
   *
   * Genera la firma de la petición a partir de los datos obtenidos de la
   * pasarela.
   *
   * @param string $datos
   *   Devolución codificada de la pasarela.
   * @param string $order
   *   Número de pedido devuelto por la pasarela.
   * @param string $key
   *   Clave pública del comercio.
   *
   * @return string
   *   Firma de los parámetros recibidos.
   */
  private function generateSignature(string $datos, string $order, string $key) {
    $key = Utils::decodeBase64($key);
    $key = Utils::encrypt3des($order, $key);
    $result = Utils::hmac256($datos, $key);
    return Utils::encodeBase64($result);
  }

}
