<?php

/** 변수 체크
 * @class io
 * @param
		$target: 대상변수
		-name: 항목명
		-reg: 정규식 제한 (true일때 통과)
		-max: 최대 문자열 길이 제한(byte)
		-min: 최소 문자열 길이 제한(byte)
		-is_not: 없는 값의 허용여부
		-type:
			#mail		메일
			#homepage	홈페이지
			#jumin		주민등록번호
			#co_num		사업자번호
			#cp			휴대전화번호
			#id			아이디(숫자, 영어, 언더바)
			#num		숫자
  * @return boolean type 만 있을 경우 해당 type 여부 리턴
 */
function check(&$target, $param) {
	global $mini;
	$param = param($param);

	def($param['is_not'], 0);
	def($param['is_num'], 0);
	def($param['is_string'], 0);
	def($param['is_eng'], 0);
	def($param['is_kr'], 0);
	def($param['name'], '');
	def($param['max'], '');
	def($param['min'], '');
	def($param['reg'], '');
	def($param['type'], '');
	
	$msg = '';
	$check = 0;

	if ($target)
	switch ($param['type']):
		// 메일
		case 'mail':
			if (!preg_match("/[a-z0-9\.\-\_\+]+\@[a-z0-9\.\-\_]+/is", $target)) {
				$msg .= '메일 형식에 맞지 않습니다\\n';
				$check = 1;
			}
			if (strpos($target, '@') === false || strpos($target, '.') === false) {
				$msg .= '메일 형식은 반드시 @ 와 . 를 포함해야 합니다\\n';
				$check = 1;
			}
			if (strCut($target)<5) {
				$msg .= '메일 형식의 길이는 5자(byte)를 넘어야 합니다\\n';
				$check = 1;
			}
			break;

		// 홈페이지
		case 'homepage':
			if (strpos($target, '://') === false) $target = "http://{$target}";
			if (preg_match("/[[:space:]]+/is", $target)) {
				$msg .= '홈페이지 형식에 맞지 않습니다\\n';
				$check = 1;
			}
			break;

		// 주민등록번호
		case 'jumin':
			$target = str_replace('-', '', $target);

			if (strCut($target) != 13) {
				$msg .= '주민등록번호 형식의 길이는 13자여야 합니다\\n';
				$check = 1;
			}
			if (preg_match("/[^0-9]/is", $target)) {
				$msg .= '주민등록번호 형식은 숫자만 허용됩니다';
				$check = 1;
			}

			// 체크 시작
			$jumin_check_digit = '234567892345'; 
			$jumin_sum = 0; 
			
			for ($i=0; $i<12; $i++) 
				$jumin_sum += (substr($target, $i, 1) * substr($jumin_check_digit, $i, 1));

			$jumin_result = (11 - ($jumin_sum % 11)) % 10;
			if ($jumin_result != substr($target, 12, 1)) {
				$msg .= '주민등록번호 형식에 맞지 않습니다\\n';
				$check = 1;
			}

			if (!$check) {
				$target = substr($target, 0, 6).'-'.substr($target, 6, 7);
			}
			break;

		// 사업자등록번호
		case 'co_num':
			$target = str_replace('-', '', $target);

			if (strCut($target) != 10) {
				$msg .= '사업자등록번호 형식의 길이는 10자여야 합니다\\n';
				$check = 1;
			}
			if (preg_match("/[^0-9]/is", $target)) {
				$msg .= '사업자등록번호 형식은 숫자만 허용됩니다';
				$check = 1;
			}

			// 체크 시작
			$co_sum = 0;
			$co_check_digit = "137137135";

			for ($i=0; $i<9; $i++) 
				$co_sum += (substr($target, $i, 1) * substr($co_check_digit, $i, 1));

			$co_sum += ((substr($target, 8, 1) * 5) / 10);
			$co_result = ($co_sum % 10 != 0) ? (10 - ($co_sum % 10)) : 0;
			if ($co_result != substr($target, 9, 1)) {
				$msg .= '사업자등록번호 형식에 맞지 않습니다\\n';
				$check = 1;
			}

			if (!$check) {
				$target = substr($target, 0, 3).'-'.substr($target, 3, 2).'-'.substr($target, 5, 5);
			}
			break;

		// 휴대전화번호
		case 'cp':
			$target = str_replace('-', '', $target);

			if (preg_match("/[^0-9]/is", $target)) {
				$msg .= '휴대전화번호 형식은 숫자만 허용됩니다';
				$check = 1;
			}

			switch (substr($target, 0, 3)):
				case '010':
				case '011':
				case '016':
				case '017':
				case '018':
				case '019':
					break;

				default:
					$msg .= '휴대전화번호 앞번호가 올바르지 않습니다\\n';
					$check = 1;
			endswitch;

			if (strCut($target) != 10 && strCut($target) != 11) {
				$msg .= '휴대전화번호 형식의 길이는 10자 혹은 11자여야 합니다\\n';
				$check = 1;
			}

			if (strCut($target) == 10) {
				$target = substr($target, 0, 3).'-'.substr($target, 3, 3).'-'.substr($target, 6, 4);
			}
			else {
				$target = substr($target, 0, 3).'-'.substr($target, 3, 4).'-'.substr($target, 7, 4);
			}
			break;

		// 아이디
		case 'id':
			if (preg_match("/[^0-9a-z\_]/is", $target)) {
				$msg .= "{$param['name']} 형식은 숫자, 영어, 언더바(_)만 허용합니다\\n";
				$check = 1;
			}
			break;

		// 패스워드(2바이트 문자 금지)
		case 'pass':
			if (preg_match("/[^0-9a-z\!\@\#\$\%\^\&\*\(\)\_\+\-\=\[\]\;\,\.\/\{\}\:\<\>\?]/is", $target)) {
				$msg .= "{$param['name']} 형식은 숫자, 영어, 기호(묶음제외)만 허용합니다\\n";
				$check = 1;
			}
			break;		

		// 숫자
		case 'num':
			if (preg_match("/[^0-9\-]/i", $target)) {
				$msg .= "{$param['name']} 형식은 숫자만 허용합니다\\n";
				$check = 1;
			}
			break;
	endswitch;

	if ($param['reg']) {
		if (!preg_match($param['reg'], $target)) {
			$msg .= "형식이 올바르지 않습니다\\n";
			$check = 1;
		}
	}

	if ($param['max'] && strCut($target) > $param['max']) {
		$msg .= "최대 길이는 {$param['max']}자(byte) 입니다\\n";
		$check = 1;
	}

	if ($param['min'] && strCut($target) < $param['min']) {
		$msg .= "최소 길이는 {$param['min']}자(byte) 입니다\\n";
		$check = 1;
	}

	if (!$param['is_not'] && (!trim($target) || !preg_match("/[^	　[:space:]]/is", $target))) {
		$msg .= "빈 값을 허용하지 않습니다. 해당 값을 입력해주세요\\n";
		$check = 1;
	}


	//// 반환
	if ($param['name']) {
		if ($check)
			__error(array(
				"msg" => "{$param['name']} 검사 오류가 있습니다 :\\n{$msg}"
			));
	}
	else {
		if ($check) return false;
		return true;
	}
} // END function


