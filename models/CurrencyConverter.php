<?php
include_once('Zend/View.php');

class CurrencyConverter {
   
  // currency conversion
 
  public function getCurrencyExchangeOutput() {

    $v_message = '';
    $v_convertto=$_POST['convert_to'];
    $v_amount=$_POST['amount'];
    
    // using Yahoo as google discontinued their lookup API
    $v_exchangeRate = $this->yahooExchangeRate('USD', $v_convertto )- 0.00;      
    if(!empty($_POST['amount'])) {
      $v_message = "<br /><h6>". $v_amount ." USD = <strong>". (round($v_exchangeRate*$v_amount,2)) ." ". $v_convertto .'</strong><span class="save">*</span></h6>';
    } else {
      $v_message = "<br /> <h6>Please Enter Amount in US Dollars.</h6>";
    }

    return $v_message;
 
  }
  
  /* 
   * getCurrencyExchange rate for International Shipping Cost Calculator.
   * 
   */
  public function getCurrencyIntlExchangeOutput($p_curr_code,$p_amt,$p_engine) {
	$v_cc='';
	$v_exchangeRate=$this->getExchangeRate('USD',$p_curr_code,$p_engine);
	if (!$v_exchangeRate==0 || !$v_exchangeRate==null){
	  if(!empty($p_amt)){
		$v_cc=(round($v_exchangeRate*$p_amt,2)) . " " . $p_curr_code;
	  }
	}
	return $v_cc;
  }
 
  /* Google did not return correct currency rate for these countries. So, we need to check with yahoo.
   */
  public function checkDiffSEngine($p_c_code){
		
	switch ($p_c_code){
	  case 'AWG':
	  case 'BSD':
	  case 'BZD':
	  case 'BBD':
	  case 'BMD':
	  case 'BTN':
	  case 'BIF':
	  case 'CVE':
	  case 'DJF':
	  case 'ETB':
	  case 'GMD':
	  case 'GHS':
	  case 'GIP':
	  case 'GTQ':
	  case 'GNF':
	  case 'HTG':
	  case 'IQD':
	  case 'ISK':
	  case 'KMF':
	  case 'KHR':
	  case 'LAK':
	  case 'LSL':
	  case 'LYD':
	  case 'LRD':
	  case 'MGA':
	  case 'MWK':
	  case 'MVR':
	  case 'MZN':
	  case 'MNT':
	  case 'MRO':
	  case 'PAB':
	  case 'RWF':
	  case 'SZL':
	  case 'TJS':
	  case 'VUV':
	  case 'VEF':
	  case 'WST':
	  case 'XPF':
	  case 'XCD':	
	  case 'XOF':	return TRUE;
	  default   :	return FALSE; 
	}
  }
  
  private function getExchangeRate( $p_fromCurrency, $p_toCurrency = 'USD', $p_scrapeFrom = 'google' ) {
    switch( $p_scrapeFrom ) {
      case 'google': return $this->googleExchangeRate($p_fromCurrency, $p_toCurrency);
      case  'yahoo': return $this->yahooExchangeRate($p_fromCurrency, $p_toCurrency )- 0.00;
      default: return $this->googleExchangeRate( $p_fromCurrency, $p_toCurrency )- 0.00; 
    }
  }

