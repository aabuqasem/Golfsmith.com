<?php

class AbandonedCart {

  private $v_email_address;
  private $v_order_number; 
  private $v_customer_number;

  public function __construct($p_user_name, $p_order_number, $p_customer_number) {

    $this->v_email_address = $p_user_name;
    $this->v_order_number = $p_order_number;
    $this->v_customer_number = $p_customer_number;

  }     
	
  public function setCookie() {
    if(!empty($this->v_email_address)) {
    	
    	if ( $_SERVER['SERVER_PORT'] == 8443 || $_SERVER['SERVER_PORT'] == 443) {
			$secure_connection = true;
		} else {
			$secure_connection = false;
		}
	  
      setcookie("email", $this->v_email_address, time()+31536000, "/",".golfsmith.com",$secure_connection,1); // expires in one year
    }
  }
	
  public function updateEmailAddress() {

    if(!empty($this->v_email_address) && !empty($this->v_order_number)) {
      global $mssql_db;

      $v_stmt = mssql_init("gsi_cmn_order_update_email");

      gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_email", $this->v_email_address, 'varchar', 240);
			
      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_update_email", "called from updateEmailAddress() in AbandonedCart.php");
      }
			
      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);

    }
  }
  
  public function findAbandonedCart($p_email, $p_time_offset) {

    global $mssql_db;
    
    $v_order_info = array();
	
    $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_ext_find_abandoned_cart");
	  
    gsi_mssql_bind($v_stmt, "@p_email_address", $p_email, 'varchar',  150);
    gsi_mssql_bind($v_stmt, "@p_time_offset", $p_time_offset, 'int');
    gsi_mssql_bind($v_stmt, "@p_order_number", $v_order_number, 'varchar', 30, true);
    gsi_mssql_bind($v_stmt, "@p_order_type", $v_order_type, 'varchar', 30, true);
	
    $v_result = mssql_execute($v_stmt);

    if (!$v_result) {
      display_mssql_error("gsi_cmn_order_ext_find_abandoned_cart", "called from findAbandonedCart() in AbandonedCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);
	
    $v_order_info['order_number'] = $v_order_number;
    $v_order_info['order_type'] = $v_order_type;
    
    return $v_order_info;

  }
		
}

?>
