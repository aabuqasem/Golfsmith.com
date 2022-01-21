<?php
// vim: set filetype=php:
/**
 * Interfaces with Oracle to get non-general XML to send to PayPal
 */

//creates a paypal order (without charging anything)
class PayPalApiCall {

  var $process_xml = '';
  var $soap_val = '';
  var $package_name = 'golf.gsi_cmn_paypal_pkg';

  //initialize object
  //nothing to do in the constructor, but maybe we'll find something at some point
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

  }

  public function send_xml() {

    date_default_timezone_set('America/Chicago');
    
    try {

      //we now use PHP5's native SOAP client
      $sclient = new SoapClient(null, array('location'=> PAYPAL_ENDPOINT, 'uri' => XMLNS_PAYPALAPI, 'trace' => TRUE, 'encoding' => 'ISO-8859-1'));

      $hdr_var = new SoapVar(generate_header_credentials(), 147);

      $hdr = new SoapHeader(XMLNS_PAYPALAPI, "RequesterCredentials", $hdr_var);

      $xml_var = new SoapVar($this->process_xml, 147);

      $msg = "XML message sent to PayPal:\n" . $this->process_xml;
      write_paypal_log($msg, ACTION_LOG);

      $resp = $sclient->__soapCall($this->soap_val . 'Req', array($xml_var), null, $hdr);

      $resp_xml = $sclient->__getLastResponse();

      //log the XML response - this one will be the full SOAP message
      $msg = "SOAP response received from PayPal:\n" . $resp_xml;
      write_paypal_log($msg, ACTION_LOG);

    } catch (SoapFault $f) {


      $resp_xml = '';
    }

    //should we authenticate here and not in calling script?

    return $resp_xml;

  }

  //check whether there was success or failure, and act accordingly 
  //right now, only use this with reauth
  //if it works, use it for processing charge/refund resp as well
  public function check_success(&$response, &$xmlObj) {

    if(!empty($response)) {
      $fault = $xmlObj->get_elements_by_tag_name('faultcode');
      if(!empty($fault[0]['value'])) {
        $fault = $fault[0]['value'];
      } else {
        $fault = '';
      }

      $ack = $xmlObj->get_elements_by_tag_name('Ack');
      $ack = $ack[0]['value'];

      if(!empty($fault) || $ack == 'Failure' || $ack == 'FailureWithWarning') { //soap fault or complete failure
        $success = FALSE;
      } else if($ack == 'SuccessWithWarning') {
        //success is true, but there was a warning... log the response to look at the warnings later
        $success = TRUE;
        write_paypal_log("Successful request, but with warnings:\n" . $resp, XML_ERROR_LOG);
      } else if($ack == 'CustomCode') {  //this case shouldn't happen, but PayPal reserves it as a possible value
        //we'll call this a failure for now
        $success = FALSE;
      } else if($ack == 'Success') { //Success is the only thing left
        $success = TRUE;
      } else {  //how did we get here?
        $success = FALSE;
      }
    } else {  //empty response will most empty be a timeout (i.e., always a failure)
      $success = FALSE;
    }

    return $success; 

  }

  /******************************************************************
  * Generates and stores XML for SetExpressCheckoutRequest
  * Note: $token is optional; we usually won't have it, 
  * but occasionally we will.
  ******************************************************************/
  public function generate_setcheckout_xml($p_gsi_order_number, $p_token) {
 
    global $mssql_db;

    $this->soap_val = 'SetExpressCheckout';
    
    $v_mapped_order_number = map_order_number($p_gsi_order_number);

    //ReturnURL and CancelURL are constants
    $v_return_url = PAYPAL_RETURN_URL . '?order_number=' . $v_mapped_order_number;
    $v_cancel_url = PAYPAL_CANCEL_URL . '?order_number=' . $v_mapped_order_number;

    //perform MSSQL call
    $v_stmt = mssql_init("gsi_paypal_create_checkout_request_xml");

    //bind vars
    mssql_bind($v_stmt, "@p_original_system_reference", $p_gsi_order_number, SQLVARCHAR, false, false, 50);
    mssql_bind($v_stmt, "@p_return_url", $v_return_url, SQLVARCHAR, false, false, 250);
    mssql_bind($v_stmt, "@p_cancel_url", $v_cancel_url, SQLVARCHAR, false, false, 250);

    mssql_bind($v_stmt, "@p_xml", $v_xml, SQLVARCHAR, true, false, 1000);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_paypal_create_checkout_request_xml", 'call to gsi_paypal_create_checkout_request_xml');
    }

    mssql_free_statement($v_stmt);

    //set/return XML
    $this->process_xml = $v_xml;

    return $v_xml;
  }

  public function generate_getdetails_xml($p_token) {

    global $apps_db;
    global $mssql_db;


    $this->soap_val = 'GetExpressCheckoutDetails';

    //perform MSSQL call
    $v_stmt = mssql_init("gsi_paypal_create_checkout_details_xml");

    mssql_bind($v_stmt, "@p_token", $p_token, SQLVARCHAR, false, false, 50);
    mssql_bind($v_stmt, "@p_xml", $v_xml, SQLVARCHAR, true, false, 1000);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_paypal_create_checkout_details_xml", 'MSSQL: call to gsi_paypal_create_checkout_details_xml'); 
    }

    //set/return XML
    $this->process_xml = $v_xml;

    return $v_xml;
  }

  public function generate_createorder_xml($p_gsi_order_number, $p_token, $p_payer_id) {

    global $mssql_db;

    $this->soap_val = 'DoExpressCheckoutPayment';

    //perform MSSQL call
    $v_stmt = mssql_init("gsi_paypal_create_checkout_payment_xml");

    //bind vars
    mssql_bind($v_stmt, "@p_original_system_reference", $p_gsi_order_number, SQLVARCHAR, false, false, 50);
    mssql_bind($v_stmt, "@p_token", $p_token, SQLVARCHAR, false, false, 50);
    mssql_bind($v_stmt, "@p_payer_id", $p_payer_id, SQLVARCHAR, false, false, 50);

    mssql_bind($v_stmt, "@p_xml", $v_xml, SQLVARCHAR, true, false, 2000);
    
    $v_result = mssql_execute($v_stmt);
    
    if(!$v_result) {
      display_mssql_error("gsi_paypal_create_checkout_payment_xml", 'MSSQL: call to gsi_paypal_create_checkout_payment_xml');
    }

    //set/return XML
    $this->process_xml = $v_xml;

    return $v_xml;  

  }

  //extra function for order auth/addition to make sure we received success
  //need to have boolean for success AND an error code, because not all failures will have error messages
  // (but we want to record error code if we get one)
  public function process_createorder(&$success, &$error_message) {

    $success = TRUE;
    $authorization_id = FALSE;
    $error_message = '';
    $order_id = '';

    if($this->soap_val != 'DoExpressCheckoutPayment') {

      //our XML isn't on the auth step
      $success = FALSE;

    } else {

      $resp = $this->send_xml();

      if(!empty($resp)) {
        $xmlObj = new xmlObject($resp);

        //check for ack
        $ack = $xmlObj->get_elements_by_tag_name('Ack');
        if(!empty($ack[0]['value'])) {
          $ack = $ack[0]['value'];
        }

        //check for soap fault -- if we don't have an ack, we should at least have a soap fault
        $fault = $xmlObj->get_elements_by_tag_name('faultcode');
        if(!empty($fault[0]['value'])) {
          $fault = $fault[0]['value'];
        } else { //else make sure $fault is empty
          $fault = '';
        }

        if(!empty($fault) || $ack == 'Failure' || $ack == 'FailureWithWarning') { //soap fault or complete failure
          $success = FALSE;
          if(!empty($fault)) {
            //collect faultstring
            $error_message = $xmlObj->get_elements_by_tag_name('faultstring');
          } else {
            //collect LongMessage for now; may need to change to ShortMessage later
            $error_message = $xmlObj->get_elements_by_tag_name('LongMessage');
          }

          //get first value
          $error_message = $error_message[0]['value'];
 
          //trim to fit into auth_decline_message field
          $error_message = substr($error_message, 0, 50);

        } else if($ack == 'SuccessWithWarning') {
          //success is true, but there was a warning... log the response to look at the warnings later
          $success = TRUE;
          write_paypal_log("Successful DoExpressCheckoutPaymentRequest, but with warnings:\n" . $resp, XML_ERROR_LOG);
        } else if($ack == 'CustomCode') {  //this case shouldn't happen, but PayPal reserves it as a possible value
          //we'll call this a failure for now
          $success = FALSE;
        } else if($ack == 'Success') { //Success is the only thing left
          $success = TRUE;
        } else {  //how did we get here?
          $success = FALSE;
        }
      } else { //empty response
        $success = FALSE;
      }

      if($success === TRUE) {
        //need to get TransactionID (OrderID)
        $order_id = $xmlObj->get_elements_by_tag_name('TransactionID');
        $order_id = $order_id[0]['value'];
      }

    }

    return $order_id;

  }

  public function process_authorization(&$success, &$error_message, &$error_code) {

    $success = TRUE;
    $auth_id = FALSE;
    $error_message = '';

    if($this->soap_val != 'DoAuthorization') {

      //our XML isn't on the auth step
      $success = FALSE;

    } else {

      $resp = $this->send_xml();

      if(!empty($resp)) {
        $xmlObj = new xmlObject($resp);

        //check for ack
        $ack = $xmlObj->get_elements_by_tag_name('Ack');
        if(!empty($ack[0]['value'])) {
          $ack = $ack[0]['value'];
        }

        //check for soap fault -- if we don't have an ack, we should at least have a soap fault
        $fault = $xmlObj->get_elements_by_tag_name('faultcode');
        if(!empty($fault[0]['value'])) {
          $fault = $fault[0]['value'];
        } else { //else make sure $fault is empty
          $fault = '';
        } 

        if(!empty($fault) || $ack == 'Failure' || $ack == 'FailureWithWarning') { //soap fault or complete failure
          $success = FALSE;
          if(!empty($fault)) {
            //collect faultstring
            $error_message = $xmlObj->get_elements_by_tag_name('faultstring');
            $error_code = '';
          } else {
            //collect LongMessage for now; may need to change to ShortMessage later
            $error_message = $xmlObj->get_elements_by_tag_name('LongMessage');
            $error_code = $xmlObj->get_elements_by_tag_name('ErrorCode');
          }

          //get first value
          $error_message = $error_message[0]['value'];

          //trim to fit into auth_decline_message field
          $error_message = substr($error_message, 0, 50);

          if(!empty($error_code)) {
            $error_code = $error_code[0]['value'];
          }

        } else if($ack == 'SuccessWithWarning') {
          //success is true, but there was a warning... log the response to look at the warnings later
          $success = TRUE;
          write_paypal_log("Successful DoAuthorization, but with warnings:\n" . $resp, XML_ERROR_LOG);
        } else if($ack == 'CustomCode') {  //this case shouldn't happen, but PayPal reserves it as a possible value
          //we'll call this a failure for now
          $success = FALSE;
        } else if($ack == 'Success') { //Success is the only thing left
          $success = TRUE;
        } else {  //how did we get here?
          $success = FALSE;
        }
      } else { //empty response
        $success = FALSE;
      }

      if($success === TRUE) {
        //need to get TransactionID (OrderID)
        $auth_id = $xmlObj->get_elements_by_tag_name('TransactionID');
        $auth_id = $auth_id[0]['value'];
      }

    }

    return $auth_id;

  }

  //this function is for using DoAuthorization for a reauth ONLY
  //it's not needed for anything else
  public function process_authorization_reauth($gsi_order_number, $paypal_order_number, $payment_detail_id = '') {

    global $apps_db;

    $authorization_id = $this->process_authorization($success, $error_message, $error_code);
 
    if($success === TRUE) {

      $status = 1;

      //now post to database
      //DO NOT add a response message in this case, because that's misleading
      //"APPROVED" in gsi_payment_details.response_message should ALWAYS mean that the capture succeeded, not the reauth
      $sql = "begin " . $this->package_name . ".post_reauthorization_result(p_gsi_order_number => :order_number, p_paypal_order_number => :paypal_order_number, p_auth_decline_message => :auth_id, p_process_flag => :status); end;";

      $stmt = OCIParse($apps_db, $sql);

      OCIBindByName($stmt, ":order_number", $gsi_order_number, -1);
      OCIBindByName($stmt, ":paypal_order_number", $paypal_order_number, -1);
      OCIBindByName($stmt, ":auth_id", $authorization_id, -1);
      OCIBindByName($stmt, ":status", $status, -1);

      OCIExecute($stmt);
      display_error($stmt, $sql);
    } else {  //we had a failure of some sort

      if($error_code == '10610') { //if amount limit exceeded was the error message, try again next time
        $status = -1;
        //don't update response message, since reauth will be tried again
        $response_message = '';
      } else {
        $status = 2;
        $response_message = 'REAUTH DECLINED ' . $error_code;
      }

      //now post to database
      $sql = "begin " . $this->package_name . ".post_reauthorization_result(p_gsi_order_number => :order_number, p_paypal_order_number => :paypal_order_number, p_process_flag => :status";


      if(!empty($payment_detail_id) && !empty($response_message)) {
        $sql .= ", p_payment_detail_id => :payment_detail_id, p_response_message => :response_message";
      }

      $sql .= "); end;";

      $stmt = OCIParse($apps_db, $sql);

      OCIBindByName($stmt, ":order_number", $gsi_order_number, -1);
      OCIBindByName($stmt, ":paypal_order_number", $paypal_order_number, -1);
      OCIBindByName($stmt, ":status", $status, -1);

      if(!empty($payment_detail_id) && !empty($response_message)) {
        OCIBindByName($stmt, ":payment_detail_id", $payment_detail_id, -1);
        OCIBindByName($stmt, ":response_message", $response_message, -1);
      }

      OCIExecute($stmt);
      display_error($stmt, $sql);
    }

    return $success;

  }

  public function generate_authorization_xml($gsi_order_number, $paypal_order_number, $is_reauth = FALSE, &$payment_detail_id) {

    global $mssql_db;
    global $apps_db;

    $this->soap_val = 'DoAuthorization';

    //if we're doing initial auth, call mssql
    if($is_reauth !== TRUE) {

      $v_stmt = mssql_init("gsi_paypal_create_authorization_xml");

      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $gsi_order_number, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_paypal_order_number", $paypal_order_number, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_xml", $v_xml, 'varchar', 2000, true);

      $v_result = mssql_execute($v_stmt);
    
      if(!$v_result) {
        display_mssql_error('gsi_paypal_create_authorization_xml', 'call to gsi_paypal_create_checkout_request_xml');
      }

      mssql_free_statement($v_stmt);

    } else { //reauths need to call Oracle
          
      $is_reauth = 'Y';

      $sql = "begin :xml := " . $this->package_name . ".create_authorization_xml(p_gsi_order_number => :order_number, p_paypal_order_number => :paypal_order_number, p_is_reauth => :is_reauth, p_payment_detail_id => :payment_detail_id); end;";

      $stmt = OCIParse($apps_db, $sql);

      OCIBindByname($stmt, ":order_number", $gsi_order_number, -1);
      OCIBindByName($stmt, ":paypal_order_number", $paypal_order_number, -1);
      OCIBindByName($stmt, ":is_reauth", $is_reauth, -1);

      OCIBindByName($stmt, ":xml", $v_xml, 10000);
      OCIBindByName($stmt, ":payment_detail_id", $payment_detail_id, 50);
      OCIExecute($stmt);

      display_error($stmt, $sql);

    }

    //set/return XML
    $this->process_xml = $v_xml;

    return $v_xml;

  }

  //keep MSSQL call separate now; we still need Oracle call for reauths
  public function generate_initial_auth_xml($gsi_order_number, $paypal_order_number, &$payment_detail_id) {

    global $mssql_db;

    $this->soap_val = 'DoAuthorization';

    //perform MSSQL call
    $v_stmt = mssql_init("gsi_paypal_create_authorization_xml");

    //bind vars
    mssql_bind($v_stmt, "@p_original_system_reference", $p_gsi_order_number, SQLVARCHAR, false, false, 50);
    mssql_bind($v_stmt, "@p_paypal_order_number", $p_paypal_order_number, SQLVARCHAR, false, false, 250);

    mssql_bind($v_stmt, "@p_xml", $v_xml, SQLVARCHAR, true, false, 2000);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_paypal_create_authorization_xml", 'call to gsi_paypal_create_authorization_xml');
    }

    //set/return XML
    $this->process_xml = $v_xml;

    return $v_xml;

  }

  public function generate_capture_xml($gsi_order_number) { //add params as needed

    global $apps_db;

    $this->soap_val = 'DoCapture';

    //perform Oracle call
    $sql = "begin :xml := " . $this->package_name . ".create_capture_request_xml(p_gsi_order_number => :order_number); end;";

    $stmt = OCIParse($apps_db, $sql);

    OCIBindByname($stmt, ":order_number", $gsi_order_number, -1);

    OCIBindByName($stmt, ":xml", $xml, 10000);
    OCIExecute($stmt);

    display_error($stmt, $sql);

    //set/return XML
    $this->process_xml = $xml;

    return $xml;

  }

  public function process_refund_response($gsi_order_number, $merchant_settlement_id, $payment_detail_id, $response) {
    global $apps_db;

    $xmlObj = new xmlObject($response);

    $fault = $xmlObj->get_elements_by_tag_name('faultcode');
    if(!empty($fault[0]['value'])) {
      $fault = $fault[0]['value'];
    } else {
      $fault = '';
    }

    $ack = $xmlObj->get_elements_by_tag_name('Ack');
    $ack = $ack[0]['value'];

    if(!empty($fault) || $ack == 'Failure' || $ack == 'FailureWithWarning') { //soap fault or complete failure
      $success = FALSE;
    } else if($ack == 'SuccessWithWarning') {
      //success is true, but there was a warning... log the response to look at the warnings later
      $success = TRUE;
      write_paypal_log("Successful request, but with warnings:\n" . $resp, XML_ERROR_LOG);
    } else if($ack == 'CustomCode') {  //this case shouldn't happen, but PayPal reserves it as a possible value
      //we'll call this a failure for now
      $success = FALSE;
    } else if ($ack == 'Success') { //Success is the only thing left
      $success = TRUE;
    } else {  //how did we get here?
      $success = FALSE;
    }

    if($success === TRUE) {
      $status = 'CONF';
      $response_message = 'APPROVED';
    } else {
      $status = 'FAILED';
      if(empty($faultcode) && !empty($response)) {
        $response_message = $xmlObj->get_elements_by_tag_name('ErrorCode');
        $response_message = $response_message[0]['value'];
        $response_message = 'DECLINED ' . $response_message;
      } else if(!empty($faultcode)) {
        $response_message = 'FAILURE: SOAPFAULT';
      } else if(empty($response)) {
        $response_message = 'FAILURE: NO RESPONSE';
      } else {
        $response_message = 'FAILURE: UNKNOWN';
      }
    }

    //get the amount out of the request for matching purposes
    $requestObj = new xmlObject($this->process_xml);

    $amount = $requestObj->get_elements_by_tag_name('Amount');
    $currency = $amount[0]['attributes']['CURRENCYID'];
    $amount = $amount[0]['value'];

    //now post to database

    $sql = "begin " . $this->package_name . ".post_refund_result(p_gsi_order_number => :order_number, p_amount => :amount, p_currency => :currency, p_merchant_settlement_id => :transaction_id, p_charge_status => :status, p_response_message => :response_message";


    if(!empty($payment_detail_id)) {
      $sql .= ", p_payment_detail_id => :payment_detail_id";
    }

    $sql .= "); end;";

    $stmt = OCIParse($apps_db, $sql);

    OCIBindByName($stmt, ":order_number", $gsi_order_number, -1);
    OCIBindByName($stmt, ":amount", $amount, -1);
    OCIBindByName($stmt, ":currency", $currency, -1);
    OCIBindByName($stmt, ":transaction_id", $merchant_settlement_id, -1);
    OCIBindByName($stmt, ":status", $status, -1);
    OCIBindByName($stmt, ":response_message", $response_message, -1);

    if(!empty($payment_detail_id)) {
      OCIBindByName($stmt, ":payment_detail_id", $payment_detail_id, -1);
    }

    OCIExecute($stmt);

    display_error($stmt, $sql);

    return $success;

  }

  public function process_charge_response($gsi_order_number, $payment_detail_id, &$response) {

    global $apps_db;

    $xmlObj = new xmlObject($response);

    $fault = $xmlObj->get_elements_by_tag_name('faultcode');
    if(!empty($fault[0]['value'])) {
      $fault = $fault[0]['value'];
    } else {
      $fault = '';
    }

    $ack = $xmlObj->get_elements_by_tag_name('Ack');
    if(!empty($ack[0]['value'])) {
      $ack = $ack[0]['value'];
    } else {
      $ack = '';
    }

    if(empty($response) || !empty($fault) || $ack == 'Failure' || $ack == 'FailureWithWarning') { //soap fault or full failure
      $success = FALSE;
    } else if($ack == 'SuccessWithWarning') {
      //success is true, but there was a warning... log the response to look at the warnings later
      $success = TRUE;
      write_paypal_log("Successful request, but with warnings:\n" . $resp, XML_ERROR_LOG);
    } else if($ack == 'CustomCode') {  //this case shouldn't happen, but PayPal reserves it as a possible value
      //we'll call this a failure for now
      $success = FALSE;
    } else if($ack == 'Success') { //Success is the only thing left
      $success = TRUE;
    } else { //how did we get here?
      $success = FALSE;
    }

    if($success === TRUE) {

      $transaction_id = $xmlObj->get_elements_by_tag_name('TransactionID');
      $transaction_id = $transaction_id[0]['value'];

      if(empty($payment_detail_id)) {
        $amount = $xmlObj->get_elements_by_tag_name('GrossAmount');
        $currency = $amount[0]['attributes']['CURRENCYID'];
        $amount = $amount[0]['value'];
      }

      $status = 'CONF';

      $response_message = 'APPROVED';

      //now post to database

      $sql = "begin " . $this->package_name . ".post_charge_result(p_gsi_order_number => :order_number, p_merchant_settlement_id => :transaction_id, p_charge_status => :status, p_response_message => :response_message";

      if(!empty($payment_detail_id)) {
        $sql .= ", p_payment_detail_id => :payment_detail_id";
      } else {
        $sql .= ", p_amount => :amount, p_currency => :currency";
      }

      $sql .= "); end;";

      $stmt = OCIParse($apps_db, $sql);

      OCIBindByName($stmt, ":order_number", $gsi_order_number, -1);
      OCIBindByName($stmt, ":transaction_id", $transaction_id, -1);
      OCIBindByName($stmt, ":status", $status, -1);
      OCIBindByName($stmt, ":response_message", $response_message, -1);

      if(!empty($payment_detail_id)) {
        OCIBindByName($stmt, ":payment_detail_id", $payment_detail_id, -1);
      } else {
        OCIBindByName($stmt, ":amount", $amount, -1);
        OCIBindByName($stmt, ":currency", $currency, -1);
      }

      OCIExecute($stmt);

      display_error($stmt, $sql);

    } else {  //also need to post failure to database

      //get error data from the response XML
      if(empty($faultcode) && !empty($response)) {
        $response_message = $xmlObj->get_elements_by_tag_name('ErrorCode');
        $response_message = $response_message[0]['value'];
        $response_message = 'DECLINED ' . $response_message;
      } else if(!empty($faultcode)) {
        $response_message = 'FAILURE: SOAPFAULT';
      } else if(empty($response)) {
        $response_message = 'FAILURE: NO RESPONSE';
      } else {
        $response_message = 'FAILURE: UNKNOWN';
      }

      //our amount, etc. will have to come from the request
      //we won't have a transaction ID
      if(empty($payment_detail_id)) {
        $requestObj = new xmlObject($this->process_xml);

        $amount = $requestObj->get_elements_by_tag_name('Amount');
        $currency = $amount[0]['attributes']['CURRENCYID'];
        $amount = $amount[0]['value'];
      }

      $status = 'FAILED';

      $sql = "begin " . $this->package_name . ".post_charge_result(p_gsi_order_number => :order_number, p_charge_status => :status, p_response_message => :response_message";

      if(!empty($payment_detail_id)) {
        $sql .= ", p_payment_detail_id => :payment_detail_id";
      } else {
        $sql .= ", p_amount => :amount, p_currency => :currency";
      }

      $sql .= "); end;";

      $stmt = OCIParse($apps_db, $sql);

      OCIBindByName($stmt, ":order_number", $gsi_order_number, -1);
      OCIBindByName($stmt, ":status", $status, -1);
      OCIBindByName($stmt, ":response_message", $response_message, -1);

      if(!empty($payment_detail_id)) {
        OCIBindByName($stmt, ":payment_detail_id", $payment_detail_id, -1);
      } else {
        OCIBindByName($stmt, ":amount", $amount, -1);
        OCIBindByName($stmt, ":currency", $currency, -1);
      }

      OCIExecute($stmt);

      display_error($stmt, $sql);

    }

    return $success;

  }

  public function generate_refund_xml($merchant_settlement_id, $payment_detail_id) { //add params as needed

    global $apps_db;

    $this->soap_val = 'RefundTransaction';

    //perform Oracle call
    $sql = "begin :xml := " . $this->package_name . ".create_refund_transaction_xml(p_merchant_settlement_id => :merchant_settlement_id, p_payment_detail_id => :payment_detail_id); end;";

    $stmt = OCIParse($apps_db, $sql);

    OCIBindByname($stmt, ":merchant_settlement_id", $merchant_settlement_id, -1);
    OCIBindByName($stmt, ":payment_detail_id", $payment_detail_id, -1);

    OCIBindByName($stmt, ":xml", $xml, 10000);
    OCIExecute($stmt);

    display_error($stmt, $sql);

    //set/return XML
    $this->process_xml = $xml;

    return $xml;

  }

  public function generate_reauthorize_xml($gsi_order_number) {
    global $apps_db;

    $this->soap_val = 'DoReauthorization';

    //perform Oracle call
    $sql = "begin :xml := " . $this->package_name . ".create_reauthorization_xml(p_gsi_order_number => :order_number); end;";

    $stmt = OCIParse($apps_db, $sql);

    OCIBindByname($stmt, ":order_number", $gsi_order_number, -1);

    OCIBindByName($stmt, ":xml", $xml, 10000);
    OCIExecute($stmt);

    display_error($stmt, $sql);

    //set/return XML
    $this->process_xml = $xml;

    return $xml;

  }

  public function process_reauthorize_response($gsi_order_number, $paypal_order_number, &$response) {

    global $apps_db;

    $xmlObj = new xmlObject($response);

    $success = $this->check_success($response, $xmlObj);

    if($success === TRUE) {

      $authorization_id = $xmlObj->get_elements_by_tag_name('AuthorizationID');
      $authorization_id = $authorization_id[0]['value'];

      $status = 1;

      //now post to database
      $sql = "begin " . $this->package_name . ".post_reauthorization_result(p_gsi_order_number => :order_number, p_paypal_order_number => :paypal_order_number, p_auth_decline_message => :auth_id, p_process_flag => :status); end;";

      $stmt = OCIParse($apps_db, $sql);

      OCIBindByName($stmt, ":order_number", $gsi_order_number, -1);
      OCIBindByName($stmt, ":paypal_order_number", $paypal_order_number, -1);
      OCIBindByName($stmt, ":auth_id", $authorization_id, -1);
      OCIBindByName($stmt, ":status", $status, -1);

      OCIExecute($stmt);
      display_error($stmt, $sql);

    } else {

      $status = 2;

      //now post to database
      $sql = "begin " . $this->package_name . ".post_reauthorization_result(p_gsi_order_number => :order_number, p_paypal_order_number => :paypal_order_number, p_process_flag => :status); end;";

      $stmt = OCIParse($apps_db, $sql);

      OCIBindByName($stmt, ":order_number", $gsi_order_number, -1);
      OCIBindByName($stmt, ":paypal_order_number", $paypal_order_number, -1);
      OCIBindByName($stmt, ":status", $status, -1);

      OCIExecute($stmt);
      display_error($stmt, $sql);
    }

    return $success;

 }
 
 
  public function generate_get_trans_xml($p_tran_id) { //add params as needed

    $this->soap_val = 'GetTransactionDetails';

    $xml .= '<GetTransactionDetailsRequest xmlns="urn:ebay:api:PayPalAPI">';
    $xml .= '<Version xmlns="urn:ebay:apis:eBLBaseComponents">58</Version>';
    $xml .= '<TransactionID>' . $p_tran_id . '</TransactionID>';
    $xml .= '</GetTransactionDetailsRequest>';
    
    //set/return XML
    $this->process_xml = $xml;

    return $xml;

  }

}

?>
