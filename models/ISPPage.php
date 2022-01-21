<?php

class Isppage {
    
    public function getDateAndQty($param){
        
        global $mssql_db;
        
        $v_item_id = $param['item_id'];
        $v_org_id = $param['org_id'];
        $v_qty = $param['qty'];
        $v_promise_date = $param['promise_date'];
        
        
        $v_sp = mssql_init("direct.dbo.gsi_isp_pickup_dates");
        gsi_mssql_bind($v_sp, "@p_item_id", $v_item_id, "varchar", 20, false);
        gsi_mssql_bind($v_sp, "@p_org_id", $v_org_id, "bigint", -1, false);
        gsi_mssql_bind($v_sp, "@p_qty", $v_qty, "bigint", -1, false);
        gsi_mssql_bind($v_sp, "@p_promise_date", $v_promise_date, "varchar", 16, false);
        gsi_mssql_bind($v_sp, "@p_return_date", $v_return_date, "varchar", 16, true);
        gsi_mssql_bind($v_sp, "@p_qty_onhand", $v_qty_onhand, "bigint", -1, true);
        
        $v_result = mssql_execute($v_sp);
        
        if (!$v_result){
            echo "2";
            display_mssql_error2("direct.dbo.gsi_isp_pickup_dates - call from ISPPage.php");
            //die('MSSQL error: ' . mssql_get_last_message());
        }
        
        mssql_free_statement($v_sp);
        mssql_free_result($v_result);
        
        $response = array();
        $response['qty_onhand'] = $v_qty_onhand;
        $response['return_date'] = $v_return_date;
        
        return $response;
    }
    
    public function get_image($p_style_num, $p_segment2){
        global $web_db;
        
        $v_sql  =   "SELECT full_name
                    FROM " . MS_WEBONLYDB_NAME.".gsi_scene7_data
                    WHERE (style_number = '$p_style_num' or style_number = '$p_style_num')
                    and image_type='im'
                    and segment2 = '$p_segment2'";
        
        $v_result = mysqli_query($web_db, $v_sql);
        
        //mysql_error();
        
        if (mysqli_num_rows($v_result) > 0) {
            $va_row =mysqli_fetch_assoc($v_result);

            $p_main_image = SCENE7_URL . $va_row['full_name'] . "?\$sm\$";
            return $p_main_image;
        }else{
            false;
        }

    }
    
    
    public function isIspAvailable($inventoryId, $qty){
        global $web_db;
        
        $inventoryId = mysqli_real_escape_string($web_db, $inventoryId);
        
        $v_sql  =   "SELECT store_pickup_flag, austin_to_store_flag, dropship_flag
                    FROM gsi_item_info_all
                    WHERE inventory_item_id ='{$inventoryId}'
                    AND (store_pickup_flag = 'N'
                    OR austin_to_store_flag = 'N')";
    
        $v_result = mysqli_query($web_db, $v_sql);
        
        $v_row = mysqli_fetch_assoc($v_result);
        
        //if is droopship and there is at least one item on the store is able to pick up store
        if($v_row['dropship_flag'] == "Y"){
            if($qty > 0){
                return 'D';
            }else{
                return 'N';
            }
        }else{

            if (mysqli_num_rows($v_result) > 0) {
                return 'N';
            }else{
                return 'Y';
            }
        }

    }

}