/** 문자열 인덱싱
 * @class String
 * @param
		$data: 문자열
		$mode: tag뽑기모드일때 tag, 검색모드일때 search
 * @return Array index Strings
 */
function getIndex($data, $mode='') {
	global $mini;
	$output = array();

	//// 패턴 입력
		if ($mini['set']['lang'] == 'UTF-8') {
			$pattern = array(
				"\xEC\x99\x80",
				"\xEA\xB3\xBC",
				"\xEC\x9D\x84",
				"\xEB\xA5\xBC",
				"\xEC\x9D\x80",
				"\xEB\x8A\x94",
				"\xEC\x9D\xB4",
				"\xEA\xB0\x80",
				"\xEB\x8F\x84",
				"\xEA\xB3\xA0",
				"\xEC\x97\x90\xEC\x84\x9C",
				"\xEC\x97\x90\xEA\xB2\x8C",
				"\xEC\x97\x90",
				"\xEB\xB3\xB4\xEB\x8B\xA4",
				"\xEC\x9D\x98",
				"\xEC\x9D\xB4\xEB\x8B\xA4",
				"\xEB\x8B\x88\xEB\x8B\xA4",
				"\xEB\x8B\xA4",
				"\xEB\xA1\x9C\xEC\x84\x9C",
				"\xEB\xA1\x9C\xEC\x8D\xA8",
				"\xEB\xA1\x9C\xEB\xB6\x80\xED\x84\xB0",
				"\xEB\xB6\x80\xED\x84\xB0",
				"\xEC\x9C\xBC\xEB\xA1\x9C"
			);
		}
		else {
			$pattern = array(
				"\xBF\xCD",
				"\xB0\xFA",
				"\xC0\xBB",
				"\xB8\xA6",
				"\xC0\xBA",
				"\xB4\xC2",
				"\xC0\xCC",
				"\xB0\xA1",
				"\xB5\xB5",
				"\xB0\xED",
				"\xBF\xA1\xBC\xAD",
				"\xBF\xA1\xB0\xD4",
				"\xBF\xA1",
				"\xBA\xB8\xB4\xD9",
				"\xC0\xC7",
				"\xC0\xCC\xB4\xD9",
				"\xB4\xCF\xB4\xD9",
				"\xB4\xD9",
				"\xB7\xCE\xBC\xAD",
				"\xB7\xCE\xBD\xE1",
				"\xB7\xCE\xBA\xCE\xC5\xCD",
				"\xBA\xCE\xC5\xCD",
				"\xC0\xB8\xB7\xCE"
			);
		}

	//// 복수공백 제거, 공백기호 적용
		$data = preg_replace("/&#34;|&#039;|&#124;|[\\x28\\x29\\x2A-\\x2F\\x5B\\x5D\\x7B\\x7D\\x7E]/s", " ", $data);

	//// 태그, 특수기호 제거
		$data = preg_replace(array(
			"/\<[^\>]+\>/s", 
			"/&lt;|&gt;|&amp;/s",
			"/[\\x21-\\x2F\\x3A-\\x40\\x5B-\\x60\\x7B-\\x7E]/s"
		), "", $data);

	//// 공백을 기준으로 나눔
		$mat = preg_split("/\s+/is", $data, -1, PREG_SPLIT_NO_EMPTY);

	//// 나눠진 토큰을 기준으로 규칙 적용
		if (is_array($mat))
		foreach ($mat as $key=>$val):
			$val = trim($val);

			if ($val && strCut($val) >= 2 && $mode != 'search') {
				// 불필요 조사 제거(글 등록때)
					if (!$mode) {
						$val = preg_replace("/^(".implode("|", $pattern).")$/", "", $val);
					}
					else {
						$val = preg_replace("/(".implode("|", $pattern).")$/", "", $val);
					}

				if ($val) {
					if ($mode != 1) {
					// 1byte문자는 3글자, 2byte문자는 2글자를 기준으로 패턴 적용한다.
						$check = array();
						$check = strCut($val, 0, 'both');

						if (($check['is_multi'] && $check['length'] >= 4) || (!$check['is_multi'] && $check['length'] >= 3)) {
							while($val && strCut($val, 0) >= 3):
								$output[] = $val;
								$val = preg_replace("/^".($check['is_multi'] ? strCut($val, 2, '') : strCut($val, 1, ''))."/s", "", $val);
							endwhile;
						}
						else {
							$output[] = $val;
						}
					}
					else {
						$output[] = $val;
					}
				}
			}
			else {
				$output[] = $val;
			}
		endforeach;
	
	//// 중복 제거
		$output = array_unique($output);

	return $output;
} // END function


/** 로그 기록
 * @class 
 * @param
		-mode: 모드
		-field1: 필드1
		-field2: 필드2
		-field3: 필드3
		-field4: 필드4
		-field5: 필드5
		-ment: 내용
		-result: 결과값 [1|0]
		-target_member: 대상회원
  */
function addLog($param = '') {
	global $mini;
	$param = param($param);
	$check = 1;

	if (empty($param['mode'])) __error('기록 mode가 없습니다');
	if ($param['mode'] == 'point' && empty($mini['set']['use_log_point'])) $check = 0;

	if ($check) {
		iss($param['field1']);
		iss($param['field2']);
		iss($param['field3']);
		iss($param['field4']);
		iss($param['field5']);
		iss($param['ment']);
		iss($mini['member']);
		iss($mini['member']['no']);
		def($param['result'], 1);
		def($param['target_member'], $mini['member']['no']);
		def($param['date'], $mini['date']);
		def($param['ip'], $mini['ip']);
		if (is_array($param['ment'])) $param['ment'] = serialize($param['ment']);

		sql("INSERT INTO {$mini['name']['log']} SET ".query($param, 'update'));
	}
} // END function


?>