<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/ProductPage.php');
require_once ('models/CustomClub.php');

class ProductController extends Zend_Controller_Action {

  public function __call($p_method, $p_args) {

    //call indexAction
    $this->indexAction();

  }

  public function indexAction() {
   	
	    $v_port = $v_port = $_SERVER['SERVER_PORT'];
	    if($v_port == '443') {
	      $this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	      return;
	    }
	    //action is actually style number
	    $va_params = $this->_getAllParams();
	    $v_style_number = $va_params['action'];
		$v_style_number = strip_tags($v_style_number);
		$v_style_number = str_replace("'","",$v_style_number);
		$v_style_number = str_replace(" or","",$v_style_number);
		$v_style_number = str_replace(" OR","",$v_style_number);
	    //we don't really care about the rest of the values after style number -- just the normal GET params
	    
		//if($v_style_number=="gkp" || $v_style_number == "30043603"){
			// Ticket TICK:35021
			//Header( "HTTP/1.1 301 Moved Permanently" );
			//Header( "Location: " . "http://" . $_SERVER['SERVER_NAME'] . "/product/30046010/golfsmith-gripping-supply-kit");
		//}
	    $i_ppage = new ProductPage($v_style_number);	
	    $i_ppage->displayPage();
			  
  }
   /*
   * Author Chantrea (10/18/2012) for Ajax Call get New SKU Number post to GA Tracking
   * Updated Hosam 2-2-2015 for the ATP service 
   */
  public function getskunumberAction(){
  	
  	$i_request = $this->getRequest();  
  	$inv_item_id = strip_tags($i_request->getParam('inv_item_id')); 
  	global $connect_mssql_db;
    $connect_mssql_db = 1;   
    $i_site_init = new SiteInit('product');
    $i_site_init->loadInit($v_connect_mssql);
    // adding the get ATP here 
  	$skunumber = ProductPage::getSKUNumber($inv_item_id);
	
    $checkATP = ProductPage::checkATPProc($inv_item_id);

    if ($checkATP["status"] == "INVALID" || $checkATP["totalQTY"] < 1){
    	// there are some error or there are no quintity
    	$return["status"]="invalid";
    	$return["skunumber"]=$skunumber;
    	$return["msg"]="This Item is out of stock"; // here is the msg for you in case something wrong
    } elseif ($checkATP["status"] == "VALID"){
    	$return["status"]="valid";
    	$return["skunumber"] = $skunumber;
    	$return["msg"]= $checkATP["MSG"]; // here is here the message of the status depond on some logic here
    	$return["promisedDate"]= $checkATP["promisedDate"];
    }elseif ($checkATP["status"]	== "OLDCODE"){
    	$return["status"]="valid";
    	$return["skunumber"] = $skunumber;
    	$return["msg"]= date(" F j, Y",strtotime($checkATP["promise_date"]));
    }
    
  	echo json_encode($return);  	  
  }
  
     /*
   * Author Chantrea (10/18/2012) for Ajax Call get New SKU Number post to GA Tracking
   * Updated Hosam 2-2-2015 for the ATP service 
   */
  public function getskumsgAction(){
  	
  	$i_request = $this->getRequest();  
  	$inv_item_id = strip_tags($i_request->getParam('inv_item_id')); 
  	$qty = strip_tags($i_request->getParam('qty'));
    $i_site_init = new SiteInit('product');
    $i_site_init->loadInit($v_connect_mssql);
    // adding the get ATP here 
	
    $checkATP = ProductPage::checkATPMSGProc($inv_item_id,$qty);

    if ($checkATP["status"] == "INVALID" || $checkATP["totalQTY"] < 1){
    	// there are some error or there are no quintity
    	$return["status"]="invalid";
    	$return["msg"]="This Item is out of stock"; // here is the msg for you in case something wrong
    } else{
		if ($checkATP["totalQTY"] < $qty ){
			$return["status"]="outofstock";
    		$return["msg"]= $checkATP["MSG"]; // here is here the message of the status depond on some logic here 
		}else{
    		$return["status"]="valid";
    		$return["msg"]= $checkATP["MSG"]; // here is here the message of the status depond on some logic here 
		}
    }
    
  	echo json_encode($return);  	  
  }
  
  public function validateqbdAction() {
   
  	$i_request = $this->getRequest();
    $v_qbd_ordered_qty = strip_tags($i_request->getParam('qbd_ordered_qty'));    
    $v_qbd_style = strip_tags($i_request->getParam('qbd_style'));
    $v_qbd_long_desc = strip_tags($i_request->getParam('qbd_long_desc'));
    $v_qbd_price = strip_tags($i_request->getParam('qbd_price'));
    
    $i_site_init = new SiteInit('product');
    $i_site_init->loadInit($v_connect_mssql);
    
    $objProduct = new ProductPage($v_qbd_style);
    $v_QBD_info = $objProduct->get_QBD_info();

    if ($v_QBD_info['active'] != 'Y'){
    	echo 'Sorry, we do not have any more of the ' . $v_qbd_long_desc . ' at the $' . $v_qbd_price . ' Hot Deal price; the last of the available quantity has just been sold. If you still wish to buy this item you may do so at the regular selling price. Please click the "OK" button to continue.';
    }else {
    	echo 'Success';
    }
        
  }
  
