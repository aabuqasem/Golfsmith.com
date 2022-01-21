<?php

 /*******************************************************************************************************
 * Program name				: 		IntlShippingCalc.php												*
 * Type						:		Model																*
 * Language					:		PHP																	*
 * Description				:		Gathers the data necessary for calculation from Database and		*
 * 									calculates International Shipping Cost. It also helps in showing the*
 * 									equivalent currency of the cost. To show currency equivalence, it 	*
 *									uses CurrencyConverter.php present at include/models directory.		*
 * 									equivalent currency of the cost. 									*
 * Depending files			:		intl_shipping_cost.phtml (view)										*
 * 																										*
 * Created on				:		01/20/2011															*
 * 																										*
 *******************************************************************************************************/

include_once('Zend/View.php');
include ("gsi_common.inc");
include_once ('CurrencyConverter.php');
include_once( FPATH.'countries.inc');

class IntlShippingCalc extends CurrencyConverter {
   
  public function calcDisplay(){
	$this->makePreloads();
  }
	
  /* Initializes by loading the values into existing elements */
  public function makePreloads(){
	$z_view = new Zend_View(array('scriptPath' => VIEW_PATH)); 
	$z_view->intl_ship_methods=$this->fill_shipMethods();
	$z_view->intl_weights=$this->fill_weights();
	$i_countries = new countries;   
    $z_view->intl_countries=$i_countries->fill_countries();
	echo $z_view->render("intl_shipping_cost.phtml");
  }

  /* Returns the amount from the database */
  public function showResult(){
	$v_message = '';

	$v_weight=post_or_get("weight");
	$v_country=post_or_get("country");
	$v_ship=post_or_get("ship");
		
	if($v_weight!="" && $v_country!="" && $v_ship!=""){
	  $v_message=$this->checkShippingAmount($v_weight,$v_country,$v_ship);
	}
		
	if($v_message=="")
	  return $v_message="...";
	else if(is_string((int)$v_message)==TRUE)
	  return $v_message="$...";
	else
	  return $this->formatCurrency($v_message);// formats in 2 decimal places
  }
	
