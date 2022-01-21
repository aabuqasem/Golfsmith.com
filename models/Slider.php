<?php
require_once('Zend/View.php');
class Slider{

        private $v_max_display;
        private $arr_records;
        private $arrstart = array(1);
        private $arrend = array();

        private $v_slideshow;     
        private $label_slide;
        
		private $slideshowSpeed;
		private $animationDuration;
		private $directionNav;
		private $controlNav;
		private $keyboardNav;
		private $touchSwipe;
		private $prevText;
		private $nextText;
		private $pausePlay;
		private $randomize;
		private $slideToStart;
		private $animationLoop;
		private $pauseOnAction;
		private $pauseOnHover;  
   

		public function __construct($p_records, $p_max_cnt,$p_type) {
           $this->arr_records = array_unique($p_records);
           $this->v_max_display = $p_max_cnt;
         	if($p_type=="review")
         	{
         		require_once(PHP_INC_PATH."sliderconfig/reviewconfig.php");
         	}
         	if($p_type=="customer")
         	{
         		require_once(PHP_INC_PATH."sliderconfig/customerwhobought.php");
         	}  
         $this->v_slideshow = $slideshow;
         $this->label_slide = $labelslide;
         $this->slideshowSpeed = $slideshowSpeed;
		 $this->animationDuration = $animationDuration;
		 $this->directionNav = $directionNav;
		 $this->controlNav = $controlNav;
		 $this->keyboardNav = $keyboardNav;
		 $this->touchSwipe = $touchSwipe;
		 $this->prevText = $prevText;
		 $this->nextText = $nextText;
		 $this->pausePlay = $pausePlay;
		 $this->randomize = $randomize;
		 $this->slideToStart = $slideToStart;
		 $this->animationLoop = $animationLoop;
		 $this->pauseOnAction = $pauseOnAction;
		 $this->pauseOnHover =  $pauseOnHover;      
           
           
        }
        
        public function setSlideshow($p_slideshow)
        {
        	$this->v_slideshow = $p_slideshow;        	
        }

        public function displaySlider()
        {
        	if (count($this->arr_records) > 0)
        	{
                 $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));                 
                 $z_view->records = $this->arr_records;
                 $v_countlist = ceil(sizeof($this->arr_records)/$this->v_max_display);
                 $this->generatedArrayList($v_countlist);
                 $z_view->arrStart = $this->arrstart;
                 $z_view->arrEnd = $this->arrend;
                 $z_view->sliderlabel = $this->label_slide; 
                 $z_view->slideshow = $this->v_slideshow;
           		 $z_view->slideshowSpeed = $this->slideshowSpeed;  
				 $z_view->animationDuration = $this->animationDuration;  
		 		 $z_view->directionNav = $this->directionNav;  
		 		 $z_view->controlNav = $this->controlNav;  
		 		 $z_view->keyboardNav = $this->keyboardNav;  
		 		 $z_view->touchSwipe = $this->touchSwipe;  
		 		 $z_view->prevText = $this->prevText;  
				 $z_view->nextText = $this->nextText;  
		 		 $z_view->pausePlay = $this->pausePlay;  
		 		 $z_view->randomize = $this->randomize;  
		 		 $z_view->slideToStart = $this->slideToStart;  
		 		 $z_view->animationLoop = $this->animationLoop;  
		 		 $z_view->pauseOnAction = $this->pauseOnAction;  
		 		 $z_view->pauseOnHover = $this->pauseOnHover;         
                 
                 echo $z_view->render('slider.phtml');
        	}
        }


        function generatedArrayList($countlist)
        {       
                $k = 1; 
                $ke = 0;
                for($i=0;$i<$countlist;$i++)
                {       
                        $k = $k + $this->v_max_display;
                        $ke = $ke + $this->v_max_display;
                        $this->arrstart[] = $k;
                        $this->arrend = $ke;
                }       
        }       
}
