<?php
/*
 * 
 * @author Hosam Mahmoud
 * @version 0.1
 * @Store controller golfsmith.com/stores/ golfsmith.com/stores/*
 * 
 * 
 */ 
require_once('Zend/Controller/Action.php');
require_once('Zend/View.php');
require_once('models/SiteInit.php');
include_once('models/Slider.php');
//include('gsi_common.inc');
// Ended here the clean stuff
// clean all the $_GET vars
$_GET = filter_input_array(strip_tags(INPUT_GET), FILTER_SANITIZE_STRING);

class SearchController extends Zend_Controller_Action {
	
	private $i_site_init;
	private $store_search;
	private $endecaURL = null;
	
	private function startInit()
	{       
		global $connect_mssql_db;
    	$connect_mssql_db = 1;
    	$this->i_site_init = new SiteInit();
        $this->i_site_init->loadInit();
        $this->z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
	}

	public function __call($p_method, $p_args) {
	    //call indexAction
    	$this->indexAction();
  	}

	public function indexAction() {
		global $override_header_contents;
		$v_port = $v_port = $_SERVER['SERVER_PORT'];
	    if($v_port == '443') {
	      $this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	      return;
	    }
		// google remarketing 
		$g_data["pageType"]="searchresults";
      	$g_data["prodid"]="";
      	$g_data["totalvalue"]="";
      	
	    //action is actually style number
		$this->startInit();
		// get all the paramters
		$va_params = $this->_getAllParams();
		// to clean the precedent part of the url 
		$searhCtlArray = array("/ps", "/ws", "/ol","/cm","/search/","/search","?s="); 
		$varURL = str_replace($searhCtlArray,"",$_SERVER[REQUEST_URI]);
		//NTT should be a straight text search - no logic with the exception of the alternateSearchTerm below 
		if(!isset($va_params['Ntt'])){
			if(strpos($varURL, '?') === false){
				$this->checkCategories($varURL);
			}else{
				$tempURL = explode("?", $varURL);
				$varURL = $tempURL[0];
				$this->checkCategories($varURL);				
			}
		}


 		// Get all the paramters
	    $va_params = $this->_getAllParams();
	    prepareAlltheGET();
		$va_params = array_merge($va_params,$_GET);

	    if (isset($_GET["N"]) && $_GET["N"] != 0){

	    	// here the category is set and will start to play
	    	//$_GET["N"]= str_replace ("N-","",substr( $varURL , strpos($varURL, 'N-') , (strpos($varURL, '?') - strpos($varURL, 'N-')) ));
	    	//if(is_int(strpos($_SERVER[REQUEST_URI],"N-"))){
	    	if(is_int(strpos($_SERVER[REQUEST_URI],"N-")) and !strpos($_SERVER[REQUEST_URI],"MSN-")){
	    		$_GET["N"]= str_replace ("N-","",substr( $_SERVER[REQUEST_URI] , strpos($_SERVER[REQUEST_URI], 'N-') , (strpos($_SERVER[REQUEST_URI], '?') - strpos($_SERVER[REQUEST_URI], 'N-')) ));
	    	   	foreach ($_GET as $key=>$value){
	    			$urlExtension[] = urlEncode(strip_tags(htmlspecialchars($key))) . '=' . str_replace("&#39;","'",str_replace("%2B","+",($value)));
	    		}
	    	}else{
				$replaceArray = array("+","-","_");
	    		$n_searchTerm = ucwords(str_replace($replaceArray," ", $varURL));
	    		$n_searchTerm = $_GET["Ntt"];
	    		$urlExtension = array();
	    	   	foreach ($_GET as $key=>$value){
	    			$urlExtension[] = urlEncode(strip_tags(htmlspecialchars($key))) . '=' . str_replace("&#39;","'",$value);
	    		}
	    	} 
	    	$this->endecaURL = "?".implode("&",$urlExtension);
	    }else if (isset($_GET["Gx"])){
	    	// here the category is set and will start to play 
	    	$_GET["N"]= str_replace ("N-","",substr( $varURL , strpos($varURL, 'N-') , (strpos($varURL, '?') - strpos($varURL, 'N-')) ));
	    	$n_searchTerm = $_GET["Ntt"];
	    	$urlExtension = array();
	    	foreach ($_GET as $key=>$value){
	    		$urlExtension[] = urldecode(strip_tags(htmlspecialchars($key))) . '=' . $value;
	    	}
	    	$this->endecaURL = "?".implode("&",$urlExtension);
	    }else if (strpos($_SERVER[REQUEST_URI],"N-") ){
			$endecaUrlPart = str_replace("/search", "" ,$_SERVER[REQUEST_URI]);
	    	$this->endecaURL = $endecaUrlPart; 	    	// In case there are no search and someone tried to access this controller directly
	    } else if (isset($va_params["Ntt"]) && !isset($va_params["s"])){
	    	$n_searchTerm = $_GET["Ntt"];
	    	$va_params['s'] = alternateSearchTerm($va_params["Ntt"]);
	    	if (empty($va_params["Ntt"])){
	    		header('Location: /');
	    		exit();
	    	}
	    	// this means the are no new search term just old search come with some refinement(filtering)
	    	// we have here tp pull the data directly after make some changes in Ntt to make sure it is clear
	    	// we can get the search term from xml <Property name="endeca:assemblerRequestInformation"><Property name="endeca:searchTerms">
	    	// here will get the new link and get it from Endeca without nay modification except remove all extra unsaft chars
	    	// I have to check it later to make sure it is endeca link with the Ntt will not make any issues
	    	$endecaUrlPart = str_replace("/search", "" ,$_SERVER[REQUEST_URI]);
	    	$this->endecaURL = $endecaUrlPart; 
	    	// here 
	    	$va_params["s"] = urlencode(strip_tags(htmlspecialchars($va_params["Ntt"])));
	    }else if(isset($va_params["s"])){
	    	$n_searchTerm = $_GET["s"];
	    	if (empty($va_params["s"])){
	    		header('Location: /');
	    		exit();
	    	}
	    	// here will make a new search even $va_params["Ntt"] is set beacsue we have here a new search term
	    	// we will clean all the unsafe chars and encode the url for a new call to endeca
	    	// In case there are map terms the next function will replace it
	    	$va_params["s"] = alternateSearchTerm($va_params["s"]);
	    	$va_params["s"] = str_replace(" ","+",htmlspecialchars_decode($va_params["s"]));
	    	$va_params["s"] = str_replace('&#34;',"",($va_params["s"]));
	    	$va_params["s"] = str_replace('&#39;',"'",($va_params["s"]));
	    }else if ( !isset($va_params["Ntt"]) && ! isset($va_params["s"]) && !isset($va_params["N"]) ){
	    	header('Location: /');
	    	exit();
	    }

		// if the cal come from browseIndex();
		if (!is_null($this->endecaURL)){
			$search_base_url = ENDECA_SERVER_URL.$this->endecaURL;
		}else{
			$search_base_url = ENDECA_SERVER_URL."?Dy=1&Nty=1&Ntt=".$va_params["s"];
		}
	
		// the function body 
 		//$search_base_url ="http://gsiendeca05-uat:8006/golfsmith-assembler/xml/pages/browse?Dy=1&Nty=1&Ntt=driver";
 		//echo '<br>';
 		//echo $search_base_url;
 		//echo '<br>';
 		//echo '<br>';
 		$results_xml = file_get_contents(str_replace(" ","+",$search_base_url));
 		// in case I can get the content of the file 
 		if (!$results_xml){
 			$this->i_site_init->loadMain();
 			echo "The service is not available";
 			$this->i_site_init->loadFooter($g_data);
 			exit();
 		}
 		
 		// I have to put exception here to catch any error to conver the file to smileXML object
 		$xml=simplexml_load_string($results_xml);
 		$xml->registerXPathNamespace('ns', 'http://endeca.com/schema/xavia/2010');

 		if (!empty($_GET["Ntt"])){
 			$this->findStyleNum($_GET["Ntt"]);
 			$this->z_view->serach_type = "searchText";
 			$this->z_view->serach_term = $_GET['Ntt'];
 		}

 		//Search Term
 		//Takes into consideration both Ntt and Gx
 		$searchTerm = $xml->xpath('//ns:Property[@name="endeca:searchTerms"]/ns:String');
 		if( !empty($searchTerm[0]) )
 			$this->z_view->serach_term = (string)$searchTerm[0];

		//Build Header H1
		if(!empty($_GET['Gx'])){
            $this->z_view->header_term = ucwords(strtolower((getForecastDesc(post_or_get('Gx')))));
        }elseif( "" != $this->z_view->serach_term ) {
			//get H1 from search terms
			$this->z_view->header_term = $this->z_view->serach_term;
        }else{
			//get h1 from dimensionValues
			$theHeader = $xml->xpath('//ns:Property[@name="endeca:dimensionValues"]/ns:List');
			$tempHeader = '';
			$tempTitleArray = array();
			$tempTitle = '';
			$brandArray = array();
			$catDesc = '';
			foreach ($theHeader as $opt){
				foreach($opt as $k => $v){
					if( strpos($v,'Category') ){
						$tempHeader = explode('/',$v);
					}

					if( strpos($v,'Brand') ){
						$brandCounter++;
						$tempTitleArray = explode('/Brand/',$v);
						$brandArray[]= $tempTitleArray[1];
					}

					if( strpos($v,'Category') ){
						$tempTitleArray = explode('/',$v);
						$tempTitle = end($tempTitleArray);
						$catDesc = $tempTitle;
					}

				}
			}
			if('' != $tempHeader){
				$this->z_view->header_term = array_pop($tempHeader);	
			} else {
				//It's a brand
				$tempHeader = explode('/',$theHeader[0]->String);
				$this->z_view->header_term = array_pop($tempHeader);;
			}

			if('' == $tempHeader)
				$this->z_view->header_term = '';
        }//end header


		// For the Page title in case there are a search this will get it 
        if(!empty($_GET['Gx'])){
            $v_page_title=ucwords(strtolower((getForecastDesc(post_or_get('Gx')))));
        }else if(!empty($_GET['s'])){
  			$v_page_title=ucwords(strtolower((post_or_get('s'))));	
 		}else if(!empty($_GET['Ntt'])){
  			$v_page_title=ucwords(strtolower((post_or_get('Ntt'))));	
 		}
		
/* 
    	if (is_null($v_page_title)){
    		
    		$pageTitleTemp=ucwords(strtolower(str_replace("-"," ",$varURL)));
    		$pageTitleTemp=str_replace("%27","'",$pageTitleTemp);
    		$pageTitleTemp=str_replace("+"," ",$pageTitleTemp);
    		$v_page_title= $pageTitleTemp ." at Golfsmith.com";
    	} 
*/
		
		//if ($v_page_title == null ){
		//	$v_page_title = $this->z_view->header_term. " at Golfsmith.com"; 
		//}

        $brandDesc = '';
        $metaBrand = FALSE;
		if(count($brandArray) == 1)
		{
			$tempTitle = $brandArray[0].' '.$tempTitle;
			$brandDesc = $brandArray[0];
			$metaBrand = TRUE;
			//$v_page_title = $tempTitle;
		}


		//new page_title
		if ($v_page_title == null ){
			$v_page_title = $tempTitle;
		}

		$this->z_view->header_term = $v_page_title;
		$v_page_title = ucfirst(htmlspecialchars_decode(cleanTitle($v_page_title)));
		$v_page_title = htmlentities($v_page_title, ENT_QUOTES, 'UTF-8');

		//Build Meta Title Tags
		if($metaBrand){
			// Meta Tags for the search page 
	 		$override_header_contents = "<title>$v_page_title at Golfsmith.com</title>"."\n".
			'<meta  name="description" content="'.$v_page_title.' for Less at Golfsmith.com. 
			The largest selection and lowest prices on '.$v_page_title.'.">';			
		}elseif($catDesc != '') {
			// Meta Tags for the search page 
	 		$override_header_contents = "<title>$v_page_title at Golfsmith.com</title>"."\n".
			'<meta  name="description" content="'.$v_page_title.' for Less at Golfsmith.com. 
			The largest selection and lowest prices on '.htmlspecialchars_decode($catDesc).' from the biggest brands in golf">';
		}else {
			// Meta Tags for the search page 
	 		$override_header_contents = "<title>$v_page_title at Golfsmith.com</title>"."\n".
			'<meta  name="description" content="'.$v_page_title.' for Less at Golfsmith.com. 
			The largest selection and lowest prices on '.$v_page_title.' from the biggest brands in golf">';	
		}

		// Start push the page to the web site visitors 
		$this->i_site_init->loadMain();
		// Total results count
		$totalNumRecs = $xml->xpath('//ns:Property[@name="totalNumRecs"]');
		$searchResultsTotalrec = (string)$totalNumRecs[0]->Long;
		$this->z_view->search_total_records = $searchResultsTotalrec;

 		// First record number  
		$firstRecNum = $xml->xpath('//ns:Property[@name="firstRecNum"]');
		$searchFirstRecNum = (string)$firstRecNum[0]->Long;
		$this->z_view->search_first_rec_num = $searchFirstRecNum;

		// Last record number 
		$lastRecNum = $xml->xpath('//ns:Property[@name="lastRecNum"]');
		$searchLastRecNum = (string)$lastRecNum[0]->Long;
		$this->z_view->search_last_rec_num = $searchLastRecNum;
		
		//AutoCorrect
		$autoCorrectXML = $xml->xpath('//ns:Property[@name="endeca:assemblerRequestInformation"]/ns:Item[@type="AssemblerRequestEvent"]/ns:Property[@name="endeca:autocorrectTo"]');
		$this->z_view->autoCorrect = (string)$autoCorrectXML[0]->String;

		// navigationState URI 
		$navigationState = $xml->xpath('//ns:Item/ns:Property[@name="pagingActionTemplate"]/ns:Item/ns:Property[@name="navigationState"]');
		$searchNavigationState = (string)$navigationState[0]->String;
		$this->z_view->search_navigation_state = str_replace(" ","+",$searchNavigationState);
		
		// Price sliderMaxValue
		$sliderMaxValue = $xml->xpath('//ns:Item/ns:Property[@name="sliderMaxValue"]');
		$PriceSliderMaxValu = (string)$sliderMaxValue[0]->Integer;
		$this->z_view->price_slider_max_value = $PriceSliderMaxValu;
		// Price sliderMinValue
		$sliderMinValue = $xml->xpath('//ns:Item/ns:Property[@name="sliderMinValue"]');
		$PriceSliderMinValu = (string)$sliderMinValue[0]->Integer;
		$this->z_view->price_slider_min_value = $PriceSliderMinValu;
		// Selected refinements DimisnionIDS
		$RefinementDimensionsIDs=$xml->xpath('//ns:Item[@type="SearchResultsPage"]/ns:Property[@name="navigationSecondary"]/ns:List/ns:Item[@type="ContentSlotSecondary"]/ns:Property[@name="contents"]/ns:List/ns:Item[@type="GuidedNavigation"]/ns:Property[@name="navigation"]/ns:List/ns:Item[@type="GSRefinementMenu"]/ns:Property[@name="selectedDimensions"]/ns:List');
		$dimensionsIdArray = array();
		foreach ($RefinementDimensionsIDs[0] as $dimensionsID) {
			$dimensionsIdArray[]= (string)$dimensionsID;
		}
		$this->z_view->refinement_dimensions_id_array = $dimensionsIdArray;
		// the item per page links
		$recordperPage = 30;
		for ($i=0; $i<4;$i++){
			$tempNavigationURL = urldecode ($searchNavigationState);
			$tempNavigationURL = str_replace("{offset}",0,$tempNavigationURL) ;
			$tempNavigationURL = str_replace("{recordsPerPage}",$recordperPage,$tempNavigationURL) ;
			$navigationURLArray[$recordperPage] = str_replace(" ","+",$tempNavigationURL) ;
			$recordperPage =  $recordperPage +30 ;
		}
		$this->z_view->navigation_url_array = $navigationURLArray;
		// record Per page 
		$recsPerPage = $xml->xpath('//ns:Property[@name="recsPerPage"]');
		// recordes per page if it is in the URl we willnot read the default value from the XML file
		if (!$va_params["Nrpp"]){
			$searchRecsPerPage = (string)$recsPerPage[0]->Long;
			$this->z_view->search_record_per_page = $searchRecsPerPage;
		}else{
			$this->z_view->search_record_per_page = $va_params["Nrpp"];
		}
		
		// category refinements Category
		$ancestorCounter = 0;
		$unsetAncestor = array();
		$refinementCrumbs = $xml->xpath('//ns:Item[@type="SearchResultsPage"]/ns:Property[@name="bannerMain"]/ns:List/ns:Item[@type="BreadcrumbsMain"]/ns:Property[@name="refinementCrumbs"]/ns:List/ns:Item[@class="com.endeca.infront.cartridge.model.RefinementBreadcrumb"]/ns:Property[@name="ancestors"]/ns:List/ns:Item[@class="com.endeca.infront.cartridge.model.Ancestor"]');
		$ancestorCatArray = array();
		foreach ($refinementCrumbs as $sortOpt){
			foreach ($sortOpt as $obj){
				foreach($obj->attributes() as $a => $b) {
					if($b[0] == 'label'){
						if(strpos($obj->String,'$') !== false){
							$unsetAncestor []= $ancestorCounter;
						}
					}

					$value = (string)$obj->String==""? (string)$obj->Boolean == "" ? (string)$obj->Integer == "" ? (string)$obj->Long /*to add any more*/ : (string)$obj->Integer : (string)$obj->Boolean :(string)$obj->String;
					$ancestorCatArray[$ancestorCounter][(string)$b]= $value;
				}
			}
			$ancestorCounter++;
		}
		$searchCrumbs = $xml->xpath('//ns:Item[@type="SearchResultsPage"]/ns:Property[@name="bannerMain"]/ns:List/ns:Item[@type="BreadcrumbsMain"]/ns:Property[@name="searchCrumbs"]/ns:List/ns:Item[@class="com.endeca.infront.cartridge.model.SearchBreadcrumb"]/ns:Property[@name="removeAction"]/ns:Item[@class="com.endeca.infront.cartridge.model.NavigationAction"]');
		foreach ($searchCrumbs as $sortOpt){
			foreach ($sortOpt as $obj){
				foreach($obj->attributes() as $a => $b) {
					$value = (string)$obj->String==""? (string)$obj->Boolean == "" ? (string)$obj->Integer == "" ? (string)$obj->Long /*to add any more*/ : (string)$obj->Integer : (string)$obj->Boolean :(string)$obj->String;
					$ancestorCatArray[$ancestorCounter][(string)$b]= $value;
				}
			}
			$ancestorCounter++;
		}
		
		// Get the Refinment Dimension
		$refinementDimensionXML = $xml->xpath('//ns:Item[@class="com.endeca.infront.cartridge.model.RefinementBreadcrumb"]');
		$refineOptionsCount = 0;
		$refineOptionsArray = array();
		$currentState = array();
		foreach ($refinementDimensionXML as $sortOpt){
			foreach ($sortOpt as $obj){
				foreach($obj->attributes() as $a => $b) {
					$value = (string)$obj->String==""? (string)$obj->Boolean == "" ? (string)$obj->Integer == "" ? (string)$obj->Long /*to add any more*/ : (string)$obj->Integer : (string)$obj->Boolean :(string)$obj->String;
					$refineOptionsArray[$refineOptionsCount][(string)$b]= $value;
					if ((string)$b == "label"){
						$currentState[$refineOptionsCount]["label"]= $value;
					}
					//if ( ( (string)$b == "dimensionName" && $value == "Category" ) || ( (string)$b == "dimensionName" && $value == "Price" )){
					if ( (string)$b == "dimensionName" && $value == "Category" ){
						$currentState[$refineOptionsCount]["State"]= $value;
					}
					// if there are another dimnsion not cateogry 
					//if ( ( (string)$b == "dimensionName" && $value !="Category") || ( (string)$b == "dimensionName" && $value !="Price") ) {
					if ( (string)$b == "dimensionName" && $value !="Category" ) {
						$this->z_view->refinement_choice_boolean = true;
	                                                                                                
						if(isset($currentState[$refineOptionsCount]["label"])){
							unset($currentState[$refineOptionsCount]);
						}
					}
	                                                                
					if ((string)$b == "ancestors"){
						$refineOptionsArray[$refineOptionsCount]["ancestorsNavigation"]= (string)$obj->List->Item->Property->String;
					}
				}
				$secObejcts = $obj->Item->Property;
				foreach($secObejcts as $secObj){
					foreach($secObj->attributes() as $a => $b) {
						$value = (string)$secObj->String==""? (string)$secObj->Boolean == "" ? (string)$secObj->Integer == "" ? (string)$secObj->Long /*to add any more*/ : (string)$secObj->Integer : (string)$secObj->Boolean :(string)$secObj->String;
						$refineOptionsArray[$refineOptionsCount][(string)$b]= $value;
					}              
				}
			}
			$refineOptionsCount++;
		}

		$currentState = array_values($currentState);
		$ancestorCatArray[$ancestorCounter]["label"]=$currentState[0]["label"];

		//Unset any ancestors that had $ - hack for price should be looking at dimensionName
		foreach ($unsetAncestor as $k => $v){
			unset($ancestorCatArray[$v]);
		}

		$this->z_view->refinement_cat_array = $ancestorCatArray;
		$this->z_view->refinement_options_array = $refineOptionsArray;
		
 		// in case there are no results but still we have breadcrumps 
 		// we forward them to zero results page
 		$specialRefinementsHasResults = true; 
		if ((count($ancestorCatArray)!=0 || count($refineOptionsArray) != 0 ) && $searchResultsTotalrec == 0){
			$specialRefinementsHasResults =  false;
		}
		
		// in case there are no results and now bread crupms forwad them to Oops mulligan.html  
		if ($searchResultsTotalrec == 0 && (count($ancestorCatArray)==0 && count($refineOptionsArray) == 0 ) ){
 			include( PHP_INC_PATH . 'mulligan.html');
 			$this->i_site_init->loadFooter($g_data);
 			exit();
 		}
		
                                 		
 		// the banner 
 		$bannerString = $xml->xpath('//ns:Item[@type="SearchResultsPage"]/ns:Property[@name="bannerMain"]/ns:List/ns:Item[@type="RichTextMain"]/ns:Property[@name="content"]');
 		$this->z_view->banner_string = $bannerString[0]->String;

  		// the refinement loop 		
  		$searchResultList = $xml->xpath('//ns:Item[@type="GuidedNavigation"]/ns:Property[@name="navigation"]/ns:List');
		foreach ($searchResultList as $element) {
		// get the categories 
		$catCounter = 0;
			foreach($element->Item as $a){
	  			$CategoryArray[$catCounter];
	  			$tempXML = simplexml_load_string($a->asXML());
	  			// Multi Select value
	  			$multiSelect= $tempXML->xpath("/Item/child::Property[attribute::name='multiSelect']");
				$CategoryArray[$catCounter]["multiSelect"] = (string)$multiSelect[0]->Boolean;
				// category name 
	  			$cat_name= $tempXML->xpath("/Item/child::Property[attribute::name='name']");
				$CategoryArray[$catCounter]["name"] = ((string)$cat_name[0]->String);
				// Dimension Name
	  			$dimensionName= $tempXML->xpath("/Item/child::Property[attribute::name='dimensionName']");
				$CategoryArray[$catCounter]["dimensionName"]= (string)$dimensionName[0]->String;
				if ($CategoryArray[$catCounter]["dimensionName"] == "Price"){
					$sliderAction= $tempXML->xpath("/Item/child::Property[attribute::name='sliderAction']/Item/Property");
					foreach ($sliderAction as $sortOpt){
							foreach($sortOpt->attributes() as $a => $b) {
    							$value = (string)$sortOpt->String==""? (string)$sortOpt->Boolean == "" ? (string)$sortOpt->Integer == "" ? (string)$sortOpt->Long /*to add any more*/ : (string)$sortOpt->Integer : (string)$sortOpt->Boolean :(string)$sortOpt->String;
    							$CategoryArray[$catCounter]["sliderAction"][(string)$b]= $value;
							}

						//$ancestorCounter++;
 					}
				}
				// Display Name
	  			$displayName= $tempXML->xpath("/Item/child::Property[attribute::name='displayName']");
				$CategoryArray[$catCounter]["displayName"] = (string)$displayName[0]->String;
				// Ancestors names 
				//if( $CategoryArray[$catCounter]["dimensionName"] == "Category" || $CategoryArray[$catCounter]["dimensionName"] == "Price"){
				if( $CategoryArray[$catCounter]["dimensionName"] == "Category" ){
					$ancestorCounter = 0;
					$ancestorCatArray = array();
					$ancestorsTemp= $tempXML->xpath("/Item/child::Property[attribute::name='ancestors']/List/Item");
					foreach ($ancestorsTemp as $sortOpt){
						foreach ($sortOpt as $obj){
							foreach($obj->attributes() as $a => $b) {
    							$value = (string)$obj->String==""? (string)$obj->Boolean == "" ? (string)$obj->Integer == "" ? (string)$obj->Long /*to add any more*/ : (string)$obj->Integer : (string)$obj->Boolean :(string)$obj->String;
    							$ancestorCatArray[$ancestorCounter][(string)$b]= $value;
							}
						}
						$ancestorCounter++;
 					}
				}
	  			$ancestors= $tempXML->xpath("/Item/child::Property[attribute::name='ancestors']");
				$CategoryArray[$catCounter]["ancestors"] = (string)$ancestors[0]->String;
				// moreLink 
	  			$moreLink= $tempXML->xpath("/Item/child::Property[attribute::name='moreLink']");
	  			if (isset($moreLink[0]->Item->Property->String)){
	  				$CategoryArray[$catCounter]["moreLink"] = (string)$moreLink[0]->Item->Property->String;
	  			}
				// The elements of the category 
				$refinements= $tempXML->xpath("/Item/child::Property[attribute::name='refinements']/List/Item");
				$elemetCounter = 0 ;
	  			foreach($refinements as $b){
	 	  				foreach ($b->Property as $obj){
		  				foreach($obj->attributes() as $d => $eleName){
		  					switch  ($eleName){
				  			    case "multiSelect":
							        $CategoryArray[$catCounter]["elements"][$elemetCounter]["multiSelect"] = (string)$obj->Boolean;
							        break;
							    case "navigationState":
							        $CategoryArray[$catCounter]["elements"][$elemetCounter]["navigationState"] = (string)$obj->String;
							        break;
							    case "contentPath":
							        $CategoryArray[$catCounter]["elements"][$elemetCounter]["contentPath"] = (string)$obj->String;
							        break;			
							    case "count":
							        $CategoryArray[$catCounter]["elements"][$elemetCounter]["count"] = (string)$obj->Integer;
							        break;			
							    case "siteRootPath":
							        $CategoryArray[$catCounter]["elements"][$elemetCounter]["siteRootPath"] = (string)$obj->String;
							        break;			
							    case "label":
							        $CategoryArray[$catCounter]["elements"][$elemetCounter]["label"] = (string)$obj->String;
							        break;
							    case "properties":
		  							foreach ($obj->Item->Property as $obj2){
										foreach($obj2->attributes() as $a => $b) {
					    					$value = (string)$obj2->String==""? (string)$obj2->Boolean == "" ? (string)$obj2->Integer == "" ? (string)$obj2->Long /*to add any more*/ : (string)$obj2->Integer : (string)$obj2->Boolean :(string)$obj2->String;
    										$CategoryArray[$catCounter]["elements"][$elemetCounter][(string)$b]= $value;
										}
									}
		  					}
	  					}
	  				}
	  				$elemetCounter++;
  				}
  				$catCounter++;
  			}
  			// The page
  			// The left search resutls column 

  			
  			$this->z_view->search_refinement = $CategoryArray;
  			$leftSearcResults = $this->z_view->render("left_search_results.phtml");
  			
			// the Search Page header
			//$this->z_view->refinement_cat_array = $ancestorCatArray;
  			$this->z_view->header_search_results = $this->z_view->render("headers_search_results.phtml");
  			//echo $headerSearcResults;
		}

		
		// sort Options 
		$sortOptionsXML = $xml->xpath('//ns:Item[@class="com.endeca.infront.cartridge.model.SortOptionLabel"]');
		$sortOptionsCount = 0;
		$sortOptionsArray = array();
		foreach ($sortOptionsXML as $sortOpt){
			//$sortOpt->registerXPathNamespace('ns', 'http://endeca.com/schema/xavia/2010');
			//print_r($sortOpt->attributes());
			foreach ($sortOpt as $obj){
				foreach($obj->attributes() as $a => $b) {
    				$value = (string)$obj->String==""? (string)$obj->Boolean == "" ? (string)$obj->Integer == "" ? (string)$obj->Long /*to add any more*/ : (string)$obj->Integer : (string)$obj->Boolean :(string)$obj->String;
    				$sortOptionsArray[$sortOptionsCount][(string)$b]= $value;
				}
			}
			$sortOptionsCount++;
 		}
 		$this->z_view->search_sort_options_array = $sortOptionsArray;
  		// serach results  
  		$searchResultList = $xml->xpath('//ns:Item[@class="com.endeca.infront.cartridge.model.Record"]');
  		// the next line has displayColorSwatches 
  		$searchResultList = $xml->xpath('//ns:Item[@type="ResultsList"]');
  		
  		if(!empty($searchResultList)){
  		
	   		$tempSearchRecords = $searchResultList[0]->xpath("child::*[attribute::name='records']/child::*");
			$styleAttributeCount = 0;
			$styleAttributeArray = array();
	  		foreach ($tempSearchRecords[0]->Item as $searchResult) {
	  			// Style Detail Action
	  			$styleAttributes = $searchResult->xpath("child::*[attribute::name='detailsAction']");
				foreach ($styleAttributes[0]->Item->Property as $obj){
					foreach($obj->attributes() as $a => $b) {
	    				$value = (string)$obj->String==""? (string)$obj->Boolean == "" ? (string)$obj->Integer == "" ? (string)$obj->Long /*to add any more*/ : (string)$obj->Integer : (string)$obj->Boolean :(string)$obj->String;
	    				$styleAttributeArray[$styleAttributeCount][(string)$b]= $value;
					}
				}
	  			// Style Num Records 
	  			$styleAttributes = $searchResult->xpath("child::*[attribute::name='numRecords']");
				foreach($styleAttributes[0]->attributes() as $a => $b) {
	    			$value = (string)$styleAttributes[0]->Long;
	    			$styleAttributeArray[$styleAttributeCount][(string)$b]= $value;
				}
	  			
	  			// Style Attirbute 
	  			$styleAttributes = $searchResult->xpath("child::*[attribute::name='attributes']");
				foreach ($styleAttributes[0]->Item->Property as $obj){
					foreach($obj->attributes() as $a => $b) {
						//var_dump($b);
	    				$value = (string)$obj->List->String==""? (string)$obj->List->Boolean == "" ? (string)$obj->List->Integer == "" ? (string)$obj->List->Long /*to add any more*/ : (string)$obj->List->Integer : (string)$obj->List->Boolean :(string)$obj->List->String;
	    				$styleAttributeArray[$styleAttributeCount][(string)$b]= $value;
					}
				}
				// Sku level 
				$skuCounter = 0;
	  			$styleAttributes = $searchResult->xpath("child::*[attribute::name='records']");
				foreach ($styleAttributes[0]->List->Item as $listobj){
					$skuDetailsAction = $listobj->xpath("child::*[attribute::name='detailsAction']");
					foreach ($skuDetailsAction[0]->Item->Property as $obj){
						foreach($obj->attributes() as $a => $b) {
							//var_dump($b);
	    					$value = (string)$obj->String==""? (string)$obj->Boolean == "" ? (string)$obj->Integer == "" ? (string)$obj->Long /*to add any more*/ : (string)$obj->Integer : (string)$obj->Boolean :(string)$obj->String;
	    					$styleAttributeArray[$styleAttributeCount]["SKU"][$skuCounter][(string)$b]= $value;
						}
					}
					// Style Num Records 
		  			$styleAttributes = $listobj->xpath("child::*[attribute::name='numRecords']");
					foreach($styleAttributes[0]->attributes() as $a => $b) {
		    			$value = (string)$styleAttributes[0]->Long;
		    			$styleAttributeArray[$styleAttributeCount]["SKU"][$skuCounter][(string)$b]= $value;
					}
					$skuDetailsAction = $listobj->xpath("child::*[attribute::name='attributes']");
					foreach ($skuDetailsAction[0]->Item->Property as $obj){
						foreach($obj->attributes() as $a => $b) {
	    					$value = (string)$obj->List->String==""? (string)$obj->List->Boolean == "" ? (string)$obj->List->Integer == "" ? (string)$obj->List->Long /*to add any more*/ : (string)$obj->List->Integer : (string)$obj->List->Boolean :(string)$obj->List->String;
	    					$styleAttributeArray[$styleAttributeCount]["SKU"][$skuCounter][(string)$b]= $value;
						}
					}
					$skuCounter++;
				}
				
				// 
				$styleAttributeCount++;
	  		}//end foreach
  		}//end if !empty SearchResults List

  		$this->z_view->search_result_set_array = $styleAttributeArray;
  		if ($specialRefinementsHasResults){
  			echo $leftSearcResults;
  			$bodySearcResults = $this->z_view->render("body_search_results.phtml");
  		}else{
  			if (is_null($CategoryArray)){
  				include( PHP_INC_PATH . 'mulligan.html');
 				$this->i_site_init->loadFooter($g_data);
 				exit();
  			}
  			echo $leftSearcResults;
  			$bodySearcResults = $this->z_view->render("body_search_no_results.phtml");
  		}
  		// the search results body
  		$this->z_view->obj_slider = new Slider($_SESSION ['recently_viewed_item_list']['order'],6,"review");
  		echo $bodySearcResults;
  		echo '<script type="text/javascript"> 
  			var _smtr = _smtr || window._smtr || []; 
			_smtr.push(["pageView", { "pageType": "search",
    		"searchPhrase": "'.$v_page_title.'" }]);
    		</script>';
  		echo $this->z_view->render("search_bottom.phtml");
	    $this->i_site_init->loadFooter($g_data);
	}
	
