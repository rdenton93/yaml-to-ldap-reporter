<?php 

// (c) MIT License - See LICENSE for details
 
namespace Report;

/**
* @author Robbie Denton - rjdenton93@gmail.com
*/

Abstract class AbstractADObject implements ADObjectInterface {

	use DateConversionTrait;

	public $object;
	public $date_format;

	public function __construct($object){

		$this->object = $object;
		$this->date_format = "d-m-Y";

	}

	public function generalFormatter(){

		if(isset($this->object["lastLogon"])){

			$this->object["lastLogon"][0] = ($this->object["lastLogon"][0]!=0)? $this->convertTimeStamp($this->object["lastLogon"][0]):"Never";
		}

		if(isset($this->object["whenCreated"])){

			$this->object["whenCreated"][0] = $this->convertLdapTime($this->object["whenCreated"][0])->format($this->getDateFormat());
		}

		if(isset($this->object["whenChanged"])){

			$this->object["whenChanged"][0] = $this->convertLdapTime($this->object["whenChanged"][0])->format($this->getDateFormat());
		}

		return $this;
	}

	public function convertTimeStamp($timestamp){

		return date($this->getDateFormat(),$this->convertTimestampToDate($timestamp));

	}

	public function convertLdapTime($ldap_time)
	{

    	return $this->convertLdapTimeToDate($ldap_time,$this->getDateFormat());
	
	}

	public function getDateFormat(){

		return $this->date_format;
	}


	abstract function contextFormatter();

	
}

?>