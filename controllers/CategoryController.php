<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/DisplayPage.php');

class CategoryController extends Zend_Controller_Action {

  public function __call($p_method, $p_args) {

    //call indexAction
    $this->indexAction();

  }

  public function indexAction() {
  	
  	//our parameter isn't named in the URL, so we have to get it manually
    $v_file = $this->getRequest()->getRequestUri();
    $v_file = strstr($v_file, '/category/');
    $v_file = str_replace('/category/', '', $v_file);

    //if the last character is '/', remove it
    $v_last_character = substr($v_file, strlen($v_file) - 1, 1);
    
    if($v_last_character == '/') {
      $v_file = substr($v_file, 0, strlen($v_file) - 1);
    }

    $i_display = new DisplayPage('category', $v_file);

    $i_display->displayPage();
  }
}
  
?>
