<?php
define("ESCAPE_HTML", "<!--//>-->");

class iiDebug {
	static private $traceCount = 0;
	static private $times = array();	
	
	/**
	 * trace
	 * @param all $target
	 * @param Boolean $is_end
	 */
	static public function trace($target, $is_end = false) {
		self::$traceCount++;

		// parsing
		$data = print_r($target, 1);
		$data = str_replace(array("  ", "<"), array("&nbsp;&nbsp;", "&lt;"), $data);
		$data = preg_replace("/\[([^\]]+)\] \=\> /ismU", "<b>[\\1]</b> ", $data);
		$data = nl2br($data);		
		
		// GUI
		if (!$is_end) {
			$position = self::$traceCount * 50;
			echo "<div style='position:absolute; left:{$position}px; top:{$position}px; padding:5px; background-color:#fff; font:10px verdana; border:3px solid red; z-index:100;' ondblclick='this.parentNode.removeChild(this);'>{$data}</div>";
		}
		
		// inline
		else {
			echo ESCAPE_HTML."<hr />{$data}";
		}
	}
	
	/**
	 * 시간을 체크한다
	 * @param String $name prefix
	 */
	static public function check($name) {
		$microtime = microtime();
		$tmp = explode(" ", $microtime);
		$time = $tmp[0] + $tmp[1];
		
		// 결과값 넣기
		if (isset(self::$times[$name])) {
			self::$times[$name]['end'] = $time;
		}
		
		// 시작값 넣기
		else {
			self::$times[$name] = array(
				'start' => $time,
				'end' => null
			);
		}
	}
	
	/**
	 * 체크된 시간을 출력한다
	 * @param String $name
	 * @param Boolean $is_return
	 * @return string message is_return 이 true 일 때만 return 값이 있음 
	 */
	static public function printTime($name, $is_return = false) {
		if (!isset(self::$times[$name])) {
			$result = "didn't set check for {$name}";
		}
		else if (self::$times[$name]['end'] == null) {
			self::check($name);
		}
		
		if (isset(self::$times[$name])) {
			$result = sprintf("%f", (self::$times[$name]['end'] - self::$times[$name]['start']));
		}
		
		$msg = "[{$name}] {$result}\n";
		
		if ($is_return) {
			return $msg;
		}
		else {
			self::trace($msg);
		}
	}
	
	/**
	 * 모든 체크된 시간을 출력한다
	 */
	static public function printTimes() {
		$output = "";
		foreach (self::$times as $key => $val) {
			$output .= self::printTime($key, 1);
		}
		
		self::trace($output);
	}	
}
?>