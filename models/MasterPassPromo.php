<?php
class MasterPassPromo
{
    public function isMPPromo($code)
    {
        global $web_db;
        
        // Prepare the statement
        $sql = "SELECT RealCode, Message
                FROM webonly.gsi_mp_promo
                WHERE GS = 1
                    AND StartDate <= NOW() AND (EndDate IS NULL OR EndDate > NOW())
                    AND FakeCode = '".mysql_escape_string($code)."'
                ORDER BY StartDate DESC
                LIMIT 1;";
        
        $rows = mysqli_query($web_db, $sql);
        display_mysqli_error($sql);
        
        $result = false;
        
        if($myrow = mysqli_fetch_array($rows))
        {
            $result = new stdClass();
            $_SESSION["mpPromoCode"] = $myrow["RealCode"];
            $result->Message = $myrow["Message"];
            
            // Only one promo allowed per order
            unset($_SESSION["s_source_code"]);
        }
        else
        {
            // Only one promo allowed per order
            unset($_SESSION["mpPromoCode"]);
        }
        
        mysqli_free_result($rows);
        
        return $result;
    }
}
?>