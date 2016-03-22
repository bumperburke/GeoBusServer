<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
  */

//set timeozne
date_default_timezone_set('Europe/Dublin');

class DBManager {

	//Create class variables
	private $dbconn;
	private $host = DB_HOST;
	private $uname = DB_USERNAME;
	private $pass = DB_PASS;
	private $dbname = DB_NAME;
	private $vendor = DB_VENDOR;

	public $INT = PDO::PARAM_INT;
	public $STRING = PDO::PARAM_STR;

	//constructor
	function __construct(){
	}

	/*
	 * Function to open the database connection
	 */
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

	/*
	 * Function to prepare an SQL query
	 * 
	 * @param String query you wish to prepare
	 */
	function prepareQuery($query){
		$stmt = $this->dbconn->prepare($query);
		return $stmt;
	}

	/*
	 * Function to bind a value to a statement
	 * 
	 * @param String Statement that you want to bind to
	 * @param Integer Position where you want to bind value
	 * @param Mixed Value you want to bind
	 * @param Mixed Data type of the value
	 */
	function bindVal($stmt, $pos, $val, $type){
		$stmt->bindValue($pos, $val, $type);
	}

	/*
	 * Function to execute a query
	 * 
	 * @param String Prepared statement to execute
	 */
	function executeQuery($stmt){
		$stmt->execute();
	}

	/*
	 * Function to fetch the results from an executed query
	 * 
	 * @param Object Statement object that contains database rows
	 */
	function fetchResults($stmt){
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}

	/*
	 * Function to close the database connection.
	 */
	function closeConn(){
		$this->dbconn = null;
	}
}