  private function currencyCode($p_ccode){
	switch ($p_ccode){
	  case 'AF' : return 'AFN';
	  case 'AL' : return 'ALL';
	  case 'DZ' : return 'DZD';
	  case 'AS' : return 'USD';
	  case 'AD' : return 'EUR';
	  case 'AO' : return 'AOA';
	  case 'AI' : return 'XCD';
	  case 'AQ' : return '' ;
	  case 'AG' : return 'XCD';
	  case 'AR' : return 'ARS';
	  case 'AM' : return 'AMD';
	  case 'AW' : return 'AWG';
	  case 'AU' : return 'AUD';
	  case 'AT' : return 'EUR';
	  case 'AZ' : return 'AZN';
	  case 'BS' : return 'BSD';
	  case 'BH' : return 'BHD';
	  case 'BD' : return 'BDT';
	  case 'BB' : return 'BBD';
	  case 'BY' : return 'BYR';
	  case 'BE' : return 'EUR';
	  case 'BZ' : return 'BZD';
	  case 'BJ' : return 'XOF';
	  case 'BM' : return 'BMD';
	  case 'BT' : return 'BTN';
	  case 'BO' : return 'BOB';
	  case 'BA' : return 'BAM';
	  case 'BW' : return 'BWP';
	  case 'BV' : return 'NOK';
	  case 'BR' : return 'BRL';
	  case 'IO' : return 'USD';
	  case 'BN' : return 'BND';
	  case 'BG' : return 'BGN';
	  case 'BF' : return 'XOF';
	  case 'BI' : return 'BIF';
	  case 'KH' : return 'KHR';
	  case 'CM' : return 'XAF';
	  case 'CV' : return 'CVE';
	  case 'KY' : return 'KYD';
	  case 'CF' : return 'XAF';
	  case 'TD' : return 'XAF';
	  case 'CL' : return 'CLP';
	  case 'CN' : return 'CNY';
	  case 'CX' : return 'AUD';
	  case 'CC' : return 'AUD';
	  case 'CO' : return 'COP';
	  case 'KM' : return 'KMF';
	  case 'CG' : return 'XAF';
	  case 'CK' : return 'NZD';
	  case 'CR' : return 'CRC';
	  case 'CI' : return 'XOF';
	  case 'HR' : return 'HRK';
	  case 'CU' : return 'CUC';
	  case 'CY' : return 'CYP';
	  case 'CZ' : return 'CZK';
	  case 'DK' : return 'DKK';
	  case 'DJ' : return 'DJF';
	  case 'DM' : return 'XCD';
	  case 'DO' : return 'DOP';
	  case 'TP' : return 'USD';
	  case 'EC' : return 'USD';
	  case 'EG' : return 'EGP';
	  case 'SV' : return 'SVC';
	  case 'GQ' : return 'XAF';
	  case 'ER' : return 'ERN';
	  case 'EE' : return 'EUR';
	  case 'ET' : return 'ETB';
	  case 'FK' : return 'FKP';
	  case 'FO' : return 'DKK';
	  case 'FJ' : return 'FJD';
	  case 'FI' : return 'EUR';
	  case 'FR' : return 'EUR';
	  case 'FX' : return '' ;
	  case 'GF' : return 'EUR';
	  case 'PF' : return 'XPF';
	  case 'TF' : return 'EUR';
	  case 'GA' : return 'XAF';
	  case 'GM' : return 'GMD';
	  case 'GE' : return 'GEL';
	  case 'DE' : return 'EUR';
	  case 'GH' : return 'GHS';
	  case 'GI' : return 'GIP';
	  case 'GR' : return 'EUR';
	  case 'GL' : return 'DKK';
	  case 'GD' : return 'XCD';
	  case 'GP' : return 'EUR';
	  case 'GU' : return 'USD';
	  case 'GT' : return 'GTQ';
	  case 'GN' : return 'GNF';
	  case 'GW' : return 'XOF';
	  case 'GY' : return 'GYD';
	  case 'HT' : return 'HTG';
	  case 'HM' : return 'AUD';
	  case 'HN' : return 'HNL';
	  case 'HK' : return 'HKD';
	  case 'HU' : return 'HUF';
	  case 'IS' : return 'ISK';
	  case 'IN' : return 'INR';
	  case 'ID' : return 'IDR';
	  case 'IR' : return 'IRR';
	  case 'IQ' : return 'IQD';
	  case 'IE' : return 'EUR';
	  case 'IL' : return 'ILS';
	  case 'IT' : return 'EUR';
	  case 'JM' : return 'JMD';
	  case 'JP' : return 'JPY';
	  case 'JO' : return 'JOD';
	  case 'KZ' : return 'KZT';
	  case 'KE' : return 'KES';
	  case 'KI' : return 'AUD';
	  case 'KP' : return 'KPW';
	  case 'KR' : return 'KRW';
	  case 'KW' : return 'KWD';
	  case 'KG' : return 'KGS';
	  case 'LA' : return 'LAK';
	  case 'LT' : return '' ;
	  case 'LV' : return 'LVL';
	  case 'LB' : return 'LBP';
	  case 'LS' : return 'LSL';
	  case 'LR' : return 'LRD';
	  case 'LY' : return 'LYD';
	  case 'LI' : return 'CHF';
	  case 'LX' : return 'LTL';
	  case 'LU' : return 'EUR';
	  case 'MO' : return 'MOP';
	  case 'MK' : return 'MKD';
	  case 'MG' : return 'MGA';
	  case 'MW' : return 'MWK';
	  case 'MY' : return 'MYR';
	  case 'MV' : return 'MVR';
	  case 'ML' : return 'XOF';
	  case 'MT' : return 'MTL';
	  case 'MH' : return 'USD';
	  case 'MQ' : return 'EUR';
	  case 'MR' : return 'MRO';
	  case 'MU' : return 'MUR';
	  case 'YT' : return 'EUR';
	  case 'MX' : return 'MXN';
	  case 'FM' : return 'USD';
	  case 'MD' : return 'MDL';
	  case 'MC' : return 'EUR';
	  case 'MN' : return 'MNT';
	  case 'MS' : return 'XCD';
	  case 'MA' : return 'MAD';
	  case 'MZ' : return 'MZN';
	  case 'MM' : return 'MMK';
	  case 'NA' : return 'NAD';
	  case 'NR' : return 'AUD';
	  case 'NP' : return 'NPR';
	  case 'NL' : return 'EUR';
	  case 'AN' : return 'ANG';
	  case 'NC' : return 'XPF';
	  case 'NZ' : return 'NZD';
	  case 'NI' : return 'NIO';
	  case 'NE' : return 'XOF';
	  case 'NG' : return 'NGN';
	  case 'NU' : return 'NZD';
	  case 'NF' : return 'AUD';
	  case 'MP' : return 'USD';
	  case 'NO' : return 'NOK';
	  case 'OM' : return 'OMR';
	  case 'PK' : return 'PKR';
	  case 'PW' : return 'USD';
	  case 'PA' : return 'PAB';
	  case 'PG' : return 'PGK';
	  case 'PY' : return 'PYG';
	  case 'PE' : return 'PEN';
	  case 'PH' : return 'PHP';
	  case 'PN' : return 'NZD';
	  case 'PL' : return 'PLN';
	  case 'PT' : return 'EUR';
	  case 'PR' : return 'USD';
	  case 'QA' : return 'QAR';
	  case 'RE' : return 'EUR';
	  case 'RO' : return 'RON';
	  case 'RU' : return 'RUB';
	  case 'RW' : return 'RWF';
	  case 'SH' : return 'SHP';
	  case 'KN' : return 'XCD';
	  case 'LC' : return 'XCD';
	  case 'PM' : return 'EUR';
	  case 'VC' : return 'XCD';
	  case 'WS' : return 'WST';
	  case 'SM' : return 'EUR';
	  case 'ST' : return 'STD';
	  case 'SA' : return 'SAR';
	  case 'SN' : return 'XOF';
	  case 'SC' : return 'SCR';
	  case 'SL' : return 'SLL';
	  case 'SG' : return 'SGD';
	  case 'SK' : return 'EUR';
	  case 'SI' : return 'EUR';
	  case 'SB' : return 'SBD';
	  case 'SO' : return 'SOS';
	  case 'ZA' : return 'ZAR';
	  case 'GS' : return 'GBP';
	  case 'ES' : return 'EUR';
	  case 'LK' : return 'LKR';
	  case 'SD' : return 'SDG';
	  case 'SR' : return 'SRD';
	  case 'SJ' : return 'NOK';
	  case 'SZ' : return 'SZL';
	  case 'SE' : return 'SEK';
	  case 'CH' : return 'CHF';
	  case 'SY' : return 'SYP';
	  case 'TW' : return 'TWD';
	  case 'TJ' : return 'TJS';
	  case 'TZ' : return 'TZS';
	  case 'TH' : return 'THB';
	  case 'TG' : return 'XOF';
	  case 'TK' : return 'NZD';
	  case 'TO' : return 'TOP';
	  case 'TT' : return 'TTD';
	  case 'TN' : return 'TND';
	  case 'TR' : return 'TRY';
	  case 'TM' : return 'TMT';
	  case 'TC' : return 'USD';
	  case 'TV' : return 'AUD';
	  case 'UG' : return 'UGX';
	  case 'UA' : return 'UAH';
	  case 'AE' : return 'AED';
	  case 'GB' : return 'GBP';
	  case 'UM' : return 'USD';
	  case 'UY' : return 'UYU';
	  case 'UZ' : return 'UZS';
	  case 'VU' : return 'VUV';
	  case 'VA' : return 'EUR';
	  case 'VE' : return 'VEF';
	  case 'VN' : return 'VND';
	  case 'VG' : return 'USD';
	  case 'VI' : return 'USD';
	  case 'WF' : return 'XPF';
	  case 'EH' : return 'MAD';
	  case 'YE' : return 'YER';
	  case 'YU' : return 'YUM';
	  case 'ZR' : return 'ZRN';
	  case 'ZM' : return 'ZMK';
	  case 'ZW' : return 'ZWD';
	  case 'US' : return 'USD';
	  case 'CA' : return 'CAD';
	  default: 	return false;
	}
  }
	