  public function validateatpAction() {  	
  	$i_request = $this->getRequest();
  	$v_style = strip_tags($i_request->getParam('style'));
    $v_ordered_qty = strip_tags($i_request->getParam('ordered_qty'));
    $v_iid = strip_tags($i_request->getParam('inv_id'));
    $v_org_id = strip_tags($i_request->getParam('org_id'));
    
    global $connect_mssql_db;
    $connect_mssql_db = 1;
  	$i_site_init = new SiteInit('product');
    $i_site_init->loadInit($v_connect_mssql);
    
    $objProduct = new ProductPage($v_style);
    
    $v_atp = $objProduct->validateATP($v_iid, $v_org_id);
    
    if (($v_atp >= 0) && ($v_atp < $v_ordered_qty)){
    	echo 'We\'re sorry, but the quantity you requested is not in stock. Please enter a lower quantity and try again';
    }else {
    	echo 'Success';
    }
    
  }
  
  public function customclubAction(){
        $cc_obj = new CustomClub ();
        $i_request = $this->getRequest();
        $v_style = strip_tags($i_request->getParam('customStyleNo'));
        $v_iid = strip_tags($i_request->getParam('ii'));
        $v_styleNo = strip_tags($i_request->getParam('styleNo'));
        $v_brandName = strip_tags($i_request->getParam('brandName'));
        $v_justStyle = strip_tags($i_request->getParam('justStyle'));
        
        
       // ob_start();
       $outPut = $cc_obj->DisplayCustomizeClub ($v_style,$v_iid,$v_styleNo,$v_brandName,$v_justStyle); 

       if ( $outPut == "true"){
           $jsonresponse["CustomClubSet"]="True";
           $jsonresponse["CustomClubLink"]="/customclub";
       }else{
            $jsonresponse["CustomClubSet"]="False";
            $jsonresponse["CustomClubHtml"] = $outPut;
       }
       

        header('Content-type: application/json');
        echo (json_encode($jsonresponse));
        
    
  }
  
  public function lieangleoptionsAction() {
      $i_request = $this->getRequest ();
  
      $v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
      $v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
      $v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
  
      $cc_club_obj = new CustomClub();
      $va_lie_angle_options = $cc_club_obj->GetLieAngleOptions($v_session_id, $v_model, $v_dexterity);
  
      /****************
       * 		inventory_item_id,
       hand,
       min_lie_angle,
       max_lie_angle,
       lie_increment,
       standard_lie_angle
      */
      $options = "";
  
      if (is_array($va_lie_angle_options) && !empty($va_lie_angle_options)) {
          if ($va_lie_angle_options[0]["standard_lie_angle"] != "") {
              $v_standard_lie_angle = $va_lie_angle_options[0]["standard_lie_angle"];
          } else { // it's absent so we need a bit of math to figure out the "standard"
              $v_standard_lie_angle = 0;
          }
          for($x=$va_lie_angle_options[0]["min_lie_angle"];$x<=$va_lie_angle_options[0]["max_lie_angle"];$x+=$va_lie_angle_options[0]["lie_increment"]) {
              if ($x<0) {
                  $options .= /*(number_format($v_standard_lie_angle,2)+$x) . */ " Flat (" . number_format($x,2) . ")" . "|";
              } elseif ($x == 0 || number_format($x,1) == 0.0) {
                  $options .= /* number_format($v_standard_lie_angle,2) . */ " Standard (" . number_format($x,2) . ")" . "|";
              } else {
                  $options .= /*(number_format($v_standard_lie_angle,2)+$x) . */ " Upright (+" . number_format($x,2) . ")" . "|";
              }
          }
          $options = rtrim($options,"|");
      } else {
          $options = "Standard";
      }
  
      echo $options;
  }
  