	function checkCategories($varURL){
		//$this->startInit();
		global $web_db;
		//$web_db = mysqli_connect('r12paulcatdb1.gsicorp.com', 'webuser', 'w3b3') ;
		$param1 = $varURL;

		if(!empty($param1)) {
			$a_inputs = explode('+',$param1);

			foreach ($a_inputs as $key => $value) {
				if (isset($a_inputs[$key])) {
					$v_column_name = 'id';
					$sql = "select id from gsi_id_name_xref
	                	where ucase(name_in_url) = '" . strtoupper($value) . "'";
					$result = mysqli_query($web_db, $sql);
					if ($row = mysqli_fetch_assoc($result)) {
						$v_nTemp = $v_nTemp . '+' . $row['id'];
					} else {
						$sql = "select category_id
							from gsi_categories
							where category_set_name ='GSI WEB CATALOG'              
							and ( concat(',',concat(ucase(replace(old_web_category_name,' ','-')), ',')) like '%," . strtoupper($value) . ",%' or ucase(replace(replace(description,' ','-'),'\'','')) = '" . strtoupper($value) . "')";
						$result = mysqli_query($web_db, $sql);
	
						if ($row = mysqli_fetch_assoc($result)){
	          				$v_nTemp = $v_nTemp . ' ' . $row['category_id'];
						}else {
				          	//$value = str_replace("_","/",$value);
				          	$sql = "select brand_id
									from gsi_endeca_brands
									where brand_digest ='" . strtoupper($value) . "'";
							$result = mysqli_query($web_db, $sql);

							if ($row = mysqli_fetch_assoc($result)){
								$v_nTemp = $v_nTemp . ' ' . $row['brand_id'];
							}
						}
					}
					while ($row = mysqli_fetch_assoc( $result)) {
						$v_nTemp = $v_nTemp . ' ' . $row[$v_column_name];
					}
					mysqli_free_result($result);    
	
				}
			}
			$v_nTemp = substr_replace($v_nTemp,'',0,1);
    		if('' != $v_nTemp)
    			$_GET["N"] = $v_nTemp;   
		}

		$va_Temp_N = explode(" ",$_GET["N"]);
		foreach ($va_Temp_N as $key => $value) { 
			// I don't know what is the benefit of this Number but it seems like old store ID
  			if ($value == '4294965587') { 
    			unset($va_Temp_N[$key]); 
  			} 
		}
		$v_Temp_N = implode("_",$va_Temp_N);
		//if N value is golfstore, redirect to ps 
		if($v_Temp_N == 1847822) {
  			header( 'Location: /ps/');
  			die();
		}
	}//end function checkCategories
	
