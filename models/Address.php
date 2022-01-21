<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here

class Address {

  private $v_customer_id;
  private $v_address_id;
  private $v_contact_id;
  private $v_phone_id;

  //no constructor needed -- we're using the CheckoutPage constructor
  public function __construct($p_customer_id, $p_address_id, $p_contact_id, $p_phone_id) {
    $this->setAll($p_customer_id, $p_address_id, $p_contact_id, $p_phone_id);
  } 

  public function setCustomerId($p_customer_id) {
    $this->v_customer_id = $p_customer_id;
  }

  public function setAddressId($p_address_id) {
    $this->v_address_id = $p_address_id;
  }

  public function setContactId($p_contact_id) {
    $this->v_contact_id = $p_contact_id;
  }

  public function setPhoneId($p_phone_id) {
    $this->v_phone_id = $p_phone_id;
  }

  public function setAll($p_customer_id, $p_address_id, $p_contact_id, $p_phone_id) {
    $this->v_customer_id = $p_customer_id;
    $this->v_address_id = $p_address_id;
    $this->v_contact_id = $p_contact_id;
    $this->v_phone_id = $p_phone_id;
  }

  public function getAddressFields(&$p_is_international, &$pa_address) {

    $v_province = '';
    $p_is_international = FALSE;

    if(!empty($this->v_address_id)) {
      $this->retrieveAddress($pa_address);

      if(empty($pa_address['area_code'])) {
        $pa_address['area_code'] = $_SESSION['area_code'];
      }

      if($_SESSION['site'] == 'CA') {
        $pa_address['state'] = $pa_address['province'];
      }

      if (!empty($pa_address['country'])) {
        if($pa_address['country'] != 'US' && $pa_address['country'] != 'CA') {
          $p_is_international = TRUE;
          //need to use address3 to populate city/state/postal code fields
          $va_line3 = explode(',', $pa_address['line3']);
          $pa_address['city'] = trim($va_line3[0]);
          $pa_address['postal_code'] = trim($va_line3[1]);
          $pa_address['state'] = trim($va_line3[2]);
        }
      }
      
      if(!empty($pa_address['area_code']) && !empty($pa_address['phone'])) {
        $pa_address['phone'] = $pa_address['area_code'] . '-' . substr($pa_address['phone'], 0, 3) . '-' . substr($pa_address['phone'], 3);
      } else if(empty($pa_address['phone']) && !empty($_SESSION['phone'])) {
      	if(!empty($pa_address['area_code']))
      	{
      		$pa_address['phone'] = $pa_address['area_code'] . '-' . substr($_SESSION['phone'], 0, 3) . '-' . substr($_SESSION['phone'], 3);
      	} else {
	        $pa_address['phone'] = substr($_SESSION['phone'], 0, 3) . '-' . substr($_SESSION['phone'], 3, 3) . '-' . substr($_SESSION['phone'], 6);
      	}
      }

    }
  }

