<?php
/**
 * @package wc visa woocommerce
 * WC_Gateway_VISA_Connection for visa
*/
class WC_Gateway_VISA_Connection {
  protected $curlObj;

  function __construct($merchantObj) {
    // configure cURL proxy options by calling this function
    $this->ConfigureCurlProxy($merchantObj);
  }

  

  // Send transaction to payment server
  public function SendTransaction($merchantObj, $request) {
        $args = array(
            'body'        => $request,
            //'timeout'     => '5',
            //'redirection' => '5',
            //'httpversion' => '1.0',
            //'blocking'    => true,
            'sslverify'=> $merchantObj->GetCertificateVerifyPeer(),
            'headers'     => [
                "Content-Length" => strlen($request),
                "Content-Type" => "application/x-www-form-urlencoded;charset=UTF-8",
            ],
            'cookies'     => array(),
        );

 
 

    // tells cURL to return the result if successful, of FALSE if the operation failed
    //curl_setopt($this->curlObj, CURLOPT_RETURNTRANSFER, TRUE);
 
  
    $response = wp_remote_post( $merchantObj->GetGatewayUrl(), $args);
    //wp_remote_retrieve_response_code
    $response     = wp_remote_retrieve_body( $response );
   
 
  if ( is_wp_error( $response ) )
  {
    $error_message = $response->get_error_message();
    return "Error: $error_message";
  }
  else
  { 
      return $response;
   }
    // assigns the cURL error to response if something went wrong so the caller can echo the error
    /*if (curl_error($this->curlObj))
      $response = "cURL Error: " . curl_errno($this->curlObj) . " - " . curl_error($this->curlObj);
        */
    // respond with the transaction result, or a cURL error message if it failed
    
  }

  // [Snippet] howToConfigureProxy - start
  // Check if proxy config is defined, if so configure cURL object to tunnel through
  protected function ConfigureCurlProxy($merchantObj) {

    add_action('http_api_curl', function( $handle ) use($merchantObj){
    //Don't verify SSL certs
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $merchantObj->GetCertificateVerifyPeer());
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, $merchantObj->GetCertificateVerifyHost());
        
    // If proxy server is defined, set cURL option
    if ($merchantObj->GetProxyServer() != "") {
      curl_setopt($handle, CURLOPT_PROXY, $merchantObj->GetProxyServer());
      curl_setopt($handle, $merchantObj->GetProxyCurlOption(), $merchantObj->GetProxyCurlValue());
    }
    // If proxy authentication is defined, set cURL option
    if ($merchantObj->GetProxyAuth() != "")
      curl_setopt($handle, CURLOPT_PROXYUSERPWD, $merchantObj->GetProxyAuth());

    if ($merchantObj->GetCertificatePath() != "")
      curl_setopt($handle, CURLOPT_CAINFO, $merchantObj->GetCertificatePath());

    }, 10);

  }

}



class WC_Gateway_VISA_Parser extends WC_Gateway_VISA_Connection {
  function __construct($merchantObj) {
    // call parent ctor to init members
    parent::__construct($merchantObj);
  }

  function __destruct() {
    // call parent dtor to free resources
    parent::__destruct();
  }

	// [Snippet] howToConfigureURL - start
  // Modify gateway URL to set the version
  // Assign it to the gatewayUrl member in the merchantObj object
  public function FormRequestUrl($merchantObj) {
    $gatewayUrl = $merchantObj->GetGatewayUrl();
    $gatewayUrl .= "/version/" . $merchantObj->GetVersion();

    $merchantObj->SetGatewayUrl($gatewayUrl);
    return $gatewayUrl;
  }
  // [Snippet] howToConfigureURL - end

  // [Snippet] howToConvertFormData - start
  // Form NVP formatted request and append merchantId, apiPassword & apiUsername
  public function ParseRequest($merchantObj, $formData) {
    $request = "";

    if (count($formData) == 0)
      return "";

    foreach ($formData as $fieldName => $fieldValue) {
      if (strlen($fieldValue) > 0 && $fieldName != "merchant" && $fieldName != "apiPassword" && $fieldName != "apiUsername") {
        // replace underscores in the fieldnames with decimals
        for ($i = 0; $i < strlen($fieldName); $i++) {
          if ($fieldName[$i] == '_')
            $fieldName[$i] = '.';
        }
        $request .= $fieldName . "=" . urlencode($fieldValue) . "&";
      }
    }

    // [Snippet] howToSetCredentials - start
    // For NVP, authentication details are passed in the body as Name-Value-Pairs, just like any other data field
    $request .= "merchant=" . urlencode($merchantObj->GetMerchantId()) . "&";
    $request .= "apiPassword=" . urlencode($merchantObj->GetPassword()) . "&";
    $request .= "apiUsername=" . urlencode($merchantObj->GetApiUsername());
    // [Snippet] howToSetCredentials - end

    return $request;
  }
  // [Snippet] howToConvertFormData - end
}

?>