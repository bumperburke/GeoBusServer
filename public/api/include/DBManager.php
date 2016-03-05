<?php

date_default_timezone_set('Europe/Dublin');

class DBManager {
	
	private $dbconn;
	private $host = DB_HOST;
	private $uname = DB_USERNAME;
	private $pass = DB_PASS;
	private $dbname = DB_NAME;
	private $vendor = DB_VENDOR;
	
	public $INT = PDO::PARAM_INT;
	public $STRING = PDO::PARAM_STR;
	
	function __construct(){
	}
	
	function openConn(){
		try{
			$connection = $this->vendor.":host=".$this->host.";dbname=".$this->dbname.";charset=utf8";
			$this->dbconn = new PDO($connection, $this->uname, $this->pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
		}
		catch (PDOException $e){
			echo 'Connection Failed: '.$e->getMessage();
			exit;
		}
	}
	
	function prepareQuery($query){
		$stmt = $this->dbconn->prepare($query);
		return $stmt;
	}
	
	function bindVal($stmt, $pos, $val, $type){
		$stmt->bindValue($pos, $val, $type);
	}
	
	function executeQuery($stmt){
		$stmt->execute();
	}
	
	function fetchResults($stmt){
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	
	function closeConn(){
		$this->dbconn = null;
	}
}