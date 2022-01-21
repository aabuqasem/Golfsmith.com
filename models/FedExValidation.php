<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here

class FedExValidation {

  public function __construct() {
  }

	public function validateAddress($p_address1, $p_address2, $p_city, $p_postal_code, $p_state, $p_country, &$p_is_valid, &$va_proposed_address, &$va_error_list) {
	//I'll keep the function and var names just to remove any confusing in the codeing call 
    	//$v_web_service = FEDEX_SERVER_URL;
    	$v_web_service = dirname(__FILE__) . '/AddressVerification.wsdl';
    	//$v_web_service = "http://devsoacluster.gsicorp.com:7777/osb/WebPub/AddressVerificationSvc?wsdl";
    	try {
      	$va_params = array ('AddressVerificationParameters' =>
      					array(
      					'OriginatingSystem' => "GS.COM", 
      					'ShippingAddress' => array(
      							'Address1' => $this->changeIsNull($p_address1), 
      							'Address2' => $this->changeIsNull($p_address2),
      							'City' => $this->changeIsNull($p_city), 
      							'PostalCode' => $this->changeIsNull($p_postal_code), 
      							'StateProvince' => $this->changeIsNull($p_state), 
      							'CountryCode' => $this->changeIsNull($p_country))
      					)
      				);
 		
      	$v_sclient = new SoapClient($v_web_service, array("soap_version"  => SOAP_1_1, 'trace' => true));

		$credentials = array("UsernameToken" => array("Username" => 'phpweb', "Password" => 'g$iw3B!nV' ));
		$security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
		//create header object
		$header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true);
		//$v_sclient->__setSoapHeaders( $header );
	      				
    	$v_result = $v_sclient->process($va_params);
    	$ValidFlag = $v_result->AddressVerificationResult->ValidFlag;
    	if ($ValidFlag == "INVALID"){
    		$v_return_state["status"]="INVALID";
    		$v_return_state["msg"]="INVALID";
    	} else if ($ValidFlag == "SUGGEST"){
    		$v_return_state["status"]="SUGGEST";
    		$v_return_state["suggestions"]=$v_result->AddressVerificationResult->SuggestedAddresses->address;
    		
    	}else if ($ValidFlag == "VALID"){
    		$v_return_state["status"]="VALID";
    	}
      	//$v_xml_data = $v_result->ValidateAddressResult->any;

      //$va_proposed_address = $this->getProposedAddresses($v_xml_data);

      //This will get the xml value of the IsValid tag
      //$p_is_valid = $this->getXMLValue($v_xml_data,'<IsValid>','</IsValid>', 1);

      //$va_error_list = $this->getErrorList($v_xml_data);
		return $v_return_state;
    } catch (SoapFault $f) {
      $v_return_status = "Service not available\n";
    }

    return $v_return_status;

  }

  public function translateState($p_state) {

    switch ($p_state) {
      case 'AA': $v_new_state = 'FL';
                 break;
      case 'AE': $v_new_state = 'NY';
                 break;
      case 'AP': $v_new_state = 'CA';
                 break;
      default:   $v_new_state = $p_state;
                 break;
    }

    return $v_new_state;

  }

  public function isUSZip($p_postal_code) {
    $p_postal_code = str_replace('-', '', $p_postal_code);
    if(preg_match('/[^0-9]/', $p_postal_code)) {
      return false;
    } else {
      return true;
    }
  }

  private function getProposedAddresses($p_xml) {

    $v_add_occur = substr_count($p_xml, '<Address>');

    $va_add = array();
    $va_add2 = array();

    if($v_add_occur > 0) {
      for($i = 1; $i <= $v_add_occur; $i++) {
        $v_xml_add = $this->getXMLValue($p_xml, '<Address>', '</Address>', $i);

        $v_street_occur = substr_count($v_xml_add, '<StreetLine');

        for($j = 1; $j <= $v_street_occur; $j++) {
          $v_street_tagname = 'StreetLine' . $j;
          $v_street_start_tag = '<' . $v_street_tagname . '>';
          $v_street_end_tag = '</' . $v_street_tagname . '>';
          $va_add2[$v_street_tagname] = $this->getXMLValue($v_xml_add, $v_street_start_tag, $v_street_end_tag, 1);
        }

        $va_add2['City'] = $this->getXMLValue($v_xml_add, '<City>', '</City>', 1);
        $va_add2['State'] = $this->getXMLValue($v_xml_add, '<State>', '</State>', 1);
        $va_add2['PostalCode'] = $this->getXMLValue($v_xml_add, '<PostalCode>', '</PostalCode>', 1);
        $va_add2['Country'] = $this->getXMLValue($v_xml_add, '<Country>', '</Country>', 1);
        $va_add2['AddressType'] = $this->getXMLValue($v_xml_add, '<AddressType>', '</Addresstype>', 1);

        $va_add[] = $va_add2;
      }
    }

    return $va_add;

  }

  private function getErrorList($p_xml) {

    $v_change_occur = substr_count($p_xml, '<Change>');
    $va_error = array();

    if($v_change_occur > 0) {
      for($i = 1; $i <= $v_change_occur; $i++) {
        $va_error[] = $this->getXmlValue($p_xml, '<Change>', '</Change>', $i);
      }
    }

    return $va_error;

  }
  
  private function getXmlValue($p_str_xml, $p_start_tag, $p_end_tag, $p_offset) {

    $v_start_pos = $this->strposOffset($p_start_tag, $p_str_xml, $p_offset) + strlen($p_start_tag);
    $v_end_pos = $this->strposOffset($p_end_tag, $p_str_xml, $p_offset);

    return substr($p_str_xml, $v_start_pos, $v_end_pos - $v_start_pos);

  }

  private function strposOffset($p_search, $p_string, $p_offset) {
    // explode the string
    $va_string_parts = explode($p_search, $p_string);

    // check the search is not out of bounds
    switch($p_offset) {
        case $p_offset == 0: return false;
                             break;

        case $p_offset > max(array_keys($va_string_parts)):
                             return false;
                             break;

        default:
                             return strlen(implode($p_search, array_slice($va_string_parts, 0, $p_offset)));
    }
  }

  private function changeIsNull($p_value) {
    return (is_null($p_value) ? '' : $p_value);
  }

}
?>
