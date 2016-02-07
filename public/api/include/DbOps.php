<?php

class DbOps {
	
	private $conn;
	
	function DbOps(){
		require_once dirname(__FILE__) . '/Conn.php';
		
		$db = new Conn();
		$this->conn = $db->connect();
	}
	
	//Function to register user. Called in index.php in the /register path function.
	public function registerUser($forename, $surname, $dob, $sex, $email, $pass){
		
		if(!$this->checkExistingUsers($email)){
			$query = $this->conn->prepare("INSERT INTO users(forename, surname, birthdate, sex, email, password) VALUES (?,?,?,?,?,?)");
			$query->bind_param("ssssss", $forename, $surname, $dob, $sex, $email, $pass);
			
			$result = $query->execute();
			$query->close();
			
			if($result){
				return "Success";
			} else {
				return "Fail";
			}
		} else {
			return "Duplicate User";
		}
	}
	
	private function checkExistingUsers($email){
		$stmt = "SELECT email FROM users WHERE email = ?";
		$query = $this->conn->prepare($stmt);
		$show = $query->bind_param("s", $email);
		$query->execute();
		$query->store_result();
		$result = $query->num_rows;
		$query->close();
		
		if($result > 0){
			return true;
		}else{
			return false;
		}
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
