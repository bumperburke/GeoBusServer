<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
**/
//Set the timezone
date_default_timezone_set('Europe/Dublin');

class Conn {
	
	private $conn;
	
	function conn(){
		
	}
	
	function connect() {
		include_once(dirname(__FILE__) . '/config.php');
		
		$this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASS, DB_NAME, PORT);
		
		if(mysqli_connect_errno()){
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}
		
		return $this->conn;
	}
	
}


?>
