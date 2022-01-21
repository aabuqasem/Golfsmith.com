<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
require_once('Zend/View.php');

class CheckoutPage {
 
  protected $v_order_number;
  protected $i_site_init;

  public function __construct($p_check_order_number = FALSE) {

    $this->i_site_init = new SiteInit();

    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");

    //if init is already loaded, don't load it again
    //otherwise, we get function redefinition errors
    if(!function_exists('gsi_mssql_bind')) {

      //load site init for database connections, helper functions, etc.
      global $connect_mssql_db;
      $connect_mssql_db = 1;

      //$this->i_site_init = new SiteInit(); 
      $this->i_site_init->loadInit($v_connect_mssql);
    }

    //only shopping cart should do the below, but it needs to happen before setting $this->v_order_number
    if($p_check_order_number === TRUE) {
      //if we have an order number via GET and session order number is empty, load order number
      //(this is mainly for use with Google)
      if(!empty($_GET['order_number']) && empty($_SESSION['s_order_number'])) {

        $v_received_order_number = strip_tags($_GET['order_number']);

        //reverse the mapping and store
        $_SESSION['s_order_number'] = unmap_order_number($v_received_order_number);

      }

      //if we have an order number but it's mapped, unmap it
      if(!empty($_SESSION['s_order_number']) and substr($_SESSION['s_order_number'], 0, 1) != 'G') {
        $_SESSION['s_order_number'] = unmap_order_number($_SESSION['s_order_number']);
      }

      $i_google_checkout = new GoogleCheckout();
      $v_clear_order = $i_google_checkout->hasExistingGoogleOrder();
      if($v_clear_order === TRUE) {
        $_SESSION['s_order_number'] = NULL;
      }
    }

    $this->v_order_number = $_SESSION['s_order_number'];
  }

  //checks if user is already logged in
  //this will be called via Ajax
  public function checkLoggedIn() {
    $v_customer_id = $_SESSION['s_customer_id'];    
    $v_logged_in = 'false';

    if (isset( $_COOKIE['CloseEvent'])) {
	    if(!empty($v_customer_id)) {
	      $v_logged_in = 'true';
	    } else {
	      $v_logged_in = 'false';
	    }
    }    
    
    return $v_logged_in;
  }

  //top piece of the checkout page
  protected function displayHeader($p_page_type, $p_step = 0) {
    if(!empty($p_page_type)) {
      $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
      $z_view->page_type = $p_page_type;
      $z_view->step = $p_step;
      $z_view->checkout_header_path = HTML_PATH . '_shopping_cart/headers/header_' . $p_page_type . '.html';
      return $z_view->render("checkout_header.phtml");
    }
  }

  //generic pieces needed for the bottom of the page
  protected function displayFooter($p_page_type, $p_show_div = TRUE) {
    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
    $z_view->page_type = $p_page_type;
    $z_view->show_div = $p_show_div;
    return $z_view->render("checkout_footer.phtml");
  }

  protected function displayCheckoutSteps($p_step, $p_show_div = TRUE) {
    //this function will render the view that displays the checkout steps
    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
    $z_view->step = $p_step;
    $z_view->show_div = $p_show_div;
    return $z_view->render("checkout_steps.phtml");
  }

  //order totals will be used on every page, so include this here, rather than in any child classes
  protected function getMerchandiseTotal(&$p_num_items, &$p_sub_total) {

    global $mssql_db;

    $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_order_summary");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_num_items", $p_num_items, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_sub_total", $p_sub_total, 'gsi_amount_type', -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("direct.dbo.gsi_cmn_order_order_summary", "called from CheckoutPage.php");
    }

    if(empty($p_sub_total)) {
      $p_sub_total = 0;
    }

    if(empty($p_num_items)) {
      $p_num_items = 0;
    }

