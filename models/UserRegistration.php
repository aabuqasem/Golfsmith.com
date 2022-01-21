<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
require_once('Zend/View.php');
require_once('FedExValidation.php');
require_once('Address.php');
require_once('AbandonedCart.php');

class UserRegistration {

  public function __construct() {
  }

  public function fedExValidation() {

  	//FedEx
    $p_valid_address = TRUE;
    $v_is_valid = 'True';
    $v_add_type = 'UNDETERMINED';

    $i_validation = new FedExValidation();

    $_SESSION['skip_default_fedex_check'] = TRUE;

    $v_fname = strip_tags($_POST['fname_ship_input']);
    $v_lname = strip_tags($_POST['lname_ship_input']);
    $v_addr1 = strip_tags($_POST['add1_ship_input']);
    $v_addr2 = strip_tags($_POST['add2_ship_input']);
    $v_city = strip_tags($_POST['city_ship_input']);
    $v_pcode = strip_tags($_POST['pcode_ship_input']);
    $v_state = strip_tags($_POST['state_ship_input']);
    $v_country = strip_tags($_POST['country_ship_select']);
    $v_phone = strip_tags($_POST['phone_ship_input']);
    $v_referring_page = strip_tags($_POST['referring_page']);
    $v_containing_page = strip_tags($_POST['containing_page']);
    $v_address_type = strip_tags($_POST['address_type']);

    if(strtoupper($v_country) == 'US' && $i_validation->isUSZip($v_pcode)) {
      $v_return_state = $i_validation->validateAddress($v_addr1, $v_addr2, $v_city, $v_pcode, $v_state, $v_country, $v_is_valid, $va_proposed_addresses, $va_error_list);
    }

    if ($v_return_state["status"] == "VALID") {

		$v_state_bill = $i_validation->translateState($v_state_bill);
		$v_state_ship = $i_validation->translateState($v_state_ship);

		//Validation returns true on initial checking
		if($_POST['FedExVerify'] != 'N' && strtoupper($v_country_bill) == 'US' && $i_validation->isUSZip($v_pcode_bill)) {
			$v_address_type        = $va_proposed_addresses[0]['AddressType'];
		}

		if ( (isset($_POST['FedExAddType'])) && ($_POST['FedExAcceptAdd'] == 'Y') ) {
			$v_address_type = strip_tags($_POST['FedExAddType']);
		}

    } else  if ($v_return_state["status"] == "INVALID") {
		$z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
		$p_valid_address = FALSE;
	} else  if ($v_return_state["status"] == "SUGGEST") {
    	$p_valid_address = FALSE;
		$z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
		//send back the HTML for the FedEx warning message
		$z_view->entered_fname = $v_fname;
		$z_view->entered_lname = $v_lname;
      	$z_view->entered_address1 = $v_addr1;
      	$z_view->entered_address2 = $v_addr2;
      	$z_view->entered_city = $v_city;
      	$z_view->entered_pcode = $v_pcode;
      	$z_view->entered_state = $v_state;
      	$z_view->entered_phone = $v_phone;
		$z_view->suggestions = $v_return_state["suggestions"];
      if($v_referring_page == 'address_book') {

        //pull address ID from the session and then retrieve the address
        $i_address = new Address($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_bill_phone_id']);
        $i_address->getAddressFields($v_is_international, $va_address);

        $z_view->bill_fname = $va_address['first_name'];
        $z_view->bill_lname = $va_address['last_name'];
        $z_view->bill_address1 = $va_address['line1'];
        $z_view->bill_address2 = $va_address['line2'];
        $z_view->bill_city = $va_address['city'];
        $z_view->bill_pcode = $va_address['postal_code'];
        $z_view->bill_state = $va_address['state'];
        $z_view->bill_country = $va_address['country'];

      } else {
        $z_view->bill_fname = strip_tags($_POST['fname_bill_input']);
        $z_view->bill_lname = strip_tags($_POST['lname_bill_input']);
        $z_view->bill_address1 = strip_tags($_POST['add1_bill_input']);
        $z_view->bill_address2 = strip_tags($_POST['add2_bill_input']);
        $z_view->bill_city = strip_tags($_POST['city_bill_input']);
        $z_view->bill_pcode = strip_tags($_POST['pcode_bill_input']);
        $z_view->bill_state = strip_tags($_POST['state_bill_input']);
        $z_view->bill_country = strip_tags($_POST['country_bill_select']);
      }

      $z_view->address_id = strip_tags($_POST['address_id']);
      $z_view->contact_id = strip_tags($_POST['contact_id']);
      $z_view->phone_id = strip_tags($_POST['phone_id']);
      $z_view->address_type = $v_address_type;
      $z_view->referring_page = $v_referring_page;
      $z_view->containing_page = $v_containing_page;

      return $z_view->render('checkout_fedex_message.phtml');

    }

  }

  public function registerCustomer() {

    $_SESSION['phone_number'] = strip_tags($_POST['phone_bill_input']);
    $_SESSION['phone_number2'] = strip_tags($_POST['phone_ship_input']);
    $_SESSION['s_email_address'] = strip_tags($_POST['email_address_input']);

    //Canadian Ship To PO
    if ($_SESSION['site'] == 'CA') {
      $_SESSION['s_province_bill'] = strip_tags($_POST['state_bill_input']);
      $_SESSION['s_province_ship'] = strip_tags($_POST['state_ship_input']);

      if (isset($_POST['shiptopo'])) {
        $_SESSION['shiptopo'] = TRUE;
        $v_shiptopo = TRUE;
      } else {
        $v_shiptopo = FALSE;
      }
    } else {
      $shiptopo = FALSE;
    }

    //FedEx validation now has already run by this point
    $i_validation = new FedExValidation();

    $v_addr1_ship   = strip_tags($_POST['add1_ship_input']);
    $v_addr2_ship   = strip_tags($_POST['add2_ship_input']);
    $v_city_ship    = strip_tags($_POST['city_ship_input']);
    $v_pcode_ship   = strip_tags($_POST['pcode_ship_input']);
    $v_country_ship = strip_tags($_POST['country_ship_select']);
    $v_state_ship   = $i_validation->translateState(strip_tags($_POST['state_ship_input']));

    $v_addr1_bill   = strip_tags($_POST['add1_bill_input']);
    $v_addr2_bill   = strip_tags($_POST['add2_bill_input']);
    $v_city_bill    = strip_tags($_POST['city_bill_input']);
    $v_pcode_bill   = strip_tags($_POST['pcode_bill_input']);
    $v_country_bill = strip_tags($_POST['country_bill_select']);
    $v_state_bill   = $i_validation->translateState(strip_tags($_POST['state_bill_input']));

    //Validation returns true on initial checking
    if($_POST['FedExVerify'] != 'N' && strtoupper($v_country_bill) == 'US' && $i_validation->isUSZip($v_pcode_bill)) {
      $v_add_type        = $va_proposed_addresses[0]['AddressType'];
    }

    if ( (isset($_POST['FedExAddType'])) && ($_POST['FedExAcceptAdd'] == 'Y') ) {
      $v_add_type = strip_tags($_POST['FedExAddType']);
    }

    $v_fstnm_bill = strip_tags($_POST['fname_bill_input']);
    $v_lstnm_bill = strip_tags($_POST['lname_bill_input']);
    $v_fstnm_ship = strip_tags($_POST['fname_ship_input']);
    $v_lstnm_ship = strip_tags($_POST['lname_ship_input']);
    $v_email = strip_tags($_POST['email_address_input']);
    $v_pnum = strip_tags($_POST['phone_bill_input']);
    $v_pnum = preg_replace('/[^0-9]/', '', $v_pnum);
    if (strlen($v_pnum) >= 10)
    {
    	$v_area_pnum = substr($v_pnum,0,3);
    	$v_loc_pnum = substr($v_pnum,3,strlen($v_pnum));
    } else {
    	$v_area_pnum = '';
    	$v_loc_pnum = $v_pnum;
    }
    
    $v_pnum2 = strip_tags($_POST['phone_ship_input']);
    $v_pnum2 = preg_replace('/[^0-9]/', '', $v_pnum2);
  	if (strlen($v_pnum2) >= 10)
    {
    	$v_area_pnum2 = substr($v_pnum2,0,3);
    	$v_loc_pnum2 = substr($v_pnum2,3,strlen($v_pnum2));
    } else {
    	$v_area_pnum2 = '';
    	$v_loc_pnum2 = $v_pnum2;
    }

    $v_GSEmailOptIn = NULL;

    

    //CHANGE THIS TO TAKE DIFFERENT SETS OF FIELDS FOR SHIPPING/BILLING
    $this->verifyCustomer(
      strtoupper($v_email),
      null,
      '',
      $v_fstnm_ship,
      $v_lstnm_ship,
      strtoupper($v_addr1_ship),
      strtoupper($v_addr2_ship),
      strtoupper($v_city_ship),
      strtoupper($v_state_ship),
      strtoupper($v_pcode_ship),
      strtoupper($v_country_ship),
      $v_fstnm_bill,
      $v_lstnm_bill,
      strtoupper($v_addr1_bill),
      strtoupper($v_addr2_bill),
      strtoupper($v_city_bill),
      strtoupper($v_state_bill),
      strtoupper($v_pcode_bill),
      strtoupper($v_country_bill),
      strtoupper($v_area_pnum),
      strtoupper($v_loc_pnum),
      strtoupper($v_area_pnum2),
      strtoupper($v_loc_pnum2),
      strtoupper($v_GSEmailOptIn),       
      $v_address_id,
      $v_contact_id,
      $v_customer_id,
   	  $p_ship_contact_id, 
	  $p_phone_id,
	  $p_ship_phone_id,      
      $v_return_status,
      $v_ship_return_status,
      $v_bill_return_status,
      $v_ship_site_use_id,
      $v_bill_site_use_id,
      $shiptopo
    );

    if(!empty($v_return_status) || !empty($v_bill_return_status) || !empty($v_ship_return_status)) {
      $v_ret_err_msg = '<h4>There were errors with your submission:</h4>';
      if ($v_return_status == 'Check Address')
      {
      	if (!empty($v_bill_return_status))
      	{
      		$v_ret_err_msg = '<h4>There was an error with your Billing Address submission:</h4>';
      		$v_ret_err_msg .= '<p>' . $v_bill_return_status . '</p>';
      	}
      	if (!empty($v_ship_return_status))
      	{
      		if (!empty($v_bill_return_status))
      		{
      			$v_ret_err_msg .= '<br />';
      		}
      		$v_ret_err_msg .= '<h4>There was an error with your Shipping Address submission:</h4>';
      		$v_ret_err_msg .= '<p>' . $v_ship_return_status . '</p>';
      	}      	
      	
      } else {      
      	$v_ret_err_msg .= '<p>' . $v_return_status . '<br/>' . $v_bill_return_status . '<br/>' . $v_ship_return_status . '<p>';
      } 
      
      return $v_ret_err_msg;
    } else {

	  $_SESSION['s_is_guest_checkout'] = 'Y';
	  
    	if ( $_SERVER['SERVER_PORT'] == 8443 || $_SERVER['SERVER_PORT'] == 443) {
			$secure_connection = true;
		} else {
			$secure_connection = false;
		}
	  setcookie("CloseEvent", "CloseEvent", 0, "/",".golfsmith.com",$secure_connection,1);
      
      //loyalty enrollment
      if($_POST['rewards_player_input'] == 'Y') {
        $v_loyalty_return_status = $this->loyaltyEnrollment($v_email, $v_customer_id, "CONSUMER");
      }

      //email enrollment goes here
      if($_POST['rewards_emails_input'] == 'Y') {
        $this->setEmailSubscriptions($v_email, $v_fstnm_bill, $v_lstnm_bill, $v_pcode_bill, 'Y', 'N', 'N', 'N');
      }

      //set cookie for abandoned cart detection
      $i_abandoned_cart = new AbandonedCart($v_email, $_SESSION['s_order_number'], $_SESSION['s_customer_number']);
      $i_abandoned_cart->setCookie();
      $i_abandoned_cart->updateEmailAddress();
      
      $_SESSION['s_email_address'] = $v_email;
    }


    //more probably has to go here

  }
  
  public function registerIspCustomer($info){
  
    //  $_SESSION['phone_number'] = strip_tags($info['phone_bill_input']);
    //  $_SESSION['phone_number2'] = strip_tags($info['phone_bill_input']);
   //   $_SESSION['s_email_address'] = strip_tags($info['email_address_input']);
  
      $shiptopo = FALSE;

  
      $v_addr1_ship   = strip_tags($info['add1_ship_input']);
      $v_addr2_ship   = strip_tags($info['add2_ship_input']);
      $v_city_ship    = strip_tags($info['city_ship_input']);
      $v_pcode_ship   = strip_tags($info['pcode_ship_input']);
      $v_country_ship = strip_tags($info['country_ship_select']);
      $v_state_ship   = strip_tags($info['state_ship_input']);
  
      $v_addr1_bill   = strip_tags($info['add1_bill_input']);
      $v_addr2_bill   = strip_tags($info['add2_bill_input']);
      $v_city_bill    = strip_tags($info['city_bill_input']);
      $v_pcode_bill   = strip_tags($info['pcode_bill_input']);
      $v_country_bill = strip_tags($info['country_bill_select']);
      $v_state_bill   = strip_tags($info['state_bill_input']);
       
  
  
      $v_fstnm_bill = strip_tags($info['fname_bill_input']);
      $v_lstnm_bill = strip_tags($info['lname_bill_input']);
      $v_fstnm_ship = strip_tags($info['fname_ship_input']);
      $v_lstnm_ship = strip_tags($info['lname_ship_input']);
      $v_email = strip_tags($info['email_address_input']);
      $v_pnum = strip_tags($info['phone_bill_input']);
      $v_pnum = preg_replace('/[^0-9]/', '', $v_pnum);
      if (strlen($v_pnum) >= 10)
      {
          $v_area_pnum = substr($v_pnum,0,3);
          $v_loc_pnum = substr($v_pnum,3,strlen($v_pnum));
      } else {
          $v_area_pnum = '';
          $v_loc_pnum = $v_pnum;
      }
  
      $v_pnum2 = strip_tags($info['phone_bill_input']);
      $v_pnum2 = preg_replace('/[^0-9]/', '', $v_pnum2);
      if (strlen($v_pnum2) >= 10)
      {
          $v_area_pnum2 = substr($v_pnum2,0,3);
          $v_loc_pnum2 = substr($v_pnum2,3,strlen($v_pnum2));
      } else {
          $v_area_pnum2 = '';
          $v_loc_pnum2 = $v_pnum2;
      }
  
      $v_GSEmailOptIn = $info['GSEmailOptIn'];
  
  
  
      //CHANGE THIS TO TAKE DIFFERENT SETS OF FIELDS FOR SHIPPING/BILLING
      $this->verifyCustomer(
          strtoupper($v_email),
          null,
          '',
          $v_fstnm_ship,
          $v_lstnm_ship,
          strtoupper($v_addr1_ship),
          strtoupper($v_addr2_ship),
          strtoupper($v_city_ship),
          strtoupper($v_state_ship),
          strtoupper($v_pcode_ship),
          strtoupper($v_country_ship),
          $v_fstnm_bill,
          $v_lstnm_bill,
          strtoupper($v_addr1_bill),
          strtoupper($v_addr2_bill),
          strtoupper($v_city_bill),
          strtoupper($v_state_bill),
          strtoupper($v_pcode_bill),
          strtoupper($v_country_bill),
          strtoupper($v_area_pnum),
          strtoupper($v_loc_pnum),
          strtoupper($v_area_pnum2),
          strtoupper($v_loc_pnum2),
          strtoupper($v_GSEmailOptIn),
          $v_address_id,
          $v_contact_id,
          $v_customer_id,
          $p_ship_contact_id,
          $p_phone_id,
          $p_ship_phone_id,
          $v_return_status,
          $v_ship_return_status,
          $v_bill_return_status,
          $v_ship_site_use_id,
          $v_bill_site_use_id,
          $shiptopo
      );
  
      if(!empty($v_return_status) || !empty($v_bill_return_status) || !empty($v_ship_return_status)) {
          $v_ret_err_msg = '<h4>There were errors with your submission:</h4>';
          if ($v_return_status == 'Check Address')
          {
              if (!empty($v_bill_return_status))
              {
                  $v_ret_err_msg = '<h4>There was an error with your Billing Address submission:</h4>';
                  $v_ret_err_msg .= '<p>' . $v_bill_return_status . '</p>';
              }
              if (!empty($v_ship_return_status))
              {
                  if (!empty($v_bill_return_status))
                  {
                      $v_ret_err_msg .= '<br />';
                  }
                  $v_ret_err_msg .= '<h4>There was an error with your Shipping Address submission:</h4>';
                  $v_ret_err_msg .= '<p>' . $v_ship_return_status . '</p>';
              }
               
          } else {
              $v_ret_err_msg .= '<p>' . $v_return_status . '<br/>' . $v_bill_return_status . '<br/>' . $v_ship_return_status . '<p>';
          }
  
          return $v_ret_err_msg;
      } else {
  
          $_SESSION['s_is_guest_checkout'] = 'Y';
           
          if ( $_SERVER['SERVER_PORT'] == 8443 || $_SERVER['SERVER_PORT'] == 443) {
              $secure_connection = true;
          } else {
              $secure_connection = false;
          }
          setcookie("CloseEvent", "CloseEvent", 0, "/",".golfsmith.com",$secure_connection,1);
  
          //loyalty enrollment
          if($_POST['rewards_player_input'] == 'Y') {
              $v_loyalty_return_status = $this->loyaltyEnrollment($v_email, $v_customer_id, "CONSUMER");
          }
  
          //email enrollment goes here
          if($_POST['rewards_emails_input'] == 'Y') {
              $this->setEmailSubscriptions($v_email, $v_fstnm_bill, $v_lstnm_bill, $v_pcode_bill, 'Y', 'N', 'N', 'N');
          }
  
          //set cookie for abandoned cart detection
          $i_abandoned_cart = new AbandonedCart($v_email, $_SESSION['s_order_number'], $_SESSION['s_customer_number']);
          $i_abandoned_cart->setCookie();
          $i_abandoned_cart->updateEmailAddress();
  
          $_SESSION['s_email_address'] = $v_email;
      }
  
  
      //more probably has to go here
  
  }

  public function hasWebAccount($p_web_user_name) {

    global $mssql_db;

    $v_stmt = mssql_init("customer.dbo.gsi_cust_has_web_account");

    gsi_mssql_bind($v_stmt, "@p_web_user_name", $p_web_user_name, 'varchar', 120);
    gsi_mssql_bind($v_stmt, "@p_has_web_account", $v_has_web_account, 'char', 1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer.dbo.gsi_cust_has_web_account", "called from hasWebAccount() in UserRegistration.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    if($v_has_web_account == 'Y') {
      $v_has_web_account = TRUE;
    } else {
      $v_has_web_account = FALSE;
    }

    return $v_has_web_account;

  }

  public function updateAccount($p_customer_id, $p_email_address, $p_web_password, $p_text_password) {

    global $mssql_db;

    $v_stmt = mssql_init("customer.dbo.gsi_cust_update_customer_attributes");

    gsi_mssql_bind($v_stmt, "@p_customer_id", $p_customer_id, 'gsi_id_type');
    gsi_mssql_bind($v_stmt, "@p_username", $p_email_address, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_web_password", $p_web_password, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_text_password", $p_text_password, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer.dbo.gsi_cust_update_customer_attributes", "called from updateAccount() in UserRegistration.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_return_status;

  }
  
  public function verifyCustomer($p_email, $p_username_temp, $p_password, $p_ship_first_name, $p_ship_last_name, $p_ship_address1,
                                 $p_ship_address2, $p_ship_city, $p_ship_state, $p_ship_postal_code, $p_ship_country, 
                                 $p_bill_first_name, $p_bill_last_name, $p_bill_address1, $p_bill_address2, $p_bill_city, 
                                 $p_bill_state, $p_bill_postal_code, $p_bill_country, $p_area_code, $p_phone_number, 
                                 $p_area_code2, $p_phone_number2, $p_GSEmailOptIn, &$p_address_id, &$p_contact_id,&$p_customer_id,
                                 &$p_ship_contact_id, &$p_phone_id, &$p_ship_phone_id, 
                                 &$p_return_status, &$p_ship_return_status, &$p_bill_return_status, &$p_ship_site_use_id, 
                                 &$p_bill_site_use_id, $p_shiptopo, $mpLongAccess = null) {
   
    global $mssql_db;

    $p_customer_id = '';
    $p_address_id = '';
    $p_contact_id = '';
    $p_ship_contact_id = '';
    $p_phone_id = '';
    $p_ship_phone_id = '';
    $p_return_status = '';
    $v_profile_scope = C_WEB_PROFILE;


    //setting up web_password
    if(!empty($p_password) && !empty($p_username_temp)) {
      $v_web_password=crypt($p_password, 'GS');
    } else {
      $p_password = '';
      $p_username_temp = '';
    }

    //order number for time logging
    $v_order_number = $_SESSION['s_order_number'] ;

    $v_stmt = mssql_init("gsi_cmn_customer_verify_customer");

    
    //KBOX#44713
    //bind the vars
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_bill_first_name", html_entity_decode( htmlentities($p_bill_first_name, ENT_COMPAT, 'UTF-8')), 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_bill_last_name", html_entity_decode( htmlentities($p_bill_last_name, ENT_COMPAT, 'UTF-8')), 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_ship_first_name", html_entity_decode( htmlentities($p_ship_first_name, ENT_COMPAT, 'UTF-8')), 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_ship_last_name", html_entity_decode( htmlentities($p_ship_last_name, ENT_COMPAT, 'UTF-8')), 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_ship_address1", $p_ship_address1, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_ship_address2", $p_ship_address2, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_ship_city", $p_ship_city, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_ship_state", $p_ship_state, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_ship_postal_code", $p_ship_postal_code, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_ship_country", $p_ship_country, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_bill_address1", $p_bill_address1, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_bill_address2", $p_bill_address2, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_bill_city", $p_bill_city, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_bill_state", $p_bill_state, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_bill_postal_code", $p_bill_postal_code, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_bill_country", $p_bill_country, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_area1", $p_area_code, 'varchar', 10);
    gsi_mssql_bind($v_stmt, "@p_phone1", $p_phone_number, 'varchar', 25);
    gsi_mssql_bind($v_stmt, "@p_area2", $p_area_code2, 'varchar', 10);
    gsi_mssql_bind($v_stmt, "@p_phone2", $p_phone_number2, 'varchar', 25);
    gsi_mssql_bind($v_stmt, "@p_email", $p_email, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_text_password", $p_text_password, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_web_password", $v_web_password, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_username", $p_username_temp, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_GSEmailOptIn", $p_GSEmailOptIn, 'varchar', 1);
    gsi_mssql_bind($v_stmt, "@p_mp_long_access", $mpLongAccess, 'varchar', 60);


    //output params
    gsi_mssql_bind($v_stmt, "@p_customer_id", $p_customer_id, 'bigint', -1, true);
    gsi_mssql_bind($v_stmt, "@p_customer_number", $v_customer_number, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_contact_id", $p_contact_id, 'bigint', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_contact_id", $p_ship_contact_id, 'bigint', -1, true);    
    gsi_mssql_bind($v_stmt, "@p_phone_id", $p_phone_id, 'bigint', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_phone_id", $p_ship_phone_id, 'bigint', -1, true);        
    gsi_mssql_bind($v_stmt, "@p_ship_address_id", $v_ship_address_id, 'bigint', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_site_use_id", $p_ship_site_use_id, 'bigint', -1, true);
    gsi_mssql_bind($v_stmt, "@p_bill_address_id", $v_bill_address_id, 'bigint', -1, true);
    gsi_mssql_bind($v_stmt, "@p_bill_site_use_id", $p_bill_site_use_id, 'bigint', -1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $p_return_status, 'varchar', 200, true);
    gsi_mssql_bind($v_stmt, "@p_ship_return_status", $p_ship_return_status, 'varchar', 200, true);
    gsi_mssql_bind($v_stmt, "@p_bill_return_status", $p_bill_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

   
    //--------------
    
    if(!$v_result) {
      display_mssql_error("direct.dbo.gsi_cmn_customer_verify_customer", "called from verifyCustomer() in Login.php");
    }
    

    if($p_return_status == '0') {
      $p_return_status = '';
    }

    if ($p_ship_return_status != '') {
      $_SESSION[insert_ship_address_err] = "Y";
    }

    if ($p_bill_return_status != '') {
      $_SESSION[insert_bill_address_err] = "Y";
    }

    if ($p_return_status != '') {
      $_SESSION['insert_address_err'] != 'Y';
    }

    $_SESSION['s_email_address']     = $p_email;
    $_SESSION['s_first_name']        = htmlentities($p_bill_first_name, ENT_COMPAT, 'ISO-8859-15');
    $_SESSION['s_last_name']         = htmlentities($p_bill_last_name, ENT_COMPAT, 'ISO-8859-15');
    $_SESSION['s_ship_address1']     = $p_ship_address1 ;
    $_SESSION['s_ship_address2']     = $p_ship_address2 ;
    $_SESSION['s_ship_city']         = $p_ship_city ;
    $_SESSION['s_ship_state']        = $p_ship_state ;
    $_SESSION['s_ship_postal_code']  = $p_ship_postal_code ;
    $_SESSION['s_ship_country']      = $p_ship_country ;
    $_SESSION['s_ship_first_name']   = htmlentities($p_ship_first_name, ENT_COMPAT, 'ISO-8859-15');
    $_SESSION['s_ship_last_name']    = htmlentities($p_ship_last_name, ENT_COMPAT, 'ISO-8859-15');
    $_SESSION['s_user_name']         = $p_email;     //  $username ;
    $_SESSION['s_bill_address1']     = $p_bill_address1 ;
    $_SESSION['s_bill_address2']     = $p_bill_address2 ;
    $_SESSION['s_bill_city']         = $p_bill_city ;
    $_SESSION['s_bill_state']        = $p_bill_state ;
    $_SESSION['s_bill_postal_code']  = $p_bill_postal_code ;
    $_SESSION['s_bill_country']      = $p_bill_country ;
    $_SESSION['s_bill_first_name']   = $p_bill_first_name ;
    $_SESSION['s_bill_last_name']    = $p_bill_last_name;
    $_SESSION['area_code'] = $p_area_code;
    $_SESSION['phone'] = preg_replace('/[^0-9]/', '', $p_phone_number);

    if($p_return_status != '' || $p_bill_return_status != '' || $p_ship_return_status != '') {
      return;
    }

    if(empty($p_username_temp)) {
      $_SESSION['is_guest_checkout'] = TRUE;
    } else {
      $_SESSION['is_guest_checkout'] = FALSE;
    }

    $_SESSION['s_customer_id']       = $p_customer_id ;
    $_SESSION['s_customer_number']   = $v_customer_number ;
    $_SESSION['s_bill_address_id']   = $v_bill_address_id;
    $_SESSION['s_bill_contact_id']   = $p_contact_id;
    $_SESSION['s_ship_address_id']   = $v_ship_address_id;
    $_SESSION['s_ship_contact_id']   = $p_ship_contact_id;
    $_SESSION['s_bill_phone_id'] 	 = $p_phone_id;
    $_SESSION['s_ship_phone_id'] 	 = $p_ship_phone_id;

    //for FedEx
    $p_address_id = $v_ship_address_id;

  }


  private function loyaltyEnrollment($p_email, $p_customer_id, $p_record_type = "CONSUMER") {

    if(strtoupper($v_loyalty_status) != 'Y') {

      if(!empty($p_customer_id)) {

         $this->loyaltyRegistration($p_email, $p_customer_id, $p_record_type, $v_return_status, $v_loyalty_number);

         if(empty($v_return_status)) {
           if(!empty($v_loyalty_number)) {
             $_SESSION['s_loyalty_number'] = $v_loyalty_number;
           }
         }
      } else { //how did we lose customer id?? log order number to research this after the fact
         $v_msg = "Loyalty enrollment failed due to missing customer id.  Order number was $v_order_number and email was $v_email.  Other session data was:\n" . print_r($_SESSION, TRUE);
         write_to_log($v_msg);
      }
    }

    return $v_return_status;

  }

  private function loyaltyRegistration($p_email, $p_customer_id, $p_record_type, &$p_return_status, &$p_loyalty_number) {
       
    global $mssql_db ;
      
    $p_return_status = $this->checkLoyalty($p_customer_id, $p_record_type);

    if($p_return_status == 'N') {
      $p_return_status = '';
    } else if ($p_return_status == 'Y') {
      $p_return_status = 'Already enrolled';
    } else if($p_return_status == 'I') {
      $p_return_status = 'International';  //this can only happen for existing users
    }

    if(empty($p_return_status)) {

      //Hardcode $sales_channel_code and $organization_id
      $v_sales_channel_code="WEB";
      $v_organization_id="25";
 
      $v_clubmaking_flag = 'N';
      $v_junior_golfer_flag = 'N';
      $v_junior_tennis_flag = 'N';
      $v_play_golf_flag = 'N';
      $v_play_tennis_flag = 'N';
 
      $v_stmt = mssql_init("customer.dbo.gsi_loyalty_enrollment");

      gsi_mssql_bind($v_stmt, "@p_customer_id", $p_customer_id, 'int');
      gsi_mssql_bind($v_stmt, "@p_salesrep_id", $v_salesrep_id, 'varchar', 20);
      gsi_mssql_bind($v_stmt, "@p_team_name", $p_team_name, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_clubmaking_flag", $v_clubmaking_flag, 'varchar', 1);
      gsi_mssql_bind($v_stmt, "@p_junior_golfer_flag", $v_junior_golfer_flag, 'varchar', 1);
      gsi_mssql_bind($v_stmt, "@p_junior_tennis_flag", $v_junior_tennis_flag, 'varchar', 1);
      gsi_mssql_bind($v_stmt, "@p_play_golf_flag", $v_play_golf_flag, 'varchar', 1);
      gsi_mssql_bind($v_stmt, "@p_play_tennis_flag", $v_play_tennis_flag, 'varchar', 1);
      gsi_mssql_bind($v_stmt, "@p_sales_channel_code", $v_sales_channel_code, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_organization_id", $v_organization_id, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_record_type", $p_record_type, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_loyalty_number", $p_loyalty_number, 'varchar', 200, true);
      gsi_mssql_bind($v_stmt, "@p_return_status", $p_return_status, 'varchar', 200, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_loyalty_enrollment", "called from loyaltyRegistration in UserRegistration.php");
      }

      mssql_free_result($v_result);

    }

    return $p_return_status;

  }

  private function checkLoyalty($p_customer_id, $p_record_type = "CONSUMER") {
    global $mssql_db;

    $v_stmt = mssql_init("customer.dbo.gsi_cust_check_loyalty");

    gsi_mssql_bind($v_stmt, "@p_customer_id", $p_customer_id, 'int');
    gsi_mssql_bind($v_stmt, "@p_record_type", $p_record_type, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_status, 'varchar', 10, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer.dbo.gsi_cust_check_loyalty", "called from checkLoyalty() in UserRegistration.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_status;

  }

  private function setEmailSubscriptions($p_email_address, $p_first_name, $p_last_name, $p_postal_code, $p_mens_golf = 'N', $p_womens_golf = 'N', $p_clubmaking = 'N', $p_tennis) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_subscription_adjust_customer");

    gsi_mssql_bind($v_stmt, "@p_email_address", $p_email_address, 'varchar', 200);
    gsi_mssql_bind($v_stmt, "@p_first_name", $p_first_name, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_last_name", $p_last_name, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_postal_code", $p_postal_code, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_mens_golf", $p_mens_golf, 'char', 1);
    gsi_mssql_bind($v_stmt, "@p_womens_golf", $p_womens_golf, 'char', 1);
    gsi_mssql_bind($v_stmt, "@p_clubmaking", $p_clubmaking, 'char', 1);
    gsi_mssql_bind($v_stmt, "@p_tennis", $p_tennis, 'char', 1);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_subscription_adjust_customer", "called from setEmailSubscriptions() in UserRegistration.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_status;

  }

}
?>