  public function clubheadoptionsAction() {
      $i_request = $this->getRequest ();
  
      $v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
      $v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
      $v_club_type = strip_tags( $i_request->getParam('p_club_type'));
  
      $cc_club_obj = new CustomClub();
      $va_club_head_options = $cc_club_obj->getClubHeadOptions($v_model, $v_dexterity);
      #print_r($va_club_head_options);
      // as a backup find out the normal club retail price for these club models
      $v_retail_price = $cc_club_obj->getClubModelPrice($v_model);
  
      //echo $va_club_head_options;
      if (is_array($va_club_head_options) && !empty($va_club_head_options)) {
          $va_clubheads = array();
          $va_tempclubheads = array();
          $va_sets = array();
          $va_clubset = array();
          $va_tempsets = array();
          //echo "<pre>";
          //print_r($va_club_head_options);
          //echo "</pre>";
          foreach($va_club_head_options as $key => $value) {
              // loop through the record set and find the dist
              $va_sets[$value['set_id']][] = $value['description'];
              $va_clubheads[] = $value['description'];
              // Force the $value['set_retail'] to be 0 all the time for now. In the future remove it, once the code can handle the set
              //$value['set_retail'] = 0;
              // the combined list over ALL club possibilities
              $va_clubset[$value['head_id']]['head_id'] 			= $value['head_id'];
              $va_clubset[$value['head_id']]['club_type'] 		= strtolower(str_replace("/","",$value['club_type']));
              $va_clubset[$value['head_id']]['ind_retail'] 		= $value['ind_retail'];
              $va_clubset[$value['head_id']]['set_retail'] 		= $value['set_retail'];
              $va_clubset[$value['head_id']]['description'] 		= $value['description'];
              $va_clubset[$value['head_id']]['hybrid_flag'] 		= $value['hybrid_flag'];
              $va_clubset[$value['head_id']]['availability_date']	= $value['availability_date'];
          }
          $va_tempclubheads = array_unique($va_clubheads);
          #echo "<pre>";
          #print_r($va_sets);
          #echo "</pre>";
          	
          $va_clubheads = array(); // reset
          $va_clubheads = array_values($va_tempclubheads);
          #print_r($va_club_head_options);
          foreach($va_sets as $set_id => $list) {
              $v_tempsetlist = "";
              $list = array_unique($list);
              foreach($list as $index => $head_id) {
                  $v_tempsetlist .= $head_id . ",";
              }
              $v_tempsetlist = rtrim($v_tempsetlist,",");
              $va_tempsets[$set_id] = $v_tempsetlist;
          }
          //print_r($va_tempsets);
          $va_tempsets = array_unique($va_tempsets);
          #print_r($va_tempsets);
          	
      }
  
      // single out the sets if there are more than one set
      // to do the next check have to be answered 
      $v_club_set_count = 0;
      if (is_array($va_tempsets) && !empty($va_tempsets)) {
          if (sizeof($va_tempsets)>0 and (strtolower($v_club_type) == "irons" || strtolower($v_club_type) == "combo/hybrid" || strtolower($v_club_type) == "combohybrid" ) ) {
              $va_tempsets = array_unique($va_tempsets);
              echo "<strong>Possible Sets:</strong><br>";
              $listcount = 0;
  
              foreach($va_tempsets as $selection => $list) {
                  echo "<input type=\"hidden\" name=\"" . strtolower(str_replace("/","",$v_club_type)) . "_" . $listcount . "_set\" id=\"" . strtolower(str_replace("/","",$v_club_type)) . "_" . $listcount . "_set\" value=\"" . $list . "\" />\r\n";
                  echo (++$listcount) . ") " . $list . "<BR>";
              }
              $v_club_set_count = sizeof ($va_tempsets);
          }
      }
      echo "<input type=\"hidden\" name=\"" . strtolower(str_replace("/","",$v_club_type)) . "_set_count\" id=\"" . strtolower(str_replace("/","",$v_club_type)) . "_set_count\" value=\"" . $v_club_set_count . "\" />";
      #echo "<pre>";
      #print_r($va_clubset);
      #echo "</pre>";
      $options = "";
      $index = 0;
      $index2= 0 ;
      if (is_array($va_clubset) && !empty($va_clubset)) {
          $clubCount = count ($va_clubset);
          $clusSplitCount = 0;
          $options .=  "<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\">";
          $options .= "
					<tr>
						<th style=\"padding:2px;\" colspan=\"2\"><strong>Club</strong></th>
						<th style=\"padding:2px; text-align:right;\"><strong>Ind. Price</strong></th>
                        <th style=\"padding:2px; text-align:right;\"><strong>";
          if ($value['set_retail'] != 0 ){
			$options .= "Set Price*";
          }
          $options .= "</strong></th>";
          
          if ($clubCount>3){
              $clusSplitCount= floor($clubCount/2);
              $options .= "
						<th style=\"padding:2px;\" colspan=\"2\"><strong>Club</strong></th>
						<th style=\"padding:2px; text-align:right;\"><strong>Ind. Price</strong></th>
                        <th style=\"padding:2px; text-align:right;\"><strong>";
          if ($value['set_retail'] != 0 ){
			$options .= "Set Price*";
          }
          $options .= "</strong></th>";
          }
          $options .= " </tr>";
          foreach($va_clubset as $head_id => $value) {
              $tempCLubArr[$index2]=$value;
              $index2++;
          }
          foreach($tempCLubArr as $key => $value) {
              // attempt to override and detect if the set relationship exists.
              $v_setprice = str_replace("$0.00","",format_currency($value['set_retail']));
              //hosam
              //echo $key." =>". $value."=".$clusSplitCount;
              if ($clusSplitCount == 0 || $clusSplitCount > $index){	
                $options .= "
					<tr valign=\"top\">
						<td style=\"padding:2px;\">
							<input
								type=\"checkbox\"
								name=\"_club_head_id_" . $index . "\"
								id=\"_club_head_id_" . $index . "\"
								value=\"" . $value["head_id"] . "\"
								onKeyUp=\"javascript: total_amount_cusclub(); findOldestDate();\"
								onClick=\"javascript: total_amount_cusclub(); findOldestDate();\"
                            >
							<input type=\"hidden\" name=\"_club_head_description_" . $index . "\" id=\"_club_head_description_" . $index . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"_club_head_hybrid_flag_" . $index . "\" id=\"_club_head_hybrid_flag_" . $index . "\" value=\"" . $value['hybrid_flag'] . "\" />
							<input type=\"hidden\" name=\"_club_head_set_price_" . $index . "\" id=\"_club_head_set_price_" . $index . "\" value=\"" . $value['set_retail'] . "\" />
							<input type=\"hidden\" name=\"_club_head_ind_price_" . $index . "\" id=\"_club_head_ind_price_" . $index . "\" value=\"" . $value['ind_retail'] . "\" />
							<input type=\"hidden\" name=\"_club_head_name_" . $index . "\" id=\"_club_head_name_" . $index . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"_availability_date_" . $index . "\" id=\"_availability_date_" . $index . "\" value=\"" . strtotime($value['availability_date']) . "\" />
						</td>
						<td style=\"padding:2px;\">" . $value["description"] . "</td>
						<td style=\"padding:2px; text-align:right;\">" . format_currency($value['ind_retail']) . "</td>
  						<td style=\"padding:2px; text-align:right;\" >$v_setprice</td>
  						";
              }
              if ($clubCount>3){
                  $key2 = $key + $clusSplitCount;
                  $value = $tempCLubArr[$key2];
                  $index3= $index + $clusSplitCount;
                  //var_dump(__LINE__,$value,$key2,$index);
                  $options .= "<td style=\"padding:2px;\">
							<input
								type=\"checkbox\"
								name=\"_club_head_id_" . $index3 . "\"
								id=\"_club_head_id_" . $index3 . "\"
								value=\"" . $value["head_id"] . "\"
								onKeyUp=\"javascript: total_amount_cusclub(); findOldestDate();\"
								onClick=\"javascript: total_amount_cusclub(); findOldestDate();\"
                            >
							<input type=\"hidden\" name=\"_club_head_description_" . $index3 . "\" id=\"_club_head_description_" . $index3 . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"_club_head_hybrid_flag_" . $index3 . "\" id=\"_club_head_hybrid_flag_" . $index3 . "\" value=\"" . $value['hybrid_flag'] . "\" />
							<input type=\"hidden\" name=\"_club_head_set_price_" . $index3 . "\" id=\"_club_head_set_price_" . $index3 . "\" value=\"" . $value['set_retail'] . "\" />
							<input type=\"hidden\" name=\"_club_head_ind_price_" . $index3 . "\" id=\"_club_head_ind_price_" . $index3 . "\" value=\"" . $value['ind_retail'] . "\" />
							<input type=\"hidden\" name=\"_club_head_name_" . $index3 . "\" id=\"_club_head_name_" . $index3 . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"_availability_date_" . $index3 . "\" id=\"_availability_date_" . $index3 . "\" value=\"" . strtotime($value['availability_date']) . "\" />
						</td>
						<td style=\"padding:2px;\">" . $value["description"] . "</td>
						<td class='ind_price' style=\"padding:2px; text-align:right;\">" . format_currency($value['ind_retail']) . "</td>
                  						<td style=\"padding:2px; text-align:right;\" >$v_setprice</td>";
              }
              $options .= "</tr>";
              if ($key2 == ($index2 -1)) {
                  $index = ++$index3;
                  break;
              }
              $index++;
              $v_club_type = $value["club_type"];
          }
          $options .= "</table>
			<input type=\"hidden\" name=\"_club_head_index\" id=\"_club_head_index\" value=\"$index\" /> <br>";
			if ($value['set_retail'] != 0 ){
  			   $options .=" <div class=\"CCM_birdseed\" style=\"$v_setprice\">*Set price applies to combo/hybrid iron sets only and is applied when the individual clubs
  			   selected complete a qualifying set composition. Qualifying set compositions are determined
  			   by the manufacturer and vary by make and model. <a href=\"#\">See qualifying set compositions</a></div>";
			}
      } else {
          $options = "
				<input type=\"hidden\" name=\"_club_head_index\" id=\"_club_head_index\" value=\"$index\" />
  				<input type=\"hidden\" name=\"_club_head_ind_price_" . $index . "\" id=\"_club_head_ind_price_" . $index . "\" value=\"" . $v_retail_price . "\" />
				<input type=\"hidden\" name=\"_club_head_name_" . $index . "\" id=\"_club_head_name_" . $index . "\" value=\"\" />
				Club Heads aren't available for this hand selection yet.";
      }

  
      echo $options;
  }
  
  
  
