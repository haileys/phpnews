<?php

class DB {
	private $conn;
	public function __construct($dsn, $user, $pass) {
		$this->conn = new PDO($dsn, $user, $pass);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->queries = array();
	}
	public function query($sql, $params = array()) {
		$stmt = $this->conn->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
	    $start_time = microtime(true);
		$stmt->execute($params);
		$this->queries[] = array("query" => $sql, "params" => $params, "time" => microtime(true) - $start_time);
		
		$rows = array();
		while($row = $stmt->fetch()) {
		    $rows[] = $row;
		}
		return $rows;
	}
	public function non_query($sql, $params = array()) {
		$stmt = $this->conn->prepare($sql);
	    $start_time = microtime(true);
		$retn = $stmt->execute($params);
		$this->queries[] = array("query" => $sql, "params" => $params, "time" => microtime(true) - $start_time);
		return $retn;
	}
	public function last_id() {
	    return $this->conn->lastInsertId();
	}
}