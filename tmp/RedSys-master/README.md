Pagos vía RedSys con SOAP y redirección
===

>Librería para integrar en un módulo personalizado de *Drupal* y poder ejecutar
>cobros a través de la pasarela RedSys.
>
>También puede ser utilizada fuera de un entorno *Drupal* adaptando los
>correspondientes ***namespaces*** y ***uses***.

[![version][version-badge]][changelog]
[![Licencia][license-badge]][license]
[![Donate][donate-badge]][donate-url]

## Instalación

### Requerimientos

>* Esta clase funciona con PHP >= 5.5.0 pero se recomienda usar la última
>  versión estable.
>
>* Para poder usar la funcionalidad de envío (desde el comercio) de los datos
>  de la tarjeta debemos solicitar a nuestro banco que activen dicha opción.

### Instalación manual

>Copiamos la carpeta ***src*** dentro de nuestro módulo Drupal y cambiamos los
>***namespaces*** en los archivos:
> * RedSys.php
> * Exception\RedSysException.php
> * Messages\RedSysMessages.php
> * Utils\Utils.php
> * Utils\Validators.php
>
> Sustituímos el valor ***module_name*** por el nombre de nuestro módulo.
>
>Por otro lado, en el archivo *RedSys.php* tendremos que modificar también
>las sentencias ***uses*** para adaptarlas al cambio anterior así como los
>comentarios que poseen cláusulas ***@throws***.


## Documentación oficial de RedSys

