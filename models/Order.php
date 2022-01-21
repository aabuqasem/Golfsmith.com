<?php 
require_once('CreditCardPayment.php');
require_once('GiftCardPayment.php');
require_once('Address.php');
include_once('functions/QBD_Job.php');

class Order {

  private $v_order_number;

  //no constructor needed -- we're using the CheckoutPage constructor
  public function __construct($p_order_number) {
    $this->v_order_number = $p_order_number;
  } 

  /**
   * Checks to see if any line item in the the order is of source_type_code external
   *  or availability_date > { fn NOW() }
   * If it does, return true.  False otherwise
   * this is the pre-book prebook 100% deposit 
   * changed from hasExternalItems()
   */
  public function checkForPrebook() {
    $sql_db = mssql_connect(SQL_SERVER,SQL_USER,SQL_PASSWORD);
    $mssql_db = mssql_select_db(SQL_DB, $sql_db);
    //added availabilty date
    $v_sql = "SELECT count(*) as xnum FROM dbo.GSI_CMN_ORDER_LINES_V
     	where order_number='" . $this->v_order_number . "' and ( source_type_code='EXTERNAL' or AVAILABILITY_DATE > {fn NOW()} )";
    $v_result = mssql_query($v_sql);
    
    if(!$v_result) {
      display_mssql_error("checkForPrebook", "called from order.php");
    }
    $row = mssql_fetch_assoc($v_result);
    $xnum  = $row['xnum'];
    
    mssql_free_result($v_result);
    
    if($xnum >0) {
      return true;
    } else {
      return false;
    }
  }
    
  
  public function getDropshipRestrictions(&$p_restricted_item_list) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_ship_restrict_get_dropship_restrictions");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_results", $p_restricted_item_list, 'varchar', 2000, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_ship_restrict_get_dropship_restrictions", "called from getDropshipRestrictions() in Order.php");
    }

    mssql_free_statement($v_stmt);

    return $v_return_status;

  }

  public function updateOrder($p_customer_id, $p_bill_address_id, $p_bill_contact_id, $p_ship_address_id, $p_ship_contact_id, $p_ship_method_flag) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    if(!empty($this->v_order_number)) {
      //link addresses
      $v_stmt = mssql_init("customer.dbo.gsi_cust_link_addresses");

      gsi_mssql_bind($v_stmt, "@p_customer_id", $p_customer_id, 'gsi_id_type', 50, false);
      gsi_mssql_bind($v_stmt, "@p_bill_address_id", $p_bill_address_id, 'gsi_id_type', 50, false);
      gsi_mssql_bind($v_stmt, "@p_ship_address_id", $p_ship_address_id, 'gsi_id_type', 50, false);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("customer.dbo.gsi_cust_link_addresses", "called from updateOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      //update header
      $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_update_cust_hdr_info");

      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_customer_id", $p_customer_id, 'gsi_id_type', 50, false);
      gsi_mssql_bind($v_stmt, "@p_inv_address_id", $p_bill_address_id, 'gsi_id_type', 50, false);
      gsi_mssql_bind($v_stmt, "@p_inv_contact_id", $p_bill_contact_id, 'gsi_id_type', 50, false);
      gsi_mssql_bind($v_stmt, "@p_ship_address_id", $p_ship_address_id, 'gsi_id_type', 50, false);
      gsi_mssql_bind($v_stmt, "@p_ship_contact_id", $p_ship_contact_id, 'gsi_id_type', 50, false);
      gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_update_cust_hdr_info", "called from updateOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      //update ship method
      $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_update_ship_method");

      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50, false);
      gsi_mssql_bind($v_stmt, "@p_ship_method_flag", $p_ship_method_flag, 'varchar', 30, false);
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30, false);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_update_ship_method", "called from updateOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      if(empty($v_return_status)) {
      
        $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_update_cust_line_info");

        gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50, false);
        gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50, false);
        gsi_mssql_bind($v_stmt, "@p_customer_id", $p_customer_id, 'gsi_id_type', 50, false);
        gsi_mssql_bind($v_stmt, "@p_ship_address_id", $p_ship_address_id, 'gsi_id_type', 50, false);
        gsi_mssql_bind($v_stmt, "@p_ship_contact_id", $p_ship_contact_id, 'gsi_id_type', 50, false);
        gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

        $v_result = mssql_execute($v_stmt);

        if(!$v_result) {
          display_mssql_error("gsi_cmn_order_update_cust_line_info", "called from updateOrder() in Order.php");
        }

        mssql_free_statement($v_stmt);
        mssql_free_result($v_result);

      }

    }

    $_SESSION['s_do_upd_cust_order'] = '';

    return $v_return_status;

  }

  public function updateAmazonOrderShipping($p_ship_method_flag) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    if(!empty($this->v_order_number)) {

      //update ship method
      $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_update_ship_method");

      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50, false);
      gsi_mssql_bind($v_stmt, "@p_ship_method_flag", $p_ship_method_flag, 'varchar', 30, false);
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30, false);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_update_ship_method", "called from updateOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);
    }

    $_SESSION['s_do_upd_cust_order'] = '';

  }
  
  public function addGiftCardPayment($p_remaining_total, $p_gc_num, $p_gc_pin) {

    if(!empty($p_gc_num)) {

      if ($p_remaining_total == '0') {
        $v_isp_dummy_pmt_status = $this->findISPDummyPayment();
      }

      $i_payment = new GiftCardPayment();

      $v_status = $i_payment->getGCBalance($p_gc_num, $p_gc_pin, $this->v_order_number, $v_face_value);

      if(empty($v_status)) {

        if($v_face_value >= $p_remaining_total && $p_remaining_total > 0) { //payment amount is order total
          $v_payment_amount = $p_remaining_total;
          $v_gc_amount_left = floatval($v_face_value) - floatval($v_payment_amount);
        } else if($v_face_value < $p_remaining_total) { //payment amount is face value
          $v_payment_amount = $v_face_value;
          $v_gc_amount_left = 0;
        } else { //somehow, we got a negative order total
          $v_payment_amount = 0;
          $v_gc_amount_left = $v_face_value;
        }

        if($p_isp_dummy_pmt_status == 'NOT PAID') {
          $v_payment_amount = 0;
        }

        if ($_SESSION['site'] == 'CA' && $_SESSION[s_ship_method] == 'I') {
          $v_payment_amount = 0;
        }

        if($v_payment_amount > 0) {
          $v_status = $i_payment->add_payment($v_payment_amount, $p_gc_num, $p_gc_pin, $v_gc_amount_left);
        }
      }
    } else {
      $v_status = 'Missing gift card number';
    }

    if(!empty($v_status)) {
      if($v_status == 'INVALID') {
        $v_status = 'Invalid gift card number or pin';
      }
      $v_status = 'Error: ' . $v_status;
    } else {
      //we need to indicate which page to load
      if($v_payment_amount == $p_remaining_total) {
        $v_status = 'review';
      } else {
        $v_status = 'payment';
      }
    }

    return $v_status;

  }

  public function removeGiftCardPayment($p_gc_num) {
    $i_payment = new GiftCardPayment();
    $i_payment->removeGCPayment($p_gc_num);
  } 

  public function adjustGiftCardPayments() {
    $i_payment = new GiftCardPayment();
    $i_payment->adjustGiftCardPayments();
  }

  public function findISPDummyPayment() {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_isp_find_dummy_payment");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_isp_find_dummy_payment", "called from findISPDummyPayment() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_return_status;

  }

  public function calcTotals(&$p_sub_total, &$p_ship_total, &$p_tax_total, &$p_gc_total, &$p_cc_total, &$p_return_status) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    if($_SESSION['s_do_totals'] == 'Y') {
      $this->orderSummary($p_sub_total);

      //get shipping charge
      $v_stmt = mssql_init("direct.dbo.gsi_get_shipping");

      gsi_mssql_bind ($v_stmt, "@p_original_system_reference", $this->v_order_number, "varchar", 50);
      gsi_mssql_bind ($v_stmt, "@p_profile_scope", $v_profile_scope, "varchar", 30);
      gsi_mssql_bind ($v_stmt, "@p_shipping", $p_ship_total, 'money', -1, true);
      gsi_mssql_bind ($v_stmt, "@o_return_status", $p_return_status, 'varchar', 200, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_get_shipping", "called from calcTotals() in Order.php");
      }

      if ($p_ship_total < 0) {
        $p_ship_total = 0;
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      //update shipping charges in header; will be used for tax calculation
      $v_stmt = mssql_init("gsi_cmn_order_update_header_attributes");

      gsi_mssql_bind ($v_stmt, "@p_original_system_reference", $this->v_order_number, "varchar", 50, false);
      gsi_mssql_bind ($v_stmt, "@p_attribute13", $p_ship_total, "varchar", 40, false);
      gsi_mssql_bind ($v_stmt, "@p_profile_scope", $v_profile_scope, "varchar", 20, false);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_update_header_attributes", "update to attribute13, called by calcTotals() in Order.php");
      }

      mssql_free_statement($v_stmt);

      //tax
      $v_stmt = mssql_init("gsi_calculate_taxes");

      gsi_mssql_bind ($v_stmt, "@p_original_system_reference", $this->v_order_number, "varchar", 50, false);
      gsi_mssql_bind ($v_stmt, "@p_profile_scope", $v_profile_scope, "varchar", 30, false);
      gsi_mssql_bind ($v_stmt, "@p_shipping_amount", $p_ship_total, "money", 20, false);
      gsi_mssql_bind ($v_stmt, "@p_tax_amount", $p_tax_total, "money", 20, true);
      gsi_mssql_bind ($v_stmt, "@o_return_status", $p_return_status, "varchar", 200, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_calculate_taxes", "called from calcTotals() in Order.php");
      }
      
      // update the header attributes
      $p_order_total = $p_sub_total + $p_ship_total + $p_tax_total;
      
      //need to check for tax free promo in table
      //if tax free promo, tax total is 0, save real attribute8, and packing instructions gets real tax
      //else tax total stays as-is
      $v_attribute8 = "$p_sub_total|0|$p_ship_total|$p_tax_total|$p_order_total" ;

      //update attribute8 in header
      $v_stmt = mssql_init("gsi_cmn_order_update_header_attributes");

      gsi_mssql_bind ($v_stmt, "@p_original_system_reference", $this->v_order_number, "varchar", 50, false);
      gsi_mssql_bind ($v_stmt, "@p_attribute8", $v_attribute8, "varchar", 40, false);
      gsi_mssql_bind ($v_stmt, "@p_profile_scope", $v_profile_scope, "varchar", 20, false);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_update_header_attributes", "update to attribute8, called by calcTotals() in Order.php");
      }

      mssql_free_statement($v_stmt);

      $_SESSION['s_do_totals'] = '' ;

    } else {

      // get and parse attributes for totals
      $v_stmt = mssql_init("gsi_cmn_order_retrieve_order_totals");

      gsi_mssql_bind ($v_stmt, "@p_order_number", $this->v_order_number, "varchar", 50, false);
      gsi_mssql_bind ($v_stmt, "@p_profile_scope", $v_profile_scope, "varchar", 50, false);
      gsi_mssql_bind ($v_stmt, "@p_sub_total", $p_sub_total, "money", 20, true);
      gsi_mssql_bind ($v_stmt, "@p_tax_total", $p_tax_total, "money", 20, true);
      gsi_mssql_bind ($v_stmt, "@p_ship_total", $p_ship_total, "money", 20, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_retrieve_order_totals", "called by calcTotals() in Order.php");
      }

      mssql_free_statement($v_stmt);

    }

    $v_stmt = mssql_init("gsi_cmn_payments_retrieve_pmt_totals");

    gsi_mssql_bind ($v_stmt, "@p_order_number", $this->v_order_number, "varchar", 30, false);
    gsi_mssql_bind ($v_stmt, "@p_gc_total", $p_gc_total, "money", 20, true);
    gsi_mssql_bind ($v_stmt, "@p_cc_total", $p_cc_total, "money", 20, true);

    $result = mssql_execute($v_stmt);

    if(!$result) {
      display_mssql_error("direct.dbo.gsi_cmn_payments_retrieve_pmt_totals", "called from calcTotals() in Order.php");
    }

    mssql_free_statement($v_stmt);

  }

  //retrieve totals for an order that's already been submitted
  public function retrieveSubmittedOrderTotals(&$p_sub_total, &$p_tax_total, &$p_ship_total) {

    global $mssql_db;

    $v_order_source_id = '9';

    $v_stmt = mssql_init("orders.dbo.gsi_ord_retrieve_order_totals");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_order_source_id", $v_order_source_id, 'gsi_id_type');
    gsi_mssql_bind($v_stmt, "@p_sub_total", $p_sub_total, 'gsi_amount_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_tax_total", $p_tax_total, 'gsi_amount_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_total", $p_ship_total, 'gsi_amount_type', -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("orders.dbo.gsi_ord_retrieve_order_totals", "called from retrieveSubmittedOrderTotals() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function calcISPWarehouseTotals($p_warehouse_id, &$p_sub_total, &$p_tax_total) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;
    $v_isp_order = 'Y';
    $v_shipping_amount = '0.0';

    //calculate item subtotal
    $v_stmt = mssql_init("gsi_cmn_order_get_isp_warehouse_subtotal");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_warehouse_id", $p_warehouse_id, 'int');
    gsi_mssql_bind($v_stmt, "@p_sub_total", $p_sub_total, 'money', -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_get_isp_warehouse_subtotal", "called from calcISPWarehouseTotals() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    if(empty($p_sub_total)) {
      $p_sub_total = 0;
    }

    //calculate tax total
    $v_stmt = mssql_init("gsi_calculate_taxes");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_shipping_amount", $v_shipping_amount, 'money');
    gsi_mssql_bind($v_stmt, "@p_isp_order", $v_isp_order, 'char', 1);
    gsi_mssql_bind($v_stmt, "@p_warehouse_id", $p_warehouse_id, 'int');
    gsi_mssql_bind($v_stmt, "@p_tax_amount", $p_tax_total, 'money', -1, true);
    gsi_mssql_bind($v_stmt, "@o_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_calculate_taxes", "called from calcISPWarehouseTotals() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result); 

    if(empty($p_tax_total)) {
      $p_tax_total = 0;
    }

  }

  public function validateOrderQtys() {
  	
    global $mssql_db;
 	
    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("gsi_cmn_order_validate_qtys");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 2000, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_validate_qtys", "called from validateOrderQtys() in Order.php");
    }
    
	if (strpos($v_return_status,"is below the minimum quantity required to process your order.")){
		return $v_return_status;
	}
	
    mssql_free_statement($v_stmt);
	// ----
    $v_organization_id = 25;
    if ($_SESSION['site'] != 'US') {
    	$v_organization_id = 173;
    }

	$atp_sql = "select
                v.original_system_line_reference
              , v.inventory_item_id
              , v.selling_price
              , v.sku_desc
              , v.style_number
              , v.ordered_quantity
              from  direct.dbo.gsi_cmn_order_lines_v v
              where order_number = '$this->v_order_number'
              order by ship_set_number, line_number";
	
    $atp_result = mssql_query($atp_sql);

    if (!$v_result){
      display_mssql_error($v_sql, "cart items ATP query from order ");
    }
    while($v_row = mssql_fetch_array($atp_result)) {
    	// the new ATP array
    	// it seems like I'll use the v.selling_price to stop adding the array,but lets wait :D 
    	if ($v_row["selling_price"] != 0){
    		if ($v_row["inventory_item_id"] == "332375"){
    			// as SPEC ORDER FEE MENS EQUIPMENT AND SUPPLIES.
    			continue;
    		}
    		
    		$tempATPArr[] = array('INVENTORY_ITEM_ID'=>$v_row["inventory_item_id"],'QUANTITY'=>$v_row["ordered_quantity"]);
    		$ItemKey = $v_row["inventory_item_id"];
    		$tempTotalOrdQtyArr[$ItemKey] += $v_row["ordered_quantity"];
    		$tempItemsDesc[$ItemKey] =  array('LineNumber'=>$v_row["original_system_line_reference"],'skuDesc'=>$v_row["sku_desc"],'styleNo'=>$v_row["style_number"],'OrdQty'=>$v_row["ordered_quantity"]);
    	}
    }
    foreach ($tempTotalOrdQtyArr as  $key => $value){
        $atp_arr[]= array("iiv"=>$key,"qty"=>$value);
    }
  
    //$atp_arr = array(array("iiv"=>$inv_item_id,"qty"=>$qtp));
    $ATPresults = ATPExtendedSoap::checkATPProc($atp_arr,"Shoppingcart");
  
    if (is_array($ATPresults)){
        foreach ($ATPresults as $key => $value) {
            // I added the nest line as check point otherwise log the objects for future more investigation 
            $tmpInvId = $key;
            if (isset($value['promise_date']) && $value['promise_date'] != NULL && $value['promise_date'] != 0 ){
                $_SESSION["ATPPromisedDATEs"][$tmpInvId]= $value['promise_date'];
            }else{ // to discover the problem I'll log the whole object $ATPresults
                write_to_log("PromisedDate Problem: "." The Object:".var_export($ATPresults,TRUE)."Session Vars:".var_export($_SESSION["ATPPromisedDATEs"],true)." Order#:".$this->v_order_number );
            }
            
            // $va_item['promise_date'] override this value for the display
            // $va_item['atp_qty'] this is ATP QTY but there are no usage of it in the old code
            if ($value["totalQTY"]<$tempTotalOrdQtyArr[$ItemKey]){
                if ($tempItemsDesc[$tmpInvId]["OrdQty"] == 1 ){
                    $errors .= "We dont have the ". $tempItemsDesc[$tmpInvId]["skuDesc"] ." (".$tempItemsDesc[$tmpInvId]["styleNo"].") Please remove it.";
                }else{
                    $errors .= "The quantity for the ". $tempItemsDesc[$tmpInvId]["skuDesc"] ." (".$tempItemsDesc[$tmpInvId]["styleNo"].") is more than we have in stock.";
                }
            }
        }
    }
    // get the SOAP results and updat the
    if ( $errors != ""){
        // here will make the calculations for PROMISED DATE and the Quantity
          return $errors;
    }else{
          return "";
    }

    /*
 	//$SOAPURL =ATPSOAPSERVICE_URL; 
 	$SOAPURL = dirname(__FILE__) . '/GetATPQuantity.wsdl';
    try{
		$v_sclient = new ATPExtendedSoap($SOAPURL,array("soap_version"  => SOAP_1_1, 'trace' => true));
		$v_sclient->setArrayRequest($tempATPArr);
			$credentials = array("UsernameToken" => array("Username" => ORDER_STATUS_USER, "Password" => ORDER_STATUS_PASSWORD ));
			$security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
			//	create header object
			$header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true);
			//$v_sclient->__setSoapHeaders( $header );

   			$results = $v_sclient->process( "" );
			$errors = "";
  			if (is_array($results->ATPOutput)){	
				foreach ($results->ATPOutput as $value) {
					// $va_item['promise_date'] override this value for the display 
					// $va_item['atp_qty'] this is ATP QTY but there are no usage of it in the old code
					$value = (object) array("ATPOutput"=>$value);
					$tempVal = ATPExtendedSoap::ATPPromisedDatesMSG($value,$tempTotalOrdQtyArr[$ItemKey]);
					$ATPresults[]= $tempVal;
					$tmpInvId = $tempVal["inventoryID"];
					$_SESSION["ATPPromisedDATEs"][$tmpInvId]= $tempVal["promisedDate"]; 
					if ($tempVal["totalQTY"]<$tempTotalOrdQtyArr[$ItemKey]){
						if ($tempItemsDesc[$tmpInvId]["OrdQty"] ==1 ){
							$errors .= "We dont have the". $tempItemsDesc[$tmpInvId]["skuDesc"] ." (".$tempItemsDesc[$tmpInvId]["styleNo"].") Please remove it.";
						}else{
							$errors .= "The quantity for the". $tempItemsDesc[$tmpInvId]["skuDesc"] ." (".$tempItemsDesc[$tmpInvId]["styleNo"].") is more than we have in stock.";
						}
					}
				}
			}else{
				//$ATPresults=ATPExtendedSoap::ATPPromisedDatesMSG($results,1);
				$tempVal = ATPExtendedSoap::ATPPromisedDatesMSG($results,$tempTotalOrdQtyArr[$ItemKey]);
				$ATPresults= $tempVal;
				$tmpInvId = $tempVal["inventoryID"];
				$_SESSION["ATPPromisedDATEs"][$tmpInvId]= $tempVal["promisedDate"];

				if ($tempVal["totalQTY"]<$tempTotalOrdQtyArr[$ItemKey]){
					if ($tempItemsDesc[$tmpInvId]["OrdQty"] ==1 ){
						$errors .= "We dont have the". $tempItemsDesc[$tmpInvId]["skuDesc"] ." (".$tempItemsDesc[$tmpInvId]["styleNo"].") Please remove it.";
					}else{
						$errors .= "The quantity for the". $tempItemsDesc[$tmpInvId]["skuDesc"] ." (".$tempItemsDesc[$tmpInvId]["styleNo"].") is more than we have in stock.";
					}
				}
			}
			// get the SOAP results and updat the
    		if ( $errors != ""){
    			// here will make the calculations for PROMISED DATE and the Quantity
    			return $errors; 
    		}else{
    			return ""; 
    		}

		}catch (SoapFault $e ){
			// in any case the SOAP service has some issues log the error and keep the old table date and Quantity
			write_to_log("ATP Error ". $e->getMessage());
			return $v_return_status; 
    	}
    	*/
    // ----
    return $v_return_status;  	
  }
  
  public function processOrder() {

    global $mssql_db;

    $v_ship_address_id = $_SESSION['s_ship_address_id'];
    $v_bill_address_id = $_SESSION['s_bill_address_id'];
    $v_user_name = $_SESSION['s_user_name'];
    $v_profile_scope = C_WEB_PROFILE;
    $i_payment = new Payment();
    $shoppingCart = new ShoppingCart();
    $status = FALSE;

    if($_SESSION['site'] == 'US'){
      $v_stmt = mssql_init("gsi_stop_intl_order");
      gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_ship_address_id", $v_ship_address_id, 'int');
      gsi_mssql_bind($v_stmt, "@p_inv_address_id", $v_bill_address_id, 'int');
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_return_status", $v_stop_intl_status, 'varchar', 200, true);
      gsi_mssql_bind($v_stmt, "@o_return_status", $v_status, 'varchar', 200, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_stop_intl_order", "called from processOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

    } else { // canadian order
      $v_stmt = mssql_init("gsi_cmn_isp_process_canada_isp_order");

      gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_return_status", $v_can_isp_status, 'varchar', 200, true);
      gsi_mssql_bind($v_stmt, "@o_return_status", $v_status, 'varchar', 200, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_isp_process_canada_isp_order", "called from processOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);
    }

    $v_dropship = 'N';
    $v_oem = 'N';
    $v_proprietary = 'N';

    if(empty($v_process)) {
      $v_stmt = mssql_init("gsi_check_oem_proprietary");
      gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, "varchar", 25);
      gsi_mssql_bind($v_stmt, "@o_dropship", $v_dropship, "varchar", 1, true);
      gsi_mssql_bind($v_stmt, "@o_oem", $v_oem, "varchar", 1, true);
      gsi_mssql_bind($v_stmt, "@o_proprietary", $v_proprietary, "varchar", 1, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result){
        display_mssql_error("gsi_check_oem_proprietary", "called from processOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      // ship_set atp_dates
      $v_stmt = mssql_init("gsi_cmn_order_ext_ship_set_atp_dates");

      gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 30);
      gsi_mssql_bind($sql, "@p_profile_scope", $v_profile_scope, 'varchar', 30);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result){
        display_mssql_error("gsi_cmn_order_ext_ship_set_atp_dates", "called from processOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      //call cc/gc auth
      $v_success = $i_payment->auth_payment($this->v_order_number, $v_return_status);
	  // if is ok the CC is ok and confirmed and next condition is true
      if($v_success === TRUE && $v_return_status=="confirm") {
	
        if ( (isset($_SERVER['HTTP_VIA']))  && (strrpos($_SERVER['HTTP_VIA'], "moovweb") > 0))
        {
                $v_ip_address = substr($_SERVER['HTTP_X_FORWARDED_FOR'],0,strripos($_SERVER['HTTP_X_FORWARDED_FOR'],',') );
        }
        else if (isset($_SERVER['HTTP_TRUE_CLIENT_IP']))
 		{
 			$v_ip_address    = $_SERVER['HTTP_TRUE_CLIENT_IP'];
 		}
      	else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
 		{
 			$v_ip_address    = $_SERVER['HTTP_X_FORWARDED_FOR'];
 		}
 		 else {
        	$v_ip_address    = ( !empty($_SERVER['HTTP_CLIENT_IP']) ?  $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'] ) ;
 		}

        $v_stmt = mssql_init("gsi_cmn_order_ext_update_order_attributes");

        gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 25);
        gsi_mssql_bind($sql, "@p_tracking_code", $v_tracking_code, 'varchar', 25);
        gsi_mssql_bind($sql, "@p_ip_address", $v_ip_address, 'varchar', 25);

        $v_result = mssql_execute($v_stmt);

        if (!$v_result){
          display_mssql_error("gsi_cmn_order_ext_update_order_attributes", "called from processOrder() in Order.php");
        }

        mssql_free_statement($v_stmt);
        mssql_free_result($v_result);
		// 
		// call the decrement ATP new service 
		$itemsDTS_str = $this->ATPdecrement();
		$atpFlag = 'N';
        //process order

		global $mssql_db;
		get_mssql_connection();
		
		$DTSstatus = $this->UpdateDTS($itemsDTS_str);
		
        $v_stmt = mssql_init("gsi_cmn_order_process_order");

        gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 30);
        gsi_mssql_bind($v_stmt, "@p_email", $v_user_name, 'varchar', 50);
        gsi_mssql_bind($v_stmt, "@p_catp_service_flag", $atpFlag, 'varchar', 50);
        gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
        gsi_mssql_bind($v_stmt, "@p_return_status", $v_process_status, 'varchar', 500, true);

        $v_result = mssql_execute($v_stmt);
 
	   if(!$v_result) {

          display_mssql_error("gsi_cmn_order_process_order", "called from processOrder() in Order.php");
        }
        $v_return_status=$v_process_status;

        mssql_free_statement($v_stmt);
        mssql_free_result($v_result);
      } else {

        $v_process_status = $v_return_status;
        if(empty($v_process_status)) {
           $v_process_status = 'Failed to process credit cards';
        }
        
      }
    }
    
// last line get me "Procedure 'gsi_transfer_direct"
//if $v_process_status is empty then we are good to go 
    if (empty($v_process_status)) {
      //update QBD qty if applicable
      $this->updateQBDQty();
        
     // Order Confirmation Email procedure which insert data into reports..gsi_order_confirmation_email table
      $v_email_stmt=mssql_init("reports.dbo.gsi_save_order_confirmation_email");
      gsi_mssql_bind($v_email_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 30);
      $v_email_result = mssql_execute($v_email_stmt);
      
     if(!$v_email_result) {
        display_mssql_error("reports.dbo.gsi_save_order_confirmation_email", "called from processOrder() in Order.php");
      }
           
     // End Order Confirmation Email Insert Data Procedure

      $status = TRUE;
    }
    
    if($_SESSION["mpTransaction"])
    {
        $shoppingCart->mpPostBack($status, $this->v_order_number);
        $this->clearMPSessionInfo();
    }
    
    return($v_return_status) ;

  }
  
  /**
   * Save ISP info
   */
  public function saveISPInfo($orderNumber, $phone, $email, $name)
  {
      global $mssql_db;
      get_mssql_connection();
      
      $v_stmt = mssql_init("direct.dbo.gsi_save_isp_pickup_info");
      gsi_mssql_bind($v_stmt, "@p_order_number", $orderNumber, "varchar", 50);
      gsi_mssql_bind($v_stmt, "@p_customer_phone", $phone, "varchar", 35);
      gsi_mssql_bind($v_stmt, "@p_customer_email", $email, "varchar", 100);
      gsi_mssql_bind($v_stmt, "@p_customer_name", $name, "varchar", 50);
      
      $v_result = mssql_execute($v_stmt);
      
      if(!$v_result)
      {
          display_mssql_error("direct.dbo.gsi_save_isp_pickup_info", 
              "called from processOrder() in Order.php using OrderNumber: $orderNumber, Phone: $phone, Email: $email, Name: $name");
      }
      
      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);
  }
  
  
  /**
   * 
   * Luis And Ahmad ISP Project
   */
  
  public function processIspOrder($info) {
  
      global $mssql_db;
      get_mssql_connection();
  
      $v_user_name = $_SESSION['s_user_name']; //this is the email of customer
      $v_first_name = $_SESSION['s_first_name'];
      $v_last_name = $_SESSION['s_last_name'];
      $v_GSEmailOptIn = $info['GSEmailOptIn'];
      $v_profile_scope = C_WEB_PROFILE;
      $v_customer_id = $_SESSION['s_customer_id'];
      
   
      $v_dropship = 'N';
      $v_oem = 'N';
      $v_proprietary = 'N';
  
      //phone number
      //---------------------------------------------------
      $v_pnum = strip_tags($_SESSION['phone_number']);
      $v_pnum = preg_replace('/[^0-9]/', '', $v_pnum);
      if (strlen($v_pnum) >= 10)
      {
          $v_area_pnum = substr($v_pnum,0,3);
          $v_loc_pnum = substr($v_pnum,3,strlen($v_pnum));
      } else {
          $v_area_pnum = '';
          $v_loc_pnum = $v_pnum;
      }

      $v_ship_phone_id = $_SESSION['s_ship_phone_id'];
      $v_ship_contact_id = $_SESSION['s_ship_contact_id'];
      $v_ship_address_id = $_SESSION['s_ship_address_id'];
      
      //---------------------------------------------------
      
  

      $v_stmt = mssql_init("gsi_check_oem_proprietary");
      gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, "varchar", 25);
      gsi_mssql_bind($v_stmt, "@o_dropship", $v_dropship, "varchar", 1, true);
      gsi_mssql_bind($v_stmt, "@o_oem", $v_oem, "varchar", 1, true);
      gsi_mssql_bind($v_stmt, "@o_proprietary", $v_proprietary, "varchar", 1, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result){
          display_mssql_error("gsi_check_oem_proprietary", "called from processOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);


      if ( (isset($_SERVER['HTTP_VIA']))  && (strrpos($_SERVER['HTTP_VIA'], "moovweb") > 0))
      {
          $v_ip_address = substr($_SERVER['HTTP_X_FORWARDED_FOR'],0,strripos($_SERVER['HTTP_X_FORWARDED_FOR'],',') );
      }
      else if (isset($_SERVER['HTTP_TRUE_CLIENT_IP']))
      {
          $v_ip_address    = $_SERVER['HTTP_TRUE_CLIENT_IP'];
      }
      else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
      {
          $v_ip_address    = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
      else {
          $v_ip_address    = ( !empty($_SERVER['HTTP_CLIENT_IP']) ?  $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'] ) ;
      }

      $v_stmt = mssql_init("gsi_cmn_order_ext_update_order_attributes");

      gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 25);
      gsi_mssql_bind($v_stmt, "@p_tracking_code", $v_tracking_code, 'varchar', 25);
      gsi_mssql_bind($v_stmt, "@p_ip_address", $v_ip_address, 'varchar', 25);

      $v_result = mssql_execute($v_stmt);

      if (!$v_result){
          display_mssql_error("gsi_cmn_order_ext_update_order_attributes", "called from processIspOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);
      
      
      //----------------------------------------------------------------------------------
      // Create a new customer
      //----------------------------------------------------------------------------------
      if( $info['isp_guest_checkout'] == "N" ){

          $v_stmt = mssql_init("customer.dbo.gsi_cust_isp_insert_contact_and_phone");
          
          gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);
          gsi_mssql_bind($v_stmt, "@p_customer_id",$v_customer_id, 'gsi_id_type');
          gsi_mssql_bind($v_stmt, "@p_first_name", $v_first_name, 'varchar', 50);
          gsi_mssql_bind($v_stmt, "@p_last_name", $v_last_name, 'varchar', 50);
          gsi_mssql_bind($v_stmt, "@p_email", $v_user_name, 'varchar', 240);
          gsi_mssql_bind($v_stmt, "@p_area1", $v_area_pnum, 'varchar', 10);
          gsi_mssql_bind($v_stmt, "@p_phone1", $v_loc_pnum, 'varchar', 25);
          gsi_mssql_bind($v_stmt, "@p_address_id", $v_ship_address_id, 'gsi_id_type');
          gsi_mssql_bind($v_stmt, "@p_contact_id", $p_contact_id, 'gsi_id_type', -1, true);
          gsi_mssql_bind($v_stmt, "@p_phone_id1", $p_phone_id, 'gsi_id_type', -1, true);
          gsi_mssql_bind($v_stmt, "@p_GSEmailOptIn", $v_GSEmailOptIn, 'varchar', 1);
          gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 400, true);
          
          $v_result = mssql_execute($v_stmt);
          
          if(!$v_result) {
              display_mssql_error("customer..gsi_cust_isp_insert_contact_and_phone", "called from processIspOrder() in Order.php");
          }
          
          mssql_free_statement($v_stmt);
          mssql_free_result($v_result);
          
          $_SESSION['s_ship_contact_id'] = $v_ship_contact_id = $p_contact_id;
          $v_ship_phone_id = $p_phone_id;

      }

      //----------------------------------------------------------------------------------
      
      
      // update customer name in order header
      $v_stmt = mssql_init("gsi_cmn_order_update_isp_customer_info");
      
      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_first_name", $v_first_name, 'varchar', 50); 
      gsi_mssql_bind($v_stmt, "@p_last_name", $v_last_name, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_area_pnum", $v_area_pnum, 'varchar', 10);
      gsi_mssql_bind($v_stmt, "@p_loc_pnum", $v_loc_pnum, 'varchar', 25);
      gsi_mssql_bind($v_stmt, "@p_GSEmailOptIn", $v_GSEmailOptIn, 'varchar', 1);
      gsi_mssql_bind($v_stmt, "@p_ship_phone_id", $v_ship_phone_id, 'bigint', -1);
      gsi_mssql_bind($v_stmt, "@p_ship_contact_id", $v_ship_contact_id, 'bigint', -1);
      
      $v_result = mssql_execute($v_stmt);
      
      if (!$v_result){
          display_mssql_error("gsi_cmn_order_update_isp_customer_info", "called from processIspOrder() in Order.php");
      }
      
      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);
      

      $this->updateOrder($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], NULL);
       

      // call the decrement ATP new service
      // $itemsDTS_str = $this->ATPdecrement();
      $atpFlag = 'N';
      //process order

     
      //$DTSstatus = $this->UpdateDTS($itemsDTS_str);

      $v_stmt = mssql_init("gsi_cmn_order_process_order");

      gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_email", $v_user_name, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_catp_service_flag", $atpFlag, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_return_status", $v_process_status, 'varchar', 500, true);

      $v_result = mssql_execute($v_stmt);
    
      if(!$v_result) {
            display_mssql_error("gsi_cmn_order_process_order", "called from processOrder() in Order.php");
      }
     $v_return_status=$v_process_status;

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      // last line get me "Procedure 'gsi_transfer_direct"
      //if $v_process_status is empty then we are good to go
      if (empty($v_process_status)) {
          //update QBD qty if applicable
          $this->updateQBDQty();
      
          // Order Confirmation Email procedure which insert data into reports..gsi_order_confirmation_email table
          $v_email_stmt=mssql_init("reports.dbo.gsi_save_order_confirmation_email");
          gsi_mssql_bind($v_email_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 30);
              $v_email_result = mssql_execute($v_email_stmt);
      
         if(!$v_email_result) {
            display_mssql_error("reports.dbo.gsi_save_order_confirmation_email", "called from processOrder() in Order.php");
         }
   
       // End Order Confirmation Email Insert Data Procedure
  
      }
      return($v_return_status) ;
  
    }//isporder ends
  

	public function  ATPdecrement(){
    	global $mssql_db;
    	$returnFlag = "N";
    	
    	$items_to_decrement = "";
    	
    	$atp_sql = "select
    	v1.inventory_item_id
    	, sum(v2.ordered_quantity) as ordered_quantity
    	from  direct.dbo.gsi_cmn_order_lines_v v1,direct.dbo.gsi_cmn_order_lines_v v2
    	where v1.ORIGINAL_SYSTEM_LINE_REFERENCE = v2.ORIGINAL_SYSTEM_LINE_REFERENCE
    	and v1.order_number = '$this->v_order_number'
    	group by v1.INVENTORY_ITEM_ID";
    	 
    	
    	$atp_result = mssql_query($atp_sql);

    	if (!$atp_result){
      		display_mssql_error($v_sql, "cart item query from order ATP new service ");
    	}
    	
    	while($v_row = mssql_fetch_array($atp_result)) {
    		if ($v_row["inventory_item_id"] == "332375"){
    			// as SPEC ORDER FEE MENS EQUIPMENT AND SUPPLIES.
    			continue;
    		}
    		$items_to_decrement .= (string)$v_row["inventory_item_id"].",25,".(string)$v_row["ordered_quantity"].",GS.COM,";
    	}
   
 
    	global $webpub_mssql_db;
    	get_webpub_mssql_connection();

    	// Call stored procedure for decrement quantity for all items    	   
    	$v_sp = mssql_init("gsicatp.dbo.gsi_ATP_Decrement");
    	gsi_mssql_bind ($v_sp, "@CSVList",  $items_to_decrement , "varchar", 3000);
    	gsi_mssql_bind($v_sp, "@p_return_status", $v_return_status, "varchar", 300, true);
    	$v_ATP_result = mssql_execute($v_sp) ;
    	if (!$v_ATP_result){
    	    display_mssql_error("gsicatp.dbo.gsi_ATP_Decremen " . $v_return_status . "call from Order.php");    	    	
    	}
    	$itemsDTS = "";
    	if (mssql_num_rows( $v_ATP_result ) > 0) {    	        	    
    	    while ($v_row = mssql_fetch_assoc($v_ATP_result)) {
    	        $Invkey = $v_row['inventory_item_id'];
    	        $tempPromisedDate = date("Y-m-d",$_SESSION["ATPPromisedDATEs"][$Invkey]);
    	        if (date("Y",$_SESSION["ATPPromisedDATEs"][$Invkey]) == "1969"){
    	           write_to_log("PromisedDate Problem: "." Cart Items:".var_export($items_to_decrement,TRUE)."Session Vars:".var_export($_SESSION["ATPPromisedDATEs"],true)." Order#:".$this->v_order_number );
    	        }
    	        $itemsDTS .= $v_row['inventory_item_id'].",".$v_row['DTS'].",".$tempPromisedDate.",";    	            	        	    	       
    	    } 
    	}  
    	
    	mssql_free_statement ( $v_sp );
    	mssql_free_result ($v_ATP_result);
       	return $itemsDTS;
    	 
    }
  
    public function  UpdateDTS($itemsDTS) {

        global $mssql_db;
        $v_stmt = mssql_init("gsi_lines_update_WS_DTS_promise_date");
         
        gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 30);
        gsi_mssql_bind($v_stmt, "@CSVList", $itemsDTS, 'varchar', 3000);
        gsi_mssql_bind($v_stmt, "@p_return_status", $v_process_status, 'varchar', 500, true);
         
        $v_result = mssql_execute($v_stmt);
        if(!$v_result) {
            display_mssql_error("gsi_lines_update_WS_DTS_promise_date", "called from UpdateDTS() in Order.php");
        }
        mssql_free_statement ( $v_stmt );
        mssql_free_result ($v_result);
        return $v_process_status;
    }
    
    
  public function hasQBDStyle()  {
  	
  	global $mssql_db;
  	
  	$v_has_qbd = false;
  	
  	$v_stmt = mssql_init("direct.dbo.gsi_qbd_has_qbd_style");
	  
    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar',  30);
    gsi_mssql_bind($v_stmt, "@p_ordered_qty", $v_ordered_qty, 'int', -1, true);
	
    $v_result = mssql_execute($v_stmt);

    if (!$v_result) {
      display_mssql_error("gsi_qbd_has_qbd_style", "called from hasQBDStyle() in Order.php");
    }
    
    if ($v_ordered_qty > 0) {
    	$v_has_qbd = true;
    }
        
    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);
    
    return $v_has_qbd;
  	
  }
  
  
  private function getQBDSold() {
  	
  	$v_QBD_info = array();
  	
  	global $mssql_db;
    
    $v_order_info = array();
	
    $v_stmt = mssql_init("direct.dbo.gsi_qbd_get_sold_qbd");
	  
    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar',  30);    
    gsi_mssql_bind($v_stmt, "@p_qbd_id", $p_qbd_id, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_ordered_qty", $v_ordered_qty, 'int', -1, true);
	
    $v_result = mssql_execute($v_stmt);

    if (!$v_result) {
      display_mssql_error("gsi_qbd_get_sold_qbd", "called from getQBDSold() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);
	
    $v_QBD_info['qbd_id'] = intval($p_qbd_id);
  	$v_QBD_info['qbd_sold_qty'] = $v_ordered_qty;
    
    return $v_QBD_info;
  	
  	
  }
  
  
  private function recordQBDSold($p_qbd_id, $p_qbd_sold) {
  	
  	global $web_db;
  	
  	$web_db1 = mysqli_connect(MS_DB_SERVER_WRITE, MS_DB_ADMINUSER, MS_DB_ADMINPASSWORD) ;
  	   
    $v_sql = "INSERT into webonly.gsi_qbd_sold values ($p_qbd_id,now(),$p_qbd_sold)"; 

    $v_result = mysqli_query($web_db1, $v_sql);
    
    display_mysqli_error($v_sql) ;    
    
    mysqli_free_result($v_result);
  	
  }
  
  private function decreaseQBDQty($p_qbd_id, $p_qty) {
    //this is done in preparation for mysql replication 
  	$web_db1 = mysqli_connect(MS_DB_SERVER_WRITE, MS_DB_ADMINUSER, MS_DB_ADMINPASSWORD) ;
  	   
    $v_sql = "UPDATE webonly.gsi_qbd_styles a
    		  SET a.qbd_qty_left = a.qbd_qty_left - $p_qty
			  WHERE a.qbd_id = $p_qbd_id"; 

    $v_result = mysqli_query($web_db1, $v_sql);
    
    display_mysqli_error($v_sql) ;    
    
    mysqli_free_result($v_result);

    
    //now get the remaining qbd qty  	
    $v_qbd_qty_left = 0;
    
    $v_sql = "SELECT a.qbd_qty_left cnt 
    		  FROM webonly.gsi_qbd_styles a
			  WHERE a.qbd_id = $p_qbd_id"; 

    $v_result = mysqli_query($web_db1, $v_sql);
    
    display_mysqli_error($v_sql) ;
    
    while ($myrow = mysqli_fetch_array($v_result)) {

    	$v_qbd_qty_left  = $myrow['cnt'] ;

    }

    mysqli_free_result($v_result);

    return $v_qbd_qty_left;
    
  }
  
  
  private function updateQBDQty() { 	

  	$v_sold_QBD_info = $this->getQBDSold();

  	
  	if ($v_sold_QBD_info['qbd_sold_qty'] > 0) {
  		
  		//update remaining qty 
		$v_qbd_qty_left = $this->decreaseQBDQty($v_sold_QBD_info['qbd_id'],$v_sold_QBD_info['qbd_sold_qty']);		
  		
  		//update gsi_qbd_sold for reporting
  		$this->recordQBDSold($v_sold_QBD_info['qbd_id'],$v_sold_QBD_info['qbd_sold_qty']);
  		
  		if ($v_qbd_qty_left <= 0) {
  			
  			//call startQBDJob
  			$v_QBD_Job_obj = new QBD_Job();
			$v_QBD_Job_obj->startQBDJob();
  			
  		} 
  		
  	}
  	
  }

  public function updateShipMethodCode($p_ship_method_code) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("gsi_cmn_order_update_header_attributes");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_ship_method_code", $p_ship_method_code, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_update_header_attributes", "called from updateShipMethodCode() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function updateHeaderSalesChannel($p_sales_channel_code) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("gsi_cmn_order_update_header_attributes");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_sales_channel_code", $p_sales_channel_code, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_update_attributes", "called from updateHeaderSalesChannel() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function updateHeaderPurchaseOrderNum($p_purchase_order_num) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_order_update_header_attributes");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_purchase_order_num", $p_purchase_order_num, 'varchar', 50);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_update_header_attributes", "called from updateHeaderPurchaseOrderNum() in fns_sc_order");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  private function validateEmail($p_email, $p_check_dns = TRUE) {

    if((preg_match('/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/', $p_email))
      || (preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?)$/', $p_email))) {

      if($p_check_dns && function_exists("checkdnsrr") && !$GLOBALS["winbox"]) {
        $va_host = explode('@', $email);
        // Check for MX record
        if(checkdnsrr($va_host[1], 'MX')) {
          return TRUE;
        }
        // Check for A record
        if(checkdnsrr($va_host[1], 'A')) {
          return TRUE;
        }
        // Check for CNAME record
        if(checkdnsrr($va_host[1], 'CNAME')) {
          return TRUE;
        }
      } else {
        return TRUE;
      }
    }

    return FALSE;

  }

  private function createConfirmLog($p_email_msg) {
    $v_today    = getdate() ;
    $v_date     = $today['mday'] ;
    $v_month    = $today['mon'] ;
    $v_year     = $today['year'] ;
    $v_datestr  = $v_year . $v_month . $v_date ;
    $v_filename = EMAIL_PATH . "confirm" . $v_datestr . ".txt" ;
    $v_fp = fopen($v_filename, "a") ;
    fwrite($v_fp, "$p_email_msg\n") ;
    fwrite($v_fp, "------------------------------------------------------\n\n\n") ;
    fclose($v_fp) ;
  }

  private function orderSummary(&$p_sub_total) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_order_order_summary");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50, false);
    gsi_mssql_bind($v_stmt, "@p_num_items", $p_num_items, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_sub_total", $p_sub_total, 'money', -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_order_summary", "called from orderSummary() in Order.php");
    }

    if(empty($p_sub_total)) {
      $p_sub_total = 0;
    }

    if(empty($p_num_items)) {
      $p_num_items = 0;
    }
 
    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function getPaymentMethod($p_ship_address_id, $p_amount_paid) {

    global $mssql_db;

    $v_payment_method = '';

    $v_stmt = mssql_init("gsi_cmn_payments_get_payment_method");

    gsi_mssql_bind($v_stmt, "@p_address_id", $p_ship_address_id, 'gsi_id_type');
    gsi_mssql_bind($v_stmt, "@p_order_total", $p_amount_paid, 'money');
    gsi_mssql_bind($v_stmt, "@p_payment_method", $v_payment_method, 'varchar', 25, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_get_payment_method", "called from getPaymentMethod() in Order.php");
    }

    mssql_free_statement($v_stmt);

    if(!empty($v_return_status)) {
      $v_payment_method = '';
    }

    return $v_payment_method;

  }

  public function applyPromoCode($p_promo_code) {

    $this->getOrderInfo($v_ship_method_val, $v_ship_method, $v_gift_order);

    //if there's a source code, validate it and find out whether it's a loyalty code
    if(!empty($p_promo_code)) {
      $v_scode_error = $this->checkSourceCode($p_promo_code, $v_src_loyalty_number);
      if(!empty($v_scode_error)) {
        $_SESSION['s_source_code'] = NULL;
        $v_message = 'The cash card <strong>' . $p_promo_code . '</strong> entered is invalid. Please double check the number and try again.';
      } else {
        $_SESSION['s_source_code'] = $p_promo_code;
        $v_message = 'Your promotion code has been accepted.';
      }
    }

    //update only if its a valid source code
    if ( (!empty($p_promo_code)) && (empty($v_scode_error)) ) {
	    //load changed promotion code into header attributes if applicable
    	$this->updateHeaderSourceCode($v_gift_order, $p_promo_code);
    }

    if (!empty($p_promo_code)) {
      $v_ship_source_code = $this->getValidSourceCode($p_promo_code);

      $v_stmt = mssql_init("gsi_cmn_order_ext_update_order_attributes");

      gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 25);
      gsi_mssql_bind($v_stmt, "@p_ship_source_code", $v_ship_source_code, 'varchar', 30);

      $result = mssql_execute($v_stmt);

      if(!$result) {
        display_mssql_error("gsi_cmn_order_ext_update_order_attributes", "called from applyPromoCode() in Order.php");
      }
      mssql_free_statement($v_stmt);
    }

    $_SESSION['s_ship_source_code'] = $v_ship_source_code;

    $_SESSION['s_do_reprice']   = 'Y' ;
    //$this->repriceOrder();

    return $v_message;

  }

  public function getTotalPromotionSavings() {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_order_get_total_savings");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_total_savings", $v_total_savings, 'gsi_price_type', -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_get_total_savings", "called from getTotalPromotionSavings() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_total_savings;

  }

  
  public function loadFreegoods() {
  global $mssql_db ;
  $v_profile_scope = C_WEB_PROFILE;

  // Load Freegoods
  $sql = mssql_init("direct.dbo.gsi_cmn_order_load_freegoods");

  mssql_bind ($sql, "@p_original_system_reference", $this->v_order_number, SQLVARCHAR, false, false, 30);
  mssql_bind ($sql, "@p_profile_scope", $v_profile_scope, SQLVARCHAR, false, false, 30);
  mssql_bind ($sql, "@p_return_status", $v_return_status, SQLVARCHAR, true, true, 200);

  $result = mssql_execute($sql);
  if (!$result) {
    display_mssql_error("gsi_cmn_order_load_freegoods", "called from load_freegoods() in payments_common");
  }

  mssql_free_statement($sql);
  mssql_free_result($result);

  $sql = mssql_init("direct.dbo.gsi_cmn_order_ext_update_isp_ship_set_items");

  mssql_bind ($sql, "@p_orig_sys_ref", $this->v_order_number, SQLVARCHAR, false, false, 30);

  $result = mssql_execute($sql);
  if (!$result){
    display_mssql_error("gsi_cmn_order_update_isp_ship_set_items", "called from load_freegoods() in payments_common");
  }

  mssql_free_statement($sql);
  mssql_free_result($result);

} //end of function load_freegoods
  
  public function getShippingOptions($p_has_ship_restrictions, &$pa_shipping_options) {

    $pa_shipping_options = array();

    $this->getOrderInfo($v_ship_method_val, $v_ship_method, $v_gift_order);

    $v_user_chooses_shipping = $this->userChoosesShipping($v_ship_method_val);

    $v_ship_method_code = $_SESSION['s_ship_method'];
    
    if($v_user_chooses_shipping) {

      // get  all shipping charges
      $this->getThreeShippingCharges($v_ship_method_code, $v_temp_ship_g, $v_temp_ship_2, $v_temp_ship_n, $v_temp_ship_g_method, $v_temp_ship_2_method, $v_temp_ship_n_method);

      if (!empty($v_temp_ship_g_method)) {
        $va_shipping['G'] = $v_temp_ship_g;
      }
      if (!empty($v_temp_ship_2_method)){
        $va_shipping['2']= $v_temp_ship_2;
      }
      if (!empty($v_temp_ship_n_method)){
        $va_shipping['N']= $v_temp_ship_n;
      }

    } else {
      $_SESSION['s_do_totals'] = 'Y';
    }

    $v_is_all_isp_sts = $this->isISPSTSOnly();

    if($v_is_all_isp_sts !== TRUE) {
      if($v_user_chooses_shipping === TRUE) {
        if($p_has_ship_restrictions !== TRUE) {
          if(isset($va_shipping['G']) || !empty($va_shipping['G'])) {
            $pa_shipping_options['G'] = array('name' => 'Ground', 'charge' => $va_shipping['G']);
          }

          if((isset($va_shipping['2']) || !empty($va_shipping['2'])) && $_SESSION['site'] != 'CA') {
            $pa_shipping_options['2'] = array('name' => '2nd Day Air', 'charge' => $va_shipping['2']);
          }

          if(isset($va_shipping['N']) || !empty($va_shipping['N'])) {
            $pa_shipping_options['N'] = array('name' => 'Next Day Air', 'charge' => $va_shipping['N']);
          }

          if ($_SESSION['site'] == 'CA') {
            $pa_shipping_options['I'] = array('name' => 'Pickup In Toronto', 'charge' => '0');
          }
        } else {
          if(isset($va_shipping['G']) || !empty($va_shipping['G'])) {
            $pa_shipping_options['G'] = array('name' => 'Ground', 'charge' => $va_shipping['G']);
          } else {
            $pa_shipping_options['G'] = array('name' => 'Ground');
          }
        }
      } else {
        //translate ship method here
        $v_ship_static_display = $this->translateShipMethod($v_ship_method_val);
        $pa_shipping_options[$v_ship_method_code] = array('name' => $v_ship_static_display);
      }
    }

  }

  public function getEstimatedShipping($p_postal_code, $p_shipping_method, &$pa_shipping_tax_info) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $this->getCityState($p_postal_code, $v_city, $v_state);

    if(!empty($v_city) && !empty($v_state)) {

     // $v_has_dropship = $this->hasDropship();
      $v_has_ship_restrictions = FALSE;
      $this->getDropshipRestrictions($v_dropship_restrictions);
      $va_dropship_restrictions = explode(',', str_replace('"', '', $v_dropship_restrictions));
      if($va_dropship_restrictions[0]){
      	$v_has_ship_restrictions = TRUE;
      }
      
      //get charges
      $v_stmt = mssql_init("gsi_nbt_ship_get_three_ship_charges_and_tax");

      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 15);
      gsi_mssql_bind($v_stmt, "@p_ship_method_code", $p_shipping_method, 'varchar', 15);
      gsi_mssql_bind($v_stmt, "@p_postal_code", $p_postal_code, 'varchar', 15);
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 15);
      gsi_mssql_bind($v_stmt, "@p_shipping_ground", $v_shipping_ground, 'varchar', 20, true);
      gsi_mssql_bind($v_stmt, "@p_shipping_twoday", $v_shipping_twoday, 'varchar', 20, true);
      gsi_mssql_bind($v_stmt, "@p_shipping_nextday", $v_shipping_nextday, 'varchar', 20, true);
      gsi_mssql_bind($v_stmt, "@p_tax_none", $v_tax_none, 'money', 20, true);
      gsi_mssql_bind($v_stmt, "@p_tax_ground", $v_tax_ground, 'money', 20, true);
      gsi_mssql_bind($v_stmt, "@p_tax_twoday", $v_tax_twoday, 'money', 20, true);
      gsi_mssql_bind($v_stmt, "@p_tax_nextday", $v_tax_nextday, 'money', 20, true);
      gsi_mssql_bind($v_stmt, "@o_return_status", $v_return_status, 'varchar', 100, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_nbt_ship_get_three_ship_charges_and_tax", "called from getEstimatedShipping() in CheckoutPage.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_statement($v_result);

      $pa_shipping_tax_info = array();
      $pa_shipping_tax_info['NONE'] = array('tax' => $v_tax_none);
      $pa_shipping_tax_info['G'] = array('name' => 'Ground', 'shipping' => $v_shipping_ground, 'tax' => $v_tax_ground);
      if($v_has_ship_restrictions !==TRUE){	
        if($_SESSION['site'] != 'CA') {
          if (!empty($v_shipping_twoday))
          {
          	$pa_shipping_tax_info['2'] = array('name' => '2nd Day Air', 'shipping' => $v_shipping_twoday, 'tax' => $v_tax_twoday);
          }
        }
        if (!empty($v_shipping_nextday))
        {
        	$pa_shipping_tax_info['N'] = array('name' => 'Next Day Air', 'shipping' => $v_shipping_nextday, 'tax' => $v_tax_nextday);
        }
      }


      if($_SESSION['site'] == 'CA') {
        $pa_shipping_tax_info['I'] = array('name' => 'Pickup in Toronto', 'shipping' => '0.00', 'tax' => $v_tax_none);
      }

    }

  }

  public function getCityState($p_postal_code, &$p_city, &$p_state) {
    global $mssql_db;

    $v_postal_code = str_replace("-", "", str_replace(" ", "", $p_postal_code));

    if(!is_numeric($v_postal_code)) {
      $v_postal_code = substr($v_postal_code, 0, 3) . '-' . substr($v_postal_code, 3, 3);
    } else {
      $v_postal_code = substr($v_postal_code, 0, 5);
    }

    $v_sql = mssql_init("gsi_nbt_ship_get_city_state");

    gsi_mssql_bind($v_sql, "@p_postal_code", $v_postal_code, 'varchar', 10);
    gsi_mssql_bind($v_sql, "@p_city", $p_city, 'varchar', 50, true);
    gsi_mssql_bind($v_sql, "@p_state", $p_state, 'varchar', 5, true);

    $v_result = mssql_execute($v_sql);

    if(!$v_result) {
      display_mssql_error("gsi_nbt_ship_get_city_state", "called from get_city_state() in fns_sc_order");
    }

    mssql_free_statement($v_sql);
    mssql_free_statement($v_result);
  }

  public function hasDropship() {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("gsi_cmn_order_has_dropship");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_has_dropship", $v_has_dropship, 'varchar', 1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_has_dropship", "called from hasDropship() in ShoppingCart.php");
    }

    if(!empty($v_return_status) || $v_has_dropship != 'Y') {
      $v_has_dropship = FALSE;
    } else {
      $v_has_dropship = TRUE;
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_has_dropship;

  }

  public function extractISPTotals($p_isp_total_str, &$p_isp_total, &$p_isp_sub_total, &$p_isp_tax) {

    global $web_db;

    if(empty($p_isp_total_str)) {
      return 1;
    }

    $va_isp_totals = explode('|', $p_isp_total_str);

    $v_index = 0;
    $p_isp_total = 0;
    $p_isp_sub_total = 0;
    $p_isp_tax = 0;

    while($v_index < count($va_isp_totals)) {
      if($va_isp_totals[$v_index] == "T") {
        $p_isp_total = $va_isp_totals[$v_index + 1];
        $p_isp_sub_total = $va_isp_totals[$v_index + 2];
        $p_isp_tax = $va_isp_totals[$v_index + 3];
      }
      $v_index++;
    }

    return 0;

  }

  public function getISPTotalsString() {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_isp_get_isp_totals");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_rtn_totals_string", $v_isp_totals_string, 'varchar', 150, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_isp_get_isp_totals", "called from getISPTotalsString() in Order.php");
    }

    mssql_free_statement($v_stmt);

    return $v_isp_totals_string;

  }

  public function getISPTax() {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_isp_get_isp_totals");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_rtn_totals_string", $v_isp_total_str, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_Error("gsi_cmn_isp_get_isp_totals", "called from getISPTax() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    $v_isp_total = explode('|', $v_isp_total_str);

    return $v_isp_total[3];

  }

  public function repriceOrder() {

    global $mssql_db;

    if($_SESSION['s_do_reprice'] == 'Y') {

      $v_source_code = $_SESSION['s_source_code'];
      $_SESSION['s_do_reprice'] = '';
      $v_profile_scope = C_WEB_PROFILE;

      $v_line_ref = '';

      $v_stmt = mssql_init("gsi_cmn_order_reprice_order_new");

      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_reprice_order_new", "called from repriceOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($result);

      $v_stmt = mssql_init("gsi_cmn_promotions_add_free_promo_item");

      gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_source_code", $v_source_code, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_orig_sys_line_ref", $line_ref, 'varchar', 50, true);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_promotions_add_free_promo_item", "called from repriceOrder() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

      $this->freeShipping();

      // update promise date in shopping cart
      $v_stmt = mssql_init("gsi_lines_interface_all_update_promise_date");

      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_lines_interface_all_update_promise_date", "called from repriceOrder() in Order.php");
      }
      mssql_free_statement($v_stmt);      
    }
  }

  public function getDefermentOptions(&$pa_deferment_options) {

    global $web_db;

    //we need order total for this
    $this->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_status);

    $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
    $v_net_total   = round(($v_order_total - $v_gc_total), 2) ;

    if ($v_net_total == '0'){

      if ( ($_SESSION['site'] == 'CA') && ($_SESSION[s_ship_method] == 'I') ) {
        $v_net_total = 1;
      } else {

        $v_isp_dummy_pmt_status = $this->findISPDummyPayment();

        if ($v_isp_dummy_pmt_status == 'NOT PAID') {
          $v_net_total = 1;
        }
      }
    }

    $pa_deferment_options = array();

    $v_sql = "select plan_number, plan_desc, min_purchase
              from gsi_ge_plans
              where NOW() between ifnull(start_date, date_sub(CURDATE(), INTERVAL 1 day)) 
                and ifnull(end_date, date_add(CURDATE(), INTERVAL 1 day))
              order by display_sequence ";

    $v_result = mysqli_query($web_db, $v_sql);

    while ($va_row = mysqli_fetch_assoc($v_result)) {
      if($v_net_total >= $va_row['min_purchase']) {
        $pa_deferment_options[] = $va_row;
      }
    }

  }
  
  public function getDefermentDisclosure($p_ge_cc_num, $p_ge_promo_code) {
  	 	
  	
    $this->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_status);

    $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
    $v_net_total   = round(($v_order_total - $v_gc_total), 2) ;
    
  	$v_ofp_language = '';
  	
  	$i_cc_payment = new CreditCardPayment();

    $i_cc_payment->get_ge_deferment_disclosure($p_ge_cc_num, $p_ge_promo_code, $v_net_total, $p_ofp_language, $p_status);
   	
    if (strtoupper($p_status) == 'SUCCESS'){
    		$v_ofp_language = $p_ofp_language;
    }
    
    return $v_ofp_language;
  }
   

  public function hasPayments() {

    $v_review = FALSE;
    
    $this->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);

    $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total;
    $v_net_total = $v_order_total - $v_gc_total;

    $v_isp_dummy_pmt_status = $this->findISPDummyPayment();

    if($v_order_total == 0 && $v_isp_dummy_pmt_status == 'NOT PAID') {
      $v_net_total = 1;
    }

    if($_SESSION['site'] == 'CA' && $_SESSION['smethod'] == 'I') {
      $v_net_total = 1;
    }

    if($v_net_total > 0) {

      $v_return_status = $this->getPaymentInfo($v_card_type, $v_card_number, $v_expiry, $v_amt_paid);
      $v_payment_method = $this->getPaymentMethod($_SESSION['s_ship_address_id'], $v_net_total);

      if($v_amt_paid >= $v_net_total) {
        $v_review = TRUE;
      } else if(empty($v_card_type) && $v_amt_paid > 0) {  //wire transfer -- review page will handle it
        $v_review = TRUE;
      } else if(!empty($v_card_type) && $v_amt_paid > 0) { //existing credit card payment -- review page will handle it
        $v_review = TRUE;
      } else if($_SESSION['s_using_paypal'] == 'Y' && !empty($_SESSION['s_paypal_token']) && !empty($_SESSION['s_paypal_payerid'])) {
        $v_review = TRUE;
      } else {
        //check for saved card
        $i_cc_payment = new CreditCardPayment();

        //check for a saved card
        $i_cc_payment->checkSavedCard($_SESSION['s_customer_id'], $v_token_id, $v_masked_cc, $v_cc_type, $v_expiry, $v_days_auth_valid);

        if(!empty($v_token_id)) {  //if saved card, review page will automatically add the payment
          $v_review = TRUE;
        }

      }

    } else {
      $v_review = TRUE;
    }
    
    //if site is CA make sure shipping and billing address is CA
    $i_address = new Address($_SESSION['s_customer_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], $_SESSION['s_ship_phone_id']);
    $i_address->getAddressFields($v_ship_is_international, $va_ship_address);
    $i_address->setAll($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_bill_phone_id']);
    $i_address->getAddressFields($v_bill_is_international, $va_bill_address);

    if($_SESSION['site'] == 'CA' && $va_ship_address['country'] != 'CA') {
         $v_review = FALSE;
    } else if($_SESSION['site'] == 'CA' && $va_bill_address['country'] != 'CA') {
         $v_review = FALSE;
    }
    
    //if guest checkout, always ask for cc info
    if ($_SESSION['s_is_guest_checkout'] != 'N')
    {
    	$v_review = FALSE;
    }

    return $v_review;

  }
// adding the CC type as last paramter 
  public function addCreditCardPayment($p_cc_num, $p_expiry_month, $p_expiry_year, $p_deferment, $p_save_cc_info = 'N',$p_pin_number, $v_net_total,$p_cc_type) {

    $v_cc_num = str_replace(' ', '', $p_cc_num) ;
    $v_cc_num = str_replace('-', '', $v_cc_num) ;
    $v_expiry_month = $p_expiry_month;
    $v_expiry_year = $p_expiry_year;

    //use order total as payment amount
    // I intended to remove the next procedure becasue I already called it before in checkourpayment->addCreditCardPayment
    $this->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_status);

    $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
    $v_net_total   = round(($v_order_total - $v_gc_total), 2) ;

    $v_isp_dummy_pmt_status = $this->findISPDummyPayment();

    if($v_order_total == 0 && $v_isp_dummy_pmt_status == 'NOT PAID') {
      $v_net_total = 1;
    }

    if($_SESSION['site'] == 'CA' && $_SESSION['smethod'] == 'I') {
      $v_net_total = 1;
    }
    
    if ($v_net_total == 0) 
    	$v_net_total = 1; // probably an instore pickup.
    
    if(!empty($p_expiry_month) && !empty($p_expiry_year)) { //regular credit card

      // Expiry cleanup/check
      $va_month_list = array('00','01','02','03','04','05','06','07','08','09','10','11','12');

      //populate year lists via loops, so that a hard-coded array doesn't have to be changed all the time
      $va_year_list = array();

      //anything expiring before this year is invalid
      //two-digit versions of year first
      $v_current_year = date('y');
      $v_current_year = intval($v_current_year);
      for($i = $v_current_year; $i < $v_current_year + 15; $i++) {
        $va_year_list[$i] = strval($i);
        if(strlen($va_year_list[$i]) == 1) {
          $va_year_list[$i] = '0' . $va_year_list[$i];
        }
      }

      //now, four-digit versions
      $v_current_year = date('Y');
      $v_current_year = intval($v_current_year);
      for($i = $v_current_year; $i < $v_current_year + 15; $i++) {
        $va_year_list[$i] = substr(strval($i), 2, 2);
        if(strlen($va_year_list[$i]) == 1) {
          $va_year_list[$i] = '0' . $va_year_list[$i];
        }
      }

      //check expiration date
      if($va_month_list[(int) $p_expiry_month] == '00' || !array_key_exists(((int) $p_expiry_month), $va_month_list)  || !array_key_exists(((int) $p_expiry_year), $va_year_list) ) {
        //clear fields and hopefully we'll get an error?
        $v_expiry_month = '';
        $v_expiry_year = '';
        $v_expiry = '';
        return 'Please enter a valid expiration date.';
      } else {
      	$fulldate = (int)$va_year_list[(int) $v_expiry_year].$va_month_list[(int) $v_expiry_month];
      	$currentdate = (int)date('y').date('m');
      	if($fulldate<$currentdate){
      		return 'Please enter a valid expiration date.';
      	}
      	 $v_expiry = $va_month_list[(int) $v_expiry_month] . '-' . $va_year_list[(int) $v_expiry_year] ;
      }
    
    } else if(!empty($p_deferment)) { //Golfsmith credit card
      $v_is_golfsmith_cc = TRUE;
      $v_expiry = $p_deferment;
    } else {
      return 'Please enter a valid expiration date or deferment option.';
    }
	
    if(!empty($p_cc_num)) {

      $i_payment = new CreditCardPayment();

      $v_status  = $i_payment->add_payment($v_net_total, $p_cc_num, $v_expiry, $v_is_golfsmith_cc, $p_save_cc_info,$p_pin_number,$p_cc_type) ;

      if(!empty($v_status)) {
        //test on result so that marketing can have a more specific message
        if(strtolower($v_status) == 'invalid credit card') {
          $v_return_status = 'Please check your credit card number.';
        } else if(empty($v_return_status)) {
          $v_return_status = $v_status . ": Please verify your payment information and try again." ;
        }
      }
    } else {
      $v_return_status = 'Oops! Some of your credit card information is either missing or invalid. Please review your Card Type, Card Number, Expiration Date, and Security Code.';
    }

    return $v_return_status;

  }

  public function addWireTransferPayment() {

    //use order total as payment amount
    $this->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_status);

    $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
    $v_net_total   = round(($v_order_total - $v_gc_total), 2) ;

    $i_payment = new Payment();

    $v_status = $i_payment->add_payment($v_net_total, '', '', '', '');

    return $v_status;

  }

  //checks whether order has any ISP/STS items
  public function isISP() {

    global $mssql_db;

    $v_isp = 'N';

    $v_stmt = mssql_init("gsi_is_isp");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_isp", $v_isp, 'varchar', 1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_is_isp", "called from isISP() in Order.php");
    }

    if(!empty($v_return_status)) {
      $v_isp = 'N';
    }

    mssql_free_statement($v_stmt);

    if($v_isp == 'Y') {
      $v_isp = TRUE;
    } else {
      $v_isp = FALSE;
    }

    return $v_isp;

  }

  //checks whether order has any ISP/STS items
  public function isSTS() {

    global $mssql_db;

    $v_sts = 'N';

    $v_stmt = mssql_init("gsi_is_sts");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_sts", $v_sts, 'varchar', 1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_is_sts", "called from isSTS() in Order.php");
    }

    if(!empty($v_return_status)) {
      $v_sts = 'N';
    }

    mssql_free_statement($v_stmt);

    if($v_sts == 'Y') {
      $v_sts = TRUE;
    } else {
      $v_sts = FALSE;
    }

    return $v_sts;

  }

  //checks whether order has any ISP or STS items
  public function isISPSTS() {

    global $mssql_db;

    $v_sts = 'N';

    $v_stmt = mssql_init("gsi_is_isp_sts");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_isp_sts", $v_isp_sts, 'varchar', 1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_is_isp_sts", "called from isISPSTS() in Order.php");
    }

    if(!empty($v_return_status)) {
      $v_isp_sts = 'N';
    }

    mssql_free_statement($v_stmt);

    if($v_isp_sts == 'Y') {
      $v_isp_sts = TRUE;
    } else {
      $v_isp_sts = FALSE;
    }

    return $v_isp_sts;
  } 

  //checks whether order has any ISP or STS items
  public function isISPOnly() {

    global $mssql_db;

    $v_sts = 'N';

    $v_stmt = mssql_init("gsi_is_isp_only");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_isp_only", $v_isp_only, 'varchar', 1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_is_isp_only", "called from isISPOnly() in Order.php");
    }

    if(!empty($v_return_status)) {
      $v_isp_only = 'N';
    }

    mssql_free_statement($v_stmt);

    if($v_isp_only == 'Y') {
      $v_isp_only = TRUE;
    } else {
      $v_isp_only = FALSE;
    }

    return $v_isp_only;
  }

  public function getPaymentInfo(&$p_card_type, &$p_cc_num, &$p_expiry, &$p_amt_paid) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_payments_retrieve_pmt_info");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50, false);
    gsi_mssql_bind($v_stmt, "@p_card_type", $p_card_type, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_card_number", $p_cc_num, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_expiry", $p_expiry, 'varchar', 20, true);
    gsi_mssql_bind($v_stmt, "@p_pmt_amount", $p_amt_paid, 'money', -1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_retrieve_pmt_info", "called from getPaymentInfo() in Order.php");
    }

    mssql_free_statement($v_stmt);

    return $v_return_status;

  }

  public function getPreviousGiftcards(&$pa_giftcards) {

    global $mssql_db;

    $pa_giftcards = array();

    $v_stmt = mssql_init("gsi_cmn_payments_get_previous_giftcards");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_get_previous_giftcards", "called from getPreviousGiftcards() in Order.php");
    } else {
      while($v_row = mssql_fetch_array($v_result)) {
        $pa_giftcards[] = array('gc_id' => $v_row['gift_certificate_id'], 'applied_balance' => $v_row['applied_balance']);
      }
    }

    return $v_return_status;

  }

  public function updateISPWarehouse($p_line_number, $p_store, $p_line_type) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_order_ext_update_isp_warehouse");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 20);
    gsi_mssql_bind($v_stmt, "@p_line_number", $p_line_number, 'int');
    gsi_mssql_bind($v_stmt, "@p_warehouse_id", $p_store, 'int');
    gsi_mssql_bind($v_stmt, "@p_isp_line_type", $p_line_type, 'varchar', 150);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_ext_update_isp_warehouse", "called from updateISPWarehouse() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function emptyCart() {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_order_empty_cart");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_empty_cart", "called from emptyCart() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function updatePromoCode() {

    global $mssql_db;

    $_SESSION['s_do_reprice'] = 'Y';

    //check for a source code
    $v_source_code = strtoupper($_SESSION['s_source_code']);

    //if there's a source code, validate it and find out whether it's a loyalty code
    if(!empty($v_source_code)) {

      $v_scode_error = $this->checkSourceCode($v_source_code, $v_loyalty_number);

      //if there was an error, empty out the source code
      if(!empty($v_scode_error)) {
        $_SESSION['s_source_code'] = NULL;
        $v_source_code = NULL;
        return FALSE;
      }
    }

    if(!empty($v_source_code)) {
      $this->updateHeaderSourceCode($v_gift_order, $v_source_code);
    }

//    if (empty($_SESSION['s_ship_source_code']))  {
      $v_ship_source_code = $this->getValidSourceCode($v_source_code);
      if (!empty($v_ship_source_code)){
        $_SESSION['s_ship_source_code'] = $v_ship_source_code;
        $v_stmt = mssql_init("gsi_cmn_order_ext_update_order_attributes");
        gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 50);
        gsi_mssql_bind($v_stmt, "@p_ship_source_code", $v_ship_source_code, 'varchar', 50);

        $v_result = mssql_execute($v_stmt);
        if (!$v_result) {
          display_mssql_error("gsi_cmn_order_ext_update_order_attributes", "called from sc_view");
        }

        mssql_free_statement($v_stmt);
        mssql_free_result($v_result);
      }
