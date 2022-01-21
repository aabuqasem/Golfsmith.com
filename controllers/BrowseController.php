<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');

class BrowseController extends Zend_Controller_Action {

  //override the __call() function of Zend_Controller_Action, 
  //because an action will never be specified by browse
  public function __call($p_method, $p_args) {

    if('Action' == substr($p_method, -6)) {
      $v_controller = $this->getRequest()->getControllerName();
      $v_category = $this->getRequest()->getActionName();

      //we want to use the action as our parameter, since it's really category
      $this->indexAction($v_category);

      return;


    }

    throw new Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $methodName), 500);

  }

  public function indexAction($p_category) {
    echo 'still to be implemented';

  }

}
?>
