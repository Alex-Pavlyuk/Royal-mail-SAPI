<?php

//ini_set('soap.wsdl_cache_enabled', '1'); 
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 0);

class royalmaillabelRequest {

  private $apiapplicationid = "insert urs";
  private $api_password = "insert urs";
  private $api_username = "insert urs"; //"rxxxxxAPI"
  private $api_certificate_passphrase = "insert urs";
  private $locationforrequest = 'https://api.royalmail.com/shipping/onboarding'; //live 'https://api.royalmail.com/shipping' onbording 'https://api.royalmail.com/shipping/onboarding'
  private $api_service_enhancements = "";
        
  private function preparerequest() {

    //PASSWORD DIGEST
    $time = gmdate('Y-m-d\TH:i:s');
    $created = gmdate('Y-m-d\TH:i:s\Z');
    $nonce = mt_rand();
    $nonce_date_pwd = pack("A*", $nonce) . pack("A*", $created) . pack("H*", sha1($this->api_password));
    $passwordDigest = base64_encode(pack('H*', sha1($nonce_date_pwd)));
    $ENCODEDNONCE = base64_encode($nonce);

    //SET CONNECTION DETAILS

    $soapclient_options = array();
    $soapclient_options['cache_wsdl'] = 'WSDL_CACHE_NONE';
    $soapclient_options['stream_context'] = stream_context_create(
        array('http' =>
          array(
            'protocol_version' => '1.0'
            , 'header' => 'Connection: Close'
          )
        )
    );

    $soapclient_options['local_cert'] = dirname(__FILE__) . "/certificate.pem";
    $soapclient_options['passphrase'] = $this->api_certificate_passphrase;
    $soapclient_options['trace'] = true;
    $soapclient_options['ssl_method'] = 'SOAP_SSL_METHOD_SSLv3';
    $soapclient_options['location'] = $this->locationforrequest;
    $soapclient_options['soap_version'] = 'SOAP_1_1';

    //launch soap client
    //$client = new SoapClient(dirname(__FILE__) . "/SAPI/ShippingAPI_V2_0_8.wsdl", $soapclient_options);
    $client = new SoapClient(dirname(__FILE__) . "/reference/ShippingAPI_V2_0_8.wsdl", $soapclient_options);
    $client->__setLocation($soapclient_options['location']);

    //headers needed for royal mail//D8D094FC22716E3EDE14258880881317
    $HeaderObjectXML = '<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
                                          xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                               <wsse:UsernameToken wsu:Id="UsernameToken-D8D094FC22716E3EDE14258880881317">
                                  <wsse:Username>' . $this->api_username . '</wsse:Username>
                                  <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">' . $passwordDigest . '</wsse:Password>
                                  <wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $ENCODEDNONCE . '</wsse:Nonce>
                                  <wsu:Created>' . $created . '</wsu:Created>
                               </wsse:UsernameToken>
                           </wsse:Security>';
    //push the header into soap
    $HeaderObject = new SoapVar($HeaderObjectXML, XSD_ANYXML);
    //push soap header
    $header = new SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd', 'Security', $HeaderObject);
    $client->__setSoapHeaders($header);
    return $client;
  }

  public function CreateShippiment($data) {

    $request = $this->buildCreateshippiment($data);
    $type = 'createShipment';
    return $this->makerequest($type, $request);
  }

  public function PrintLabel($shipmentNumber, $order_tracking_id) {

    $time = gmdate('Y-m-d\TH:i:s');
    $request = array(
      'integrationHeader' => array(
        'dateTime' => $time,
        'version' => '2',
        'identification' => array(
          'applicationId' => $this->apiapplicationid,
          'transactionId' => $order_tracking_id
        )
      ),
      'shipmentNumber' => $shipmentNumber,
      'outputFormat' => 'PDF',
    );
    $type = 'printLabel';
    $response = $this->makerequest($type, $request);
    return $response->label;
  }

  private function makerequest($type, $request) {

    $client = $this->preparerequest();
    $response = false;
    $times = 1;

    while (true) {

      try {
        $response = $client->__soapCall($type, array($request), array('soapaction' => $this->locationforrequest));

        //echo "REQUEST:\n" . htmlentities($client->__getLastResponse()) . "\n";
        break;
      }
      catch (Exception $e) {

        //print_r($e);
        if ( $times <= 25) { //$e->detail->exceptionDetails->exceptionCode == "E0010" &&

          sleep(1.5);
          $times++;
          continue;
        }
        else {
//          echo $e->getMessage();
//          echo "<pre>";
//          print_r($e->detail);
//          echo $client->__getLastResponse();
//          echo "REQUEST:\n" . htmlentities($client->__getLastResponse()) . "\n";
//          break;
        }
      }

      break;
    }
    return $response;
  }

  private function buildCreateshippiment($data2) {

    $time = gmdate('Y-m-d\TH:i:s');

    $data = new ArrayObject();
    foreach ($data2 as $key => $value) {
      $data->$key = $value;
    }


    $request = array(
      'integrationHeader' => array(
        'dateTime' => $time,
        'version' => '2',
        'identification' => array(
          'applicationId' => $this->apiapplicationid,
          'transactionId' => $data->order_tracking_id
        )
      ),
      'requestedShipment' => array(
        'shipmentType' => array('code' => 'Delivery'),
        'serviceOccurrence' => 1,
        'serviceType' => array('code' => $data->api_service_type),
        'serviceOffering' => array('serviceOfferingCode' => array('code' => $data->api_service_code)),
        'serviceFormat' => array('serviceFormatCode' => array('code' => $data->api_service_format)),
        'shippingDate' => date('Y-m-d'),
        'recipientContact' => array('name' => $data->shipping_name, 'complementaryName' => $data->shipping_company),
        'recipientAddress' => array('addressLine1' => $data->shipping_address1, 'addressLine2' => $data->shipping_address2, 'postTown' => $data->shipping_town, 'postcode' => $data->shipping_postcode),
        'items' => array('item' => array(
            'numberOfItems' => $data->order_tracking_boxes,
            'weight' => array('unitOfMeasure' => array('unitOfMeasureCode' => array('code' => 'g')),
              'value' => $data->order_tracking_weight,
            )
          )
        ),
      //'signature' => 0,
      )
    );

    if ($data->api_service_enhancements == 6 && $data->api_service_type == 1) {
      $request['requestedShipment']['serviceEnhancements'] = array('enhancementType' => array('serviceEnhancementCode' => array('code' => $data->api_service_enhancements)));
    }

    return $request;
  }

}
