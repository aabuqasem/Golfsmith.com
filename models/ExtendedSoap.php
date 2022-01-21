<?php 
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * THis extends soap_client so the array passed in, prelimary xml created and then
 * maniuplated for elements with the same name which can not be represented in a multi dimensional
 * associative array
 * 
 *
 * PHP version 5
 * @author     Original Author harry forbess <harry.forbess@golfsmith.com> <hforbess@gmail.com>
 *
 */


class ExtendedSoap extends SoapClient {

	public $order_lines;
	public $payment_lines;
	public $contacts;

	function __construct($wsdl, $options = null) {
		parent::__construct($wsdl, $options);
	}

	function __doRequest($request, $location, $action, $version) {
    
		$xml = simplexml_load_string( $request );
		$xml->registerXPathNamespace('soap-env', 'http://schemas.xmlsoap.org/soap/envelope/');
		$xml->registerXPathNamespace('ns1', 'http://www.golfsmith.com/xsd/order/services');
		$xml->registerXPathNamespace('ns2', 'http://www.golfsmith.com/xsd/payment/services');
		$xml->registerXPathNamespace('ns3', 'http://www.golfsmith.com/xsd/customer/services');
		$xml->registerXPathNamespace('ns4', 'http://www.golfsmith.com/wsdl/accertify/services');
      
		$sxe = new SimpleXMLElement( $xml->asXML() );
      
		$orderLines = $sxe->xpath("//ns1:OrderLines");
		for ( $x = 0; $x < count( $this->order_lines ); $x++  )
		{
			$line = $orderLines[0]->addChild("ns1:OrderLine");
			foreach ( $this->order_lines[$x] as $key => $value )
				{
				$line->addChild( "ns1:".$key, $value);
				}
		}
      
		$paymentLines = $sxe->xpath("//ns1:PaymentHeader");
		for ( $x = 0; $x < count( $this->payment_lines ); $x++  )
		{
			$line = $paymentLines[0]->addChild("ns1:line");
			foreach ( $this->payment_lines[$x] as $key => $value )
			{
				$line->addChild( "ns1:".$key, $value);
			}
		}  
		$contactLines = $sxe->xpath("//ns2:Contacts");


		for ( $x = 0; $x < count( $this->contacts ); $x++  )
		{
			$contact = $contactLines[0]->addChild("ns1:Contact");
			foreach ( $this->contacts[$x] as $key => $value )
			{
				if ( ! is_array( $value ))
				{
					$contact->addChild( "ns1:".$key, $value);
				}
				else
				{   
					$address = $contact->addChild( "ns1:ContactAddress" );
      		
					foreach ( $value as $address_field => $field_value )
					{
						$address->addChild( "ns1:" . $address_field, $field_value );
					}
				}
			}
		}

		$request =  $sxe->saveXML( );
        
		$handle = fopen("/tmp/last_request.xml", "w+");
		fwrite( $handle, $request );
		fclose( $handle );
  
      	//doRequest
      	return parent::__doRequest($request, $location, $action, $version);

	}

}
?>