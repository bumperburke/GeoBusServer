<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
  */
date_default_timezone_set('Europe/Dublin');

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
			$stmt = "INSERT INTO users(forename, surname, birthdate, sex, email, password) VALUES (?,?,?,?,?,?)";
			$query = $this->conn->prepare($stmt);
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
		$query->bind_param("s", $email);
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
	
	public function checkLoginCreds($email){
		$stmt = "SELECT email, password FROM users WHERE email = ?";
		$query = $this->conn->prepare($stmt);
		$query->bind_param("s", $email);
		$result = $query->execute();
		$query->store_result();
		$user = array();

		if($query->num_rows > 0){
			$query->bind_result($fetchedEmail, $fetchedPass);
			$data = $query->fetch();
			$user = array("email"=>$fetchedEmail, "password"=>$fetchedPass);
		}else{
			$user = null;
		}

		$query->close();
		return $user;
	}

	public function updateUserToken($email, $token, $expireTime){
		$stmt = "UPDATE users SET apiToken = ?, tokenExpire = ? WHERE email = ?";
		$query = $this->conn->prepare($stmt);
		$query->bind_param("sss", $token, $expireTime, $email);
		$query->execute();
		$query->close();
	}
	
	public function hasTokenExpired($token){
		$stmt = "SELECT tokenExpire FROM users WHERE apiToken = ?";
		$query = $this->conn->prepare($stmt);
		$query->bind_param("s", $token);
		$query->execute();
		$query->bind_result($fetchedExpirery);
		$currentTime = date('Y-m-d H:i:s');
		if($currentTime > $fetchedExpirery){
			return true;
		}
		else{
			return false;
		}
		$query->close();
	}
	
	public function keepTokenAlive($token, $expireTime){
		$stmt = "UPDATE users SET tokenExpire = ? WHERE apiToken = ?";
		$query = $this->conn->prepare($stmt);
		$query->bind_param("ss", $expireTime, $token);
		$query->execute();
		$query->close();
	}
	
	public function tokenCheck($token){
		$stmt = "SELECT userID FROM users WHERE apiToken = ?";
		$query = $this->conn->prepare($stmt);
		$query->bind_param("s", $token);
		$query->execute();
		$query->store_result();
		
		if($query->num_rows > 0){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function updateLocation($lat, $lon, $time, $devID){
		$location = 'POINT('.$lat." ".$lon.')';
		$stmt = "INSERT INTO locations (geoLocation, timestamp, deviceID) VALUES (PointFromText(?), ?, ?)";
		$query = $this->conn->prepare($stmt);
		$query->bind_param("ssi", $location, $time, $devID);
		$result = $query->execute();
		$query->close();
		if($result){
			return "success";
		}else{
			return "error";
		}
	}

	public function getLocation(){
		$stmt = "SELECT ST_Y(geoLocation) as Ypos, ST_X(geoLocation) as Xpos FROM locations WHERE deviceID = 1";
		$query = $this->conn->prepare($stmt);
		$query->execute();
		$location = $query->get_result();
		$query->close();
		return $location;
	}
}

?>
