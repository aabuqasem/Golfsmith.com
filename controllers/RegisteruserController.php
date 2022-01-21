<?php
/****************************************************************************
 *                                                                           *
 *  Program Name :  RegisterUserController.php                                           *
 *  Type         :  MVC Controller                                                    *
 *  File Type    :  PHP                                                      *
 *  Location     :  /include/Controllers                                          *
 *  Created By   :  Luis Vazquez                                     *
 *  Created Date :  08/10/2015                                               *
 *               :  Copyright 2015  Golfsmith International                  *
 *---------------------------------------------------------------------------*
 *                                                                           *
 * History:                                                                  *
 * --------                                                                  *
 * Date       By                  Comments                                   *
 * ---------- ---------------     --------------------                       *
 * 08/10/2015 Luis Vazquez        Initial Version                           *            *
 *                                                                           *
 ****************************************************************************/

require_once('Zend/Controller/Action.php');
require_once('models/RegisterUser.php');
require_once ('Zend/View.php');

class RegisteruserController extends Zend_Controller_Action {
    
    
    public function indexAction(){
        
    }
    
    
    public function isondatabaseAction(){
        
        $v_email = $this->getRequest()->getParam('email', null);
        
        if(empty($v_email)){
            echo "false";
        }else{
            
            $registerUser = new Registeruser();
            
            if(  $registerUser->customerHasProfile($v_email) ){
                echo "true";
            }else{
                echo "false";
            }
        
        }
      
    }
    
}