<?php 
/****************************************************************************
*                                                                           *
*  Program Name :  AddtocartController.php                                           *
*  Type         :  MVC Controller                                                    *
*  File Type    :  PHP                                                      *
*  Location     :  /include/Controllers                                          *
*  Created By   :  Hafeez Ullah Arain                                       *
*  Created Date :  10/12/2010                                               *
*               :  Copyright 2010  Golfsmith International                  *
*---------------------------------------------------------------------------*
*                                                                           * 
* History:                                                                  *
* --------                                                                  *
* Date       By                  Comments                                   * 
* ---------- ---------------     --------------------                       *
* 10/12/2010 Hafeez Ullah         Initial Version                           *
* 05/25/2011 Hafeez Ullah		  Added segments 6 to 10 because in R12                                                                          *
*                                 we are showing up to 10...                *
*                                                                           *
****************************************************************************/

require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/AddtoCart.php');

class AddtocartController extends Zend_Controller_Action {
	
   public function addtocartviewAction(){
    $i_request=$this->getRequest();
    $i_request->setParamSources(array('_POST'));

    $v_qty=$i_request->getParam('qty');
    $v_sku=$i_request->getParam('sku');
    $v_xref=$i_request->getParam('xref');
    $v_cseq=$i_request->getParam('cseq');
    $v_fcst=$i_request->getParam('fcst');
    $v_lcode=$i_request->getParam('lcode');
    $v_segment2=$i_request->getParam('seg2');
    $v_segment3=$i_request->getParam('seg3');
    $v_segment4=$i_request->getParam('seg4');
    $v_segment5=$i_request->getParam('seg5');
    $v_segment6=$i_request->getParam('seg6');
    $v_segment7=$i_request->getParam('seg7');
    $v_segment8=$i_request->getParam('seg8');
    $v_segment9=$i_request->getParam('seg9');
    $v_segment10=$i_request->getParam('seg10');
    $v_item_id=$i_request->getParam('inv_id');
    $v_per_item=$i_request->getParam('per_item');
    $v_style=$i_request->getParam('stynum');
    $v_description=$i_request->getParam('description');
    $v_service_item=$i_request->getParam('service_item');
    $v_record_sequence=$i_request->getParam('record_sequence');
    

    $i_addtocart= new AddtoCart();
    $v_addtocart=$i_addtocart->addtocart_product($v_style,$v_description,$v_qty,$v_item_id,$v_record_sequence,$v_per_item,$v_segment2,$v_segment3,$v_segment4,$v_segment5,$v_segment6,$v_segment7,$v_segment8,$v_segment9,$v_segment10,$v_sku,$v_service_item,$v_lcode,$v_cseq,$v_fcst,$v_xref);
    
    echo $v_addtocart;	
  }
  
  /*
   * Added by Luis Vazquez
   * 09/10/2015
   */
  public function addtocartview2Action(){
      $i_request=$this->getRequest();
      
      $data = array();
      
      $data['qty']  =  $i_request->getParam('qty');
      $data['sku'] = $i_request->getParam('sku');
      $data['xref'] = $i_request->getParam('xref');
      $data['cseq'] = $i_request->getParam('cseq');
      $data['fcst'] = $i_request->getParam('fcst');
      $data['lcode'] = $i_request->getParam('lcode');
      $data['segment2'] = $i_request->getParam('seg2');
      $data['segment3'] = $i_request->getParam('seg3');
      $data['segment4'] = $i_request->getParam('seg4');
      $data['segment5'] = $i_request->getParam('seg5');
      $data['segment6'] = $i_request->getParam('seg6');
      $data['segment7'] = $i_request->getParam('seg7');
      $data['segment8'] = $i_request->getParam('seg8');
      $data['segment9'] = $i_request->getParam('seg9');
      $data['segment10'] = $i_request->getParam('seg10');
      $data['item_id'] = $i_request->getParam('iid');
      $data['per_item'] = $i_request->getParam('per_item');
      $data['style'] = $i_request->getParam('stynum'); //style number
      $data['description'] = $i_request->getParam('sdes'); //description
      $data['service_item'] = $i_request->getParam('service_item'); //---
      $data['record_sequence'] = $i_request->getParam('record_sequence');
      $data['ship_opt'] = $i_request->getParam('ship-opt');

      
      $data['item_isp_qty'] = $i_request->getParam('quantity');
      $data['organization_id'] = $i_request->getParam('org_id');
      $data['line_type'] = $i_request->getParam('isp_type');
      
      $i_addtocart = new AddtoCart();
      
      $v_addtocart = $i_addtocart->addtocart_product2($data);
      
      echo $v_addtocart;
  }
  
  
}

?>
