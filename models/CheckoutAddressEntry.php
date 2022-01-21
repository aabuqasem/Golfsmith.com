<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
require_once('Zend/View.php');
require_once('CheckoutPage.php');
require_once('UserRegistration.php');
require_once( FPATH.'countries.inc');


class CheckoutAddressEntry extends CheckoutPage {

  //no constructor needed -- we're using the CheckoutPage constructor
 
  //display the cart page
  public function displayPage() {

    $this->i_site_init->loadMain();
    echo $this->displayHeader('addressentry', 1);
    echo $this->displayAddressEntry(TRUE);
    echo $this->displayFooter();
    // remarketing vars
	$g_data["pageType"]="other";
    $g_data["prodid"]="";
    $g_data["totalvalue"]="";
    $this->i_site_init->loadFooter($g_data);
  }

  public function displayAddressEntry($p_show_div = TRUE) {
    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

    $z_view->show_div = $p_show_div;
    $z_view->state_options_path = PHP_INC_PATH . 'state_bill_dropdown.html';;
    $z_view->state_options_ca_path = PHP_INC_PATH . 'state_bill_dropdown_ca.html';;
	$i_countries = new countries;   
    $z_view->intl_countries=$i_countries->fill_countries();
    
    return $z_view->render("checkout_addressentry.phtml");
  }

  public function fedExCheck() {
    $i_registration = new UserRegistration();
    return $i_registration->fedExValidation(); 
  }

  public function registerCustomer() {
    $i_registration = new UserRegistration();
    return $i_registration->registerCustomer();
  }
  
}
?>
