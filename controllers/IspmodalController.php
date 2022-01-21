<?php


require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/StoreFinder.php');
require_once('models/ISPPage.php');
require_once('models/Scene7.php');
require_once ('Zend/View.php');
require_once ('models/Order.php');
require_once ('models/ProductPage.php');

class IspmodalController extends Zend_Controller_Action {
    
    private $i_site_init;
    private $button_title;
    
    //Init properties
    private function _startinit(){
        
        global $connect_mssql_db;
        $connect_mssql_db = 1;
        
        $this->i_site_init = new SiteInit();
        $this->i_site_init->loadInit();
    }
    
    
    public function showispAction(){
        
        
        try{
        $this->_startinit();
        
        $i_request = $this->getRequest();
        
        $v_qty = $i_request->getParam('qty');
        $v_zipcode = $i_request->getParam('zipcode');
        $v_item_id = $i_request->getParam('iid');
        $v_promise_date = $i_request->getParam('promise_date');
        $v_style_num = $i_request->getParam('stynum');
        $v_sku = $i_request->getParam('sku');
        
        $v_sku_options = explode("-", $v_sku);
        $v_segment2 = $v_sku_options[1];
        
        $i_productPage = new ProductPage($v_style_num);
        
        $param = array();
        $param['zipcode'] = $v_zipcode;
        $param['qty'] = $v_qty;
        $param['item_id'] = $v_item_id;
        $param['promise_date'] = $v_promise_date;
        $param['isPersonalizedstyle'] = $i_productPage->isPersonalizedstyle();
       
        $scene7 = new Scene7($v_style_num);
        $image = $scene7->getSmallImage('P');

        //Include view
        $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
        
        
        //variable to view
        $z_view->button_title = $this->button_title = "Add to Cart";
        $z_view->form_id = "form_isp_add_stores";
        $z_view->form_on_submit = "golfsmith.isp.addItems()";
        $z_view->image = $image;
        $z_view->showlist = $this->_getIspList($param);

        echo $z_view->render("showISP.phtml");
        
        }catch(Exception $e){
            echo "Error: " . $e->getMessage();
        }
 
    }
    
    
    public function showispupdatestoreAction(){
    
        $this->_startinit();
    
        $i_request = $this->getRequest();
    
        $v_qty = $i_request->getParam('qty');
        $v_zipcode = $i_request->getParam('zipcode');
        $v_item_id = $i_request->getParam('iid');
        $v_promise_date = $i_request->getParam('promise_date');
        $v_style_num = $i_request->getParam('stynum');
        $v_sku = $i_request->getParam('sku');
        $v_organization_id = $i_request->getParam('organization_id');
    
        $v_sku_options = explode("-", $v_sku);
        $v_segment2 = $v_sku_options[1];
        $i_productPage = new ProductPage($v_style_num);

        $param = array();
        $param['zipcode'] = $v_zipcode;
        $param['qty'] = $v_qty;
        $param['item_id'] = $v_item_id;
        $param['promise_date'] = $v_promise_date;
        $param['organization_id'] = $v_organization_id;
        $param['isPersonalizedstyle'] = $i_productPage->isPersonalizedstyle();
         
        $scene7 = new Scene7($v_style_num);
        $image = $scene7->getSmallImage('P');
    
        //Include view
        $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
        $z_view->button_title = $this->button_title = "Update Cart";
        $z_view->form_id = "form_isp_update_stores";
        $z_view->form_on_submit = "golfsmith.isp.updateItems()";
        
        //variable to view
        $z_view->image = $image;
        $z_view->showlist = $this->_getIspList($param);
        //$z_view->organization_id = $v_organization_id;
    
        echo $z_view->render("showISP.phtml");
    
    }
    
    
    //when search in zipcode input box
    public function showisplistAction(){
        $this->_startinit();
        
        $i_request = $this->getRequest();
        $v_qty = $i_request->getParam('qty');
        $v_zipcode = $i_request->getParam('zipcode');
        $v_item_id = $i_request->getParam('iid');
        $v_promise_date = $i_request->getParam('promise_date');
        
        $param = array();
        $param['zipcode'] = $v_zipcode;
        $param['qty'] = $v_qty;
        $param['item_id'] = $v_item_id;
        $param['promise_date'] = $v_promise_date;
        
        $_SESSION['zipcode_geoLocation'] = $v_zipcode;
        //Include view
        $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
        
        
        echo $this->_getIspList($param);
    }
    
    
    private function _getIspList($param){
        
        
        $v_zipcode = $param['zipcode'];
        
        $i_storeFinder = new StoreFinder();
        $a_closesStores= $i_storeFinder->getClosesStoresByZipCode($v_zipcode);

        $ispStores = array();
        while($row = mysqli_fetch_assoc($a_closesStores)){
            $args = array();
            $args['promise_date'] =  date('m-d-Y', $param['promise_date']);
            $args['qty'] = $param['qty'];
            $args['org_id'] = $row['organization_id'];
            $args['item_id'] = $param['item_id'];

            $i_isppage = new Isppage();
            $result = $i_isppage->getDateAndQty($args);
            
            $row['qty_onhand'] = $result['qty_onhand'];
            $row['return_date'] = $result['return_date'];
            $row['isIspAvailable'] = $i_isppage->isIspAvailable($param['item_id'], $result['qty_onhand']);
            
            if($row['organization_id'] == $param['organization_id']){
                $row['qty_value_selected'] = $param['qty'];
            }
            
            $ispStores[] = $row;
        }
        
        //print_r($ispStores);
        
        $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
        
        //variable to view
        $z_view->closesStores = $ispStores;
        $z_view->button_title = $this->button_title;
        $z_view->isPersonalizedstyle = $param['isPersonalizedstyle'];
        
        
        return $z_view->render("ISPList.phtml");
        
    }
    