	public function getCatElemntsValue($node){
		
	}
	
	public function searchAction(){
		 $v_port = $v_port = $_SERVER['SERVER_PORT'];
	    if($v_port == '443') {
	      $this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	      return;
	    }
	    //action is actually style number
		$this->startInit();
		$this->i_site_init->loadMain();
	    $va_params = $this->_getAllParams();
	    // to Do 
 
		$this->i_site_init->loadFooter($g_data);
	}

	// if we find any style number we will retrurn it 
	function get_new_style($p_style){
		global $web_db;
  		$v_new_style="";
  		$v_sql="select style_number 
				from webdata.gsi_style_info_all
				where old_style='$p_style'";
  		$v_result=mysqli_query($web_db, $v_sql);
  		$v_row=mysqli_fetch_array($v_result);
  		$v_style_found=mysqli_num_rows($v_result);
  		if($v_style_found>0){
    		$v_new_style=$v_row['style_number'];
  		}else{
    		$v_new_style=$p_style;    	
  		}
   		return $v_new_style;  	
	}

	function buildSearchURL($week){
	}
	
	function prepareURL($v_sub_url){
		$v_sub_url = preg_replace("![^a-z0-9]+!i", "-", $v_sub_url);
    	$v_sub_url    = str_replace(" - ", "-", $v_sub_url);
		$v_spec_chars = array(".", ",", "'", "*", "&", "%", "$", "#", "@", "!", "(", ")", "[", "]", "{", "}", "?", "<", ">","¿");
		$v_sub_url    = str_replace($v_spec_chars, "", $v_sub_url);
		$v_spec_chars = array("_", ":", ";");
		$v_sub_url    = str_replace($v_spec_chars, "-", $v_sub_url);
		$v_sub_url	  = strtolower($v_sub_url);
		return $v_sub_url;
	}

