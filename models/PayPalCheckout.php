<?php 
require_once('gsi_paypal_defs.inc');
require_once('models/PayPalApiCall.php');
require_once('models/XmlObject.php');
require_once('models/SiteInit.php');
require_once('models/UserRegistration.php');
require_once('ShoppingCart.php');
require_once('CheckoutReview.php');

class PayPalCheckout {

  protected $v_order_number;

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

  public function getCheckoutUrl() {

    global $mssql_db;

    if(!empty($this->v_order_number)) {

      $this->setErrorHandler();

      $v_show_error = FALSE;

      $i_order = new Order($this->v_order_number);
      $i_order->updateOrder();

      $v_ship_method_code = $_SESSION['s_ship_method'];

      if(empty($v_ship_method_code)) {
        $v_ship_method_code = 'G';
      }
      $_SESSION['s_ship_method'] = $v_ship_method_code;

      $i_order->updateShipMethodCode($v_ship_method_code);

      $v_has_previous_token = FALSE;

      //in case user has already been to PayPal once, see if they already have a token
      if(!empty($_SESSION['s_paypal_token'])) {
        $v_token = $_SESSION['s_paypal_token'];
        $v_has_previous_token = TRUE;
      } else {
        $v_token = '';
      }

      if($v_has_previous_token === TRUE) { //check token age to see if it has expired
        //number of seconds between token creation and now
        $v_time_difference = time() - $_SESSION['s_paypal_token_created'];

        //3 hr * 60 min/hr * 60 sec/min = 10800 sec
        if($v_time_difference > 10800) {  //if older than 3 hr
          $v_token = '';
          $_SESSION['s_paypal_token'] = NULL;
          $_SESSION['s_paypal_token_created'] = NULL;

          $v_has_previous_token = FALSE;  //previous token no longer applies
        }
      }

      //instance for calling Oracle package and preparing/sending XML
      $i_api_call = new PayPalApiCall();

      //save the XML in case we want to do something with it later
      $v_xml_copy = $i_api_call->generate_setcheckout_xml($this->v_order_number, $v_token);

      //send the XML
      $v_resp = $i_api_call->send_xml();

      //we want to keep this value as accurate as possible to avoid transaction issues
      if($v_has_previous_token !== TRUE) {
        $_SESSION['s_paypal_token_created'] = time();
      }

      $i_xmlObj = new xmlObject($v_resp);

      $v_success = $i_xmlObj->get_elements_by_tag_name('Ack');
      $v_success = $v_success[0]['value'];

      if(strtoupper($v_success) == 'SUCCESS') {

        $v_token_value = $i_xmlObj->get_elements_by_tag_name('Token');

        if(!empty($v_token_value[0]['value'])) {  //success ONLY if we can find a token value

          $v_token_value = $v_token_value[0]['value'];

          //successful response from PayPal, so update header sales channel
          $i_order->updateHeaderSalesChannel('PAYPAL');

          $v_result = PAYPAL_CHECKOUT_URL . $v_token_value;

        } else {

          $v_show_error = TRUE;

        }

      } else { //there either was no signature, or signature didn't match
        $v_show_error = TRUE;
      }

      if($v_show_error === TRUE) {

        if(empty($_SESSION['s_paypal_token'])) { //if we didn't already have a valid token, remove creation date
          $_SESSION['s_paypal_token_created'] = NULL;
        }

        $v_result = 'Error: There was an error communicating with PayPal. We apologize for any inconvenience.';

      }

      $this->restoreErrorHandler();

    } else { //show the empty cart page if missing an order number

      $v_result = 'Error: There are no items in your cart to check out via PayPal Checkout.';

    }

    return $v_result;

  }

