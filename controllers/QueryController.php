<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/AjaxQuery.php');

class QueryController extends Zend_Controller_Action {

  public function __call($p_method, $p_args) {

    //call indexAction
    $this->indexAction();

  }

  public function indexAction() {

    //action is the query name, but we'll have enough queries that we don't want a separate action handler for each
    $va_params = $this->_getAllParams();
    $v_query = $va_params['action'];

    //create the query class
    $i_ajax_query = new AjaxQuery($v_query);

    $i_ajax_query->query();

  }

}
?>
