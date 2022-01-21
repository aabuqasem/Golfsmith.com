<?php 
require_once('gsi_google_defs.inc');
require_once('models/XmlObject.php');

class GoogleCheckout {

  protected $v_order_number;
  protected $i_site_init;

  public function __construct() {

    //if init is already loaded, don't load it again
    //otherwise, we get function redefinition errors
    if(!function_exists('gsi_mssql_bind')) {
      //load site init for database connections, helper functions, etc.
      global $connect_mssql_db;
      $connect_mssql_db = 1;

      $this->i_site_init = new SiteInit();
      $this->i_site_init->loadInit($v_connect_mssql);
    }

    $this->v_order_number = $_SESSION['s_order_number'];

  }

  public function hasExistingGoogleOrder() {
    global $mssql_db;

    if(preg_match('/^G.*/', $this->v_order_number) > 0) {
      $this->v_order_number = substr($this->v_order_number, 1);
    }

    if(empty($this->v_order_number)){
      return FALSE;
    }

    $v_sql = "select gsi_order_number
              from direct.dbo.gsi_google_header_validations
              where gsi_order_number = '$this->v_order_number'";

    $v_result = mssql_query($v_sql);
    if (!$v_result) {
      display_mssql_error($v_sql, "call from hasExistingGoogleOrder() in GoogleCheckout.php");
    }

    while ($va_row = mssql_fetch_array($result)) {
      $v_new_order_number = $va_row['gsi_order_number'];
    }

    mssql_free_statement($v_sql);
    mssql_free_result($v_result);

    if(!empty($v_new_order_number) && $v_new_order_number != 'N') {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function getCheckoutUrl() {

    global $mssql_db;

    if(!empty($this->v_order_number)) {

      $this->setErrorHandler();

      //map order number so that the "real" value isn't obvious in URL
      $v_result_order_number = map_order_number($this->v_order_number);

      $v_edit_cart_url = RETURN_BASE_URL . '/' . $_SESSION['s_screen_name']. '/checkout/cart/?order_number=' . $v_result_order_number;

      $v_partners = '|CM|';

      if(!empty($_COOKIE['prtcode'])) {
        $v_partner_code = $_COOKIE['prtcode'];
      } else {
        $v_partner_code = '';
      }

      if($v_partner_code == 'CJ') {
        $v_partners .= 'CJ|';
      }

      $v_merch_url = MERCHANT_CALC_URL;

      $v_stmt = mssql_init("gsi_google_shopping_cart_xml");

      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_merchant_url", $v_merch_url, 'varchar', 250);
      gsi_mssql_bind($v_stmt, "@p_edit_cart_url", $v_edit_cart_url, 'varchar', 250);
      gsi_mssql_bind($v_stmt, "@p_tracking_partners", $v_partners, 'varchar', 50);

      $v_result = mssql_execute($v_stmt);

      if($va_row = mssql_fetch_assoc($v_result)) {
        $v_xml_str = $va_row['xml_str'];
      }

      if (!$v_result){
        display_mssql_error("gsi_google_shopping_cart_xml", "called from getCheckoutUrl() in GoogleCheckout.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      $v_merch_url = MERCHANT_CALC_URL ;

      write_google_log($v_xml_str, ACTION_LOG);

      //post request to Google via cURL
      $v_response = $this->sendRequest($v_xml_str, get_post_url("request"));

      //form XML object
      $v_xml_data = trim($v_response);
      write_google_log($v_response, ACTION_LOG);

      $i_xml_data_object = new xmlObject($v_xml_data);
      $va_redirect_tags = $i_xml_data_object->get_elements_by_tag_name("redirect-url");

      $v_url = '';

      if(!empty($va_redirect_tags)) {
        $v_url = $va_redirect_tags[0]['value'];

        //replace &amp; in redirection URL with just plain & (although contrary to documentation, &amp; doesn't seem to occur anyway)
        $v_url = preg_replace('/&amp;/', '&', $v_url);
      }

      if(!empty($v_url)) {

        $v_result = $v_url;

      } else {

        $v_result = 'Error: There was an error transmitting your cart to Google. We apologize for any inconvenience.';

      }

      $this->restoreErrorHandler();

    } else { //show the empty cart page if missing an order number

      $v_result = 'Error: There are no items in your cart to check out via Google Checkout.';

    }

    return $v_result;

  }

  /**
   * Sends an order processing request to the Google server.
   * HTTP Basic Authentication scheme is used to authenticate the message.
   * This function utilizes cURL, client URL library functions.
   * cURL is supported in PHP 4.0.2 or later versions, documented at
   * http://us2.php.net/curl
   *
   * @param       $request                XML order processing command
   * @param       $post_url               URL address to POST the form
   * @return      $response               synchronous response received from the Google server
   */
  private function sendRequest($p_request, $p_post_url) {

    // Check for errors
    $v_error_function_name = "send_request()";

    // Check for missing parameters
    $v_check_array = array('request'=>$p_request, 'post_url'=>$p_post_url);
    $this->checkRequired($v_error_function_name, $v_check_array);

    $v_response =  $this->performCURLRequest($p_request, $p_post_url);

    return $v_response;
  }

  /**
   * Check to see if there is a missing parameter that should trigger an error
   */
  private function checkRequired($p_function_name, &$p_name_value_array) {

    foreach($p_name_value_array as $v_name=>$v_value) {
      if(empty($v_value)) {
        $v_msg = "Missing Parameter: $name must be provided.";
        trigger_error($v_msg, E_USER_ERROR);
      }
    }
  }

  /**
   * Execute a cURL request and return the cURL response
   */
  function performCURLRequest($p_request, $p_post_url) {

    $v_ch = curl_init();
    curl_setopt($v_ch, CURLOPT_URL, $p_post_url);
    curl_setopt($v_ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($v_ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($v_ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($v_ch, CURLOPT_SSL_VERIFYHOST, 2);
    $v_pos = strpos($p_post_url, "request");

    if($v_pos !== false) {

      // Set Content-type and Accept header information
      $va_header = array();
      $va_header[] = "Content-type: application/xml";
      $va_header[] = "Accept: application/xml";
      curl_setopt($v_ch, CURLOPT_USERPWD, MERCHANT_ID . ":" . MERCHANT_KEY);
      curl_setopt($v_ch, CURLOPT_HTTPHEADER, $va_header);
    }

    curl_setopt($v_ch, CURLOPT_POST, 1);
    curl_setopt($v_ch, CURLOPT_POSTFIELDS, $p_request);

    $v_response = curl_exec($v_ch);

    if(curl_errno($v_ch)) {
      trigger_error(curl_error($v_ch), E_USER_ERROR);
    } else {
      curl_close($v_ch);
    }

    return $v_response;
  }

  public function errorHandler($errno, $errstr, $errfile, $errline) {

    $v_msg = "Error or warning on line $errline: $errstr";

    write_google_log($v_msg, ERROR_LOG);

  }

  private function setErrorHandler() {
    set_error_handler(array($this, "errorHandler"), E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);
  }

  private function restoreErrorHandler() {
    restore_error_handler();
  }

}
?>
