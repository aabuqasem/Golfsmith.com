<?php

/*
 * Pages can be "P" = Product and "S" for search page. 
 * Search isn't really needed since the endeca job takes care of it
 * 
 */

class Scene7
{
    private $styleNumber;
    private $imageZero;
    private $badge;
    private $pages;
    
    function __construct($styleNumber)
    {
        $this->styleNumber = $styleNumber;
    }
    
    function getImageZero($styleNumber)
    {
        global $web_db;
        
        $sql = "SELECT full_name, b.ImageName as Badge, b.Pages
                FROM webonly.gsi_scene7_data s
                	LEFT JOIN webonly.gsi_scene7_badge_images bi ON s.style_number = bi.StyleNumber
                	LEFT JOIN webonly.gsi_scene7_badges b ON bi.BadgeID = b.ID AND NOW() >= b.StartDate AND (NOW() < b.EndDate OR b.EndDate IS NULL) AND b.GS = 1
                    ,webdata.gsi_style_info_all sia
                WHERE s.image_type = 'im' AND s.pos_num = 0 AND (s.style_number = '$styleNumber' OR s.style_number = sia.old_style) AND sia.style_number = '$styleNumber'
                    ORDER BY b.StartDate DESC
                    LIMIT 1;";
        $rows = mysqli_query($web_db, $sql);
        display_mysqli_error($sql);
        
        if ($myrow = mysqli_fetch_array($rows))
        {
            $this->imageZero = $myrow['full_name'];
            $this->badge = $myrow['Badge'];
            $this->pages = $myrow['Pages'];
        }
        
        mysqli_free_result($rows);
    }
    
    function makeUrl($imageType, $noBadge, $page = "0")
    {
        if(ISSET($this->badge) && strpos($this->pages, $page) !== false && !$noBadge)
        {
            return SCENE7_URL . $this->badge . "?\$$imageType\$&\$productimage=Golfsmith/" . $this->imageZero;
        }
        else
        {
            return SCENE7_URL . $this->imageZero . "?\$$imageType\$";
        }
    }
    
    function getMainImage($page, $noBadge = FALSE)
    {
        if(empty($this->imageZero))
        {
            $this->getImageZero($this->styleNumber);
        }
        
        if(!empty($this->imageZero))
        {
            return $this->makeUrl("main", $noBadge, $page);
        }
        else
        {
            return false;
        }
    }
    
    function getThumbImage($page, $noBadge = FALSE)
    {
        if(empty($this->imageZero))
        {
            $this->getImageZero($this->styleNumber);
        }
        
        if(!empty($this->imageZero))
        {
            return $this->makeUrl("thm", $noBadge, $page);
        }
        else
        {
            return false;
        }
    }
    
    function getSmallImage($page, $noBadge = FALSE)
    {
        if(empty($this->imageZero))
        {
            $this->getImageZero($this->styleNumber);
        }
        
        if(!empty($this->imageZero))
        {
            return $this->makeUrl("sm", $noBadge, $page);
        }
        else
        {
            return false;
        }
    }
}

?>