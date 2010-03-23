<?php
/**
 * DB Interface
 * @author mini
 *
 */
abstract class iiDB {
	static private $instance = array(); 

	/**
	 * 접속시 자동 쿼리 지정
	 * @var String query
	 */
	public $init_query;
	
	protected $no;	
	protected $sql;
	protected $host;
	protected $id;
	protected $pass;
	protected $db;	

	final private function __construct($no = 0) {
		$this->no = $no;
	}

	final private function __clone() {}
	
	/**
	 * Singleton instance 가져오기
	 * @param int instance no
	 * @return iiDB
	 * @description new_link 때문에 db instance를 no로 관리한다
	 */
	static public function getInstance($no = 0) {
		if (!isset(self::$instance[$no])) {
			$class = get_called_class();
			self::$instance[$no] = new $class($no);
		}
		
		return self::$instance[$no];
	}
	
	/**
	 * 연결이 되어 있는지 검사한다
	 */
	public function check() {
		if (!isset($this->sql)) throw new Exception('DB 연결이 필요합니다'); 
	}
	
	abstract public function connect($host = "localhost", $id, $pass, $db = ''); // DB 접속	
	abstract public function select_db($name); // DB를 선택한다
	abstract public function close(); // DB 접속을 닫는다
	abstract public function query($query); // 쿼리를 실행한다
	abstract public function count($sql); // 결과수 가져오기
	abstract public function fetch($sql); // 자료를 fetch 하여 return 한다
	abstract public function getColumns($table); // 컬럼명을 가져온다
//	abstract public function getInsertId(); // 마지막 입력된 seq를 가져온다
	
	/**
	 * 쿼리문을 통해 자료를 갖고 온다
	 * @param string $query
	 * @param function $func null인 경우 배열로 리턴된다
	 * @return array 자료가 없을 경우 false를 리턴한다
	 */
	public function gets($query, $func = null) {
		$q = $this->query($query);
		$output = array();
		$data = array();
		
		if ($this->count($q)) {
			while($data = $this->fetch($q)) {
				// listener 호출
				if ($func != null) {
					$func($data);
				}
				else {
					$output[] = $data;
				}
			}
			
			if ($func == null) return $output;
		}
		else {
			return false;
		}
	}
	
	/**
	 * 한개의 자료만 있다고 생각되는 경우 단순화하여 갖고 온다
	 * @param string $query
	 * @return mixed column이 한개일 경우 값만, 아닌 경우 key를 column 으로 가져온다
	 */
	public function get($query) {
		$data = $this->gets($query);
		
		if (empty($data[0])) return false;
		if (count($data[0]) > 1) {
			return $data[0];
		}
		else {
			return end($data[0]);
		}
	}
}
?>