 	function hasQty($p_iid) {
	   global $mssql_db;	
	   $v_atp = 0;
	   $v_org_id = 25;
	
	    $v_stmt = mssql_init("direct.dbo.gsi_get_item_atp");
	
	    gsi_mssql_bind($v_stmt, "@p_iid", $p_iid, "bigint", -1);
	    gsi_mssql_bind($v_stmt, "@p_org_id", $v_org_id, "bigint", -1);
	    gsi_mssql_bind($v_stmt, "@p_atp", $v_atp, "bigint", -1, true);
	
	    $v_result = mssql_execute($v_stmt);
		
	    if(!$v_result) {
	      display_mssql_error("direct..gsi_get_item_atp called from SearchController.php");
	      $v_atp = -1;
	    }
	
	    mssql_free_statement($v_stmt);
	    
	    return $v_atp;
  	
  }	
  
  
function findStyleNum($search_term){	
	global $web_db;	
	$potential_style_number = strToUpper( preg_replace( '/[^0-9a-zA-Z]/i', '', $search_term));
	$is_valid_style = FALSE;
	if (strlen($search_term) <= 8)
	{
		$v_SQL = "SELECT  1 valid_style , c.description brand, b.description item_desc, a.style_number
				FROM gsi_cmn_style_data a
				   , gsi_style_info_all b
				   , gsi_brands c
				WHERE a.style_number = b.style_number
				    and b.brand_id = c.brand_id
					and (b.style_number = '$potential_style_number' or b.old_style = '$potential_style_number')";
	 	$v_Result = mysqli_query($web_db, $v_SQL);
		if($v_row = mysqli_fetch_assoc($v_Result)) {
			if(strToLower($v_row['valid_style']) == 1) {
				$is_valid_style = TRUE;
				$v_prod_url = str_replace(' ','-',$v_row['brand'] . ' ' . $v_row['item_desc']);      
				$potential_style_number = $v_row['style_number'];
			}
		}
	} else {	  	
		$potential_sku_number = strToUpper( preg_replace( '/[^0-9a-zA-Z]/i', '', $search_term));
		$v_SQL = "SELECT  1 valid_style , c.description brand, b.description item_desc, a.style_number,i.inventory_item_id
				FROM gsi_cmn_style_data a
				   , gsi_style_info_all b
				   ,gsi_item_info_all i
				   , gsi_brands c
				WHERE a.style_number = b.style_number
				    and b.brand_id = c.brand_id
  				    and i.segment1 = b.style_number
					and i.sku = '$potential_sku_number'";
	 	$v_Result = mysqli_query($web_db, $v_SQL);
		if($v_row = mysqli_fetch_assoc($v_Result)) {
			if(strToLower($v_row['valid_style']) == 1) {				
				$v_has_qty = $this->hasQty($v_row['inventory_item_id']);		
				if ($v_has_qty > 0) { 
					$is_valid_style = TRUE;
					$v_prod_url = str_replace(' ','-',$v_row['brand'] . ' ' . $v_row['item_desc']);      
					$potential_style_number = $v_row['style_number'];					
				}
			}
		}
	}
		$v_seo_obj = new seo($potential_style_number);
		$v_prod_url = $v_seo_obj->get_seo_url();
	    
		if($is_valid_style) {  
			header('Location: ' . $v_prod_url);
			die();     
		}
  	  
}
  
}//end class

