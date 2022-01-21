<?php

  /**
   * vim: set filetype=php:
   * $Id: employee_credit.inc,v 1.6 2005/03/01 14:11:33 websrc Exp $
   * Copyright Golfsmith International 2004
   */

class EmployeeCredit {
  public $processed = false;
  public $complete = false;
  public $validation_message_list = array();

  public $order_number;
  public $sales_channel;
  public $store_number;
  public $salesrep_number;
  public $salesrep_name;

  public function __construct() {
    $this -> validate();
    $this -> store();
  }

  public function get_store_number() {
    if ( isset( $_SESSION['s_store_number'])) return $_SESSION['s_store_number'];

    $store_number = '';
    $v_http_via = $_SERVER['HTTP_VIA'];
    $v_hostname = '';

    if (strpos($v_http_via , 'gsicorp.com')){
      $v_http_via = "http://" . GSI_PROXY_IP_ADDRESS;
    }

    if (isset( $_SERVER['HTTP_TRUE_CLIENT_IP']))
    {
		$octet_list = explode( '.', $_SERVER['HTTP_TRUE_CLIENT_IP'] );
	    $v_hostname = gethostbyaddr($_SERVER['HTTP_TRUE_CLIENT_IP']);
    	
    } else {
	    if (isset( $_SERVER['HTTP_X_FORWARDED_FOR']) && ( strPos( $v_http_via, GSI_PROXY_IP_ADDRESS))) {
	      $octet_list = explode( '.', $_SERVER['HTTP_X_FORWARDED_FOR'] );
	      $v_hostname = gethostbyaddr($_SERVER['HTTP_X_FORWARDED_FOR']);
	    } else {
	      $octet_list = explode( '.', $_SERVER['REMOTE_ADDR']);
	      $v_hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	    }
    }

    if (sizeof( $octet_list ) > 3) {

      if ($octet_list[0] == 192) // internal address
      {
        // This accounts for store 1.
        if ($octet_list[2] < 100) {
          //check hostname
          $v_host_data = explode('.', $v_hostname);
          if($v_host_data[1] == 'gsicorp' && $v_host_data[2] == 'com') {
            $v_type = substr($v_host_data[0], 0, 3);
            $v_num = substr($v_host_data[0], 3, 3);
            if($v_type == 'reg' || $v_type == 'kio' || $v_type == 'off') {
              if(is_numeric($v_num)) {
                $store_number = $v_num;
              }
            }
          }

       } else if ($octet_list[2] == 88) {
          $store_number='888';
       } else {
          // We're not in store 001, so we're not checking DNS.
          $store_number = str_pad( (int) $octet_list[2] - 100, 3, '0', STR_PAD_LEFT);
       }

      }
    }

    $_SESSION['s_store_number'] = $store_number;
    return $store_number;
  }

  public function validate() {

    $validation_message_list =& $this -> validation_message_list;

    $this -> order_number = $_SESSION['s_order_number'];
    $this -> sales_channel = 'KIOSK';

    if(!empty($_POST['store_number'])) {
      $_SESSION['s_store_number'] = strip_tags($_POST['store_number']);
      $this -> store_number = 'S' . strip_tags($_POST['store_number']);
    } else {
      $this -> store_number = 'S' . $this->get_store_number(); // need to determine by IP address.
    }
    $this -> salesrep_number = preg_replace( '/[^0-9]/','', strip_tags($_REQUEST['salesrep_number']));
    $this -> salesrep_name = '';

    if (strlen( $this -> salesrep_number) > 5)
      $validation_message_list[] = 'Representative number must be 5 or fewer numeric digits.';

    if (strlen( $this -> salesrep_number) < 3)
      $validation_message_list[] = 'Representative number must be at least 3 numeric digits.';
  }

  public function store() {
    global $mssql_db;

    $validation_message_list =& $this -> validation_message_list;

    //submit changes to DB
    $v_stmt = mssql_init("gsi_cmn_order_web_order_from_store");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_sales_channel", $this->sales_channel, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_store_number", $this->store_number, 'varchar', 25);
    gsi_mssql_bind($v_stmt, "@p_salesrep_number", $this->salesrep_number, 'numeric');
    gsi_mssql_bind($v_stmt, "@p_salesrep_name", $this->salesrep_name, 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_return_message", $v_return_message, 'varchar', 200, true);

    $this->complete = mssql_execute($v_stmt);

    if(!$this->complete) {
      ob_start();
      $err = display_mssql_error("gsi_cmn_order_web_order_from_store", "called from employee_credit.inc");
      ob_end_clean();
    }

    if (!empty( $err)) {
      $validation_message_list[] = 'MSSQL error encountered. The error was:';
      $validation_message_list[] = $err;
    }

    if (strpos( $this -> salesrep_name, ',')) {
      list ( $lname, $fname) = explode( ',', $this -> salesrep_name);
      $this -> salesrep_name = trim( $fname) . ' ' . trim( $lname);
    }

    if ($v_return_status == 0) {
      $this -> complete = true;
      $_SESSION['form_time_list']['employee_credit'] = strip_tags($_REQUEST['employee_credit']);
    } else {
      $validation_message_list[] = $v_return_message;
    }
  }

}

?>
