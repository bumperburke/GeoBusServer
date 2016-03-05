<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
  */
date_default_timezone_set('Europe/Dublin');

class DbOps {

	private $conn;

	function DbOps($DBManager){
		$this->conn = $DBManager;
	}

	//Function to register user. Called in index.php in the /register path function.
	public function registerUser($forename, $surname, $age, $sex, $email, $pass){

		if($this->checkExistingUsers($email) == false){
			$stmt = "INSERT INTO user(forename, surname, age, sex, email, password) VALUES (?,?,?,?,?,?)";
			$query = $this->conn->prepareQuery($stmt);
			$this->conn->bindVal($query, 1, $forename, $this->conn->STRING);
			$this->conn->bindVal($query, 2, $surname, $this->conn->STRING);
			$this->conn->bindVal($query, 3, $age, $this->conn->INT);
			$this->conn->bindVal($query, 4, $sex, $this->conn->STRING);
			$this->conn->bindVal($query, 5, $email, $this->conn->STRING);
			$this->conn->bindVal($query, 6, $pass, $this->conn->STRING);

			$result = $this->conn->executeQuery($query);
			return "Success";
		} else {
			return "Duplicate User";
        }
    }
	

	private function checkExistingUsers($email){
		$stmt = "SELECT email FROM user WHERE email = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $email, $this->conn->STRING);
		$result = $this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);

		if(count($fetchedResults) > 0){
			return true;
		}else{
			return false;
        }
	}

	public function checkLoginCreds($email){
		$stmt = "SELECT email, password FROM user WHERE email = ?";
		$query = $this->conn->prepareQuery($stmt);
		$query->bindVal($stmt, 1, $email, $this->conn->STRING);
		$result = $this->conn->executeQuery($query);
		$fetchedResults = $this->conn>fetchResults($query);
		
		if(count($fetchedResults) > 0){
			return $fetchedResults;
		}else{
			$user = null;
			return $user;
		}
	}

	public function updateUserToken($email, $token, $expireTime){
		$stmt = "UPDATE user SET apiToken = ?, tokenExpire = ? WHERE email = ?";
		$query = $this->conn->prepareQuery($stmt);
		$query->bindVal($stmt, 1, $token, $this->conn->STRING);
		$query->bindVal($stmt, 2, $expireTime, $this->conn->STRING);
		$query->bindVal($stmt, 3, $email, $this->conn->STRING);
		$this->conn->executeQuery($query);
	}

	public function hasTokenExpired($token){
		$stmt = "SELECT tokenExpire FROM user WHERE apiToken = ?";
		$query = $this->conn->prepareQuery($stmt);
		$query->bindVal($stmt, 1, $token, $this->conn->STRING);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);
		$currentTime = date('Y-m-d H:i:s');
		if($currentTime > $fetchedResults['tokenExpire']){
			return true;
		}
		else{
			return false;
		}
	}

	public function keepTokenAlive($token, $expireTime){
		$stmt = "UPDATE user SET tokenExpire = ? WHERE apiToken = ?";
		$query = $this->conn->prepareQuery($stmt);
		$query->bindVal($stmt, 1, $expireTime, $this->conn->STRING);
		$query->bindVal($stmt, 2, $token, $this->conn->STRING);
		$this->conn->executeQuery($query);
	}

	public function tokenCheck($token){
		$stmt = "SELECT userID FROM user WHERE apiToken = ?";
		$query = $this->conn->prepareQuery($stmt);
		$query->bindVal($stmt, 1, $token, $this->conn->STRING);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);

		if(count($fetchedResults) > 0){
			return true;
		}
		else{
			return false;
		}
	}

	public function updateLocation($lat, $lon, $time, $busID){
		$location = 'POINT('.$lat." ".$lon.')';
		$stmt = "INSERT INTO location (geoLocation, timestamp, busID) VALUES (PointFromText(?), ?, ?)";
		$query = $this->conn->prepareQuery($stmt);
		$query->bindVal($stmt, 1, $location, $this->conn->STRING);
		$query->bindVal($stmt, 2, $time, $this->conn->STRING);
		$query->bindVal($stmt, 3, $busID, $this->conn->INT);
		$this->conn->executeQuery($query);
		
		return "success";
	}

	public function getLocation(){
		$stmt = "SELECT ST_Y(geoLocation) as Ypos, ST_X(geoLocation) as Xpos FROM location WHERE busID = 1";
		$query = $this->conn->prepareQuery($stmt);
		$query->executeQuery($query);
		$locations = $this->conn->fetchResults($query);
		return $locations;
	}

	public function getStops(){
		$stmt = "SELECT name, ST_X(location) as Xpos, ST_Y(location) as Ypos FROM stop WHERE routeID = 1;";
		$query = $this->conn->prepareQuery($stmt);
		$query->executeQuery($query);
		$data = $this->conn->fecthResults($query);
		return $data;
	}
	
	public function getTimetable($timetable){
		$stmt = "SELECT routeID FROM route WHERE routeName LIKE %?%;";
		$query = $this->conn->prepareQuery($stmt);
		$query->binVal($stmt, 1, $timetable, $this->conn->STRING);
		$query->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);
		
		
	}
}

?>