function prepareAlltheGET(){
	global $_GET;
	// I don't know yet what is GX but I'll inheritance this from the old endeca php code 
		if (isset($_GET['Gx'])) {
			
  			$querystring = $_GET['Gx'];
  			//$_GET['N'] = '0';
  			$_GET['Ntk'] = 'p_forecast';
  			$_GET['Ntt'] = $querystring;
  			$_GET['Nty'] = '1';
  			$_GET['Gp'] = '1';
		}
		$nval = 'N';
		if(empty($_GET['N']) || !isset($_GET['N'])) {
  			$nval = 'n';
  		if (!empty($_GET['n']))
    		$_GET['N']=$_GET['n'];
    		unset($_GET['n']);
		}
		//If $_GET['N'] value is not numeric, don't call endeca.
		if(preg_match('/[^0-9]/' , str_replace(' ','',$_GET['N']))) {  
			$v_call_endeca = 'N';
			header( 'Location: /ps/');
			die();
		}
		
		// All the next line are get from the search.php cleans
		//If Ne is non-numeric, don't call endeca
		if (preg_match('/[^0-9]/' , $_GET['Ne'])) {
			$v_call_endeca = 'N';
			header( 'Location: /ps/');
			die();
		}

		//If Nao is non-numeric, don't call endeca
		if (preg_match('/[^0-9]/' , $_GET['Nao'])) {
			$v_call_endeca = 'N';
			header( 'Location: /ps/');
			die();
		}
		
		if(!empty($_GET['s']) || isset($_GET['s'])) {
				$_GET['Ntt'] = $_GET['s'];
                unset($_GET['s']);
		}
		
		if(empty($_GET['Ntt']) || !isset($_GET['Ntt'])) {
			if(!empty($_GET['ntt'])) {
				$_GET['Ntt'] = $_GET['ntt'];
			}
		}
  
		$search_term = $_GET['Ntt'];
		if(empty($_GET['Ntk']) || !isset($_GET['Ntk'])) {
			if(!empty($_GET['ntk'])) {
				$_GET['Ntk']=$_GET['ntk'];
			} else {
				$_GET['Ntk'] = 'All';
			}
		}

		if($_GET['Ntk'] == 'all') {
			$_GET['Ntk'] = 'All';
		}

		if(empty($_GET['Nu']) || !isset($_GET['Nu'])) {
			if(!empty($_GET['nu'])) {
				$_GET['Nu']=$_GET['nu'];
			}
		}
		if(empty($_GET['Nty']) || !isset($_GET['Nty'])) {
			if(!empty($_GET['nty'])) {
				$_GET['Nty']=$_GET['nty'];
			}
		}
		if(empty($_GET['Ntx']) || !isset($_GET['Ntx'])) {
			if (!empty($_GET['ntx'])) {
				$_GET['Ntx']=$_GET['ntx'];
			}
		}
}//end function prepare the get