  public function getCheckoutDetails() {

    global $mssql_db;

    if(!empty($_GET['token'])) {
      $v_token = $_GET['token'];
    } else {
      $v_token = '';
    }
    if(!empty($_GET['PayerID'])) {
      $v_payer_id = $_GET['PayerID'];
    } else {
      $v_payer_id = '';
    }

    //if we don't have a token, we can't get details properly
    if(!empty($v_token)) {

      //generate class that calls Oracle and sends XML
      $api_call = new PayPalApiCall();

      //all we send is token
      $xml_copy = $api_call->generate_getdetails_xml($v_token);

      $resp = $api_call->send_xml();

      $xmlObj = new xmlObject($resp);

      $v_success = $xmlObj->get_elements_by_tag_name('Ack');

      $v_success = $v_success[0]['value'];
    } else {  //if we don't have a token, we can't
      $v_success = '';
    }

    if(strtoupper($v_success) == 'SUCCESS') {

      //processing data received

      //if we're missing PayerID, try to find it in the response
      if(empty($v_payer_id)) {

        $v_payer_id = $xmlObj->get_elements_by_tag_name('PayerID');
        if(!empty($v_payer_id[0]['value'])) {
          $v_payer_id = $v_payer_id[0]['value'];
        } else {
          $v_payer_id = '';
        }
      }

      //first, we need Golfsmith order number (don't rely on session)
      $this->v_order_number = $xmlObj->get_elements_by_tag_name('InvoiceID');
      if(!empty($this->v_order_number)) {  //we need to check for empty, so don't prepend 'G' yet
        $this->v_order_number = $this->v_order_number[0]['value'];
      }

      //if we're missing order number, we have a problem now
      //if we're missing payer ID, we won't be able to auth the order successfully with PayPal
      if(!empty($this->v_order_number) && !empty($v_payer_id)) {

        $this->setErrorHandler(); 

        //now, prepend the 'G' so that the database calls succeed
        $this->v_order_number = 'G' . $this->v_order_number;

        //update session to make sure correct order number is present
        $_SESSION['s_order_number'] = $this->v_order_number;

        $i_order = new Order($this->v_order_number);

        //We only get shipping address, so we probably have to use that as billing address as well
        $element_array = $xmlObj->get_elements_by_tag_name('FirstName');
        $first_name = $element_array[0]['value'];

        $element_array = $xmlObj->get_elements_by_tag_name('LastName');
        $last_name = $element_array[0]['value'];

        $element_array = $xmlObj->get_elements_by_tag_name('Street1');
        $line1 = $element_array[0]['value'];

        $line2 = '';

        $element_array = $xmlObj->get_elements_by_tag_name('Street2');
        if(!empty($element_array[0]['value'])) {
          $line2 = $element_array[0]['value'];
        }

        $element_array = $xmlObj->get_elements_by_tag_name('CityName');
        $city = $element_array[0]['value'];

        $element_array = $xmlObj->get_elements_by_tag_name('StateOrProvince');
        if(!empty($element_array[0]['value'])) {
          $state = $element_array[0]['value'];
        }

        $element_array = $xmlObj->get_elements_by_tag_name('PostalCode');
        if(!empty($element_array[0]['value'])) {
          $postal_code = $element_array[0]['value'];
        } else {
          $postal_code = '';
        }

        $element_array = $xmlObj->get_elements_by_tag_name('Country');
        $country = $element_array[0]['value'];

        $element_array = $xmlObj->get_elements_by_tag_name('Payer');
        $email = $element_array[0]['value'];

        $element_array = $xmlObj->get_elements_by_tag_name('ContactPhone');
        if(!empty($element_array[0]['value'])) {
          $phone = $element_array[0]['value'];
        } else {
          $phone = '';
        }

        $phone_match = preg_match('/^\d{3}-\d{3}-\d{4}$/', $phone);

        //get the normal website error handler back (the one that isn't for Google)
        restore_error_handler();

        $shiptopo = 'N';

        $i_user_registration = new UserRegistration();

        //log customer in
        $i_user_registration->verifyCustomer(
          strtoupper($email),
          null,
          '',
          $first_name,
          $last_name,
          $line1,
          $line2,
          $city,
          $state,
          $postal_code,
          $country,
          $first_name,
          $last_name,
          $line1,
          $line2,
          $city,
          $state,
          $postal_code,
          $country,
          $area_code,
          $phone,
          '',
          '',
          '',
          $address_id,
          $contact_id,
          $customer_id,
   	  	  $p_ship_contact_id, 
	  	  $p_phone_id,
	  	  $p_ship_phone_id,      
          $return_status,
          $ship_return_status,
          $bill_return_status,
          $ship_site_use_id,
          $bill_site_use_id,
          $shiptopo
        );
        //only set session and header purchase order number if login succeeded
        if(!empty($return_status)) {
          $_SESSION['s_using_paypal'] = '';
          $_SESSION['s_paypal_token'] = '';
          $_SESSION['s_paypal_payerid'] = '';

          //sales channel is no longer paypal; change it back to web now
          $i_order->updateHeaderSalesChannel('WEB');

        } else {
          $_SESSION['s_using_paypal'] = 'Y';
          $_SESSION['s_paypal_token'] = $v_token;
          $_SESSION['s_paypal_payerid'] = $v_payer_id;

          $i_order->updateHeaderPurchaseOrderNum($_SESSION['s_paypal_token']);

          //for unregistered guest checkout, we need phone data in session
          $_SESSION['area_code'] = $area_code;
          $_SESSION['phone_number'] = $phone;
        }

        //back to your regularly scheduled script

        $this->restoreErrorHandler();

      } else { //if no order number, we have no choice but to show the empty cart page

        //check the GET variable order number
        if(empty($this->v_order_number)) {
          $this->v_order_number = $_GET['order_number'];
        }

        //if we can't even find an order number there, check the session
        if(empty($this->v_order_number)) {
          $this->v_order_number = $_SESSION['s_order_number'];
        }

        //show the cart
        $i_checkout_cart = new ShoppingCart();
        $i_checkout_cart->displayPage();

        $return_status = 'Missing order number or Payer ID.';

      }

    } else { //signature was incorrect or missing

      //show the cart
      $i_checkout_cart = new ShoppingCart();
      $i_checkout_cart->displayPage();

      $return_status = 'There was an error communicating with PayPal.  We apologize for any inconvenience.';

    }

    if(empty($return_status)) {
      $i_checkout_review = new CheckoutReview();
      echo $i_checkout_review->displayPage();
    }

  }

  public function cancelPayPalCheckout() {

    $i_order = new Order($this->v_order_number);

    $_SESSION['s_using_paypal'] = 'N';
    $_SESSION['s_paypal_token'] = $v_token;
    $_SESSION['s_paypal_payerid'] = NULL;

    $i_order->updateHeaderSalesChannel('WEB');

  }

  public function errorHandler($errno, $errstr, $errfile, $errline) {

    $v_msg = "Error or warning on line $errline: $errstr";

    write_google_log($v_msg, ERROR_LOG);

  }

  public function setErrorHandler() {
    set_error_handler(array($this, "errorHandler"), E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);
  }

  public function restoreErrorHandler() {
    restore_error_handler();
  }

}
?>
