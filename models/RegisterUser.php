<?php

require_once ('models/SiteInit.php');

class Registeruser{
    
    private $i_site_init;
    
    public function __construct() {
        
        $this->i_site_init = new SiteInit ();

        
        global $connect_mssql_db;
        $connect_mssql_db = 1;
        
        $this->i_site_init->loadInit($connect_mssql_db);
        
    }
    
    public function customerHasProfile($p_email){
        
 
        global $mssql_db;
        
        $web_user_name = $p_email;
        $v_return_status = 0;

        $v_stmt = mssql_init("customer.dbo.gsi_cust_is_register");
        
        gsi_mssql_bind($v_stmt, "@email", $web_user_name, 'varchar', 120);
        gsi_mssql_bind($v_stmt, "@return_status", $v_return_status, 'int', -1, true);

        $v_result = mssql_execute($v_stmt);
        
        if (!$v_result){
            display_mssql_error("customer.dbo.gsi_cust_is_register", "");
        }

        if ($v_return_status == 1)
        {
            return TRUE;
        }
        else 
        { 
            return FALSE;
        }

        
    }
    
    
}