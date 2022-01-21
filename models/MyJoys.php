<?php
require_once('Zend/View.php');

class MyJoys{
    
    public $v_style;
    
    // Display the customizer with the provided info
    public function getCustomizerPage($progId = -1, $packId = -1, $prodId = "", $instId = ""){
        $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
        $z_view->progId = $progId;
        $z_view->packId = $packId;
        $z_view->prodId = $prodId;
        $z_view->instId = $instId;
        echo $z_view->render("FJCustomizer.phtml");
    }
    
    // Check if a product is myjoys
    public function is_myjoys() {
        global $web_db;
    
        $v_sql = "
        SELECT
        myjoys_style,
        CLC,
        NFL,
        MLB,
        original
        FROM
        webonly.gsi_myjoys_styles
        WHERE
        gsi_style = '$this->v_style'
        ";
    
        $v_result = mysqli_query($web_db, $v_sql);
         
        $va_myjoys = array ();
    
        if (mysqli_num_rows($v_result)>0) {
            $c = 0;
            while($v_row = mysqli_fetch_assoc ($v_result)) {
                $va_col = array();
                $va_col['myjoys_style'] 	= $v_row['myjoys_style'];
                $va_col['CLC'] 				= $v_row['CLC'];
                $va_col['NFL'] 				= $v_row['NFL'];
                $va_col['MLB'] 				= $v_row['MLB'];
                $va_col['original']			= $v_row['original'];
                $va_myjoys[$c++] = $va_col;
            }
        } else
            $va_myjoys = $v_sql;
    
        mysqli_free_result($v_result);
        return $va_myjoys;
    }
}

?>