function alternateSearchTerm($searchTerm){
	//Search for alternate search term to use
	//$this->startInit();
	global $web_db;
	$search_term = $searchTerm;
	if(trim($search_term) != '') {
  		$strSQL = "SELECT map_term, map_type FROM gsi_remap_keywords WHERE original_term = TRIM(LOWER('$search_term')) and map_term not like '%#pp_tabbox%'";
  		$objResult = mysqli_query($web_db, $strSQL);
  		if($row = mysqli_fetch_assoc($objResult)) {
    		if(strToLower($row['map_type']) == 'term') {
      			$search_term = $row['map_term'];
      			$strSQL2 = "SELECT map_term, map_type FROM gsi_remap_keywords 
                	 WHERE original_term = TRIM(LOWER('$search_term')) 
                 	and map_type = 'page'
                 	and map_term not like '%#pp_tabbox%'";

      			$objResult2 = mysqli_query($web_db, $strSQL2);
 
      			if($row2 = mysqli_fetch_assoc($objResult2)) {
        			header('Location: '.$row2['map_term']);
        			exit;
      			} else {
        			$search_term = $row['map_term'];
        			$_GET['Ntt'] = str_replace(' ', '+', $row['map_term']);
        			$leftnav = 'N';
      			} //end else
    		} else {
				header('Location: '.$row['map_term']);
				exit;    			
    		}
  		}
	}//search term not empty
	return $search_term;
}


function cleanTitle($title){
	$searchArray = array('+','-');
	$replaceArray = array(' ',' ');

	$title = str_replace($searchArray, $replaceArray, $title);

	return $title;

}

function getForecastDesc($p_forecast)
{
	global $web_db;	
	
	$v_fcst_desc = '';
	$p_forecast = mysqli_real_escape_string($web_db,$p_forecast);	
    $v_sql = "select description from gsi_mrp_forecast_designators
			  where forecast_designator = '".mysqli_real_escape_string($web_db,$p_forecast)."'";

    $result = mysqli_query($web_db, $v_sql);
    
	$v_num_rows = mysqli_num_rows($result);

	if($v_num_rows > 0){
			
        $row = mysqli_fetch_assoc( $result);
        $v_fcst_desc = $row['description'];
                
    }
    
    return $v_fcst_desc;
}//end get Forecast
?>
