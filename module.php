<?PHP

  //Lets try to use Royal Mail

  //include soap class
  include_once 'soap.php';

  //lets make a request
  $request = new royalmaillabelRequest(); 
  $array = array (
    'api_service_type' => "D",
    'order_tracking_id' => "",
    'api_service_code' => "SD1",
    'api_service_format' => "",
    'api_service_enhancements' => "",
    'shipping_name' => $shipping['name_line'], "Felipe",
    'shipping_company' => "splash", //$shipping['organisation_name'],
    'shipping_address1' => $shipping['thoroughfare'], //"23, St johns road", 
    'shipping_address2' => $shipping['thoroughfare'], //"",
    'shipping_town' => $shipping['premise'], //"london", 
    'shipping_postcode' => $shipping['postal_code'], //"NW11 0PE",
    'order_tracking_boxes' => "0",
    'order_tracking_weight' => "1500",
  );

  //getting a response
  $response = $request->CreateShippiment($array);

  var_dump($response);
