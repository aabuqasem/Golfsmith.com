<?php 

 /*******************************************************************************************************
 * Program name				: 		IntlShippingCalcController.php										*
 * Type						:		Controller															*
 * Language					:		PHP																	*
 * Description				:		Controller that initiates the existence of International Shipping	*
 * 									Calculator and  controls the control-flow between Browser and Data- *
 * 									base.																*
 * Depending files			:		IntlShippingCalc.php(Model), intl_shipping_cost.phtml (view)		*
 *																										*
 * Created on				:		01/20/2011															*
 * 																										*
 *******************************************************************************************************/

require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/IntlShippingCalc.php');

class IntlShippingCalcController extends Zend_Controller_Action {

  public function indexAction() {
    $this->displayAction();
  }

  public function displayAction() { // display the page
    $intl_shipping_calculator = new IntlShippingCalc();
    echo $intl_shipping_calculator->calcDisplay();
  }
  
  public function calculateAction(){ // international shipping cost calculator
  	$i_calculator = new IntlShippingCalc();
  	echo $i_calculator->showResult();
  }
  
  public function changecurrencyAction(){ // currency conversion
  	$i_change = new IntlShippingCalc();
  	echo $i_change->showChange();
  }
}
?>