  private function googleConvertCodeToName( $p_code ) {
    switch( $p_code ) {
      // case  'USD': return 'U.S. dollar';
      case  'CAD': return 'Canadian dollar';
      case  'GBP': return 'British pound';
      case  'BDT': return 'Bangladesh Taka';
      case  'BYR': return 'Belarus Ruble';
      case	'DZD': return 'Algerian Dinar';
      case	'BOB': return 'Bolivian bolivianos';
      case	'BWP': return 'Botswanan pulas';
      case	'BGN': return 'Bulgarian Lev';
      case	'KYD': return 'Cayman Islands dollars';
      case	'COP': return 'Colombian Peso';
      case	'HRK': return 'Croatian Kuna';
      case	'EGP': return 'Egyptian Pound';
      case	'SVC': return 'Salvadoran Colón';
      case	'IDR': return 'Indonesian Rupiah';
      case	'KZT': return 'Kazakhstani Tenge';
      case	'LVL': return 'Latvian Lats';
      case	'MKD': return 'Macedonian Denar';
      case	'MYR': return 'Malaysian Ringgit';
      case	'MUR': return 'Mauritian Rupee';
      case	'MDL': return 'Moldovan Leu';
      case	'NAD': return 'Namibian Dollar';
      case	'ANG': return 'Netherlands Antillean Guilder';
      case	'NIO': return 'Nicaraguan Córdoba';
      case	'NGN': return 'Nigerian Naira';
      case  'NPR': return 'Nepalese Rupees';
      case	'PYG': return 'Paraguayan Guarani';
      case	'SCR': return 'Seychelles rupees';
      case	'SLL': return 'Sierra Leonean leones';
      case	'TRY': return 'Turkish Lira';
      case	'UAH': return 'Ukrainian Hryvnia';
      case	'UYU': return 'Uruguayan Peso';
      case	'UZS': return 'Uzbekistan Som';
      case	'VND': return 'Vietnamese Dong';
      case	'YER': return 'Yemeni Rial';
      case	'ZMK': return 'Zambian Kwacha';
      case  'JPY': return 'Japanese yen';
      case  'MXN': return 'Mexican peso';
      case  'EUR': return 'Euro';
      case 	'PKR': return 'Pakistan';
      case 	'AED': return 'U.A.E';
      case	'ARS': return 'Argentina';
      case	'AUD': return 'Australia';
      case	'AWG': return 'Aruba';
      case	'BHD': return 'Bahrain';
      case	'BND': return 'Brunei';
      case	'BRL': return 'Brazil';
      case	'CHF': return 'Switzerland';
      case	'CLP': return 'Chile';
      case	'CNY': return 'China P.R.C.';
      case	'CRC': return 'Costa Rica';
      case	'CYP': return 'Cyprus';
      case	'CZK': return 'Czech Republic';
      case	'DKK': return 'Denmark';
      case	'DOP': return 'Dominican Republic';
      case	'EUR': return 'Euro';
      case	'FJD': return 'Fiji';
      case	'GBP': return 'Great Britain';
      case	'GYD': return 'Guyana';
      case	'HKD': return 'Hong Kong';
      case	'HNL': return 'Honduras';
      case	'HUF': return 'Hungary';
      case	'ILS': return 'Israel';
      case	'INR': return 'India';
      case	'ISK': return 'Iceland';
      case	'JMD': return 'Jamaica';
      case	'JOD': return 'Jordan';
      case	'KES': return 'Kenya';
      case	'KRW': return 'South Korea';
      case	'KWD': return 'Kuwait';
      case	'LBP': return 'Lebanon';
      case	'LKR': return 'Sri Lanka';
      case	'MAD': return 'Morocco';
      case	'MGA': return 'Madagascar';
      case	'MTL': return 'Malta';
      case	'NOK': return 'Norway';
      case	'NZD': return 'New Zealand';
      case	'OMR': return 'Oman';
      case	'PEN': return 'Peru';
      case	'PGK': return 'Papua New Guinea';
      case	'PHP': return 'Philippines';
      case	'PLN': return 'Poland';
      case	'QAR': return 'Qatar';
      case	'RON': return 'Romania';
      case	'RUB': return 'Russia';
      case	'SAR': return 'Saudi Arabia';
      case	'SBD': return 'Solomon Islands';
      case	'SEK': return 'Sweden';
      case	'SGD': return 'Singapore';
      case	'THB': return 'Thailand';
      case	'TND': return 'Tunisia';
      case	'TTD': return 'Trinidad';
      case	'TWD': return 'Taiwan';
      case	'TZS': return 'Tanzania';
      case	'UGX': return 'Uganda';
      case	'USD': return 'United States';
      case	'XAF': return 'Central Africa';
      case	'XCD': return 'East Caribbean';
      case	'ZAR': return 'South Africa';
      default:      return false;
    }
  }

  //function to get the Currency exchange rate from Google
  private function googleExchangeRate( $p_from, $p_to ) {
	$from = $p_from;
	$to   = $p_to;
//make string to be put in API
$string = "1".$from."=?".$to;
//Call Google API
$google_url = "http://www.google.com/ig/calculator?hl=en&q=".$string;
//Get and Store API results into a variable
$result = file_get_contents($google_url);
$rs_split_0 = explode(",",trim($result));
$rs_split_1 = explode('"',$rs_split_0[1]);
$rs_split_2 = explode(" ",$rs_split_1[1]);
//echo $result."<BR>";
//echo trim($rs_split_2[0]);
  	return trim($rs_split_2[0]);

  	
  }

 //functions to get the Currency exchange rate from Yahoo
 private function yahooExchangeRate( $p_from, $p_to ) {
    if(($v_contents = file_get_contents( 'http://finance.yahoo.com/d/quotes.csv?s=' .
       $p_from . $p_to . '=X&f=sl1d1t1ba&e=.csv' ) ) === false ) {
       return false;
    }
    $v_startPos = strpos( $v_contents, "," ) + 1;
    $v_endPos   = strpos( $v_contents, ",", $v_startPos );
    $v_exchangeRate = substr( $v_contents, $v_startPos, $v_endPos - $v_startPos );
    return $v_exchangeRate;
 }
   
}//End class
?>
