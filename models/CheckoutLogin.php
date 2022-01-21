<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
require_once('Zend/View.php');
require_once('CheckoutPage.php');
require_once('Login.php');
require_once('AbandonedCart.php');
require_once('CheckoutPayment.php');
include_once('Payment.php');
require_once('Order.php');

class CheckoutLogin extends CheckoutPage {

  //no constructor needed -- we're using the CheckoutPage constructor
 
  //display the cart page
  public function displayPage() {

    $this->i_site_init->loadMain();

    echo $this->displayHeader('login');
    echo $this->displayLogin(TRUE);
    echo $this->displayFooter();
    // remarketing vars
	$g_data["pageType"]="other";
    $g_data["prodid"]="";
    $g_data["totalvalue"]="";
    
    $this->i_site_init->loadFooter($g_data);
  }

  public function displayLogin($p_show_div = TRUE) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only show login page if not already logged in    
    if ( (empty($v_customer_number)) || (!isset( $_COOKIE['CloseEvent'])) ){

      $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
      $z_view->show_div = $p_show_div;
      
      //by Luis for ISP Project
      $i_order = new Order($_SESSION['s_order_number']);
      $z_view->is_isp = $i_order->isISP();
      $z_view->isp_only  = $i_order->isISPOnly();
      
      
      //----------------------------------------
      
      
      return $z_view->render("checkout_login.phtml");

    } else {
      $i_payment = new CheckoutPayment();
      return $i_payment->displayPayment($p_show_div);
    }
  }

  public function displayGeneralFooter($p_show_div = TRUE) {
    $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

    $z_view->show_div = $p_show_div;

    return $z_view->render("checkout_login.phtml");
  }

  public function handleLogin() {

    $v_user_name = strip_tags($_POST['user_name']);
    $v_password = strip_tags($_POST['password']);

    if(!empty($v_user_name) && !empty($v_password)) {

      $i_login = new Login();
      $v_login_status = $i_login->userLogin($v_user_name, $v_password);

      if($v_login_status === TRUE) {
      	//void any payment, just incase
      	$i_payment = new Payment();
  		$i_payment->void_payments('N');
  		$i_payment->void_payments('Y');
      	
      	if ( $_SERVER['SERVER_PORT'] == 8443 || $_SERVER['SERVER_PORT'] == 443) {
			$secure_connection = true;
		} else {
			$secure_connection = false;
		}
	    
  		//this will track if user closed the browser
  		setcookie("CloseEvent", "CloseEvent", 0, "/",".golfsmith.com",$secure_connection,1);
  	
        //set cookie for abandoned cart detection
        $i_abandoned_cart = new AbandonedCart($v_user_name, $_SESSION['s_order_number'], $_SESSION['s_customer_number']);
        $i_abandoned_cart->setCookie();
        $i_abandoned_cart->updateEmailAddress();

        $_SESSION['s_email_address'] = $v_user_name;
        
        $_SESSION['s_is_guest_checkout'] = 'N';

        //don't return anything -- caller should detect this as success
      } else {
        echo "Invalid username or password.  Please try again.";
      }

    } else {
      echo "Please enter a username and password.";
    }

  }
}
?>
