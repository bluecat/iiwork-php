<?php
@header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');

class iiWork {
	static private $_import = array();
	public $debug = false;
	
	public $dir = "";
	public $pdir = ""; 
	public $ip = "";
	public $referer = "";
	public $lang = "";
	public $http = "";
	public $filename = "";
	public $date = "";
	public $time = "";
	
	/**
	 * 생성자
	 * @param Boolean $debug [false]
	 */
	public function __construct($debug = false) {
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
		
		// error handler 등록
		function handleException(Exception $e) {
			iiWork::error($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
		}
		function handleError($errno, $errstr, $errfile, $errline) {
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
			return true;			
		}
		function handleShutdown() {
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
			if ($isError) {
				iiWork::error($error['message'], $error['type'], $error['file'], $error['line']);
			}
		}
		set_exception_handler('handleException');
		set_error_handler('handleError');
		register_shutdown_function('handleShutdown');

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

	public function iiWork($debug = false) {
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