//    }

    //reprice order, which will only happen if session var is 'Y'
    //$this->repriceOrder();
    //$_SESSION['s_do_reprice'] = 'N';

    return TRUE;

  }

  public function updateFreeShipping() {

    global $mssql_db;

    $_SESSION['s_do_reprice'] = 'Y';

 
    if (empty($_SESSION['s_ship_source_code']))  {
      $v_ship_source_code = $this->getValidSourceCode($v_source_code);
      if (!empty($v_ship_source_code)){
        $_SESSION['s_ship_source_code'] = $v_ship_source_code;
        $v_stmt = mssql_init("gsi_cmn_order_ext_update_order_attributes");
        gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 50);
        gsi_mssql_bind($v_stmt, "@p_ship_source_code", $v_ship_source_code, 'varchar', 50);

        $v_result = mssql_execute($v_stmt);
        if (!$v_result) {
          display_mssql_error("gsi_cmn_order_ext_update_order_attributes", "called from sc_view");
        }

        mssql_free_statement($v_stmt);
        mssql_free_result($v_result);
      }
    }

    return TRUE;

  }
  
  private function userChoosesShipping($p_ship_method) {

    switch (strToUpper($p_ship_method)) {
      case 'FEDEX-GND-BUSINESS':
      case 'GROUND-BUSINESS':
      case 'FEDEX-GND-RESIDENTIAL':
      case 'GROUND':
      case 'FEDEX-2DAY-ACC':
      case 'UPS-2DAY-ACC':      
      case 'FEDEX-2DAY':
      case '2DAY':
      case 'FEDEX-STD-OVERNIGHT':
      case 'FEDEX-NEXTDAY':
      case 'NEXTDAY':
      case 'UPS-GROUND-ACC':
      case 'UPS-2DAY-AIR-ACC':
      case 'UPS-NEXT-DAY-AIR-ACC':
      case 'UPS-SATURDAY-AIR-ACC':
      case 'UPS-CANADA-PP':
      case 'UPS-CAN-EXPEDITED':
      case 'UPS-CAN-EXPRESS':
      case 'UPS-CAN-STD-ZN1':
      case 'UPS-CAN-STD-ZN2':
      case 'UPS-CAN-STD-ZN3':
      case 'UPS-CAN-STD-ZN4':
      case 'UPS-CAN-STD-ZN5':
      case 'UPS-CAN-AIR-ZN1':
      case 'UPS-CAN-AIR-ZN2':
      case 'UPS-CAN-AIR-ZN3':
      case 'UPS-CAN-AIR-ZN4':
      case 'UPS-CAN-AIR-ZN5':
      case 'PPOST CAN TO CAN':
      case 'WILL-CALL':
        $v_result = true;
        break;
      default:
        $v_result = false;
        break;
    }

    return $v_result;

  }

  private function translateShipMethod($p_ship_method) {

    switch(strToUpper($p_ship_method)) {
      case 'FEDEX-GND-BUSINESS':
      case 'FEDEX-GND-RESIDENTIAL':
      case 'GROUND-BUSINESS':
      case 'GROUND':
        $v_result = 'Ground';
        break;
      case 'FEDEX-2DAY-ACC':
      case 'FEDEX-2DAY':
      case '2DAY':
        $v_result = '2-Day';
        break;
      case 'FEDEX-STD-OVERNIGHT':
      case 'FEDEX-NEXTDAY':
      case 'NEXTDAY':
        $v_result = 'Next Day';
        break;
        case 'UPS-GROUND-ACC':
        $v_result = 'Ground';
        break;
      case 'UPS-2DAY-AIR-ACC':
        $v_result = '2-Day';
        break;
      case 'UPS-NEXT-DAY-AIR-ACC':
        $v_result = 'Next Day';
        break;
      case 'UPS-SATURDAY-AIR-ACC':
        $v_result = 'SATURDAY';
        break;        
      case 'UPS-CANADA-PP':
        $v_result = 'Canadian Parcel Post';
        break;
      case 'UPS-CAN-EXPEDITED':
        $v_result = 'Expedited';
        break;
      case 'UPS-CAN-EXPRESS':
        $v_result = 'Express';
        break;
      case 'UPS-CAN-STD-ZN1':
        $v_result = 'Ground';
        break;
      case 'UPS-CAN-STD-ZN2':
        $v_result = 'Ground';
        break;
      case 'UPS-CAN-STD-ZN3':
        $v_result = 'Ground';
        break;
      case 'UPS-CAN-STD-ZN4':
        $v_result = 'Ground';
        break;
      case 'UPS-CAN-STD-ZN5':
        $v_result = 'Ground';
        break;
      case 'UPS-CAN-AIR-ZN1':
        $v_result = 'Next Day';
        break;
      case 'UPS-CAN-AIR-ZN2':
        $v_result = 'Next Day';
        break;
      case 'UPS-CAN-AIR-ZN3':
        $v_result = 'Next Day';
        break;
      case 'UPS-CAN-AIR-ZN4':
        $v_result = 'Next Day';
        break;
      case 'UPS-CAN-AIR-ZN5':
        $v_result = 'Next Day';
        break;
      case 'PPOST CAN TO CAN':
        $v_result = 'Parcel Post';
        break;
      case 'WILL-CALL':
        $v_result = 'Will-Call';
        break;
      default:
        $v_result = $p_ship_method;
        break;
    }

    return $v_result;

  }

  private function getThreeShippingCharges($p_ship_method_code, &$p_shipping_ground, &$p_shipping_twoday, &$p_shipping_nextday, &$p_shipping_ground_method, &$p_shipping_twoday_method, &$p_shipping_nextday_method) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("direct.dbo.gsi_nbt_ship_get_three_shipping_charges");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50, false);
    gsi_mssql_bind($v_stmt, "@p_ship_method_code", $p_ship_method_code, 'varchar', 30, false);
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30, false);
    gsi_mssql_bind($v_stmt, "@p_shipping_ground", $p_shipping_ground, 'money', -1, true);
    gsi_mssql_bind($v_stmt, "@p_shipping_twoday", $p_shipping_twoday, 'money', -1, true);
    gsi_mssql_bind($v_stmt, "@p_shipping_nextday", $p_shipping_nextday, 'money', -1, true);
    gsi_mssql_bind($v_stmt, "@p_shipping_ground_method", $p_shipping_ground_method, 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_shipping_twoday_method", $p_shipping_twoday_method, 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_shipping_nextday_method", $p_shipping_nextday_method, 'varchar', 50, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_nbt_ship_get_three_shipping_charges", "called from getThreeShippingCharges() in Order.php");
    }

    mssql_free_statement($v_stmt);

  }

  private function calculateISPTax($p_postal_code) {
    global $mssql_db;

    $this->getCityState($p_postal_code, $v_city, $v_state);

    if ($_SESSION['site'] == 'CA' || $v_state == 'CN') {
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
      display_mssql_error("gsi_cmn_isp_calc_isp_totals", "called from calculateISPTaxZip() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function isISPSTSOnly() {

    global $mssql_db;

    $v_all_isp_sts = 'N';

    $v_stmt = mssql_init("gsi_is_isp_sts_only");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_isp_sts_only", $v_all_isp_sts, 'varchar', 1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_is_isp_sts_only", "called from isISPSTSOnly() in Order.php");
    }

    if(!empty($v_return_status)) {
      $v_all_isp_sts = 'N';
    }

    mssql_free_statement($v_stmt);

    if($v_all_isp_sts == 'Y') {
      $v_all_isp_sts = TRUE;
    } else {
      $v_all_isp_sts = FALSE;
    }

    return $v_all_isp_sts;

  }

  private function getOrderInfo(&$p_ship_method, &$p_ship_method_dsp, &$p_gift_order) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("gsi_cmn_order_retrieve_order_info");

    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_ship_method", $p_ship_method, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_ship_method_dsp", $p_ship_method_dsp, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_gift_order", $p_gift_order, 'varchar', 150, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_retrieve_order_info", "called from getOrderInfo() in Order.php");
    }

    mssql_free_statement($v_stmt);

  }

  /**********************************************************************************
   * This function checks the validity of a source code.
   * Check 1: check that code exists in system and is generally applicable
   * Check 2: if code is loyalty code, make sure that the user is a loyalty member
   * Returns an error code of 'invalid', 'loyalty', or empty (meaning no errors)
   *********************************************************************************/
  private function checkSourceCode($p_source_code, &$p_loyalty_num) {

    global $mssql_db;

    $v_error_code = '';

    if(empty($v_error_code)) {

      $v_stmt = mssql_init("r12pricing.dbo.gsi_validate_onetime_coupon");

      gsi_mssql_bind($v_stmt, "@p_coupon_code", $p_source_code, 'varchar', 20);
      gsi_mssql_bind($v_stmt, "@p_source_code", $v_new_source_code, 'varchar', 20, true);
      gsi_mssql_bind($v_stmt, "@p_loyalty_number", $p_loyalty_num, 'bigint', 50, true);
      gsi_mssql_bind($v_stmt, "@p_return_status", $v_ret_status, 'varchar', 500, true);

      $result = mssql_execute($v_stmt);
      if (!$result){
        display_mssql_error("gsi_validate_onetime_coupon", "called from checkSourceCode() in Order.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($result);

      if($v_ret_status == 'VALID') {
        $v_use_source_code = $v_new_source_code;
      } else {
        $v_use_source_code = $p_source_code;
      }

      $sql = mssql_init("direct.dbo.gsi_cmn_order_check_source_code");
      gsi_mssql_bind ($sql, "@p_source_code", $v_use_source_code, 'VARCHAR', 30);
      gsi_mssql_bind ($sql, "@p_is_valid", $v_is_valid, 'VARCHAR',  1, true);
      gsi_mssql_bind ($sql, "@p_is_loyalty", $v_is_loyalty, 'VARCHAR', 1,true);

      $result = mssql_execute($sql);
      if (!$result){
        display_mssql_error("gsi_cmn_order_check_source_code", "called from checkSourceCode() in Order.php");
      }

      mssql_free_statement($sql);
      mssql_free_result($result);

      //interpret results
      //first, check for valid code
      if($v_is_valid != 'Y') {  //how do we store an error message to show later here?
        $v_error_code = 'invalid';
      } else if ($v_is_loyalty == 'Y') {  //if code is valid and is associated with player loyalty, do more checking
        if(empty($_SESSION['s_loyalty_number'])) { //not a loyalty member, so disallow use of loyalty code
          $v_error_code = 'loyalty';
        }
      }
    }

    return $v_error_code;
  }
  
  // KBOX#34605 added the function updateHeaderGiftOrder
  public function updateHeaderGiftOrder($p_gift_order) {
	global $mssql_db;
    $v_stmt = mssql_init("gsi_cmn_order_update_header_attributes");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_attribute2", $p_gift_order, 'varchar', 150);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_update_header_attributes", "called from updateHeaderGiftOrder() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);
  }
  
  private function updateHeaderSourceCode($p_gift_order, $p_source_code) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_order_update_header_attributes");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_attribute2", $p_gift_order, 'varchar', 150);
    gsi_mssql_bind($v_stmt, "@p_source_code", $p_source_code, 'varchar', 150);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_update_header_attributes", "called from updateHeaderSourceCode() in Order.php");
    }
    
    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  private function getValidSourceCode($p_source_code) {

    if (!empty($p_source_code)) {

      $v_stmt = mssql_init("r12pricing.dbo.gsi_validate_onetime_coupon");

      gsi_mssql_bind($v_stmt, "@p_coupon_code", $p_source_code, 'varchar', 20);
      gsi_mssql_bind($v_stmt, "@p_source_code", $v_new_source_code, 'varchar', 20, true);
      gsi_mssql_bind($v_stmt, "@p_loyalty_number", $p_loyalty_num, 'bigint', 50, true);

      gsi_mssql_bind($v_stmt, "@p_return_status", $v_ret_status, 'varchar', 500, true);

      $result = mssql_execute($v_stmt);
      if (!$result){
        display_mssql_error("gsi_validate_onetime_coupon", "called from getValidSourceCode() in Order.php");
      }
      mssql_free_statement($v_stmt);
      mssql_free_result($result);
    }

    // $v_new_source_code will have the source code according to what the user has entered if $p_source_code is valid
    // In this case, user source code is checked below if it is also shipping code, else default shipping code is returned.

    //  in case user has not entered anything i.e $p_source_code is null, then also we pass $v_new_source_code which is null to see
    //  if default shipping code is available
    if ( !empty($v_new_source_code) || (empty($p_source_code))) {

      $v_stmt = mssql_init("gsi_get_valid_ship_sourcecode");

      gsi_mssql_bind($v_stmt, "@p_cust_ship_sourcecode", $v_new_source_code, 'varchar', 50,null);
      gsi_mssql_bind($v_stmt, "@p_tab", $_SESSION['s_screen_name'], 'varchar', 3);
      gsi_mssql_bind($v_stmt, "@p_site", $_SESSION['site'], 'varchar', 2);

      gsi_mssql_bind($v_stmt, "@p_valid_sourcecode", $p_valid_sourcecode, 'varchar', 50, true);

      $result = mssql_execute($v_stmt);

      if(!$result) {
        display_mssql_error("direct.dbo.gsi_get_valid_ship_sourcecode", "called from getValidSourceCode() in Order.php");
      }

      mssql_free_statement($v_stmt);
    }

    return $p_valid_sourcecode;

  }

  private function freeShipping() {

    global $web_db;

    $v_source_code = strtoupper($_SESSION['s_source_code']);
    $v_screen_name = $_SESSION['s_screen_name'];

    $v_show_rule = 'SCREEN NAME ' . strToUpper(str_replace("'", "''", $v_screen_name));

    $v_sql = "select ship_source_code
              from gsi_web_ship_source_codes
              where NOW() between start_date and end_date
                and (show_rule = '$v_show_rule' OR show_rule IS NULL)
                and source_code = '$v_source_code'
              order by start_date";

    $v_result = mysqli_query($web_db, $v_sql);

    while($va_row = mysqli_fetch_assoc($v_result)) {
      $_SESSION['s_ship_source_code'] = $va_row['ship_source_code'];
      $v_promo_source_code = $v_source_code;
    }

    if(empty($this->v_order_number)) {
      return;
    }

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_order_ext_update_order_attributes");

    gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 25);
    gsi_mssql_bind ($v_stmt, "@p_ship_source_code", $v_promo_source_code, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_ext_update_order_attributes", "called from freeShipping() in Order.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }
  
  // Clear out the master pass info
  public function clearMPSessionInfo()
  {
      // Make sure we have the db connections
      require_once("gsi_db.inc");
      
      // If there was a MasterPass promo then remove it
      if($_SESSION["mpPromoCode"])
      {
          $this->updateHeaderSourceCode("", "DEFWEB");
          unset($_SESSION["s_source_code"]);
      }
      
      // Remove MasterPass session variables
      unset($_SESSION["mpRequestToken"]);
      unset($_SESSION["mpPairingToken"]);
      unset($_SESSION["mpTransactionId"]);
      unset($_SESSION["mpTransaction"]);
      unset($_SESSION["mpPromoCode"]);
      
      // Remove the M from attribute9 on the order
      $attr9 = null;
      $v_stmt = mssql_init("gsi_update_payment_masterpass");
      
      gsi_mssql_bind ($v_stmt, "@p_order_number", $this->v_order_number, "varchar", 50, false);
      gsi_mssql_bind ($v_stmt, "@p_attribute9", $attr9, "varchar", 150, false);
      
      $v_result = mssql_execute($v_stmt);
      
      if(!$v_result) {
          display_mssql_error("gsi_update_payment_masterpass", "update to attribute9, called by clearMPSessionInfo() in Order.php");
      }
      
      mssql_free_statement($v_stmt);
  }

}
?>