    public function showstoreAction(){
        
        $this->_startinit();
        
        $i_request = $this->getRequest();
        $v_qty = $i_request->getParam('qty');
        $v_zipcode = $i_request->getParam('zipcode');
        $v_item_id = $i_request->getParam('iid');
        $v_promise_date = $i_request->getParam('promise_date');
        
        
        if(empty($v_zipcode)){
            if(isset($_SESSION['s_zipcode']) && !empty($_SESSION['s_zipcode']) ){
                $v_zipcode = $_SESSION['s_zipcode'];
                if( strlen($v_zipcode) > 5 ){
                    $v_zipcode = substr($v_zipcode, 0, 5);
                }
            
            }else{
                //Find zipcode by IP Address
                //78753 HQ store -- default store
                $v_zipcode = '78753'; //78753
            
            }
        }else{
            $_SESSION['zipcode_geoLocation'] = $v_zipcode;
        }
        
        
        $i_storeFinder = new StoreFinder();
        $a_closesStores= $i_storeFinder->getClosesStoresByZipCode($v_zipcode);

        $ispStores = array();
        while($row = mysqli_fetch_assoc($a_closesStores)){
            $args = array();
            $args['promise_date'] =  date('m-d-Y', $v_promise_date);
            $args['qty'] = $v_qty;
            $args['org_id'] = $row['organization_id'];
            $args['item_id'] = $v_item_id;
        
            $i_isppage = new Isppage();
            $result = $i_isppage->getDateAndQty($args);
        
            $row['qty_onhand'] = $result['qty_onhand'];
            $row['return_date'] = $result['return_date'];
        
            $ispStores[] = $row;
        }
        
        //if no nearby stores are found used austin HQ store as default store
        if(count($ispStores) == 0){
            
            //78753 HQ store -- default store
            $v_zipcode = '78753'; //78753
            $a_closesStores= $i_storeFinder->getClosesStoresByZipCode($v_zipcode);
            
            $ispStores = array();
            while($row = mysqli_fetch_assoc($a_closesStores)){
                $args = array();
                $args['promise_date'] =  date('m-d-Y', $v_promise_date);
                $args['qty'] = $v_qty;
                $args['org_id'] = $row['organization_id'];
                $args['item_id'] = $v_item_id;
            
                $i_isppage = new Isppage();
                $result = $i_isppage->getDateAndQty($args);
            
                $row['qty_onhand'] = $result['qty_onhand'];
                $row['return_date'] = $result['return_date'];
            
                $ispStores[] = $row;
            }
        }

        
        $stringMessage = "";
        if( count($ispStores) > 0 ){

            $ispType = 0;
            if($v_qty > $ispStores[0]['qty_onhand']){
                $optionISP = "Ship to";
                $ispType = 1;
            }else{
                $optionISP = "Pickup at";
                $ispType = 2;
            }
            
            $stringMessage = $optionISP . " <strong>" . $ispStores[0]['location_code'] . "</strong> or ";
        }
        
        $response = array();
        
        $i_isppage2 = new Isppage();

        $message =  $stringMessage . '<a href="" id="change_store_product" class="change_store_product" data-isp_type="' . $ispType . '" data-org_id="' . $ispStores[0]['organization_id'] . '" onclick="return golfsmith.isp.showMenu(\'ps\')">Select Another Store</a>';
        
        $response['zip'] = $v_zipcode;
        $response['msg'] = $message;
        $response['organization_id'] = $ispStores[0]['organization_id'];
        
        $isIspAvailable = $i_isppage2->isIspAvailable($v_item_id, $ispStores[0]['qty_onhand']);
        if( $isIspAvailable == "Y" || $isIspAvailable == "D"){
            $response['is_isp_available'] = true;
        }else{
            $response['is_isp_available'] = false;
        }

        
        // combining two ajax functions
        // removing  cart_items.js chose_product() when ajax is called
        // adding the get ATP here
        $skunumber = ProductPage::getSKUNumber($v_item_id);
        
        $checkATP = ProductPage::checkATPProc($v_item_id);
        
        
        $embargo = strtotime($checkATP["embargo_date"]);
        $current_date = strtotime(date("Y/m/d"));
        
        if($embargo > $current_date){
            $response['is_isp_available'] = false;
        }

        if ($checkATP["status"] == "INVALID" || $checkATP["totalQTY"] < 1 ){
            // there are some error or there are no quintity
            $response["status"]="invalid";
            $response["skunumber"]=$skunumber;
            $response["message"]="This Item is out of stock"; // here is the msg for you in case something wrong
        } elseif ($checkATP["status"] == "VALID"){

            $response["status"]="valid";
            $response["skunumber"] = $skunumber;
            $response["message"]= $checkATP["MSG"]; // here is here the message of the status depond on some logic here
            $response["promisedDate"]= $checkATP["promisedDate"];

            
        }elseif ($checkATP["status"]	== "OLDCODE"){
            $response["status"]="valid";
            $response["skunumber"] = $skunumber;
            $response["message"]= date(" F j, Y",strtotime($checkATP["promise_date"]));
        }
        
       
       
        
        echo json_encode($response);
        
    }
    
    
    public function updateispwarehouseAction(){
        
        $this->_startinit();

        $i_request = $this->getRequest();

        $order_number = $_SESSION['s_order_number'];
        $v_line_number = $i_request->getParam('line_number');
        $v_organization_id = $i_request->getParam('organization_id');
        $v_line_type = $i_request->getParam('line_type');
        $v_iid = $i_request->getParam('iid');
        $v_qty = $i_request->getParam('qty');
        
        $i_order = new Order($order_number);
        $i_order->updateISPWarehouse($v_line_number, $v_organization_id, $v_line_type);
        
        $atp_arr = array();
        $atp_arr[] = array(
            "iiv" => $v_iid,
            "qty" => $v_qty
        );
        
        $ATPresults = ATPExtendedSoap::checkATPProc($atp_arr,"Shoppingcart");
        
        $ATPresults['isISPOnly'] = $i_order->isISPOnly();
        
        return  die(json_encode($ATPresults));
        
    }
    
}