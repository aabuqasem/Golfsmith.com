<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/CurrencyConverter.php');

class CurrencyconverterController extends Zend_Controller_Action {

  public function indexAction() {
    $this->convertcurrencyAction();
  }

  // Cart-related actions **********

  public function convertcurrencyAction() {
    $i_currency_converter = new CurrencyConverter();
    echo $i_currency_converter->getCurrencyExchangeOutput();
  }

}
?>