* [Documentación para redirección](https://canales.redsys.es/canales/ayuda/documentacion/Manual%20integracion%20para%20conexion%20por%20Redireccion.pdf)
* [Documentación para SOAP](https://canales.redsys.es/canales/ayuda/documentacion/Manual%20integracion%20para%20conexion%20por%20Web%20Service.pdf)

## Ejemplos de uso
* ### Pago por redirección:
  #### OPCIÓN 1.- El banco solicita los datos de la tarjeta.

  ```PHP
  /**
   * @file
   * payment.php
   */
  use Drupal\module_name\lib\redsys\RedSys\RedSys;
  use Drupal\module_name\lib\redsys\RedSys\Exception\RedSysException;

  /* Creamos una instancia en entorno de pruebas
   * (omitimos el último parámetro para un entorno de producción). */
  $redsys = new Redsys('test');

  try {
    /* Parámetros obligatorios */
    $redsys->setOrder('0001aa');
    $redsys->setMerchantCode('999008881');
    $redsys->setAmount(99.99);
    $redsys->setTradeName('Mi tienda, s.l.');
    $redsys->setTitular('Perico de los Palotes');
    $redsys->setProductDescription('Compra de producto 1');

    /* Estos parámetros sólo son necesarios si son distintos de los
     * indicados por defecto. */
    $redsys->setTerminal(1);
    $redsys->setCurrency(978);
    $redsys->setTransactionType('0');
    $redsys->setMethod('C');
    $redsys->setVersion('HMAC_SHA256_V1');
    $redsys->setLanguage('001');

    /* URL de notificación y redirección */
    $redsys->setNotification('https://localhost/notification.php');
    $redsys->setUrlOk('https://localhost/payment_ok.php');
    $redsys->setUrlKo('https://localhost/payment_ko.php');

    /* Establezco cambios en el formulario del TPV.
     * Estos parámetros son opcionales. */
    $redsys->setNameForm('redsys_form');
    $redsys->setIdForm('redsys_form');
    $redsys->setAttributesSubmit(
      'btn_submit',
      'btn_submit',
      'Realizar pago',
      '',
      'btn btn-primary');

    /* Muestro el formulario sin redirección automática */
    $redsys->generateMerchantSignature('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
    $form = $redsys->createForm(FALSE);
  }
  catch (RedSysException $e) {
    echo $e->getMessage();
    die;
  }

  echo $form;

  ```

  #### OPCIÓN 2.- Capturamos nosotros los datos de la tarjeta.

  ```PHP
  /**
   * @file
   * payment.php
   */
  use Drupal\module_name\lib\redsys\RedSys\RedSys;
  use Drupal\module_name\lib\redsys\RedSys\Exception\RedSysException;

  /* Creamos una instancia en entorno de pruebas
   * (omitimos el último parámetro para un entorno de producción). */
  $redsys = new Redsys('test');

  try {
    /* Parámetros obligatorios */
    $redsys->setOrder('0001aa');
    $redsys->setMerchantCode('999008881');
    $redsys->setAmount(99.99);
    $redsys->setTradeName('Mi tienda, s.l.');
    $redsys->setTitular('Perico de los Palotes');
    $redsys->setProductDescription('Compra de producto 1');

    /* Estos parámetros sólo son necesarios si son distintos de los
     * indicados por defecto. */
    $redsys->setTerminal(1);
    $redsys->setCurrency(978);
    $redsys->setTransactionType('0');
    $redsys->setMethod('C');
    $redsys->setVersion('HMAC_SHA256_V1');
    $redsys->setLanguage('001');

    /* Datos de la tarjeta del cliente.
     * (El comercio debe estar autorizado para poder capturar estos datos) */
    $redsys->setPan('4548812049400004');
    $redsys->setExpiryDate('2012');
    $redsys->setCVV('123');

    /* URL de notificación y redirección */
    $redsys->setNotification('https://localhost/notification.php');
    $redsys->setUrlOk('https://localhost/payment_ok.php');
    $redsys->setUrlKo('https://localhost/payment_ko.php');

    /* Establezco cambios en el formulario del TPV.
     * Estos parámetros son opcionales. */
    $redsys->setNameForm('redsys_form');
    $redsys->setIdForm('redsys_form');
    $redsys->setAttributesSubmit(
      'btn_submit',
      'btn_submit',
      'Realizar pago',
      '',
      'btn btn-primary');

    /* Muestro el formulario sin redirección automática */
    $redsys->generateMerchantSignature('sq7HjrUOBfKmC576ILgskD5srU870gJ7');
    $form = $redsys->createForm(FALSE);
  }
  catch (RedSysException $e) {
    echo $e->getMessage();
    die;
  }

  echo $form;

  ```

  #### RESULTADO: Captura de la devolución de la pasarela.

  ```PHP
  /**
   * @file
   * notification.php
   */
  use Drupal\module_name\lib\redsys\RedSys\RedSys;
  use Drupal\module_name\lib\redsys\RedSys\Exception\RedSysException;

  $redsys = new Redsys('test');

  try {
    $result = $redsys->checkPaymentResponse($_POST, 'sq7HjrUOBfKmC576ILgskD5srU870gJ7');
  }
  catch (RedSysException $e) {
      echo $e->getMessage();
      die;
  }

  var_dump($result);

  ```

  #### Resultado de las peticiones por formulario
  ##### Con error:

  ```PHP
  $resultado = [
    'error' => true,
    'code' => "SIS0051",
    'error_info' => [
      'code' => 'SIS0051',
      'response' => 9051,
      'message' => 'Error número de pedido repetido',
      'msg' => 'MSG0001',
      'detail' => '',
    ],
    'error_info' => '',
    'Ds_Date' => '06/10/2020',
    'Ds_Hour' => '19:00',
    'Ds_SecurePayment' => '0',
    'Ds_ExpiryDate' => '2012',
    'Ds_Merchant_Identifier' => '3208c536f53db9c01106e87f6a867ce832ee9358',
    'Ds_Card_Country' => '724',
    'Ds_Amount' => '9999',
    'Ds_Currency' => '978',
    'Ds_Order' => '6666c',
    'Ds_MerchantCode' => '351570213',
    'Ds_Terminal' => '001',
    'Ds_Response' => '0000',
    'Ds_MerchantData' => '',
    'Ds_TransactionType' => '0',
    'Ds_ConsumerLanguage' => '1',
    'Ds_AuthorisationCode' => '173839',
    'Ds_Card_Brand' => '1',
    'Ds_ProcessedPayMethod' => '14',
  ];

  ```

  ##### Sin error:

  ```PHP
  $resultado = [
    'error' => false,
    'code' => '0',
    'error_info' => '',
    'Ds_Date' => '06/10/2020',
    'Ds_Hour' => '19:00',
    'Ds_SecurePayment' => '0',
    'Ds_ExpiryDate' => '2012',
    'Ds_Merchant_Identifier' => '3208c536f53db9c01106e87f6a867ce832ee9358',
    'Ds_Card_Country' => '724',
    'Ds_Amount' => '9999',
    'Ds_Currency' => '978',
    'Ds_Order' => '6666c',
    'Ds_MerchantCode' => '351570213',
    'Ds_Terminal' => '001',
    'Ds_Response' => '0000',
    'Ds_MerchantData' => '',
    'Ds_TransactionType' => '0',
    'Ds_ConsumerLanguage' => '1',
    'Ds_AuthorisationCode' => '173839',
    'Ds_Card_Brand' => '1',
    'Ds_ProcessedPayMethod' => '14',
  ];

  ```

* ### Pago por WebService:
  #### Solicitud del pago

  ```PHP
  /**
   * @file
   * payment.php
   */
  use Drupal\module_name\lib\redsys\RedSys\RedSys;
  use Drupal\module_name\lib\redsys\RedSys\Exception\RedSysException;

  /* Creamos una instancia en entorno de pruebas
   * (omitimos el último parámetro para un entorno de producción). */
  $redsys = new Redsys('test');

  try {
      /* Parámetros obligatorios */
      $redsys->setMerchantcode('999008881');
      $redsys->setAmount(99.99);
      $redsys->setOrder('9999K');

      /* Establecemos el tipo y método de la transacción */
      $redsys->setTransactiontype('A');
      $redsys->setMethod('T');

      /* Estos parámetros sólo son necesarios si son distintos de los
       * indicados por defecto. */
      $redsys->setTerminal(1);
      $redsys->setCurrency(978);
      $redsys->setVersion('HMAC_SHA256_V1');
      $redsys->setLanguage('001');
      $redsys->setIdentifier('REQUIRED');

      /* Indicamos los datos de la tarjeta del cliente */
      $redsys->setPan('4548812049400004');
      $redsys->setExpiryDate('2012');
      $redsys->setCVV('123');

      /* Genero la firma y ejecuto el cobro */
      $result = $redsys->firePayment('sq7HjrUOBfKmC576ILgskD5srU870gJ7');

    }
    catch (RedSysException $e) {
      echo $e->getMessage();
      die;
    }

    var_dump($result);

  ```

  #### Solicitud de pago con identificador (*Pago recurrente*)

  ```PHP
  /**
   * @file
   * payment.php
   */
  use Drupal\module_name\lib\redsys\RedSys\RedSys;
  use Drupal\module_name\lib\redsys\RedSys\Exception\RedSysException;

  /* Creamos una instancia en entorno de pruebas
   * (omitimos el último parámetro para un entorno de producción). */
  $redsys = new Redsys('test');

  try {
      /* Parámetros obligatorios */
      $redsys->setMerchantcode('999008881');
      $redsys->setAmount(99.99);
      $redsys->setOrder('9999K');

      /* Establecemos el tipo y método de la transacción */
      $redsys->setTransactiontype('A');
      $redsys->setMethod('T');

      /* Estos parámetros sólo son necesarios si son distintos de los
       * indicados por defecto. */
      $redsys->setTerminal(1);
      $redsys->setCurrency(978);
      $redsys->setVersion('HMAC_SHA256_V1');
      $redsys->setLanguage('001');

      /* Indicamos el identificador del pago anterior */
      $redsys->setIdentifier('d202286b28232a55160890eedac145a70d1b8cd3');

      /* Genero la firma y ejecuto el cobro */
      $result = $redsys->firePayment('sq7HjrUOBfKmC576ILgskD5srU870gJ7');

    }
    catch (RedSysException $e) {
      echo $e->getMessage();
      die;
    }

    var_dump($result);

  ```

  #### Resultados de las peticiones SOAP
  ##### Con error:

  ```PHP
  $resultado = [
    "error" => true,
    "code" => "SIS0051",
    "error_info" => [
      "code" => "SIS0051",
      "response" => 9051,
      "message" => "Error número de pedido repetido",
      "msg" => "MSG0001",
      "detail" => "",
    ],
    "DS_MERCHANT_DIRECTPAYMENT" => [],
    "DS_MERCHANT_CURRENCY" => "978",
    "DS_MERCHANT_TRANSACTIONTYPE" => "A",
    "DS_MERCHANT_TERMINAL" => "001",
    "DS_MERCHANT_CONSUMERLANGUAGE" => "001",
    "DS_MERCHANT_PAYMETHODS" => "T",
    "DS_MERCHANT_IDENTIFIER" => "REQUIRED",
    "DS_MERCHANT_MERCHANTCODE" => "999008881",
    "DS_MERCHANT_AMOUNT" => "9999",
    "DS_MERCHANT_ORDER" => "9999J",
    "DS_MERCHANT_PAN" => "4548812049400004",
    "DS_MERCHANT_EXPIRYDATE" => "2012",
    "DS_MERCHANT_CVV2" => "123",
  ];

  ```

  ##### Sin error:

  ```PHP
  $resultado =[
    "error" => false,
    "code" => "0",
    "error_info" => NULL,
    "Ds_Amount" => "9999",
    "Ds_Currency" => "978",
    "Ds_Order" => "9999J",
    "Ds_Signature" => "vAf4mdomXrabre/f9xOkbTOtQN4KaqRi6Sj8Hq5dvzQ=",
    "Ds_MerchantCode" => "999008881",
    "Ds_Terminal" => "1",
    "Ds_Response" => "0000",
    "Ds_AuthorisationCode" => "462168",
    "Ds_TransactionType" => "A",
    "Ds_SecurePayment" => "0",
    "Ds_Language" => "1",
    "Ds_CardNumber" => "454881******0004",
    "Ds_ExpiryDate" => "2012",
    "Ds_Merchant_Identifier" => "d202286b28232a55160890eedac145a70d1b8cd3",
    "Ds_MerchantData" => [],
    "Ds_Card_Country" => "724",
    "Ds_Card_Brand" => "1",
    "Ds_ProcessedPayMethod" => "3",
  ];

  ```

## Autor
- Óscar Novás - [OscarNovas.com](https://oscarnovas.com)

## Organizaciones de apoyo
- [Gloudify.com](https://gloudify.com)

## Créditos
Esta librería nace a partir del código de otras tres:
* [buuum/Redsys](https://github.com/buuum/Redsys)
* [ssheduardo/sermepa](https://github.com/ssheduardo/sermepa)
* [eusonlito/redsys-Messages](https://github.com/eusonlito/redsys-Messages)

Aquí puedes comprobar la lista de [contribuyentes][contributors]
a este repositorio.

## Histórico de cambios
Aquí puedes comprobar la lista de [cambios][changelog] efectuados hasta el
momento.

---
⌨️ con ❤️ por [Óscar Novás](https://oscarnovas.com) 😊

[version]: v0.0.1
[version-badge]: https://img.shields.io/badge/version-0.0.1-blue.svg

[license]: LICENSE.md
[license-badge]: https://img.shields.io/github/license/oscarnovasf/redsys

[changelog]: CHANGELOG.md
[contributors]: https://github.com/oscarnovasf/redsys/contributors

[donate-badge]: https://img.shields.io/badge/Donate-PayPal-green.svg
[donate-url]: https://paypal.me/oscarnovasf
