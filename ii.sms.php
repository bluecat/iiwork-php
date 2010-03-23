<?
# 미니아이 SMS 전송 - 클라이언트 모듈
# Rainbow Framework 의존성을 제거함

if (!function_exists('iiSMSConnect')) {

/**
 * SMS 접속
 * @class sms
 * @param
		$query: URL Parameter
 * @return String XML data
 */
function iiSMSSocket($query) {
	global $iiSMS, $mini;
	$msg = $data = '';

	// 미니아이 SMS Server 주소
	$host = 'mini-i.com';

	// 클라이언트 아이디
	$id = '';

	// 클라이언트 패스워드
	$pass = '';

	if (!empty($mini['set']['sms_id'])) $id = $mini['set']['sms_id'];
	if (!empty($mini['set']['sms_pass'])) $pass = $mini['set']['sms_pass'];

	if (empty($id)) $msg = 'SMS 아이디가 없습니다';
	if (empty($pass)) $msg = 'SMS 암호가 없습니다';

	if (!$msg) {
		// 가공
//		$pass = md5(md5($pass));
		$host = "http://{$host}/mt.php";
		$url = array();
		$query = "?id=".urlencode($id)."&pass=".urlencode($pass).$query;

		// 주소 파싱
		if (!empty($host)) {
			if (strpos($host, '://') === false) $host = "http://{$param['url']}";
			$url = parse_url($host);
		}

		// 쿼리 파싱
		$fp = fsockopen($url['host'], 80, $errno, $errstr, 5);
		if (!$fp) {
			$msg = "SMS 서버와 연결할 수 없습니다. 다시 시도해 주세요. $errstr ($errno) ".' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')';
		}	
		else {
			$header = "POST {$url['path']}{$query} HTTP/1.1\r\n";

			// 기본 헤더정보 입력
			$header .= "Host: {$url['host']}\r\n";
			$header .= "Connection: Close\r\n";
			$header .= "\r\n";

			fwrite($fp, $header);
			while (!feof($fp)):
				$data .= fgets($fp, 128);
			endwhile;
			fclose($fp);
		}
	}

	if ($msg) {
		$data = "<error>1</error><message>{$msg}</message>";
	}

	return $data;
} // END function

/**
 * SMS 전송
 * @param
		$to: 받는사람번호
		$from: 보내는사람번호
		$msg: 본문메세지
		$charset: 언어셋타입(UTF-8|EUC-KR)
 * @return String 값이 없으면 성공, 실패면 에러메세지가 리턴 됩니다
 */
function iiSMSSend($to, $from, $msg, $charset = 'UTF-8') {
	if (!$to) return array('error'=>1, 'msg'=>'받는사람 번호가 없습니다');
	if (!$from) return array('error'=>1, 'msg'=>'보내는사람 번호가 없습니다');
	if (!$msg) return array('error'=>1, 'msg'=>'본문 메세지가 없습니다');

	if (preg_match("/[^0-9\-\,]/", $to)) return array('error'=>1, 'msg'=>'받는사람 번호 형식이 잘못 되었습니다');
	if (preg_match("/[^0-9\-]/", $from)) return array('error'=>1, 'msg'=>'보내는사람 번호 형식이 잘못 되었습니다');
	if (strlen($msg) > 80) return array('error'=>1, 'msg'=>'메세지 길이가 80bytes를 초과하였습니다');

	$query = "&to=".urlencode($to)."&from=".urlencode($from)."&msg=".urlencode($msg);
	$data = iiSMSSocket($query);
	$result = iiSMSResult($data);

	return $result;
} // END function


/**
 * 잔여 SMS수 확인
 * @return Array
 */
function iiSMSCount() {
	$query = "&mode=left";
	$data = iiSMSSocket($query);
	$result = iiSMSResult($data);

	return $result;
} // END function


/**
 * 결과 확인
 * @return Array ('error' => 1|0, 'msg' => String)
 */
function iiSMSResult($data) {
	$output = $mat = $mat2 = array();

	preg_match("/\<error>([0-9]+)\<\/error>/i", $data, $mat);
	$output['error'] = (!empty($mat[1]) ? $mat[1] : "0");

	preg_match("/\<message>\<\!\[CDATA\[([^\]]+)\]\]\>\<\/message>/i", $data, $mat2);
	$output['msg'] = (!empty($mat2[1]) ? $mat2[1] : "");

//	$output['msg'] = $data;

	return $output;
} // END function

} // END function_exists
?>