  public function clubheadoptionscustAction() {
      $i_request = $this->getRequest ();
  
      $v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
      $v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
      $v_club_type = strip_tags( $i_request->getParam('p_club_type'));
  
      $cc_club_obj = new CustomClub();
      $va_club_head_options = $cc_club_obj->getClubHeadOptions($v_model, $v_dexterity);
      #print_r($va_club_head_options);
      // as a backup find out the normal club retail price for these club models
      $v_retail_price = $cc_club_obj->getClubModelPrice($v_model);
  
      //echo $va_club_head_options;
      if (is_array($va_club_head_options) && !empty($va_club_head_options)) {
          $va_clubheads = array();
          $va_tempclubheads = array();
          $va_sets = array();
          $va_clubset = array();
          $va_tempsets = array();
          //echo "<pre>";
          //print_r($va_club_head_options);
          //echo "</pre>";
          foreach($va_club_head_options as $key => $value) {
              // loop through the record set and find the dist
              $va_sets[$value['set_id']][] = $value['description'];
              $va_clubheads[] = $value['description'];
              // Force the $value['set_retail'] to be 0 all the time for now. In the future remove it, once the code can handle the set
              //$value['set_retail'] = 0;
              // the combined list over ALL club possibilities
              $va_clubset[$value['head_id']]['head_id'] 			= $value['head_id'];
              $va_clubset[$value['head_id']]['club_type'] 		= strtolower(str_replace("/","",$value['club_type']));
              $va_clubset[$value['head_id']]['ind_retail'] 		= $value['ind_retail'];
              $va_clubset[$value['head_id']]['set_retail'] 		= $value['set_retail'];
              $va_clubset[$value['head_id']]['description'] 		= $value['description'];
              $va_clubset[$value['head_id']]['hybrid_flag'] 		= $value['hybrid_flag'];
              $va_clubset[$value['head_id']]['availability_date']	= $value['availability_date'];
          }
          $va_tempclubheads = array_unique($va_clubheads);
          #echo "<pre>";
          #print_r($va_sets);
          #echo "</pre>";
           
          $va_clubheads = array(); // reset
          $va_clubheads = array_values($va_tempclubheads);
          #print_r($va_club_head_options);
          foreach($va_sets as $set_id => $list) {
              $v_tempsetlist = "";
              $list = array_unique($list);
              foreach($list as $index => $head_id) {
                  $v_tempsetlist .= $head_id . ",";
              }
              $v_tempsetlist = rtrim($v_tempsetlist,",");
              $va_tempsets[$set_id] = $v_tempsetlist;
          }
          //print_r($va_tempsets);
          $va_tempsets = array_unique($va_tempsets);
          #print_r($va_tempsets);
           
      }
  
      // single out the sets if there are more than one set
      // to do the next check have to be answered
      $v_club_set_count = 0;
      if (is_array($va_tempsets) && !empty($va_tempsets)) {
          if (sizeof($va_tempsets)>0 and (strtolower($v_club_type) == "irons" || strtolower($v_club_type) == "combo/hybrid" || strtolower($v_club_type) == "combohybrid" ) ) {
              $va_tempsets = array_unique($va_tempsets);
              echo '<div id="modal_possible_sets" class="modal_possible_sets" title="Possible Sets" >';
              echo '<p>
                        Set price applies to combo/hybrid iron sets only and is applied when the individual clubs
    			         selected complete a qualifying set composition. Qualifying set compositions are determined
    			         by the manufacturer and vary by make and model.
                  </p>';
              echo "<h6>Qualifying set compositions include:</h6>";
              $listcount = 0;
  
              //$v_club_type = '';
              echo "<ol>";
              foreach($va_tempsets as $selection => $list) {
                  echo "<li>";
                  echo "<input type=\"hidden\" name=\"" . strtolower(str_replace("/","",$v_club_type)) . "_" . $listcount . "_set\" id=\"" . strtolower(str_replace("/","",$v_club_type)) . "_" . $listcount . "_set\" value=\"" . $list . "\" />\r\n";
                  echo  $list . "</li>";
                  $listcount++;
              }
              echo "</ol>";
              $v_club_set_count = sizeof ($va_tempsets);
              echo '</div>';
          }
      }
      echo "<input type=\"hidden\" name=\"" . strtolower(str_replace("/","",$v_club_type)) . "_set_count\" id=\"" . strtolower(str_replace("/","",$v_club_type)) . "_set_count\" value=\"" . $v_club_set_count . "\" />";
      #echo "<pre>";
      #print_r($va_clubset);
      #echo "</pre>";
      $options = "";
      $index = 0;
      $index2= 0 ;
      if (is_array($va_clubset) && !empty($va_clubset)) {
          $clubCount = count ($va_clubset);
          $clusSplitCount = 0;
          $options .=  "<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\">";
          $options .= "
					<tr>
						<th style=\"padding:2px;\" colspan=\"2\"><strong>Club</strong></th>
						<th style=\"padding:2px; text-align:right;\"><strong>Ind. Price</strong></th>
                        <th style=\"padding:2px; text-align:right;\"><strong>";
          if ($value['set_retail'] != 0 ){
              $options .= "Set Price* <i onclick='return show_modal_possible_sets()' class='fa fa-question-circle'></i>";
          }
          $options .= "</strong></th>";
  
          if ($clubCount>3){
              $clusSplitCount= floor($clubCount/2);
              $options .= "
						<th style=\"padding:2px;\" colspan=\"2\"><strong>Club</strong></th>
						<th style=\"padding:2px; text-align:right;\"><strong>Ind. Price</strong></th>
                        <th style=\"padding:2px; text-align:right;\"><strong>";
              if ($value['set_retail'] != 0 ){
                  $options .= "Set Price* <i onclick='return show_modal_possible_sets()' class='fa fa-question-circle'></i>";
              }
              $options .= "</strong></th>";
          }
          $options .= " </tr>";
          foreach($va_clubset as $head_id => $value) {
              $tempCLubArr[$index2]=$value;
              $index2++;
          }
          foreach($tempCLubArr as $key => $value) {
              // attempt to override and detect if the set relationship exists.
              $v_setprice = str_replace("$0.00","",format_currency($value['set_retail']));
              //hosam
              //echo $key." =>". $value."=".$clusSplitCount;
              if ($clusSplitCount == 0 || $clusSplitCount > $index){
                  $options .= "
					<tr valign=\"top\">
						<td style=\"padding:2px;\">
							<input
								type=\"checkbox\"
								name=\"_club_head_id_" . $index . "\"
								id=\"_club_head_id_" . $index . "\"
								value=\"" . $value["head_id"] . "\"
								onKeyUp=\"javascript: total_amount_cusclub(); findOldestDate();\"
								onClick=\"javascript: total_amount_cusclub(); findOldestDate();\"
                            >
							<input type=\"hidden\" name=\"_club_head_description_" . $index . "\" id=\"_club_head_description_" . $index . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"_club_head_hybrid_flag_" . $index . "\" id=\"_club_head_hybrid_flag_" . $index . "\" value=\"" . $value['hybrid_flag'] . "\" />
							<input type=\"hidden\" name=\"_club_head_set_price_" . $index . "\" id=\"_club_head_set_price_" . $index . "\" value=\"" . $value['set_retail'] . "\" />
							<input type=\"hidden\" name=\"_club_head_ind_price_" . $index . "\" id=\"_club_head_ind_price_" . $index . "\" value=\"" . $value['ind_retail'] . "\" />
							<input type=\"hidden\" name=\"_club_head_name_" . $index . "\" id=\"_club_head_name_" . $index . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"_availability_date_" . $index . "\" id=\"_availability_date_" . $index . "\" value=\"" . strtotime($value['availability_date']) . "\" />
						</td>
						<td style=\"padding:2px;\">" . $value["description"] . "</td>
						<td class='ind_price' style=\"padding:2px; text-align:right;\">" . format_currency($value['ind_retail']) . "</td>
  						<td style=\"padding:2px; text-align:right;\" >$v_setprice</td>
  						";
              }
              if ($clubCount>3){
              $key2 = $key + $clusSplitCount;
              $value = $tempCLubArr[$key2];
              $index3= $index + $clusSplitCount;
              //var_dump(__LINE__,$value,$key2,$index);
              $options .= "<td style=\"padding:2px;\">
							<input
								type=\"checkbox\"
								name=\"_club_head_id_" . $index3 . "\"
								id=\"_club_head_id_" . $index3 . "\"
								value=\"" . $value["head_id"] . "\"
  								    onKeyUp=\"javascript: total_amount_cusclub(); findOldestDate();\"
								onClick=\"javascript: total_amount_cusclub(); findOldestDate();\"
                            >
							<input type=\"hidden\" name=\"_club_head_description_" . $index3 . "\" id=\"_club_head_description_" . $index3 . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"_club_head_hybrid_flag_" . $index3 . "\" id=\"_club_head_hybrid_flag_" . $index3 . "\" value=\"" . $value['hybrid_flag'] . "\" />
							<input type=\"hidden\" name=\"_club_head_set_price_" . $index3 . "\" id=\"_club_head_set_price_" . $index3 . "\" value=\"" . $value['set_retail'] . "\" />
  							<input type=\"hidden\" name=\"_club_head_ind_price_" . $index3 . "\" id=\"_club_head_ind_price_" . $index3 . "\" value=\"" . $value['ind_retail'] . "\" />
  							<input type=\"hidden\" name=\"_club_head_name_" . $index3 . "\" id=\"_club_head_name_" . $index3 . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"_availability_date_" . $index3 . "\" id=\"_availability_date_" . $index3 . "\" value=\"" . strtotime($value['availability_date']) . "\" />
						</td>
              <td style=\"padding:2px;\">" . $value["description"] . "</td>
                  <td style=\"padding:2px; text-align:right;\">" . format_currency($value['ind_retail']) . "</td>
                  <td style=\"padding:2px; text-align:right;\" >$v_setprice</td>";
              }
              $options .= "</tr>";
              if ($key2 == ($index2 -1)) {
              $index = ++$index3;
                  break;
          }
          $index++;
          $v_club_type = $value["club_type"];
          }
          $options .= "</table>
          <input type=\"hidden\" name=\"_club_head_index\" id=\"_club_head_index\" value=\"$index\" /> <br>";
          if ($value['set_retail'] != 0 ){
    			   $options .=" <div class=\"CCM_birdseed\" style=\"$v_setprice\">*Set price applies to combo/hybrid iron sets only and is applied when the individual clubs
    			   selected complete a qualifying set composition. Qualifying set compositions are determined
    			   by the manufacturer and vary by make and model. <a href=\"#\" onclick='return show_modal_possible_sets()'>See qualifying set compositions</a></div>";
    			   }
    			   } else {
    			   $options = "
    			   <input type=\"hidden\" name=\"_club_head_index\" id=\"_club_head_index\" value=\"$index\" />
    			   <input type=\"hidden\" name=\"_club_head_ind_price_" . $index . "\" id=\"_club_head_ind_price_" . $index . "\" value=\"" . $v_retail_price . "\" />
    			   <input type=\"hidden\" name=\"_club_head_name_" . $index . "\" id=\"_club_head_name_" . $index . "\" value=\"\" />
				Club Heads aren't available for this hand selection yet.";
      }
      
      
      

      $options .= " <script>
            $(function(){
      
            	var prices = product_list[1]['sku_prices'];
          
            	for(var i = 0; i<prices.length; i++){
                	if(prices[i] == \"N\"){
               		 $(\".ind_price\").text('See Cart');
              		  break;
                	}
            	}
            });
          </script>
          ";
  
       echo $options;
  }
  
  /********************************
   * Function: gripoptionsAction
   * Purpose: Retrieves grip model options for said fitting session, model, and dexterity
   * Called From::
   * 	web: /customclub/changeDexterity ~ javascript/custom_club.js
   * 	Function: shaft_changed()
   * Comment Date: 4/26/2010
   * Comment Author: Robbie Smith
   */
  
  public function gripoptionsAction() {
      $i_request = $this->getRequest ();
  
      //$v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
      $v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
      $v_style = strip_tags ( $i_request->getParam ( 'cusStyle' ) );
      $v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
  
      $cc_club_obj = new CustomClub();
      $va_grip_options = $cc_club_obj->GetGripsClubs($v_model, $v_dexterity);
  
      $options = "";
      // redraw the grip model select feature...
      if (is_array($va_grip_options) && !empty($va_grip_options)) {
          $options = "
				<select name=\"_grip_id\"
				  id=\"_grip_id\"
				  onChange=\"JavaScript: grip_changedclubcust('ps','" . $v_model . "','" . strtolower($va_grip_options[0]['club_type']) . "',this.value);return total_amount_cusclub(); findOldestDate();\"
				  class=\"formbox\">
				<option value=\"0\">Select Grip</option>
			";
          $v_grip_hidden_options = "
				<input type=\"hidden\"
					name=\"_grip_id_0\"
					id=\"_grip_id_0\"
					value=\"0\" />";
          $availability_dates = "";
          $grip_count = 0;
          foreach($va_grip_options as $key => $value) {
              $options .= "<option value=\"" . $value['grip_id'] . "\">" . $value['description'] . " - " . format_currency($value['retail_price']) . "</option>";
              $v_grip_hidden_options .= '
					<input type="hidden" name="_grip_id_' . $value['grip_id'] . '"
						   id="_grip_id_' . $value['grip_id'] . '" value="' . $value['retail_price'] . '" />
					<input type="hidden" name="_grip_name_id_' . $value['grip_id'] . '"
						   id="_grip_name_id_' . $value['grip_id'] . '" value="' . $value['description'] . '" />
				';
              $availability_dates .= "<input type=\"hidden\" name=\"" . $value['grip_id'] . "_grip_availability_date_" . $grip_count . "\" id=\"" . $value['grip_id'] . "_grip_availability_date_" . $grip_count++ . "\" value=\"" . strtotime($value['availability_date']) . "\" />";
              	
          }
          $options .= "</select>";
          echo "<input type=\"hidden\" name=\"" . $value['grip_id'] . "_grip_count\" id=\"" . $value['grip_id'] . "_grip_count\" value=\"" . sizeof($va_grip_options) . "\" />";
          echo $availability_dates;
          echo $v_grip_hidden_options;
      } else {
          $options = "<select class=\"formbox\"><option>Not Available</option></select>";
      }
  
      //$options .=" &nbsp; &nbsp; <a href=\"javascript:openBrWindow('/display_page.php?page_num=ccm_glossary&amp;hdr=N#Grip','',features);\">Help</a>";
  
      echo $options;
  }
  
  /********************************
   * Function: shaftflexoptionsAction
   * Purpose: Retrieves flex options for said fitting session, trajectory, model, and shaft
   * Called From::
   * 	web: /customclub/shaftflexoptions ~ javascript/custom_club.js
   * 	Function: shaft_changed()
   * Comment Date: 3/25/2010
   * Comment Author: Robbie Smith
   */
  
  public function shaftflexoptionsAction() {
      $i_request = $this->getRequest ();
  
      $v_trajectory = strip_tags ( $i_request->getParam ( 'p_trajectory' ) );
      $v_combo_iron = strip_tags ( $i_request->getParam ( 'p_combo_iron' ) );
      $v_combo_hyrbid = strip_tags ( $i_request->getParam ( 'p_combo_hybrid' ) );
      $v_model = strip_tags ( $i_request->getParam ( 'p_model' ) );
      $v_club_type_value = strip_tags ( $i_request->getParam ( 'p_club_type_value' ) );
      $v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
  
  
      $cc_club_obj = new CustomClub();
      
      //This is to get cost price
      $_SESSION["selectedShaftId"] = $v_club_type_value;
      
      
      $va_flex_options = $cc_club_obj->GetFlexOptions($v_session_id, $v_trajectory, $v_model, $v_club_type_value,$v_dexterity);
      $options = "";
      if (is_array($va_flex_options) && !empty($va_flex_options)) { // list the flex options given by the shaft selection
          if ($v_combo_iron)
              echo "Irons: ";
          if ($v_combo_hyrbid) {
              echo "Hybrids: ";
              $appendation = "_combo";
          }
          echo "<select name=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"  id=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\" class=\"formbox\" onchange=\"findOldestDate();\">";
          $availability_dates = "";
          $flex_count = 0;
          foreach($va_flex_options as $value) {
              echo "<option value=\"" . $value['flex'] . "\">" . $value['flex'] . "</option>";
              $availability_dates .= "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_availability_date_" . $flex_count . "\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_availability_date_" . $flex_count++ . "\" value=\"" . strtotime($value['availability_date']) . "\" />";
          }
          echo "</select>";
          echo "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" value=\"" . sizeof($va_flex_options) . "\" />";
          echo $availability_dates;
         // echo "&nbsp; &nbsp;<a href=\"javascript:openBrWindow('/display_page.php?page_num=ccm_glossary&amp;hdr=N#ShaftFlex','',features);\">Help</a>";
      } else { // no shaft flex options available
          if ($v_combo_iron)
              echo "Irons: ";
          if ($v_combo_hyrbid) {
              echo "Hybrids: ";
              $appendation = "_combo";
          }
          echo "<select name=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"  id=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"  class=\"formbox\">";
          echo "<option value=\"\">No shaft flex options</option>";
          echo "</select>";
          echo "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" value=\"0\" />";
          //echo "&nbsp; &nbsp;<a href=\"javascript:openBrWindow('/display_page.php?page_num=ccm_glossary&amp;hdr=N#ShaftFlex','',features);\">Help</a>";
      }
  
  
  }
  
  /********************************
   * Function: gripsizesAction
   * Purpose: Retrieves lie angle options for said fitting session, model, and dexterity
   * Called From::
   * 	web: /customclub/shaftflexoptions ~ javascript/custom_club.js
   * 	Function: shaft_changed()
   * Comment Date: 3/29/2010
   * Comment Author: Robbie Smith
   */
  
  public function gripsizesAction() {
      $i_request = $this->getRequest ();
  
      $v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
      $v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
      $v_grip_id = strip_tags ( $i_request->getParam ( 'p_grip_id' ) );
      $v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
  
      $cc_club_obj = new CustomClub();
      
      
      
      $_SESSION["selectedGripId"] = $v_grip_id;
      
      $va_gripsizes = $cc_club_obj->GetAvailableGripSizes($v_session_id, $v_model, $v_grip_id, $v_dexterity);
  
      /****************
       *
       modelgrips.grip_id,
       clubs.club_id,
       clubs.fitting_id,
       clubs.club_type,
       clubs.manufacturer,
       clubs.model,
       grips.description,
       options.record_value
      */
      $options = "";
      if (is_array($va_gripsizes) && !empty($va_gripsizes)) {
          foreach($va_gripsizes as $key => $value) {
              $options .= $value['grip_size'] . "|";
  
          }
          $options = rtrim($options,"|");
      } else {
          $options = "Standard";
      }
  
      echo $options;
  }
  
  /**************************
   * Function: savecustomizeAction
   * Purpose: sends all data fields and collected values from the customize screen as one big parameter to a custom club object for parsing and saving
   * Called From::
   *  web: /customclub/clubselectionsave ~ /javascript/custom_club.js
   *  Function: continueToClubSelection()
   * Comment Date: 3/10/2010
   * Comment Author: Robbie Smith
   */
  
  public function savecustomizeAction() {
  
      $cc_obj = new CustomClub ();
      $i_request = $this->getRequest ();
  
      $v_customize_parameters = strip_tags ( $i_request->getParam ( 'customize_parameters' ) );
      $v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
      $cc_obj->_session_id = $v_session_id;
      $va_fitting = array();
      $va_fitting = $cc_obj->GetMyFitting();
      $cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object
      // may gain access to this incase someone goes
      // back, they can quickly get their values again
  
      $cc_obj->SaveCustomizationsCusClub ( $v_customize_parameters );
  
  
  }
  
  public function addtocartclubAction() {
      $cc_obj = new CustomClub ();
  
      $i_request = $this->getRequest ();
      $v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
  
      $cc_obj->AddToCartClub($v_session_id);
      //$cc_obj->AddToCartPopUpWindow($v_session_id);
  }
  
  /**************************
   * Function: outofstockAction
   * Purpose: gets called if an item was attempting to go into the cart but a last minute check claimed 0 quantity; happens rarely
   * Called From::
   *  web: /customclub/addtocartAction ~ /javascript/custom_club.js
   *  Function: AddToCart()
   * Comment Date: 6/23/2010
   * Comment Author: Robbie Smith
   */
  
  public function outofstockAction() {
  
      $cc_obj = new CustomClub ();
      $i_request = $this->getRequest ();
      $v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
      $v_return_status = strip_tags ( $i_request->getParam ( 'return_status' ) );
      $cc_obj->OutOfStock ( $v_session_id,$v_return_status );
      	
  }
  
}
?>
