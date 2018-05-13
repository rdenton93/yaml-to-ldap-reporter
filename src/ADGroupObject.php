<?php 

// (c) MIT License - See LICENSE for details
  

namespace Report;

/**
* @author Robbie Denton - rjdenton93@gmail.com
*/

class ADGroupObject extends AbstractADObject{

	public function contextFormatter(){

		// Amend groupType if it exists

		if(isset($this->object["groupType"])){

			switch($this->object["groupType"][0]){

        		case 2:
            	$this->object["groupType"] = "Global Distribution";
            	break;

        		case 4:
            	$this->object["groupType"] = "local Distribution";
            	break;

        		case 8:
            	$this->object["groupType"] = "Universal Distribution";
            	break;

        		case -2147483646:
            	$this->object["groupType"] = "Global Security";
            	break;

        		case -2147483644:
            	$this->object["groupType"] = "Local Security";
            	break;

        		case -2147483640:
            	$this->object["groupType"] = "Universal Security";
            	break;


        		case -2147483643:
            	$this->object["groupType"] = "Builtin Group";
            	break;
    		}

		}

		return $this;

	}


}

 ?>


