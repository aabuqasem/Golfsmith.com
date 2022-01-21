<?php
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/MyJoys.php');

class MyJoyController extends Zend_Controller_Action {
    
    public function __call($p_method, $p_args) {
        //call indexAction
        $this->indexAction();
    }
    
    public function indexAction(){
        $v_port = $v_port = $_SERVER['SERVER_PORT'];
        if($v_port == '443') {
            $this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
            return;
        }
        $this->i_site_init->loadMain();
        $this->getcustomizerAction(false);
        $this->i_site_init->loadFooter();
    }
    
    public function init(){
        $this->i_site_init = new SiteInit();
        $this->i_site_init->loadInit();
    }
    
    // Display the customizer page
    public function getcustomizerAction($fullPage = true){
        if($fullPage){
            // Load up main
            $this->i_site_init->loadMain();
        }
        
        // Get the request
        $request = $this->getRequest();
        
        // Get the customizer page
        $myJoys = new MyJoys();
        $myJoys->getCustomizerPage($request->getParam("progId"),$request->getParam("packId"),
            $request->getParam("prodId"), $request->getParam("instId"));
        
        if($fullPage){
            // Add the footer
            $this->i_site_init->loadFooter();
        }
    } 
}

?>