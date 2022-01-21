<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/PageRedirect.php');

class RedirectController extends Zend_Controller_Action {

  public function indexAction($p_redirect_name) {

    $i_site_init = new SiteInit();
    $i_site_init->loadInit();

    $i_redirect = new PageRedirect($p_redirect_name);

    $i_redirect->handleRedirect();

  }

}
?>
