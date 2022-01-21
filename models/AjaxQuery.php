<?php

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
include_once('Zend/View.php');
include_once (FPATH . 'ene_bridge.inc');

class AjaxQuery {
  
  private $v_query;

  public function __construct($p_query) {
    $this->v_query = $p_query;
  }

  public function query() {
    $i_site_init = new SiteInit( );
    
    $i_site_init->loadInit();
    
    if (! empty( $this->v_query )) {
      $this->{$this->v_query}();
    }
  }

  private function profanityCheck() {
    global $web_db;

    $v_text = strip_tags(post_or_get('personalization_text'));
    $v_sql = "select filter_text 
              from webonly.gsi_per_profanity_filter";
    $v_result = mysqli_query($web_db, $v_sql);
    while($row=mysqli_fetch_array($v_result))
    {
      $v_pos = strpos(strtolower($v_text),strtolower($row['filter_text']));
      if($v_pos === false){
        $v_num_records=0;
      }else{
        $v_num_records=1;
    	break;
      }
    	
    }
    echo  $v_num_records;

  }

}
?>
