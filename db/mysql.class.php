<?php
iiWork::import('db.db');
class iiDBMysql extends iiDB {
	public $init_query;
	
	public function connect($host = "localhost", $id, $pass, $db = '') {
		$this->host = $host;
		$this->id = $id;
		$this->pass = $pass;
		$this->db = $db;
		
		echo $this->host;
		
		$this->sql = mysql_connect($host, $id, $pass, $this->no != 0 ? true : false);
		if (!$this->sql) {
			throw new Exception('DB 연결에 실패했습니다 '.mysql_error());
		}
		
		// 초기 쿼리
		if ($this->init_query) {
			$this->query($this->init_query);
		}
		
		// db 선택
		$this->select_db($db);
	}
	
	public function select_db($name) {
		if (!mysql_select_db($name, $this->sql)) {
			throw new Exception('DB 선택에 실패했습니다 '.mysql_error());
		}
	}
	
	public function close() {
		if (!empty($this->sql)) { 
			if (mysql_close($this->sql)) {
				unset($this->sql);
			}
		}
	}
	
	/**
	 * 쿼리문 실행
	 * @param String $query
	 */
	public function query($query) {
		$this->check();
		$query = trim($query);		
		
		$q = mysql_query($query, $this->sql);
		
		if ($q === false) {
			$errno = mysql_errno();
			$msg = mysql_error();

			// 쿼리가 여러줄이면 한줄로 바꿈
			$query = str_replace(array("\r\n", "\n"), array("\n", " "), $query);
			
			// 테이블 이름 뽑기
			$mat = array();
			$result = false;
			if (strpos($msg, '.MY') !== false) preg_match("/'(.+)\.MY'/i", $msg, $mat);
			
			// 테이블 복구
			switch ($errno) {
				case 145:
					$result = $this->query("REPAIR TABLE {$mat[1]}");
					break;
			}

			if ($result) {
				$this->query($query);
			}
			else {
				throw new Exception($msg);
			}
		}
		else {
			return $q;
		}
	}
	
	public function count($sql) {
		return mysql_num_rows($sql);
	}
	
	public function fetch($sql) {
		return mysql_fetch_assoc($sql);
	}
	
	public function getColumns($table) {
		$output = array();
		$output = $this->gets("SHOW COLUMNS FROM {$table}");
		
		if (is_array($output)) {
			return $output;
		}
		else {
			return false;
		}		
	}
}
?>
