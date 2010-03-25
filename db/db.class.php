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
	 * @desc new_link 때문에 db instance를 no로 관리한다
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
	
	/**
	 * DB 접속
	 * @param string $host
	 * @param string $id
	 * @param string $pass
	 * @param string $db
	 */
	abstract public function connect($host = "localhost", $id, $pass, $db = '');	
	
	/**
	 * DB 선택
	 * @param string $name
	 */
	abstract public function select_db($name);
	
	/**
	 * DB 연결 끊기
	 */
	abstract public function close();
	
	/**
	 * SQL Query 실행
	 * @param string $query
	 * @return link SQL Link
	 */
	abstract public function query($query);
	
	/**
	 * Query 결과 수를 준다. 실패시 False 리턴
	 * @param link $sql
	 * @return int
	 */
	abstract public function count($sql);
	
	/**
	 * SQL Fetch
	 * @param link $sql
	 * @return array column이 key로 된 배열을 리턴한다
	 */
	abstract public function fetch($sql);
	
	/**
	 * Column들을 가져온다
	 * @param string $table
	 * @return array
	 */
	abstract public function getColumns($table);
	
//	abstract public function getInsertId(); // 마지막 입력된 seq를 가져온다
	
	/**
	 * 쿼리문을 통해 자료를 갖고 온다
	 * <code>
	 * 		function test($data, $args) {
	 * 			foreach ($args as $key => $val) {
	 * 				echo "{$key}: {$val}";
	 * 			}
	 * 		}
	 * 		$sql->gets('SELECT * FROM test', array('test', array('name'->'john'));
	 *
	 * 		results:
	 * 			name: john
	 * </code>
	 * @param string $query
	 * @param function $func null인 경우 배열로 리턴된다
	 * @return array 자료가 없을 경우 false를 리턴한다
	 * @desc $func 에 array를 넣는다면 첫번째가 함수명, 두번째가 두번째 인자로 넘어온다
	 */
	public function gets($query, $func = null) {
		$q = $this->query($query);
		$output = array();
		$data = array();
		
		if ($this->count($q)) {
			while($data = $this->fetch($q)) {
				// listener 호출
				if ($func != null) {
					if (!is_array($func)) {
						$func($data);
					}
					else {
						$func[0]($data, $func[1]);
					}
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
	 * @desc column이 한개일 경우 문제가 생길 수 있음
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