  //address type will be 'BILL_TO' or 'SHIP_TO'
  public function getAddressBookAddresses($p_address_type, &$pa_addresses) {

    global $mssql_db;

    $v_stmt = mssql_init("customer..gsi_cust_get_primary_info");

    gsi_mssql_bind($v_stmt, "@p_customer_id", $this->v_customer_id, 'gsi_id_type');
    gsi_mssql_bind($v_stmt, "@p_contact_id", $v_primary_contact_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_first_name", $v_primary_first_name, 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_last_name", $v_primary_last_name, 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_phone_id", $v_primary_phone_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_area_code", $v_primary_area_code, 'varchar', 10, true);
    gsi_mssql_bind($v_stmt, "@p_phone_number", $v_primary_phone, 'varchar', 100, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer..gsi_cust_get_primary_info", "called from getAddressBookAddresses() in Address.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    $v_sql = "select raa.address_id
                   , rsu.contact_id
                   , raa.address1
                   , raa.address2
                   , raa.address3
                   , raa.city
                   , raa.state
                   , raa.country
                   , raa.postal_code
                   , raa.attribute2 address_name
                   , rsu.primary_flag
                   , raa.province
              from customer..gsi_ra_addresses_all raa
                 , customer..gsi_ra_site_uses_all rsu
              where raa.customer_id   = $this->v_customer_id
                and raa.address_id    = rsu.address_id
                and rsu.site_use_code ='$p_address_type'
                and isnull(raa.attribute7, 'N') = 'N'
                and raa.status = 'A'
                and rsu.status = 'A'";

    if($_SESSION['site'] == 'CA') {
      $v_sql .= "and raa.country = 'US'
                 and raa.state = 'CN'";
    }

    $v_sql .= "order by rsu.primary_flag desc";

    $v_result = mssql_query($v_sql);

    if(!$v_result) {
      display_mssql_error($v_sql, "called from getAddressBookAddresses() in Address.php");
    }

    $pa_addresses = array();

    while($va_row = mssql_fetch_array($v_result)) {

      $va_address = array();

      $va_address['address_id'] = $va_row['address_id'];
      $va_address['contact_id'] = $va_row['contact_id'];
      $va_address['address1'] = $va_row['address1'];
      $va_address['address2'] = $va_row['address2'];
      $va_address['address3'] = $va_row['address3'];
      $va_address['city'] = $va_row['city'];
      $va_address['state'] = $va_row['state'];
      $va_address['country'] = $va_row['country'];
      $va_address['postal_code'] = $va_row['postal_code'];
      $va_address['province'] = $va_row['province'];

      if($va_row['primary_flag'] == 'Y') {
        $va_address['is_primary'] = TRUE;
      } else {
        $va_address['is_primary'] = FALSE;
      }

      $v_first_name = '';
      $v_last_name = '';

      if(!empty($va_address['contact_id'])) {
        $v_sql2 = "select first_name, last_name 
                   from customer..gsi_ra_contacts
                   where contact_id = " . $va_address['contact_id'] . "
                     and status = 'A'";

        $v_result2 = mssql_query($v_sql2);

        if(!$v_result2) {
          display_mssql_error($v_sql2, "called from getAddressBookAddresses() in Address.php");
        }

        if($va_row2 = mssql_fetch_array($v_result2)) {
          $v_first_name = $va_row2['first_name'];
          $v_last_name = $va_row2['last_name'];
        }

        mssql_free_result($v_result2);
      }

      if(empty($v_first_name)) {
        $va_address['contact_id'] = $v_primary_contact_id;
        $v_first_name = $v_primary_first_name;
        $v_last_name = $v_primary_last_name;
      }

      $va_address['first_name'] = $v_first_name;
      $va_address['last_name'] = $v_last_name;

      $v_phone_id = '';

      $v_sql2 = "select phone_id, area_code, phone_number 
                 from customer..gsi_ra_phones 
                 where address_id = " . $va_address['address_id'] . "
                   and status = 'A'";

      $v_result2 = mssql_query($v_sql2);

      if(!$v_result2) {
        display_mssql_error($v_sql2, "called from getAddressBookAddresses() in Address.php");
      }

      if($va_row2 = mssql_fetch_array($v_result2)) {
        $v_phone_id = $va_row2['phone_id'];
        $v_area_code = $va_row2['area_code'];
        $v_phone_number = $va_row2['phone_number'];
      }

      mssql_free_result($v_result2);

      if(empty($v_phone_id)) {
        $v_phone_id = $v_primary_phone_id;
        $v_area_code = $v_primary_area_code;
        $v_phone_number = $v_primary_phone;
      }

      $va_address['phone_id'] = $v_phone_id;
      $va_address['phone'] = $v_area_code . $v_phone_number;

      $pa_addresses[] = $va_address;

    }

  }

  public function setAddressAsPrimary($p_address_type) {

    global $mssql_db;

    $v_stmt = mssql_init("customer.dbo.gsi_cust_make_address_primary");

    gsi_mssql_bind($v_stmt, "@p_customer_id", $this->v_customer_id, 'int');
    gsi_mssql_bind($v_stmt, "@p_address_id", $this->v_address_id, 'int');
    gsi_mssql_bind($v_stmt, "@p_site_use_code", $p_address_type, 'varchar', 30);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_Error("customer.dbo.gsi_cust_make_address_primary", "called from setAddressAsPrimary() in Customer.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function insertAddress($p_first_name, $p_last_name, $p_address1, $p_address2, $p_city, $p_state, $p_postal_code, $p_country, $p_address_type, $p_area_code, $p_phone_number, &$p_contact_id, &$p_address_id, &$p_phone_id) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("customer.dbo.gsi_cust_insert_address");

    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_customer_id", $this->v_customer_id, 'gsi_id_type');
    gsi_mssql_bind($v_stmt, "@p_first_name", $p_first_name, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_last_name", $p_last_name, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_address1", $p_address1, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_address2", $p_address2, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_city", $p_city, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_state", $p_state, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_postal_code", $p_postal_code, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_country", $p_country, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_site_use_code", $p_address_type, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_area1", $p_area_code, 'varchar', 10);
    gsi_mssql_bind($v_stmt, "@p_phone1", $p_phone_number, 'varchar', 25);

    gsi_mssql_bind($v_stmt, "@p_address_id", $p_address_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_contact_id", $p_contact_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_phone_id1", $p_phone_id, 'gsi_id_type', -1, true);

    gsi_mssql_bind($v_stmt, "@p_bill_site_use_id", $v_bill_site_use_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_site_use_id", $v_ship_site_use_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_phone_id2", $v_phone_id2, 'gsi_id_type', -1, true);

    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer..gsi_cust_insert_address", "called from insertAddress() in Address.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    $this->v_address_id = $p_address_id;
    $this->v_contact_id = $p_contact_id;
    $this->v_phone_id = $p_phone_id;

    return $v_return_status;

  }

  public function updateAddress($p_first_name, $p_last_name, $p_address1, $p_address2, $p_city, $p_state, $p_postal_code, $p_country, $p_address_type, $p_area_code, $p_phone, $p_shiptopo, &$p_contact_id, &$p_address_id, &$p_phone_id) {

    global $mssql_db;

    $v_return_status = '';
    $v_profile_scope = C_WEB_PROFILE;

    if(empty($p_shiptopo)) {
      $v_shiptopo = 'N';
    } else {
      $v_shiptopo = $p_shiptopo;
    }

    $v_stmt = mssql_init("customer.dbo.gsi_cust_update_address");

    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_customer_id", $this->v_customer_id, 'gsi_id_type');
    gsi_mssql_bind($v_stmt, "@p_first_name", $p_first_name, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_last_name", $p_last_name, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_address1", $p_address1, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_address2", $p_address2, 'varchar', 240);
    gsi_mssql_bind($v_stmt, "@p_city", $p_city, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_state", $p_state, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_postal_code", $p_postal_code, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_country", $p_country, 'varchar', 60);
    gsi_mssql_bind($v_stmt, "@p_site_use_code", $p_address_type, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_area1", $p_area_code, 'varchar', 10);
    gsi_mssql_bind($v_stmt, "@p_phone1", $p_phone, 'varchar', 25);
    gsi_mssql_bind($v_stmt, "@p_pobox_address", $v_shiptopo, 'varchar', 1);

    gsi_mssql_bind($v_stmt, "@p_address_id", $p_address_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_contact_id", $p_contact_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_phone_id1", $p_phone_id, 'gsi_id_type', -1, true);

    gsi_mssql_bind($v_stmt, "@p_bill_site_use_id", $v_bill_site_use_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_ship_site_use_id", $v_ship_site_use_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($v_stmt, "@p_phone_id2", $v_phone_id2, 'gsi_id_type', -1, true);

    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer..gsi_cust_update_address", "called from insertAddress() in Address.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    $this->v_address_id = $p_address_id;
    $this->v_contact_id = $p_contact_id;
    $this->v_phone_id = $p_phone_id;

    return $v_return_status;

  }

  public function deleteAddress($p_address_id) {

    global $mssql_db;

    $v_stmt = mssql_init("customer.dbo.gsi_cust_addressbook_delete");

    gsi_mssql_bind($v_stmt, "@p_address_id", $p_address_id, 'gsi_id_type');

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer..gsi_cust_addressbook_delete", "called from deleteAddress() in Address.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  private function retrieveAddress(&$pa_address) {

    global $mssql_db ;

    $pa_address = array();

    $pa_address['address_id'] = $this->v_address_id;
    $pa_address['contact_id'] = $this->v_contact_id;
    $pa_address['phone_id'] = $this->v_phone_id;

    $v_stmt = mssql_init("customer.dbo.gsi_cust_retrieve_address");

    gsi_mssql_bind($v_stmt, "@p_customer_id", $this->v_customer_id, 'int');
    gsi_mssql_bind($v_stmt, "@p_address_id", $this->v_address_id, 'int');
    gsi_mssql_bind($v_stmt, "@p_contact_id", $this->v_contact_id, 'int');
    gsi_mssql_bind($v_stmt, "@p_phone_id", $this->v_phone_id, 'int');

    gsi_mssql_bind($v_stmt, "@p_address1", $pa_address['line1'], 'varchar', 240, true);
    gsi_mssql_bind($v_stmt, "@p_address2", $pa_address['line2'], 'varchar', 240, true);
    gsi_mssql_bind($v_stmt, "@p_address3", $pa_address['line3'], 'varchar', 240, true);
    gsi_mssql_bind($v_stmt, "@p_city", $pa_address['city'], 'varchar', 60, true);
    gsi_mssql_bind($v_stmt, "@p_state", $pa_address['state'], 'varchar', 60, true);
    gsi_mssql_bind($v_stmt, "@p_country", $pa_address['country'], 'varchar', 60, true);
    gsi_mssql_bind($v_stmt, "@p_zip_code", $pa_address['postal_code'], 'varchar', 60, true);
    gsi_mssql_bind($v_stmt, "@p_address_name", $pa_address['name'], 'varchar', 60, true);
    gsi_mssql_bind($v_stmt, "@p_first_name", $pa_address['first_name'], 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_last_name", $pa_address['last_name'], 'varchar', 50, true);
    gsi_mssql_bind($v_stmt, "@p_area_code", $pa_address['area_code'], 'varchar', 10, true);
    gsi_mssql_bind($v_stmt, "@p_phone_number", $pa_address['phone'], 'varchar', 25, true);
    gsi_mssql_bind($v_stmt, "@p_province", $pa_address['province'], 'varchar', 60, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("customer.dbo.gsi_cust_retrieve_address", "called from retrieveAddress() in Address.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

}
?>
