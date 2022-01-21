<?php
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/GoogleCheckout.php');
require_once('models/AmazonCheckout.php');
require_once('models/ShoppingCart.php');

class SavedCartController extends Zend_Controller_Action {

    public function loadcartAction()
    {
        $shoppingCart = new ShoppingCart();
        
        // Load the cart
        $result = $shoppingCart->loadSavedCart(post_or_get('savedCart'));
        
        // Display the cart page
        $shoppingCart->displayPage();
        
        // Display error message if there is one
        echo $result;
    }
    
    public function savecartAction()
    {
        $shoppingCart = new ShoppingCart();
        
        // Save the cart
        echo $shoppingCart->createSavedCart();
    }
}

?>