<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
require_once('Zend/View.php');

class Login {

  public function __construct() {
  }

  public function userLogin($p_username, $p_password) {

    global $mssql_db;

    $v_text_password = $p_password;
    $p_password = crypt($p_password, 'GS');

    $v_stmt = mssql_init("customer.dbo.gsi_cust_validate_logon");

    gsi_mssql_bind($v_stmt, "@p_user_name", $p_username, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_text_password", $v_text_password, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_password", $p_password, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_customer_id", $v_customer_id, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_customer_number", $v_customer_number, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_first_name", $v_first_name, 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_last_name", $v_last_name, 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_bill_address_id", $v_bill_address_id, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_bill_contact_id", $v_bill_contact_id, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_bill_phone_id", $v_bill_phone_id, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_address_id", $v_ship_address_id, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_contact_id", $v_ship_contact_id, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_phone_id", $v_ship_phone_id, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_cust_price_list_id", $v_cust_price_list_id, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_loyalty_number", $v_loyalty_number, 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_pro_loyalty_number", $v_pro_loyalty_number, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_phone_number", $v_phone_number, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_mp_long_access", $v_mp_long_access, 'varchar', 60, true);
    

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer.dbo.gsi_cust_validate_logon", "called from userLogin() in Login.php");
    }

    if(!empty($v_return_status)) {
      return FALSE; 
    }

    if(!empty($v_customer_id)) {
      $_SESSION['s_first_name']      = $v_first_name ;
      $_SESSION['s_last_name']       = $v_last_name ;
      $_SESSION['s_customer_id']     = $v_customer_id ;
      $_SESSION['s_bill_address_id'] = $v_bill_address_id ;
      $_SESSION['s_bill_contact_id'] = $v_bill_contact_id ;
      $_SESSION['s_bill_phone_id']   = $v_bill_phone_id ;
      $_SESSION['s_ship_address_id'] = $v_ship_address_id ;
      $_SESSION['s_ship_contact_id'] = $v_ship_contact_id ;
      $_SESSION['s_ship_phone_id']   = $v_ship_phone_id ;
      $_SESSION['s_customer_number'] = $v_customer_number ;
      $_SESSION['s_cust_price_list'] = $v_cust_price_list_id ;
      $_SESSION['s_loyalty_number'] = $v_loyalty_number ;
      $_SESSION['s_pro_loyalty_number'] = $v_pro_loyalty_number ;
      $_SESSION['s_user_name']       = $p_username ;
      $_SESSION['s_password']        = $p_password;
      $_SESSION['s_zipcode']         = $this->getPostalCode($v_ship_address_id);
      $_SESSION['visitor']['zip'] = $_SESSION['s_zipcode']; // used for stores/events search
      $_SESSION['phone_number'] = $v_phone_number;
      $_SESSION['phone_number2'] = $v_phone_number;
      //$_SESSION['mp_long_access'] = $v_mp_long_access; // Uncomment to track the long access token and start enabling express checkout
      if (!empty($v_customer_id)) {
        $_SESSION['s_do_upd_cust_order'] = 'Y' ;
        $_SESSION['s_do_totals']    = 'Y' ;
      }

      //see if there's a source code, and if so, check for a loyalty number
      if(!empty($_SESSION['scode']) || !empty($_SESSION['s_source_code']) || !empty($_SESSION['o_scode'])) {

        $v_source_code = $_SESSION['s_source_code'];

        $v_is_loyalty = $this->checkIsLoyalty($v_source_code);

        if($v_is_loyalty === TRUE) {
          if(empty($_SESSION['s_loyalty_number'])) {
            $_SESSION['scode'] = NULL;
            $_SESSION['s_source_code'] = NULL;
            $_SESSION['o_scode'] = NULL;

            //if there's an order, clear associated header attributes
            if(!empty($_SESSION['s_order_number'])) {
              $this->clearHeaderAttributes($v_source_code);
            }
          }
        }
      }

      if(!empty($v_cust_price_list_id)) {
        $_SESSION['s_do_reprice'] = 'Y';
      }

      return TRUE;

    } else {
      return FALSE;
    }

  }

  private function getPostalCode($p_address_id) {

    global $mssql_db;

    $v_sql = "select postal_code 
              from customer.dbo.gsi_ra_addresses_all 
              where address_id = $p_address_id";

    $v_result = mssql_query($v_sql);

    if(!$v_result) {
      display_mssql_error($v_sql, "called from function getPostalCode in Login.php");
    }

    $va_row = mssql_fetch_array($v_result);

    $v_postal_code = $va_row['postal_code'];

    mssql_free_statement($v_sql);
    mssql_free_result($v_result);

    return $v_postal_code;

  }

  private function clearHeaderAttributes($p_source_code) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;
    $v_order_number = $_SESSION['s_order_number'];

    $v_stmt = mssql_init("gsi_cmn_order_update_header_attributes");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_source_code", $p_source_code, 'varchar', 150);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_update_header_attributes", "called from clearHeaderAttributes in Login.php");
    }

    mssql_free_statement($v_stmt);

  }

  private function checkIsLoyalty($p_source_code) {

    global $mssql_db;

    $v_is_valid = 'N';
    $v_is_loyalty = 'N';

    $v_stmt = mssql_init("gsi_cmn_order_check_source_code");

    gsi_mssql_bind($v_stmt, "@p_source_code", $p_source_code, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_is_valid", $v_is_valid, 'char', 1, true);
    gsi_mssql_bind($v_stmt, "@p_is_loyalty", $v_is_loyalty, 'char', 1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_Error("gsi_cmn_order_check_source_id", "called from checkIsLoyalty() in Login.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    if($v_is_loyalty == 'Y') {
      $v_is_loyalty = TRUE;
    } else {
      $v_is_loyalty = FALSE;
    }

    return $v_is_loyalty;

  }

}
?>
