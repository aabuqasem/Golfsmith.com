<?php
require_once('Zend/View.php');

class TradeIn{

    public function __construct($wsdl)
    {
     	
    }

     /* Populates the States dropdown */
  	public  function getStatesList(){
	global $web_db;
	
	$sql3 = "SELECT distinct st.state_abb,st.state_name FROM webdata.gsi_states st,gsi_store_info si where st.state_abb = si.state order by st.state_abb";	
	$result3 = mysqli_query($web_db, $sql3);
	display_mysqli_error($sql3);
	while ($myrow3 = mysqli_fetch_array($result3)) {
	  $va_states[]=$myrow3;	   		
	}
	mysqli_free_result ($result3);
	header('Content-type: application/json');          		
  		    		echo (json_encode($va_states));
	        		exit();
  }
      
   /* Populates the Stores dropdown */
  	public function getStoresList($state){
	global $web_db;
		
	//$sql3 = "select organization_id,location_code from gsi_store_info where state='".$state."' order by location_code";
	$sql3 ="select si.organization_id,si.location_code from gsi_store_info si,gsi_store_info_ext se 
	where si.organization_id = se.organization_id
	and store_pickup_flag = 'Y'
	and state='".$state."'order by location_code";
	$result3 = mysqli_query($web_db, $sql3);
	display_mysqli_error($sql3);

	while ($myrow3 = mysqli_fetch_array($result3)) {
	  $va_stores[]=$myrow3;	  		
	}
	mysqli_free_result ($result3);
          			header('Content-type: application/json');          		
  		    		echo (json_encode($va_stores));
	        		exit();
  }      

     /* Populates the Brands dropdown */
  	public  function getBrandsList(){
	global $web_db;
	
	$sql3 = "select distinct manufacturer from gsi_tradein_clubs where (pg_only = 'N' or pg_only is NULL) order by manufacturer";	
	$result3 = mysqli_query($web_db, $sql3);
	display_mysqli_error($sql3);
	while ($myrow3 = mysqli_fetch_array($result3)) {
	  $va_brands[]=$myrow3;	   		
	}
	mysqli_free_result ($result3);
	header('Content-type: application/json');          		
  		    		echo (json_encode($va_brands));
	        		exit();
  }

  
   /* Populates the ClubType dropdown */
  	public  function getClubsTypeList($brandname){
	global $web_db;
	
	$sql3 = "select distinct club_type from gsi_tradein_clubs where manufacturer ='".$brandname."' and (pg_only = 'N' or pg_only is NULL) order by club_type";
	$result3 = mysqli_query($web_db, $sql3);
	display_mysqli_error($sql3);
	while ($myrow3 = mysqli_fetch_array($result3)) {
	  $va_brands[]=$myrow3;	   		
	}
	mysqli_free_result ($result3);
	header('Content-type: application/json');          		
  		    		echo (json_encode($va_brands));
	        		exit();
  }  

     /* Populates the ClubModel dropdown */
  	public  function getClubsModelList($clubtype,$brandname){
	global $web_db;
		
	$sql3 = "select distinct style,model from gsi_tradein_clubs where manufacturer = '".$brandname."' and club_type = '".$clubtype."' and (pg_only = 'N' or pg_only is NULL) order by model";
	$result3 = mysqli_query($web_db, $sql3);
	display_mysqli_error($sql3);
	while ($myrow3 = mysqli_fetch_array($result3)) {
	  $va_models[]=$myrow3;	   		
	}
	mysqli_free_result ($result3);
	header('Content-type: application/json');          		
  		    		echo (json_encode($va_models));
	        		exit();
  } 

     /* check for store promo */
  	public  function getStorePromo($store_id){
	global $web_db;
	
	$sql3 = "select organization_id from webonly.gsi_tradein_promo_stores where organization_id ='".$store_id."'";	
	$result3 = mysqli_query($web_db, $sql3);
	display_mysqli_error($sql3);
	while ($myrow3 = mysqli_fetch_array($result3)) {
	  $store_promo[]=$myrow3;	   		
	}
	mysqli_free_result ($result3);
	header('Content-type: application/json');          		
  		    		echo (json_encode($store_promo));
	        		exit();
  }
  
  
       /* Populates the Club Values */
  	public  function getEstimateValue($organization_id,$style,$platform){
	global $web_db;
	$z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
		
	$sql3 = "select good_buyback_price,excellent_buyback_price,like_new_buyback_price from gsi_tradein_clubs where organization_id ='".$organization_id."' and style ='".$style."' and (pg_only = 'N' or pg_only is NULL)";
	$result3 = mysqli_query($web_db, $sql3);
	display_mysqli_error($sql3); 
	if (!mysqli_num_rows($result3)) {
		$sql3 = "select good_buyback_price,excellent_buyback_price,like_new_buyback_price from gsi_tradein_clubs where organization_id ='26' and style ='".$style."' and (pg_only = 'N' or pg_only is NULL)";
		$result3 = mysqli_query($web_db, $sql3);
		display_mysqli_error($sql3); 
	 }
	
	while ($myrow3 = mysqli_fetch_array($result3)) {
	$z_view->good_buyback_price = $myrow3['good_buyback_price'];
    $z_view->excellent_buyback_price = $myrow3['excellent_buyback_price'];
    $z_view->like_new_buyback_price = $myrow3['like_new_buyback_price'];
	  $va_modelvalue[]=$myrow3;	   		
	}

    mysqli_free_result ($result3);
    if ($platform == 'Mobile') {
				header('Content-type: application/json');
  		    	return (json_encode($va_modelvalue));		
				exit();
    } else {
    	return $z_view->render("Trade_in_values.phtml");
    }
    	        		
  }  
}
?>