    $_SESSION['s_sc_total'] = $p_sub_total;
    $_SESSION['s_sc_qty'] = $p_num_items;
  }

  protected function updateIspLineAddresses() {
 	
  	global $mssql_db ;
  	//$order_number     = $_SESSION['s_order_number'] ;
  	$customer_id      = $_SESSION['s_customer_id'] ;
    $v_profile_scope  = C_WEB_PROFILE;

	$sql = mssql_init("direct.dbo.gsi_cmn_order_ext_update_isp_ship_set_items");
	mssql_bind ($sql, "@p_orig_sys_ref", $this->v_order_number, SQLVARCHAR, false, false, 30);
	
	$result = mssql_execute($sql);
	if (!$result){
	  display_mssql_error("gsi_cmn_order_ext_update_isp_ship_set_items", "called from update_isp_line_addresses() in payments_common");
	}

  	mssql_free_statement($sql);
  	mssql_free_result($result);

  $sql = mssql_init("direct.dbo.gsi_cmn_isp_update_isp_line_addresses");
  mssql_bind ($sql, "@p_order_number", $this->v_order_number, SQLVARCHAR, false, false, 30);
  mssql_bind ($sql, "@p_customer_id", $customer_id, SQLINT4, false);
  mssql_bind ($sql, "@p_profile_scope", $v_profile_scope, SQLVARCHAR, false, false, 30);

  $result = mssql_execute($sql);
  if (!$result){
    display_mssql_error("gsi_cmn_isp_update_isp_line_addresses", "called from update_isp_line_addresses() in payments_common");
  }

  mssql_free_statement($sql);
  mssql_free_result($result);
  }

  protected function calculateIspTaxZip($p_postal_code) {

    global $mssql_db;

    $i_order = new Order($this->v_order_number);
    $i_order->getCityState($p_postal_code, $v_city, $v_state);

    if($_SESSION['SITE'] == 'ca' || $v_state == 'CN') {
      $v_country = 'CA';
    } else {
      $v_country = 'US';
    }

    $v_stmt = mssql_init("gsi_cmn_isp_calc_isp_totals");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_city", $v_city, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_state", $v_state, 'varchar', 10);
    gsi_mssql_bind($v_stmt, "@p_zipcode", $p_postal_code, 'varchar', 10);
    gsi_mssql_bind($v_stmt, "@p_country", $v_country, 'varchar', 50);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_isp_calc_isp_totals", "called from calculateIspTaxZip() in CheckoutPage.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);
  }

  protected function getIspTax() {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_isp_get_isp_totals");

    gsi_mssql_bind($v_stmt, "@p_order_number, $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, @p_rtn_totals_string", $v_isp_total_str, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_isp_get_isp_totals", "called from getIspTax() in CheckoutPage.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    $v_isp_total = explode('|'. $v_isp_total_str);

    return $v_isp_total[3];

  }

  //get warehouse id for cart query
  protected function getWarehouseId() {
    global $mssql_db;

    $v_imp_warehouse_id  = "GSI_IMPORT_WAREHOUSE_ID";
    $v_web_profile       =  C_WEB_PROFILE;
    $v_stmt = mssql_init("r12pricing..gsi_profile_value");

    gsi_mssql_bind($v_stmt, "@p_profile_scope" , $v_web_profile , "varchar", 50);
    gsi_mssql_bind($v_stmt, "@p_profile_name"  , $v_imp_warehouse_id  , "varchar", 50);
    gsi_mssql_bind($v_stmt, "@o_profile_value" , $v_out_warehouse_id , "varchar", 50,true );

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("r12pricing..gsi_profile_value", "called from ShoppingCart.php");
    }
    mssql_free_statement($v_stmt);
    mssql_free_result ($v_result);

    return $v_out_warehouse_id;

  }

  //checks whether item cannot be shipped to a given country because of vendor restrictions, etc.
  protected function checkCountryRestrictedItem($p_order_line_number = null) {

    global $mssql_db;

    $v_status = 0;

    $v_stmt = mssql_init("gsi_check_country_restricted_item");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_original_system_line_reference", $p_order_line_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_status", $v_status, 'int', -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_check_country_restricted_item", "called from checkCountryRestrictedItem() in CheckoutPage.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_status;

  }

  //checks to see if a sourced item is shipped internationally
  protected function checkSourcedItemShippedInt($p_order_line_number = null) {

    global $mssql_db;

    $v_status = 0;

    $v_stmt = mssql_init("gsi_check_sourced_item_shipped_int");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_original_system_line_reference", $p_order_line_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_status", $v_status, 'int', -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_check_sourced_item_shipped_int", "called from checkSouredItemShippedInt() in CheckoutPage.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_status;
  }
  
  
  function get_isp_totals_string($p_order_number) {
  
      global $mssql_db;
  
      $v_stmt = mssql_init("gsi_cmn_isp_get_isp_totals");
  
      gsi_mssql_bind($v_stmt, "@p_order_number", $p_order_number, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_rtn_totals_string", $v_isp_totals_string, 'varchar', 150, true);
  
      $v_result = mssql_execute($v_stmt);
  
      //display_mssql_error("gsi_cmn_isp_get_isp_totals", "called from get_isp_totals() in CheckoutPage");
      //die('MSSQL error: ' . mssql_get_last_message());
      if(!$v_result) {
          display_mssql_error("gsi_cmn_isp_get_isp_totals", "called from get_isp_totals() in CheckoutPage");
      }
  
      mssql_free_statement($v_stmt);
 
      
      return $v_isp_totals_string;
  
  }

}
?>
