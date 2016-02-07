<?php

class DbOps {
	
	private $conn;
	
	function DbOps(){
		require_once dirname(__FILE__) . '/Conn.php';
		
		$db = new Conn();
		$this->conn = $db->connect();
	}
	
	public function registerUser($forename, $surname, $dob, $sex, $email, $pass){
		$response = array();
		
		if(!$this->checkExistingUsers($email)){
			$query = $this->conn->prepare("INSERT INTO users(forename, surname, birthdate, sex, email, password) VALUES (?,?,?,?,?,?)");
			$query->bind_params("qqqqqq", $forename, $surname, $dob, $sex, $email, $pass);
			
			$result = $query->execute();
			$query->close();
			
			if($result){
				return "Success";
				//return "Account Created Succesfully";
			} else {
				return "Fail";
			}
		} else {
			return "Duplicate User";
		}
		
		return $response;
	}
	
	private function checkExistingUsers($email){
		$query = $this->conn->prepare("SELECT email FROM users WHERE email = ?");
		$query->bind_params("q", $email);
		$query->execute();
		$query->store_result()->fetch_assoc();
		$num_rows = $query->num_rows;
		$query->close();
		return $num_rows > 0;
	}
	
	public function getAllUsers(){
		$query = $this->conn->prepare("SELECT * FROM users");
		$query->execute();
		$users = $query->get_result();
		$query->close();
		return $users;
	}
}

?>