  /* This function is to convert given amount into given country currency */
  public function showChange(){
	$v_message='';
	$v_convert_to=post_or_get("convert_to");
	$v_amount=post_or_get("amt");
		
	if($v_convert_to!="" && $v_amount!=""){
	  $v_temp_code=$this->currencyCode($v_convert_to);
	  
	  /* Use yahoo search engine if google fails to give correct currency rate. */
	  if(!$this->checkDiffSEngine($v_temp_code)==TRUE)
		$v_message=$this->getCurrencyIntlExchangeOutput($v_temp_code,$v_amount,'google');
	  else 
		$v_message=$this->getCurrencyIntlExchangeOutput($v_temp_code,$v_amount, 'yahoo');					
	}
		
	if($v_message==0)
	  $v_message='';
	else if($v_message==FALSE)
	  $v_message='';
	else 
	  $v_message=' ('.$v_message . ')';
	
	return $v_message;
  }
	
  /* formats the amount in 2 decimal places */
  private function formatCurrency($p_in_value){
	$v_fmt_amount = sprintf("%01.2f", $p_in_value);
	
	return $v_fmt_amount;
  }
	
  /* This function populates all available Shipping Methods from the table */
  private function fill_shipMethods(){
	global $web_db;
		
	/* ***** Retrive Shipping Methods but Unique ********* */
  	$sql2 = "select t1.ship_method_code from gsi_shipping_intl_zones t1, gsi_shipping_intl_rate_chart t2
  	WHERE t1.zone=t2.zone group by t1.ship_method_code";

  	$result2 = mysqli_query($web_db, $sql2);
  	display_mysqli_error($sql2);
		
  	while ($myrow2 = mysqli_fetch_array($result2)) {
	  $v_ship=$myrow2["ship_method_code"];
		
	  $va_ship_methods[]=$v_ship; // adding to array
    }
    mysqli_free_result ($result2);
		
	return $va_ship_methods;
  }

  
  /* Populates the Weight dropdown with respective weights */
  private function fill_weights(){
	global $web_db;
		
	/* ***** Retrive Shipping Methods but Unique ********* */
		
	$sql3 = "select distinct weight from gsi_shipping_intl_rate_chart order by weight";		
	$result3 = mysqli_query($web_db, $sql3);
	display_mysqli_error($sql3);
		
	while ($myrow3 = mysqli_fetch_array($result3)) {
	  $v_weight=$myrow3["weight"];
		
	  $va_weights[]=$v_weight; // adding to array
	}
	mysqli_free_result ($result3);
		
	return $va_weights;
  }
	
  /* This function returns rate according to the country */
  private function checkShippingAmount($p_weight,$p_country,$p_method){
	global $web_db;
			  
	/* Retreives rate out of the two tables 
	 * where special rate is taken if default rate is null.
	*/
	$sql = "SELECT CASE iz.default_rate
	WHEN 'N' THEN izr.special_rate
	WHEN NULL THEN izr.default_rate
	ELSE izr.default_rate
	END as rate
	FROM gsi_shipping_intl_zones as iz, gsi_shipping_intl_rate_chart as izr 
	WHERE iz.zone=izr.zone 
	AND izr.weight='" . $p_weight . "' 
	AND iz.country='" . $p_country . "' 
	AND iz.ship_method_code='" . $p_method . "'"; 
			  
	$result = mysqli_query($web_db, $sql);
	display_mysqli_error($sql);
			  
	if ($myrow = mysqli_fetch_array($result)) {
	  $v_output = $myrow["rate"] ;
	}
	mysqli_free_result ($result);
			
	return $v_output;
  }
}
 	
?>
