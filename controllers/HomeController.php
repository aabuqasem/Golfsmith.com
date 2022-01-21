<?php 
require_once('Zend/Controller/Action.php');
require_once('controllers/IndexController.php');

class HomeController extends Zend_Controller_Action {

  public function indexAction() {

    IndexController::indexAction();

  }

}
?>
