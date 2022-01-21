<?

include_once('Payment.php');

class GiftCardPayment extends Payment {

  function __construct() {
    parent::__construct();
  }

  public function add_payment($p_amount, $p_gc_num, $p_gc_pin, $p_gc_balance = '') {

    if(!empty($p_gc_num)) {

      $v_payment_method = 'GFTCRT';

      $v_gc_note = (!empty($p_gc_balance) ? $p_gc_balance : '0') . '|' . $p_gc_num;
      
      $v_return_status = parent::add_payment($p_amount, $v_payment_method, '', $p_gc_num, $p_gc_pin, '', '', '', $v_gc_note);

    } else {
      $v_return_status = 'Invalid Gift Card Number';
    }

    return $v_return_status;

  }

  //get all gift card payments and given order total, adjust as necessary
  public function adjustGiftCardPayments() {

    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_adjust_gc_payments");

    gsi_mssql_bind($this->v_stmt, "@p_profile_scope", $this->v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_adjust_gc_payments", "called from adjustGiftCardPayments() in GiftCardPayment.php");
    }

    mssql_free_statement($this->v_stmt);

  }

  public function removeGCPayment($p_gc_num) {
    global $mssql_db ;

    $this->v_stmt = mssql_init("gsi_cmn_payments_remove_giftcert_pmt");

    gsi_mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_giftcert_id", $p_gc_num, 'varchar', 20);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_remove_giftcert_pmt", "called from remove_gift_card_payment() in payment_processor");
    }

    mssql_free_statement($this->v_stmt);

  }

  //new function for getting GC balance - calls .NET web service
  public function getGCBalance($p_gc_num, $p_pin, $p_orig_sys_ref, &$p_balance, $checkInquiry = False ) {
      
      if(!empty($p_orig_sys_ref)) {
          $v_order_number = str_replace('G', '', $p_orig_sys_ref);
      } else {
          $v_order_number = '12345';
      }
      $v_warehouse_id = 25;
      $v_salesrep_id = 5527;
      
      $v_web_service = WEBSERVICES_URL;
      
      $a_params = array (
          "orderNumber"=> $_SESSION["s_order_number"],
          "cardType"=> "VALUE_LINK",
          "cardNumber"=> $p_gc_num,
          "pinNumber"=> $p_pin,
          "clientIpAddress"=> $_SESSION["s_remote_address"]
      ); // I need to send this to the CC service with GE card
      
      if ($checkInquiry){
          $a_params["orderNumber"]= "checkInquiry";
      }
 
      try{
          // start to soap call to verfy the CC number with CSC
          $v_sclient = new SoapClient($v_web_service, array( 'trace' => true));
          $credentials = array("UsernameToken" => array("Username" => WEBSERVICE_USERNAME, "Password" => WEBSERVICE_PASSWD ));
          $security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
          //create header object
          $header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true);
          $v_sclient->__setSoapHeaders( $header );
          $result = $v_sclient->DoGolfsmithWebVerification($a_params);
      } catch (SoapFault $f) {
          $v_return_status = "Gift card system is not available\n";
          $this->log_trans_error("failed verfication  :" . $f->getMessage());
          return $v_return_status;            
      }
      
        if ($result->approvalStatus == "APPROVED"){
            $p_balance = $result->currentBalance;
            retrun ;
        }else{
            return 'INVALID';
        }
	 		

  }

}

?>
