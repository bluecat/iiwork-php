<?php
@header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');

/**
 * iiWork Framework
 * @author SHIM, SANGMIN a.k.a mini <i@iiwork.com>
 * @copyright Copyright (c) 2010, iiwork.com
 */
class iiWork {
	/**
	 * iiWork instance
	 * @var iiWork
	 */
	static private $instance;
	
	/**
	 * import시에 중복 방지를 위한 멤버 변수
	 * @var array
	 */
	static private $_import = array();
	
	/**
	 * debug mode
	 * @var bool
	 */
	public $debug = false;

	/**
	 * framework 경로
	 * @var unknown_type
	 */
	public $dir = "";
	public $pdir = "";
	public $ip = "";
	public $referer = "";
	public $lang = "";
	public $http = "";
	public $filename = "";
	public $date = "";
	public $time = "";

	final private function __clone() {}
	
	/**
	 * Singleton instance 가져오기
	 * @return iiWork
	 */
	static public function getInstance($debug = false) {
		if (!isset(self::$instance)) {
			$class = get_called_class();
			self::$instance = new $class($debug);
		}
		
		return self::$instance;
	}
	
	/**
	 * 생성자
	 * @param Boolean $debug [false]
	 */
	final private function __construct($debug = false) {
		$this->debug = $debug;
		
		// 버젼 검사
		if (version_compare(PHP_VERSION, '5.2.0', '<')) {
			throw new Exception('php 5.2.0  이상이 설치되어 있어야 합니다.');
		}

		// 디버그 모드 설정
		if ($debug) {
			error_reporting(E_STRICT);
			ini_set('display_errors', true);
			ini_set('ignore_repeated_errors', true); 
			ini_set('ignore_repeated_source', true); 
			ini_set('html_errors', true);
			
			self::import('debug');
		}
		
		// handler 등록
		set_exception_handler('iiWork::handleException');
		set_error_handler('iiWork::handleError');
		register_shutdown_function('iiWork::handleShutdown');

		// ip 검사
		if (empty($_SERVER['REMOTE_ADDR']) || preg_match("/[^0-9.]/", $_SERVER['REMOTE_ADDR'])) $_SERVER['REMOTE_ADDR'] = "unknown"; 
		
		// 값 설정
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
		$this->lang = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_ACCESS_LANGUAGE'] : "ko"; 
		$this->http = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
		$this->time = time();
		$this->date = date('Y-m-d H:i:s', $this->time);
		$this->filename = basename($_SERVER['PHP_SELF']);
		$this->dir = dirname(__FILE__);
		$this->pdir = !empty($_SERVER['HTTP_HOST']) ? "{$this->http}://{$_SERVER['HTTP_HOST']}".dirname($_SERVER['PHP_SELF'])."/" : "";
		
		// 경로 마지막에 seperate 붙이기
		if (!empty($this->dir) && !preg_match("/(\/|\\\)$/", $this->dir)) $this->dir .= DIRECTORY_SEPARATOR;		
//		if (!empty($_POST['script']) && $_POST['script'] == 'alert') __error('URI로 script값을 alert으로 지정할 수 없습니다');

		// php 환경설정
//		@ini_set('include_path', '.:'.dirname(__FILE__));
		ini_set('arg_separator.output', '&amp;');
		ini_set('pcre.backtrack_limit', '200000');
	}	

	private function iiWork($debug = false) {
		if(version_compare(PHP_VERSION, "5.0.0", "<")){
			$this->__construct($debug);
			register_shutdown_function(array($this, "__destruct"));          
		}
	}
	
	/**
	 * 기본값 설정
	 * @param reference $target
	 * @param all $value
	 */
	public function set(&$target, $value) {
		if (!isset($target) || $target === '') $target = $value;
	}
	
	/**
	 * 클래스 로더(package)
	 * @param string $name 콤마로 여러개를 부를 수 있다
	 */
	static public function import($name) {
		$names = array();

		if (preg_match("/[^a-z0-9_.,]/i", $name)) throw new Exception("class name에 올바르지 않은 문자가 있습니다 name:{$name}");
		if (strpos($name, ",") !== false) {			
			$names = explode(",", trim($name));
		}
		else {
			$names[] = $name;
		}
		
		foreach ($names as $val) {
			$val = trim($val);
			if (empty(self::$_import[$val])) {
				include_once(str_replace(".", DIRECTORY_SEPARATOR, $val).".class.php");
				$val = str_replace(".", "__", $val);
				self::$_import[$val] = true;
			}
		}
	}
	
	static public function error($msg, $code, $file, $line, $trace = '') {
		echo "<hr />file <b>{$file}</b> lines <b>{$line}</b><br /><pre>{$msg}</pre>";
		if ($trace) echo "\n<pre>{$trace}</pre>";
	}
	
	static public function handleException(Exception $e) {
		iiWork::error($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
	}
	
	static public function handleError($errno, $errstr, $errfile, $errline) {
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		return true;			
	}
	
	static public function handleShutdown() {
		$isError = false;
		if ($error = error_get_last()) {
			switch($error['type']) {
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
					$isError = true;
					break;
			}
		}
		
		// 에러일 떄
		if ($isError) {
			iiWork::error($error['message'], $error['type'], $error['file'], $error['line']);
		}
		
		// 정상일 때
		else {
			iiStage::end();
		}
	}
}

/**
 * 화면 구조 head/foot 출력에 관한 class
 * @author Administrator
 *
 */
class iiStage {
	const DOCTYPE_XHTML_1_0_TR = 'DOCTYPE_XHTML_1_0_TR';
	
	/**
	 * doctype contents
	 * @var string
	 */
	static public $html = '';
	
	/**
	 * metatag contents
	 * @var string
	 */
	static public $meta = '';
	
	/**
	 * headtag contents
	 * @var string
	 */
	static public $head = '';
	
	/**
	 * bodytag contents
	 * @var string
	 */
	static public $body = '';
	
	/**
	 * start 실행 여부
	 * @var bool
	 */
	static public $isStart = false;

	private function iiStage() {}
	
	/**
	 * 
	 * @param string $type DOCTYPE_* 상수 참조
	 * @param string $lang 언어
	 */
	static public function setHTML($type = DOCTYPE_XHTML_1_0_TR, $lang = null) {
		$ii = iiWork::getInstance();		
		if ($lang != null) $ii->lang = $lang;
		
		switch ($type) {
			case DOCTYPE_XHTML_1_0_TR:
				self::$html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n";
				break;
		}		
		
		// language 설정
		$html .= "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='{$ii->lang}' lang='{$ii->lang}'>\n";
	}	
	
	static public function setMeta() {
		
	}
	
	static public function setHead() {
		
	}
	
	static public function setHTTPHeader() {
		
	}
	
	static public function setHeaderFile() {
		
	}
	
	static public function setHeader() {
		
	}
	
	static public function setFooterFile() {
		
	}
	
	static public function setFooter() {
		
	}
	
	static public function start() {
		// 설정 확인
		if (!self::$html) self::setHTML();
		
		// 출력
		
		
		self::$isStart = true;
	}
	
	static public function end() {
		if (self::$isStart) {
			
		}
	}
}

// 5.3.0 미만에서의 get_called_class 정의
if (!function_exists('get_called_class')) {
	function get_called_class() {
		$bt = debug_backtrace();
		$l = 0;
		
		do {
			$l++;
			$lines = file($bt[$l]['file']);
			$callerLine = $lines[$bt[$l]['line']-1];
			
			preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/', $callerLine, $matches);
		} while ($matches[1] == 'parent' && $matches[1]);
		
		return $matches[1];
	}
}

?>