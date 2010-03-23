<?php

//// 접근거부 설정
if (!empty($mini['set']['filter_agent'])) {
	$tmp_str = '';
	foreach (explode(',', $mini['set']['filter_agent']) as $val):
		$tmp_str .= "|".quotemeta(trim($val));
	endforeach;
	if ($tmp_str) {
		$tmp_str = substr($tmp_str, 1);
		if (preg_match("/".$tmp_str."/i", $_SERVER['HTTP_USER_AGENT'])) __error('사이트를 열람할 권한이 없습니다.');
		unset($tmp_str);
	}
}
if (!empty($mini['set']['filter_ip'])) {
	$tmp_str = '';
	foreach (explode(',', $mini['set']['filter_ip']) as $val):
		$tmp_str .= "|".quotemeta(trim($val));
	endforeach;
	if ($tmp_str) {
		$tmp_str = substr($tmp_str, 1);
		if (preg_match("/".$tmp_str."/", $mini['ip'])) __error('사이트를 열람할 권한이 없습니다.');
		unset($tmp_str);
	}
}


//// 테이블명 설정
$mini['name'] = array(
	'admin' => $mini['set']['name'] . '_admin',
	'member' => $mini['set']['name'] . '_member',
	'site' => $mini['set']['name'] . '_site',
	'board' => $mini['set']['name'] . '_board_',
	'cmt' => $mini['set']['name'] . '_cmt_',
	'file' => $mini['set']['name'] . '_file',
	'ses' => $mini['set']['name'] . '_session',
	'memo' => $mini['set']['name'] . '_memo',
	'report' => $mini['set']['name'] . '_report',
	'trash' => $mini['set']['name'] . '_trash',
	'zipcode' => $mini['set']['name'] . '_zipcode',
	'log' => $mini['set']['name'] . '_log',
	'counter' => $mini['set']['name'] . '_counter',
	'counter_log' => $mini['set']['name'] . '_counter_log',
	'search' => $mini['set']['name'] . '_search'
);


/** 초를 일시분초로 변환해 사용한다
 * @class date
 * @param
		$sec: 초
		$mode: 표시방법
			-simple: 가장 큰 단위만 표기한다. 한글 표기가 따라붙는다.
			-full: 전체를 표기한다. 한글 표기
 * @return String
 */
function sectotime($sec) {
	$result = array();
	foreach (array('day'=>86400, 'hour'=>3600, 'min'=>60, 'sec'=>1) as $key=>$val):
		if ($sec >= $val) {
			$result[$key] = intVal($sec / $val);
			$sec = $sec % $val;
		}
		else {
			$result[$key] = 0;
		}
	endforeach;

	return $result;	
}

function dateName($str) {
	$name = '';
	switch ($str):
		case 'day': $name = '일'; break;
		case 'hour': $name = '시간'; break;
		case 'min': $name = '분'; break;
		case 'sec': $name = '초'; break;
	endswitch;

	return $name;
}

function dateSec($sec, $mode = 'simple') {
	$result = array();
	$result = sectotime($sec);

	switch ($mode):
		case 'simple':
			foreach ($result as $key=>$val):
				if ($val)
					break;
			endforeach;
			
			return $val.datename($key);
			break;

		case 'full':
			$output = '';
			foreach ($result as $key=>$val):
				if (!$val) continue;
				$output .= " ".$val.datename($key);
			endforeach;

			return trim($output);
			break;
	endswitch;
}


/** URI parameter 갖고옴
 * @class default
 * @param
		$except: 제외하고 싶은 변수명(콤마로 복수개 가능)
		$mode: 앞에 올 문자를 선택 / &
 * @return string URI parameter
 */
function getURI($except = '', $mode = '&amp;') {
	$tmp_get = $_GET;
	$out='';
	$mode2 = $mode == '&' ? '&' : '&amp;';

	unset($tmp_get['x']);
	unset($tmp_get['y']);	
	$except = $except ? $except.",pageKey,pageCmtKey,PHPSESSID,cDiv,cStart,cPage,cQuick,cAnd,cNo" : "pageKey,pageCmtKey,PHPSESSID,cDiv,cStart,cPage,cQuick,cAnd,cNo"; //pageKey 는 넘기지 않는다

	if($except) {
		$tmp = explode(',', $except);
		foreach($tmp as $val):
			if (isset($tmp_get[trim($val)])) unset($tmp_get[trim($val)]);
		endforeach;
	}

	// Param 만들기
	foreach($tmp_get as $key=>$val):
		if (is_array($val)) {
			foreach ($val as $key2=>$val2):
				$out .= "{$mode2}{$key}[{$key2}]=".urlencode($val2);
			endforeach;
		}
		else if ($val) {
			$out .= "{$mode2}{$key}=".urlencode($val);
		}
	endforeach;

	if($out) $out = $mode . substr($out, strlen($mode2));
//	$out.="exp:{$except}";
	return $out;
}


/** 변수 문자셋을 지정한 코드로 무조건 변경
 * @class str 
 * @param
		$data: 대상 값
		$lang: 지정 문자열
		$lang_from: 예상 문자열
 * @return String 변환된 문자열
 */
function convChar($data, $lang='', $lang_from='') {
	global $mini;
	def($lang, $mini['set']['lang']);
	def($lang_from, $mini['set']['lang_from']);

	if ($data != @iconv($lang, "{$lang}//IGNORE", $data)) {
		$data = iconv($lang_from, "{$lang}//IGNORE", $data);
	}

	return $data;
}


/** Byte 문자열과 숫자의 상호변환 (encode 숫자 -> 문자열)
 * @class default
 * @param
		$str: 대상 문자열
		$mode: 모드 [encode|decode]
		$add: 추가적으로 붙는 문자
		$stop: 지정 [T|G|M|K]
 * @return String
 */
function getByte($str, $mode = 'encode', $add='B', $stop='') {
	$str = preg_replace("/[^0-9mgkt]+/i", "", $str);
	$arr = array('T' => 0, 'G' => 1, 'M' => 2, 'K' => 3);

	$mat = array(); $output = 0;
	preg_match("/[0-9]+/", $str, $mat);
	$num = (int)(!empty($mat[0])) ? $mat[0] : 0;

	if ($mode == 'decode') {
		if (preg_match("/k/i", $str)) $output = $num * 1024;
		if (preg_match("/m/i", $str)) $output = $num * 1048576;
		if (preg_match("/g/i", $str)) $output = $num * 1073741824;
		if (preg_match("/t/i", $str)) $output = $num * 1099511627776;
	}

	else {
		foreach (array(1099511627776, 1073741824, 1048576, 1024) as $key=>$val):
			if ($num / $val >= 1 || ($stop && $arr[$stop] == $key)) {
				switch ($key):
					case 0: $name = 'T'; break;
					case 1: $name = 'G'; break;
					case 2: $name = 'M'; break;
					case 3: $name = 'K'; break;
				endswitch;
				$output = round($num / $val, 1).$name.$add;
				break;
			}
			else if ($key == 3) {
				$output = $num.$add;
				break;
			}
		endforeach;		
	}

	return $output;	
}


/** level_admin을 구한다
 * @class member
 * @param
		$data: 회원정보
		$board_data: 게시판정보
		$site_data: 그룹정보
		$ret: return 여부
 * @return 
 */
function checkLevelAdmin(&$data, $board_data = '', $site_data= '', $ret = 0) {
	global $mini;
	
	if (empty($board_data) && !empty($mini['board'])) $board_data = $mini['board'];
	if (empty($site_data) && !empty($mini['site'])) $site_data = $mini['site'];

	$data['board_admin'] = array();
	$data['site_admin'] = array();
	$data['level_admin'] = 0;
	$data['is_god'] = $data['is_admin'] = $data['is_site'] = $data['is_board'] = 0;
	if (!empty($data['admin'])) {
		$tmp_arr = array();

		foreach (getStr($data['admin']) as $key=>$val):
			$tmp = array();
			$tmp = explode(":", $val);
			$tmp_arr[$key] = ($tmp[0] == 'admin' || $tmp[0] == 'god') ? $tmp[0] : array('mode' => $tmp[0], 'value' => $tmp[1]);
			
			if ($tmp[0] == 'god') {
				$data["is_god"] = 1;
				$data["is_admin"] = 1;
				$data['is_site'] = 1;
				$data['is_board'] = 1;
				$data['level_admin'] = 4;
			}
			else if ($tmp[0] == 'admin') {
				$data["is_admin"] = 1;
				$data['is_site'] = 1;
				$data['is_board'] = 1;
				if (empty($data['level_admin']) || $data['level_admin'] < 3) $data['level_admin'] = 3;
			}
			else if ($tmp[0] == 'site' && !empty($site_data['no']) && $tmp[1] == $site_data['no']) {
				$data['is_site'] = 1;
				$data['is_board'] = 1;
				if (empty($data['level_admin']) || $data['level_admin'] < 2) $data['level_admin'] = 2;
			}
			else if ($tmp[0] == 'board' && !empty($board_data['no']) &&  $tmp[1] == $board_data['no']) {
				$data['board_admin'][] = $tmp[1];
				$data['is_board'] = 1;
				if (empty($data['level_admin']) || $data['level_admin'] < 1) $data['level_admin'] = 1;
			}

			if ($tmp[0] == 'site')
				$data['site_admin'][] = $tmp[1];
			if ($tmp[0] == 'board')
				$data['board_admin'][] = $tmp[1];
		endforeach;
	}

	if ($ret)
		return $data;
}


/** 권한여부를 체크
 * @class default
 * @param
		-name: 권한명
		-mode: 게시판관리, 그룹관리 선택 [board|site]
		-data: 자료 배열
		-and: and 조건
		-member: 비교대상 자료
		-site: 사이트 자료
		-board: 게시판 자료
 * @return Boolean
 */
function getPermit($param = '') {
	global $mini;
	$param = param($param);
	$result = 0;
	$str = $and = '';
	iss($param['name']);
	iss($param['data']);
	iss($param['and']);
	iss($mini['member']);
	def($param['mode'], 'board');
	if (!empty($mini['board'])) def($param['member'], $mini['member']);
	if (!empty($mini['site'])) def($param['site'], $mini['site']);
	if (!empty($mini['board'])) def($param['board'], $mini['board']);
	def($str, $param['data']);
	def($and, $param['and']);
	
	if (!empty($param[$param['mode']]["permit_{$param['name']}"]))
		def($str, $param[$param['mode']]["permit_{$param['name']}"]);

	if (!empty($param[$param['mode']]["permit_{$param['name']}_and"]))
		def($and, $param[$param['mode']]["permit_{$param['name']}_and"]);

	if (is_array($str)) {
		foreach ($str as $key => $val):
			// 기본 입력
			$field_value = empty($param['member'][$val['field']]) ? '' : $param['member'][$val['field']];
			$value = $val['value'];

			// 특수 상황
			switch ($val['field']):
				case 'date':	
				case 'date_login':
					if (preg_match("/^\-[0-9]+$/", $value)) {
						$value = date("Y-m-d H:i:s", $mini['time'] - (3600 * str_replace('-', '', trim($value))));
					}
					if (preg_match("/^\+[0-9]+$/", $value)) {
						$value = date("Y-m-d H:i:s", strtotime($field_value) + (3600 * str_replace('+', '', trim($value))));
					}
					break;

				case 'admin':
					$field_value = empty($param['member']["is_{$value}"]) ? '' : $param['member']["is_{$value}"];
					$value = 1;
					break;

				// 그룹일 경우 회원그룹, 회원 그룹연결, 그룹의 그룹연결 순으로 체크한다
				case 'site':
					$value = 1;
					$field_value = inSite($value, $param['member'], $param['site']);
					break;
			endswitch;

			// 값 비교
			switch ($val['mode']):
				case '=':
					if ($field_value == $value) $result++;
					break;
				case '>=':
					if ($field_value >= $value) $result++;
					break;
				case '<=':
					if ($field_value <= $value) $result++;
					break;
				case '!=':
					if ($field_value != $value) $result++;
					break;
			endswitch;
		endforeach;

		$result = (($and && $result == $key + 1) || (!$and && $result)) ? 1 : 0;
	}
	else {
		$result = 1;
	}

	return $result;
}


/** 그룹 연결설정까지 확인해서 여부를 리턴한다
 * @class default
 * @param
		$no: 조회할 그룹
		$data: 기본 그룹 자료 (member.site, board.site)
		$site: 그룹 자료
 * @return Boolean
 */
function inSite($no, $data, $site = '') {
	global $mini;
	$result = 1;

	if ($no != $data['site'] && !inStr($no, $data['site_link']) && !inStr($no, $site['site_link'])) {
		$result = 0;
	}

	return $result;
}


/** 게시판 자료 parser
 * @class parser
 * @param
		$data: 대상자료 
		$mode: return mode
 */
function parseBoard(&$data, $mode=0) {
	global $mini;

	if (!is_array($data)) return false;

	//// 돌면서 처리하기
		foreach ($data as $key=>$val):
			// 기본 decode
				if ($key != 'name') {
					str($data[$key], 'decode');
				}
			
			// 권한처리
				if (preg_match("/^permit_/i", $key) && !preg_match("/_and$/i", $key) && $val) {
					str($data[$key], 'decode');

					preg_match_all("/\[(.+)([\^\!\>\<]?\=)(.+)\]/iU", $data[$key], $mat);
					$data[$key] = array();

					foreach ($mat[1] as $key2=>$val2):
						$data[$key][] = array(
							'field' => $mat[1][$key2],
							'mode' => $mat[2][$key2],
							'value' => $mat[3][$key2]
						);
					endforeach;
				}
		endforeach;

	//// 테이블 정보
		$data['table'] = $mini['name']['board'].$data['no'];
		$data['table_cmt'] = $mini['name']['cmt'].$data['no'];

	//// 총 게시물 수
		iss($data['total']);
		if ($data['total']) $data['total'] = unserialize($data['total']);
		def($data['total']['default'], 0);
		iss($data['total']['category']);

	//// 카테고리
		$data['category_child'] = array();		
		iss($data['category']);
		iss($data['category_option']);
		iss($data['category_name']);
		iss($data['admin_category']);
		if ($data['category']) {
			$data['category'] = unserialize($data['category']);

			iss($before_no);
			iss($before_depth);
			$parent = array();
			if (is_array($data['category']))
			foreach ($data['category'] as $key => $val):
				if (!preg_match("/[^0-9]/", $key)) {
					// 관리자 카테고리 저장
						if (preg_match("/^\!/", $val['name'])) {
							$data['admin_category'] .= "[{$val['no']}]";
						}

					$val['name'] = preg_replace("/^\!/", "", $val['name']);

					// 총 게시물 수 입력
						$data['category'][$key]['total'] = !empty($data['total']['category'][$val['no']]) ? $data['total']['category'][$val['no']] : 0;

					// 카테고리 옵션 생성
						$data['category_option'] .= "<option value='{$val['no']}'>".str_repeat("&nbsp;&nbsp;&nbsp;", $val['depth'])."{$val['name']}</option>\n";

					// 카테고리 이름 뺴기
						$data['category_name'][$val['no']] = $val['name'];
					
					// 자식노드 지정
						def($before_no, $val['no']);
						def($before_depth, $val['depth']);

						// 깊어지면 부모에 이전 no를 입력
						if ($before_depth < $val['depth']) $parent[] = $before_no;

						// 얕아지면 마지막 부모를 없앰
						else if ($before_depth > $val['depth'] && is_array($parent) && count($parent) > 0) array_pop($parent);

						// 부모가 있을 경우 자식에 편입
						if (is_array($parent) && count($parent) > 0) $data['category_child'][$parent[count($parent)-1]][] = $val;

						// 마지막 자료 입력
						$before_no = $val['no'];
						$before_depth = $val['depth'];
				}
				else {
					unset($data['category'][$key]);
				}
			endforeach;
		}

	//// 그룹연결
		$data['site_link_arr'] = array($data['site']);
		if (!empty($data['site_link'])) {
			$data['site_link_arr'] = getStr($data['site_link']);
		}
		if (!empty($mini['site']['site_link_arr'])) {
			$data['site_link_arr'] = array_merge($mini['site']['site_link_arr']);
		}
		$data['site_link_arr'] = array_unique($data['site_link_arr']);

	//// 추가필드
		iss($data['field']);
		if ($data['field']) {
			$data['field'] = unserialize($data['field']);

			// 특별설정 반영
			if (!empty($data['field']))
			foreach ($data['field'] as $key=>$val):
				$data['field'][$key]['is_admin'] = preg_match("/^\!/i", $val['name']) ? 1 : 0;
				$data['field'][$key]['is_req'] = preg_match("/\@$/i", $val['name']) ? 1 : 0;
				$data['field'][$key]['name'] = preg_replace(array("/^\!/", "/\@$/"), "", $val['name']);
			endforeach;
		}

	//// 스킨옵션
		iss($data['options']);
		if ($data['options']) {
			$data['options'] = unserialize($data['options']);
		}

	//// 단축키
		iss($data['key_map']);
		if ($data['key_map']) {
			$data['key_map'] = unserialize($data['key_map']);
		}

	//// 용량
		iss($data['file_limit_size']);
		if (!empty($data['file_limit']))
			$data['file_limit_size'] = getByte($data['file_limit']."M", 'decode');
		iss($data['file_limit_each_size']);
		if (!empty($data['file_limit_each']))
			$data['file_limit_each_size'] = getByte($data['file_limit_each']."M", 'decode');

		$limitsize = getByte(get_cfg_var("upload_max_filesize"), 'decode');
		if (empty($data['file_limit_each_size']) || $limitsize < $data['file_limit_each_size']) {
			$data['file_limit_each'] = preg_replace("/[^0-9]+/", "", getByte($limitsize, 'encode', 'B', 'M'));
			$data['file_limit_each_size'] = $limitsize;
		}

	//// 기타
		$data['is_thumb'] = !empty($data['thumb_width']) || !empty($data['thumb_height']);
		$data['is_image_limit'] = !empty($data['image_width_limit']) || !empty($data['image_height_limit']);
		$data['is_image_auto'] = !empty($data['image_width_auto']) || !empty($data['image_height_auto']);


	if ($mode) 
		return $data;
}


/** 사이트 자료 parser
 * @class parser
 * @param
		$data: 대상자료 
		$mode: return mode
 */
function parseSite(&$data, $mode = 0) {
	global $mini;

	if (!is_array($data)) return false;

	//// 돌면서 decode 하기
		foreach ($data as $key=>$val):
			if ($key != 'name') {
				str($data[$key], 'decode');
			}

			// 권한처리
				if (preg_match("/^permit_/i", $key) && !preg_match("/_and$/i", $key) && $val) {
					str($data[$key], 'decode');

					preg_match_all("/\[(.+)([\^\!\>\<]?\=)(.+)\]/iU", $data[$key], $mat);
					$data[$key] = array();

					foreach ($mat[1] as $key2=>$val2):
						$data[$key][] = array(
							'field' => $mat[1][$key2],
							'mode' => $mat[2][$key2],
							'value' => $mat[3][$key2]
						);
					endforeach;
				}
		endforeach;

	//// 추가필드
		iss($data['field']);
		if ($data['field']) {
			$data['field'] = unserialize($data['field']);

			// 특별설정 반영
			if (!empty($data['field']))
			foreach ($data['field'] as $key=>$val):
				$data['field'][$key]['is_admin'] = preg_match("/^\!/i", $val['name']) ? 1 : 0;
				$data['field'][$key]['is_req'] = preg_match("/\@$/i", $val['name']) ? 1 : 0;
				$data['field'][$key]['name'] = preg_replace(array("/^\!/", "/\@$/"), "", $val['name']);
			endforeach;
		}

	//// 가입설정
		iss($data['join_setting']);
		if ($data['join_setting']) {
			$data['join_setting'] = unserialize($data['join_setting']);
		}

	//// 템플릿
		iss($data['template']);
		if ($data['template']) {
			$data['template'] = unserialize($data['template']); 
		}

	//// 그룹연결
		$data['site_link_arr'] = array($data['no']);
		if (!empty($data['site_link'])) {
			$data['site_link_arr'] = getStr($data['site_link']);
		}		

	//// 가입설정으로 check 만듦
		if (is_array($data['join_setting'])) {
			$join_arr = array(
				'pass_q' => '비밀번호질답',
				'real_name' => '실명',
				'jumin' => '주민등록번호',
				'mail' => '메일',
				'cp' => '휴대전화',
				'birth' => '생일',
				'tel' => '전화',
				'address' => '주소',
				'homepage' => '홈페이지',
				'sex' => '성별',
				'chat' => '메신져',
				'ment' => '자기소개',
				'sign' => '서명',
				'co' => '사업자정보'
			);

			foreach ($data['join_setting'] as $key=>$val):
				if ($val == 2) {
					switch ($key):
						case 'address':
							$data['join_check'][$key] = array('name' => '주소');
							$data['join_check']['zipcode'] = array('name' => '우편번호');
							break;

						case 'pass_q':
							$data['join_check'][$key] = array('name' => '비밀번호 질문');
							$data['join_check']['pass_a'] = array('name' => '비밀번호 답변');
							break;

						case 'co':
							$data['join_check']['co_num'] = array('name' => '사업자등록번호');
							$data['join_check']['co_title'] = array('name' => '업체명');
							$data['join_check']['co_name'] = array('name' => '대표자명');
							$data['join_check']['co_cate1'] = array('name' => '업종');
							$data['join_check']['co_cate2'] = array('name' => '업태');
							$data['join_check']['co_tel'] = array('name' => '사업자전화번호');
							$data['join_check']['co_address'] = array('name' => '사업자주소');
							$data['join_check']['co_zipcode'] = array('name' => '사업자우편번호');
							$data['join_check']['co_tel'] = array('name' => '사업자전화번호');
							$data['join_check']['co_fax'] = array('name' => '사업자팩스');
							break;
						
						default:
							if ($key == 'age') continue;
							if (empty($join_arr[$key]) && strpos($key, 'field') !== false) {
								$keys = str_replace('field_', '', $key);
								$key = "field[{$keys}]";
								$join_arr[$key] = $data['field'][$keys]['name'];
							}

							$data['join_check'][$key] = array('name' => $join_arr[$key]);
					endswitch;					
				}
				else {
					iss($data['join_check'][$key]);
				}
			endforeach;
		}

	//// 회원상태목록
		iss($data['status']);
		if ($data['status']) {
			str($data['status'], 'decode');

			preg_match_all("/\[([^:]+)\:(.+)\]/iU", $data['status'], $mat);
			$data['status'] = array();
			$data['status_name'] = array();

			foreach ($mat[1] as $key=>$val):
				$data['status'][] = array(
					'name' => $mat[1][$key],
					'image' => $mat[2][$key]
				);

				$data['status_name'][$key] = $mat[1][$key];
			endforeach;

		}

	//// 기타
		$data['admit_sms'] = !empty($data['admit']) && $data['admit'] == 'sms' ? 1 : 0;
		$data['admit_mail'] = !empty($data['admit']) && $data['admit'] == 'mail' ? 1 : 0;

	//// 스킨 Dir 정하기
		iss($mini['skinDir']);
		if (!is_array($mini['skinDir'])) {
			iss($mini['skinDir']['join']);
			iss($mini['skinDir']['login']);
			iss($mini['skinDir']['board']);
			iss($mini['skinDir']['pass']);
			iss($mini['skinDir']['file']);
			iss($mini['skinDir']['manage']);
			iss($mini['skinDir']['mymenu']);
			iss($mini['board']['skin']);
			iss($mini['board']['skin_pass']);
			iss($mini['board']['skin_file']);
			iss($mini['board']['skin_report']);
			iss($mini['site']['skin_join']);
			iss($mini['site']['skin_login']);
			iss($mini['site']['skin']);
			iss($mini['site']['skin_pass']);
			iss($mini['site']['skin_file']);
			iss($mini['site']['skin_manage']);
			iss($mini['site']['skin_mymenu']);

			$mini['skinDir']['join'] =	!empty($mini['board']['skin_join']) ? 
				"{$mini['dir']}skin/join/{$mini['board']['skin_join']}/":
				"{$mini['dir']}skin/join/{$data['skin_join']}/";
			$mini['skinDir']['login'] =	!empty($mini['board']['skin_login']) ? 
				"{$mini['dir']}skin/login/{$mini['board']['skin_login']}/":
				"{$mini['dir']}skin/login/{$data['skin_login']}/";

			$mini['skinDir']['board'] = "{$mini['dir']}skin/board/{$mini['board']['skin']}/";
			$mini['skinDir']['pass'] = "{$mini['dir']}skin/pass/{$mini['board']['skin_pass']}/";
			$mini['skinDir']['file'] = "{$mini['dir']}skin/file/{$mini['board']['skin_file']}/";
			$mini['skinDir']['report'] = "{$mini['dir']}skin/report/{$mini['board']['skin_report']}/";
			$mini['skinDir']['manage'] = "{$mini['dir']}skin/manage/{$data['skin_manage']}/";
			$mini['skinDir']['mymenu'] = "{$mini['dir']}skin/mymenu/{$data['skin_mymenu']}/";
		}

	if ($mode) 
		return $data;
}


/** 회원 자료 parser
 * @class parser
 * @param
		$data: 대상자료 
		$mode: return mode
 */
function parseMember(&$data, $mode = 0) {
	global $mini;

	def($mini['set']['up_point'], 100);
	def($mini['set']['up_start'], 0);

	if (!is_array($data)) return false;

	//// 돌면서 decode 하기
		foreach ($data as $key=>$val):
			if ($key != 'name') {
				str($data[$key], 'decode');
			}
		endforeach;

	//// 특별권한
		checkLevelAdmin($data);

	//// 그룹연결
		$data['site_link_arr'] = array($data['site']);
		if (!empty($data['site_link'])) {
			$data['site_link_arr'] = getStr($data['site_link']);
		}
		if (!empty($mini['site']['site_link_arr'])) {
			$data['site_link_arr'] = array_merge($mini['site']['site_link_arr']);
		}
		$data['site_link_arr'] = array_unique($data['site_link_arr']);

	//// 공개여부
		$data['is_open'] = array();
		if (!empty($data['open'])) {
			foreach (getStr($data['open']) as $key=>$val):
				$data['is_open'][$val] = 1;
			endforeach;
		}

	//// 메신져
		iss($data['chat']);
		if ($data['chat']) {
			$tmp_arr = array();
			foreach (getStr($data['chat']) as $key=>$val):
				$tmp = array();
				$tmp = explode(":", $val);
				$tmp_arr[$key] = array('mode'=>$tmp[0], 'value'=>$tmp[1]);
			endforeach;

			$data['chat'] = $tmp_arr;
		}

	//// 추가필드
		iss($data['field']);
		if ($data['field']) {
			$data['field'] = unserialize($data['field']);
		}

	//// 환경설정
		iss($data['ini']);
		if ($data['ini']) {
			$data['ini'] = unserialize($data['ini']);
		}

	//// 생일
		iss($data['birth']);
		$data['birth'] = ($data['birth'] != '0000-00-00 00:00:00' && $data['birth']) ? date("Y-m-d", strtotime($data['birth'])) : '';

	//// 성별
		if (!empty($mini['site']['join_setting']['sex']) && $mini['site']['no'] == $data['site'])
			$data['sex_out'] = ($data['sex'] == 'man') ? '남자' : '여자';
		else
			$data['sex_out'] = $data['sex'] = '';

	//// 레벨명 처리
		$data['level_name'] = "{$data['level']}레벨";
		if (!empty($mini['site']['level_name'])) {
			$mat = array();
			preg_match("/\[".$data['level']."\:([^\]]+)\]/", $mini['site']['level_name'], $mat);
			if (!empty($mat[1]))
				$data['level_name'] = $mat[1];
		}

	//// 폼 상태가 아닐 때 값 처리
		if ($mini['filename'] != 'member.php') {
			$data['ment'] = nl2br($data['ment']);
			$data['sign'] = nl2br($data['sign']);
		}

	//// 닉콘
		parseName($data);

	//// 표시
		$data['point_out'] = number_format($data['point']);
		$data['point_sum_out'] = number_format($data['point_sum']);

	//// 로그인 여부 체크
		$data['is_login'] = 0;
		if (!empty($data['date_login']) && $data['date_login'] != '0000-00-00 00:00:00') {
			$data['is_login'] = ($mini['time'] - $mini['set']['login_time'] > strtotime($data['date_login'])) ? 0 : 1;
			$data['date_login_str'] = $data['is_login'] ? dateSec($mini['time'] - strtotime($data['date_login']))."전" : '';
		}
		else {
			$data['date_login_str'] = '-';
		}

	//// 상태
		if (!empty($data['status']) && $data['is_login'] && !empty($mini['site']) && $mini['site']['no'] == $data['site']) {
			$tmp_key = array_search($data['status'], $mini['site']['status_name']); 
			if (!empty($tmp_key) && $tmp_key !== false) {
				$data['status'] = "<img src='{$mini['dir']}{$mini['site']['status'][$tmp_key]['image']}' border='0' style='vertical-align:middle;' alt='{$data['status']}' />";
			}
		}

		if ($data['is_login'] && empty($data['status'])) {
			$data['status'] = "<img src='{$mini['dir']}sfile/status/status_0.gif' border='0' style='vertical-align:middle;' alt='온라인' />";
		}

		else if (empty($data['status'])) {
			$data['status'] = "<img src='{$mini['dir']}sfile/status/status_1.gif' border='0' style='vertical-align:middle;' alt='오프라인' />";	
		}

	/*
	//// 포인트 레벨 구함
	if ($data['point_sum'] <= 0 || $data['point_sum'] <= $mini['set']['up_start']) {
		$data['point_level'] = 1;
	}
	else {
		// 최대 정수의 근의공식
		$data['point_level'] = floor((($mini['set']['up_point']-2*$mini['set']['up_start'])+sqrt(pow(2*$mini['set']['up_start']-$mini['set']['up_point'],2)+8*$mini['set']['up_point']*$data['point_sum']))/(2*$mini['set']['up_point']));
	}

	//// 포인트 레벨 관련 변수 구함
	$data['point_prev'] = $data['point_level']*($mini['set']['up_start']*2+($data['point_level']-1)*$mini['set']['up_point'])/2; // 등차수열의 합 일반항
	$tmp = $data['point_level'] + 1;
	$data['point_next'] = $tmp*($mini['set']['up_start']*2+($tmp-1)*$mini['set']['up_point'])/2;
	def($data['point_per'], 0);
	if ($data['point_sum']) {
		if($data['point_sum'] < $mini['set']['up_start'] || $data['point_level'] == 1) 
			$data['point_per'] = $data['point_sum'] / $data['point_next'];
		else
			$data['point_per'] = ($data['point_sum']-$data['point_prev'])/($data['point_next']-$data['point_prev']);
	}
	*/

	if ($mode) 
		return $data;
}


/** 이름 꾸미기
 * @class io
 * @param
		$data: 자료
		$name: name 이름
		$site_data: 그룹자료
		$ret: 리턴여부
 * @return String
 */
function parseName(&$data, $name = 'name', $site_data = '', $ret = 0) {
	global $mini;

	$data["{$name}_out"] = $data["{$name}_out_not_link"] = $data["{$name}_text"] = $data[$name];
	$data["{$name}_icon"] = $data["{$name}_icon_name"] = '';

	if (!empty($data['target_member'])) {
		$target = $data['target_member'];
	}
	else if (!empty($data['uid'])) {
		$target = $data['no'];
	}

	if (!empty($mini['site']) && empty($site_data)) $site_data = $mini['site'];

	if (!empty($target)) {
		// 닉콘
		if ((empty($site_data) || !empty($mini['site']['use_icon_name'])) && file_exists("{$mini['dir']}sfile/icon_name/{$target}.gif")) {
			$data["{$name}_out"] = "<img src='{$mini['dir']}sfile/icon_name/{$target}.gif' border='0' style='vertical-align:middle;' alt='{$data[$name]}' />";
			$data["{$name}_icon_name"] = "{$mini['dir']}sfile/icon_name/{$target}.gif";
		}

		// 아이콘
		if ((empty($site_data) || !empty($mini['site']['use_icon'])) && file_exists("{$mini['dir']}sfile/icon/{$target}.gif")) {
			$data["{$name}_out"] = "<img src='{$mini['dir']}sfile/icon/{$target}.gif' border='0' style='vertical-align:middle;' alt='아이콘' />".$data["{$name}_out"];
			$data["{$name}_icon"] = "{$mini['dir']}sfile/icon/{$target}.gif";
		}

		iss($id);
		if (!empty($mini['board']['id'])) $id = $mini['board']['id'];
		if (!empty($_REQUEST['id'])) $id = $_REQUEST['id'];

		$data["{$name}_out_not_link"] = $data["{$name}_out"];
		$data["{$name}_out_window"] = "<a href='#' onclick='view_member.open(event, { target_member: \"{$target}\", id: \"{$id}\", new:1, is_memo:1 });' title='회원팝업'>".$data["{$name}_out"]."</a>";
		$data["{$name}_out"] = "<a href='#' onclick='view_member.open(event, { target_member: \"{$target}\", id: \"{$id}\" });' title='회원팝업'>".$data["{$name}_out"]."</a>";
	}

	if ($ret)
		return $data;
}


/** HEAD 스크립트
 * @class default
 * @param
		-title: 브라우져 제목
		-head: head스크립트 추가내용
		-style: stylesheet 파일경로(,로 여러개 가능)
		-script: javascript 파일경로(,로 여러개 가능)
		-body: body태그 내용
		-lang: 언어
 * @return 
 */
function head($param = '') {
	global $mini;
	$param = param($param);

	iss($mini['head']);
	iss($mini['body']);
	iss($param['head']);
	iss($param['body']);
	iss($param['style']);
	iss($param['script']);

	def($param['title'], 'the M');
	def($param['lang'], $mini['set']['lang']);
	def($mini['set']['use_ob_start'], 1);
	$mini['is_layout'] = empty($_REQUEST['new']) && empty($_REQUEST['cmt']) && ($mini['filename'] == 'mini.php' || $mini['filename'] == 'write.php');

	if ($param['script']) {
		foreach (explode(',', $param['script']) as $key=>$val):
			$val = trim($val);
			$param['head'] .= "<script type='text/javascript' src='{$val}'></script>\n";
		endforeach;
	}

	if ($mini['set']['debug']) {
		$param['head'] .= "<script type='text/javascript' src='{$mini['dir']}js/ii.debug.js'></script>\n";
	}

	if ($param['style']) {
		foreach (explode(',', $param['style']) as $key=>$val):
			$val = trim($val);
			$param['head'] .= "<link rel='stylesheet' type='text/css' href='{$val}' />\n";
		endforeach;
	}

	// 게시판 HEAD
	if (!empty($mini['board']['head'])) {
		$param['head'] .= $mini['board']['head'];
	}

	// 게시판 BODY
	if (!empty($mini['board']['body'])) {
		$param['body'] .= (!empty($param['body']) ? " " : "").$mini['board']['body'];
	}

	// title 빼기
	if (!empty($param['head']) && strpos($param['head'], '<title>') !== false) $param['title'] = '';

	if (!$mini['head']) {
		if (!empty($param['body'])) $param['body'] = ' '.$param['body'];

		// ob_start 적용
		if (empty($mini['ob_start']) && (!empty($mini['set']['use_ob_start']) || (!empty($mini['board']['layout']) && !empty($mini['is_layout'])))) {
			ob_start();
			$mini['ob_start'] = 1;
		}

		echo 
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n" .
		"<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='{$_SERVER['HTTP_ACCESS_LANGUAGE']}' lang='{$_SERVER['HTTP_ACCESS_LANGUAGE']}'>\n" .
		"<head>\n" .
		"<meta http-equiv='Content-Type' content='text/html; charset={$mini['set']['lang']}' />\n" .
		(!empty($param['title']) ? "<title>{$param['title']}</title>\n" : "").
		"<script type='text/javascript' src='{$mini['dir']}js/mootools.js'></script>\n" .
		"<script type='text/javascript' src='{$mini['dir']}js/ii.js'></script>\n" .
		"<script type='text/javascript' src='{$mini['dir']}js/size.js'></script>\n" .
		"<script type='text/javascript'>\n".
		"//<![CDATA[\n".
		"\tvar miniDir = '{$mini['dir']}';\n".
		(!empty($_REQUEST['id']) ? "\tvar id = '{$_REQUEST['id']}';\n" : "").
		"var quick = '".(!empty($_REQUEST['quick']) ? $_REQUEST['quick'] : "")."';\n".
		"var and = '".(!empty($_REQUEST['and']) ? $_REQUEST['and'] : "")."';\n".
		"var is_cmt = '".(!empty($_REQUEST['is_cmt']) ? $_REQUEST['is_cmt'] : "")."';\n".
		"window.addEvent('keydown', function (e) { var event = setEvent(e); if (\$chk(event.key) && event.key == 'esc') { iiPopup.close(); if (\$chk(parent.iiPopup)) parent.iiPopup.close(); } });\n".
		"//]]>\n".
		"</script>\n".
		"{$param['head']}\n" .
		"</head>\n\n" . 
		"<body{$param['body']}>\n".
		"<div id='loading' style='position:absolute; display:none;' class='iiFormLoading'></div>\n".
		"<!--startTheM-->\n";

		$mini['head'] = 1;
	}

	// 게시판 헤더
	if (!empty($mini['board']['header_url']) && !empty($mini['is_layout']))
		include $mini['board']['header_url'];
	if (!empty($mini['board']['header']) && !empty($mini['is_layout']))
		echo $mini['board']['header'];
}


/** FOOT 스크립트
 * @class default
 * @param
		$name: description
 * @return 
 */
function foot($param = '') {
	global $mini;
	$param = param($param);

	iss($mini['set']['debug']);
	checkTime('all');

/*
	if (!empty($mini['set']['debug'])) {
		echo "
		<script type='text/javascript'>
		//<![CDATA[
		error(\"[실행시간]".nl2br2(printTime($mini['set']['debug'], 1))."\");
		//]]>
		</script>
		";		
	}
*/

	if ($mini['head']) {
		// 게시판 푸터
		if (!empty($mini['board']['footer']) && !empty($mini['is_layout']))
			echo $mini['board']['footer'];
		if (!empty($mini['board']['footer_url']) && !empty($mini['is_layout']))
			include $mini['board']['footer_url'];
		
		echo "\n<!--endTheM-->\n</body>\n</html>\n";

		$mini['foot'] = 1;
	}

	// sql 닫기
	iss($mini['sql']);
	if (is_array($mini['sql'])) {
		foreach ($mini['sql'] as $val)
			sqlClose($val);
	}

	// ob_end 적용
	if (!empty($mini['ob_start'])) {
		@ob_end_flush();
		unset($mini['ob_start']);
	}
}

/** 로그인 체크
 * @class default
 * @param
		$name: description
 * @return 
 */
function getMember() {
	global $mini;

	if (!is_array($mini['member'])) {
		iss($_SESSION['m_no']);
		iss($_SESSION['m_pass']);
		iss($_COOKIE['m_no']);
		iss($_COOKIE['m_pass']);
		iss($_COOKIE['m_autologin']);
		iss($mini['set']['use_login_session']);
		$mini['log'] = 0;

		$check = 0;
		$no = $_SESSION['m_no'];
		$pass = $_SESSION['m_pass'];
		$autologin = $_COOKIE['m_autologin'];
		def($no, $_COOKIE['m_no']);
		def($pass, $_COOKIE['m_pass']);

		// 정보 불러오기
		if ($no) {
			if (preg_match("/[^0-9]/i", $no)) __error('로그인 체크 도중 회원번호에 숫자가 아닌것이 발견되었습니다'.' ('.__FILE__.' line '.__LINE__.')');
			
			// 데이터 체크
			$data = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$no}");
			if (is_array($data)) {
				$arr_key_login = getStr($data['key_login']);
				if (is_array($arr_key_login)) {
					if ($_SERVER['SERVER_ADDR'] == $mini['ip'] && preg_match("/^(phpThumb\.php|trackback\.php)$/", $mini['filename'])) {
						$mini['msg'] = "access! {$mini['ip']}";
						$data['ip'] .= "[{$mini['ip']}]";
					}

					foreach (getStr($data['ip']) as $key=>$val):
						if (empty($mini['set']['use_login_multi']) && $key != count($arr_key_login)-1) continue;

						// 자동 로그인 처리
						if ($autologin && !empty($arr_key_login[$key]) && $autologin == md5("{$data['pass']}|{$val}|{$arr_key_login[$key]}")) {
							$check = 1;
						}
					
						// 일반 로그인 처리
						if ($pass && $pass == md5("{$data['pass']}|{$val}")) {
							$check = 1;
						}
					endforeach;
				}
			}
		}

		//// 로그인 완료
		if ($check) {
			$mini['log'] = 1;
			$mini['member'] = parseMember($data, 1);

			// 인증, 징계상태, 경고 수 충족 시
			if ((empty($mini['member']['admit']) && empty($mini['member']['level_admin'])) || ($mini['member']['date_punish'] && $mini['member']['date_punish'] != '0000-00-00 00:00:00' && $mini['time'] < strtotime($mini['member']['date_punish'])) || (!empty($mini['set']['punish_count']) && $mini['member']['count_alert'] >= $mini['set']['punish_count'])) {
				$mini['member']['level'] = 0;
			}

			sql("UPDATE {$mini['name']['member']} SET date_login='{$mini['date']}' WHERE no={$data['no']}");
			
			/*
			// 패스워드 갱신
			$new_pass = md5("{$data['pass']}|{$data['ip']}");
			if ($mini['set']['use_login_session']) 
				$_SESSION['m_pass'] = $new_pass;
			else
				setcookie('m_pass', $new_pass, 0, '/');
			*/

/*
			// 손님 세션 제거
			if ($mini['set']['use_guest_session']) {
				unset($_SESSION['guest']);
				sql("UPDATE {$mini['name']['ses']} SET special='member' WHERE id='".session_id()."' and ip='{$mini['ip']}'");
			}
*/
		}

		//// 로그인 제거
		else {
			unset($mini['member']);
			iss($mini['member']);
/*
			// 손님 세션 생성
			if ($mini['set']['use_guest_session']) {
				$mini['is_guest'] = 1;
				sql("UPDATE {$mini['name']['ses']} SET special='guest' WHERE id='".session_id()."' and ip='{$mini['ip']}'");
			}
*/
		}
	} // END is_array
}

if ($mini['filename'] != '_db.php' && $mini['filename'] != 'install.x.php' && $mini['filename'] != 'uninstall.php' && $mini['filename'] != 'uninstall.x.php') getMember();


/** 포인트 증/감
 * @class member
 * @param
		-target: 회원번호
		-point: 포인트, -가 포함되면 감소한다
		-msg: 설명
		-parent_no: 게시판, 그룹번호
		-data_no: 자료번호
		-is_del: 되돌리는 상황[0!|1]
		-is_sum: point_sum 까지 조절 [0!|1]
  */
function setPoint($param) {
	global $mini;
	$param = param($param);
	iss($param['ment']);
	iss($param['data_no']);
	iss($param['parent_no']);
	iss($param['msg']);
	def($param['is_del'], 0);
	def($param['is_sum'], 0);

	$q = $mode = '';
	$param['point'] = preg_replace("/[^0-9\-]+/", "", $param['point']);
	$tmp_q = '';

	if (strpos($param['point'], '-') !== false || $param['point'] < 0) {
		$param['point'] = str_replace("-", "", $param['point']);
		$q = "- {$param['point']}";
		$mode = "-";

		if ($param['is_sum']) {
			$tmp_q = ",point_sum = point_sum {$q}";
		}
	}
	else {
		$param['point'] = str_replace("+", "", $param['point']);
		$q = "+ {$param['point']}";
		$mode = "+";
		$tmp_q = ",point_sum = point_sum {$q}";
	}

	if ($param['is_del']) {
		$q = str_replace($mode, (($mode == '+') ? '-' : '+'), $q);
		if ($tmp_q) $tmp_q = str_replace($mode, (($mode == '+') ? '-' : '+'), $tmp_q);
		$mode = ($mode == '+') ? '-' : '+';
	}

	sql("UPDATE {$mini['name']['member']} SET point = point {$q} {$tmp_q} WHERE no={$param['target']}");
	addLog("
		mode: point
		target_member: {$param['target']}
		field1: {$mode}
		field2: {$param['point']}
		field3: {$param['msg']}
		field4: {$param['parent_no']}
		field5: {$param['data_no']}
	");
}


/** 파일의 크기와 내용으로 Hash를 생성
 * @class String
 * @param
		$data: $_FILES 데이터, chkFile을 지난 데이터여야 한다. (path가 있어야함)
 * @return String Hash Data
 */
function getHash($data) {	
	$output = $data['size'];
	$path = (!empty($data['path'])) ? $data['path'] : $data['tmp_name'];

	if (file_exists($path)) {
		$fp = fopen($path, 'rb');
		$tmp = 0;
		$hash = '';
		while ($tmp < $data['size']):
			fseek($fp, $tmp);
			$hash .= "|".fgets($fp, 256);
			$tmp += 10240;
		endwhile;
		fclose($fp);
		$output .= "|".md5($hash);
	}
	else {
		$output = '';
	}

	return $output;
}


/** JSON 형식 만들기
 * @class String
 * @param
		$arr: target Array
		$mode: data type[object|array]
 * @return String
 */
function setJSON($arr, $mode = 'object') {	
	iss($sep);
	iss($output);

	if ($mode == 'array') {
		$sep[0] = '[';
		$sep[1] = ']';
	}
	else if ($mode == 'object') {
		$sep[0] = '{';
		$sep[1] = '}';
	}

	if (is_array($arr)) {
		$output .= $sep[0];
		foreach ($arr as $key=>$val):
			$output .= setJSON_while($key, $val, $sep);
		endforeach;
		if (substr($output, -1) == ',') $output = substr($output, 0, -1);
		$output .= $sep[1];
	}
	else {
		$arr = addSlashes($arr);
		$output = "{$sep[0]}\"string\":\"".nl2br2($arr)."\"{$sep[1]}";
	}

	return $output;
}

function setJSON_while($key, $val, $sep) {	
	iss($output);

	if (is_array($val)) {
		$sep[0] = addSlashes($sep[0]);
		$output .= "\"{$key}\":{$sep[0]}";
		foreach ($val as $key2=>$val2):
			$output .= setJSON_while($key2, $val2, $sep);
		endforeach;
		if (substr($output, -1) == ',') $output = substr($output, 0, -1);
		$output .= "{$sep[1]},";
	}
	else {
		$val = addSlashes($val);
		$output .= "\"{$key}\":\"".nl2br2($val)."\",";
	}
	
	return $output;
}


/** 캐쉬 페이지 생성 시작
 * @class cache
 * @param
		-dir: 저장경로
		-name: 파일명
		-interval: 캐쉬 생성 간격(초)
 * @description $mini['cache'] 값이 있을 때만 구문을 실행하면 됩니다.
 */
function cacheStart($param) {	
	global $mini;
	$param = param($param);

	iss($param['dir']);
	iss($param['name']);
	def($param['interval'], '300');

	$mini['cache_time'] = $mini['cache_date'] = $mini['cache_date_str'] = '';

	if (!empty($mini['cache'])) __error('cacheEnd 가 쓰이기 전에 cacheStart 가 두번 이상 쓰였습니다'.' ('.__FILE__.' line '.__LINE__.')');
	if (!$param['name']) __error('캐쉬 파일명이 없습니다'.' ('.__FILE__.' line '.__LINE__.')');
	if ($param['dir'] && strpos($param['dir'], '/') === false) $param['dir'] .= '/';
	$filename = $param['dir'].$param['name'];
	$check = 0;

	//// 캐쉬파일 확인
	if (file_exists($filename)){
		$mini['cache_time'] = filemtime($filename);
		if (!empty($mini['cache_time'])) {
			$mini['cache_date'] = date("Y-m-d H:i:s", $mini['cache_time']);
			$mini['cache_date_str'] = dateSec($mini['time'] - $mini['cache_time'])."전";
		}
		if ((time() - $param['interval']) > $mini['cache_time']) 
			$check = 1;
	}
	else {
		$check = 1;
	}

	//// 캐쉬 시작
	if ($check) {
		if (empty($mini['ob_start'])) {
			ob_start();
		}
		else {
			@ob_end_flush();
			ob_start();
		}
		$mini['cache'] = $mini['skin']['cache'] = $filename;
	}

	//// 캐쉬 로드
	else {
		if (filesize($filename)) {
			$fp = fopen($filename, "r");
			echo fread($fp, filesize($filename));
			fclose($fp);
		}

		$mini['cache'] = '';
		if (!empty($mini['skin']['cache'])) $mini['skin']['cache'] = '';
	}
}


/** 캐쉬 페이지 생성 끝
 * @class cache
 */
function cacheEnd() {	
	global $mini;

	iss($mini['cache']);
	iss($fp);
	
	//// 캐쉬가 시작되었다면 맺음
	if ($mini['cache']) {
		$ment = ob_get_contents();
		ob_end_flush();

		if (!is_writable($mini['cache']) && file_exists($mini['cache'])) __error(array('mode'=>'alert', 'msg'=>"퍼미션 오류: {$mini['cache']} 에 쓰기 권한이 없습니다."));
		else {
			// 파일 생성
			$fp = fopen($mini['cache'], 'w+');
			if ($fp) {
				flock($fp, LOCK_EX);
				ftruncate($fp, 0);
				fwrite($fp, $ment);
				flock($fp, LOCK_UN);
				fclose($fp);

				chmod($mini['cache'], 0707);
			}
		}

		if (empty($mini['ob_start'])) $mini['ob_start'] = '';
	}

	//// 캐쉬를 로드했다면
	else {
	}

	unset($mini['cache']);
	if (!empty($mini['skin']['cache'])) unset($mini['skin']['cache']);
}


/** fsockopen
 * @class default
 * @param
		-host: host주소
		-url: url
		-port: 포트
		-path: 경로
		-query: 쿼리스트링
		-timeout: 시간제한
		-header: HTTP 헤더정보
		-method: 방식
		-action: 명령어를 직접 입력
		-skip_header: 출력내용중 헤더를 건너뜁니다
		-skip_error: 에러표시를 건너뜁니다
		-data: 자료
		-skip_close: 소켓을 닫지 않는다
		-skip_data: 아무내용도 넣지 않는다
 * @return String
 */
function getSocket($param = '', $action_data = '') {
	global $mini;
	$param = param($param);
	$url = array();
	$fp = $header = $data = '';
	def($param['port'], 80);
	def($param['timeout'], 10);
	def($param['method'], 'post');
	def($param['skip_header'], 0);
	iss($param['path']);
	iss($param['query']);
	
	if (empty($param['host']) && empty($param['url']))
		__error('주소가 없습니다. '.' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');

	// 주소 파싱
	if (!empty($param['url'])) {
		if (strpos($param['url'], '://') === false) $param['url'] = "http://{$param['url']}";
		$url = parse_url($param['url']);
		$param = array_merge($param, $url);
	}

	$param['query'] = (!empty($param['query']) ? "?".$param['query'] : "");

	// 쿼리 파싱
	$fp = fsockopen($param['host'], $param['port'], $errno, $errstr, $param['timeout']);
	if (!$fp) {
		if (empty($param['skip_error']))
			__error("$errstr ($errno) ".' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');
		else
			return false;
	}
	else {
		if (!empty($param['action'])) {
			$header = $param['action'];
		}
		
		// 기본 액션
		else {
			switch ($param['method']):
				case 'rpc':
					$header = "POST {$param['path']}{$param['query']} HTTP/1.0\r\n";
					$header .= "Content-Type: text/xml\r\n";
					break;
				case 'head':
					$header = "HEAD {$param['path']}{$param['query']} HTTP/1.1\r\n";
					break;
				case 'get':
					$header = "GET {$param['path']}{$param['query']} HTTP/1.0\r\n";
					break;
				default:
					$header = "POST {$param['path']}{$param['query']} HTTP/1.1\r\n";
			endswitch;
		}

		// 추가 헤더정보 입력
		if (!empty($param['header'])) {
			$header .= $param['header'];
		}

		// 기본 헤더정보 입력
		if (empty($param['action'])) {
			if (!empty($param['data']) && !preg_match("/content\-length\:/i", $header))
				$header .= "Content-length: ".strlen($param['data'])."\r\n";
			$header .= "Host: {$param['host']}\r\n";
			$header .= "Connection: Close\r\n";
			$header .= "\r\n";
		}

		// 자료 입력
		if (!empty($param['data'])) {
			$header .= $param['data'];
		}

		if (!empty($action_data)) {
			$header = $action_data;
		}

		if (empty($param['skip_data'])) {
			fwrite($fp, $header);
			while (!feof($fp)):
				$data .= fgets($fp, 4096);
			endwhile;
		}

		if (!empty($param['skip_close'])) {
			$mini['socket'] = $fp;
		}
		else {
			fclose($fp);
		}

		if (!empty($param['skip_header'])) {
			$data = strstr($data, "\r\n\r\n");
			$data = substr($data, strlen("\r\n\r\n"));
		}

		return $data;
	}
}


/** Bot 체크
 * @class io
 * @return Boolean
 */
function checkBot() {
	return !empty($_SERVER['HTTP_USER_AGENT']) ? preg_match("/(bot|baidu|spider|webauto|slurp|empas|alexa)/i", $_SERVER['HTTP_USER_AGENT']) : false;
}


/** 카운터 설정
 * @class log
 * @param
		-is_all: 모든 상황에서 기록한다 [0!|1]
		-is_select: 자료를 불러오기만 한다 [0!|1]
 * @return 
 */
function setCounter($param = '') {
	global $mini;
	$param = param($param);
	def($param['is_all'], 0);
	def($param['is_select'], 0);
	
	$uv = '';
	$now = date("Y/m/d");
	$q_id = $q_id2 = '';

	if (!check($param['id'], 'type:num')) $param['id'] = '';
	if (!empty($param['id'])) {
		$q_id = " or id={$param['id']}";
		$q_id2 = " and id={$param['id']}";
	}

	if (!empty($mini['set']['use_counter'])) {
		if (empty($param['is_select']) && (!empty($mini['set']['use_count_bot']) || !checkBot())) {
	
			if (!empty($param['is_all']) || empty($mini['referer']) || a(parse_url($mini['referer']), 'host') != $_SERVER['HTTP_HOST'] || (!empty($_REQUEST['referer']) && a(parse_url($_REQUEST['referer']), 'host') != $_SERVER['HTTP_HOST'])) {

				// uv 기록
				if (!sql("SELECT COUNT(*) FROM {$mini['name']['counter']} WHERE date>='{$now}' and date<=DATE_ADD('{$now}', INTERVAL 1 DAY) and ip='{$mini['ip']}'")) {
					$uv = ", uv=uv+1";
					$url = url("", 1);
					str($url, 'encode');

					sql("INSERT INTO {$mini['name']['counter']} (lang, agent, referer, url, ip, date) VALUES ('{$_SERVER['HTTP_ACCESS_LANGUAGE']}', '{$_SERVER['HTTP_USER_AGENT']}', '{$mini['referer']}', '{$url}', '{$mini['ip']}', '{$mini['date']}')");
				}	

				if (!sql("SELECT COUNT(*) FROM {$mini['name']['counter_log']} WHERE date='0000-00-00 00:00:00'")) {
					$tmp = sql("SELECT SUM(pv) as pv, SUM(uv) as uv FROM {$mini['name']['counter_log']}");
					if(empty($tmp['pv'])) $tmp['pv'] = 0;
					if(empty($tmp['uv'])) $tmp['uv'] = 0;

					sql("INSERT INTO {$mini['name']['counter_log']} SET pv={$tmp['pv']}, uv={$tmp['uv']}, date='0000-00-00 00:00:00'");
				}

				// 수정
				if (sql("SELECT COUNT(*) FROM {$mini['name']['counter_log']} WHERE date='{$now}'")) {
					sql("UPDATE {$mini['name']['counter_log']} SET pv=pv+1{$uv} WHERE (date='{$now}' and (id=0{$q_id})) or date='0000-00-00 00:00:00'");
				}

				// 입력
				else {
					sql("INSERT INTO {$mini['name']['counter_log']} SET id=0, pv=1, uv=1, date='{$now}'");
					if (!empty($param['id'])) sql("INSERT INTO {$mini['name']['counter_log']} SET id={$param['id']}, pv=1, uv=1, date='{$now}'");
					sql("UPDATE {$mini['name']['counter_log']} SET pv=pv+1, uv=uv+1 WHERE date='0000-00-00 00:00:00'");
				}
			}
		}

		// 어제/오늘/내일 자료 뽑음
		$data = sql("
			q:SELECT * FROM {$mini['name']['counter_log']} WHERE (id=0{$q_id}) and date>=DATE_ADD('{$now}', INTERVAL -1 DAY) and date<=DATE_ADD('{$now}', INTERVAL 1 DAY)
			mode:array
		");

		$mini['counter']['board'] = array();
		$mini['counter']['all'] = array();
		$mini['counter']['all']['total'] = sql("SELECT * FROM {$mini['name']['counter_log']} WHERE date='0000-00-00 00:00:00'");
		$mini['counter']['all']['total']['uv_out'] = number_format($mini['counter']['all']['total']['uv']);
		$mini['counter']['all']['total']['pv_out'] = number_format($mini['counter']['all']['total']['pv']);

		if (is_array($data))
		foreach ($data as $key=>$val):
			$tmp_name = (!empty($val['id']) ? 'board' : 'all');
			$val['uv_out'] = number_format($val['uv']);
			$val['pv_out'] = number_format($val['pv']);

			if (mktime(0,0,0,date("m"),date("d")-1,date("Y")) == strtotime($val['date']))
				$mini['counter'][$tmp_name]['yesterday'] = $val;
			else if (mktime(0,0,0,date("m"),date("d")+1,date("Y")) == strtotime($val['date']))
				$mini['counter'][$tmp_name]['tomorrow'] = $val;
			else
				$mini['counter'][$tmp_name]['today'] = $val;
		endforeach;
	}
}


/** SORT 변수 갖고옴
 * @class default
 * @param
		$name: 정렬 필드명
		$name2: SORT변수의 변수명 / sort
 * @return URI parameter
 */
function getSort($name, $name2 = 'sort') {
	global $$name2;
	
	if($$name2){
		$tmp_sort = explode(',', $$name2);
	}

	if($name){
		$tmp = explode(',', $name);
		foreach($tmp as $val):
			$val = trim($val);
			if(@in_array($val,$tmp_sort) || !$tmp_sort)
				$output.= ",{$val}!";
			else
				$output.= ",{$val}";
		endforeach;
	}

	if($output) $output = substr($output, 1);
	return $output;	
}


/** 문자열 자르기 함수
 * @class String
 * @param
		$str: 대상 문자열
		$cut: 자를 길이(한글 및 특수기호는 2길이 취급, 0이면 길이를 구함, 0에 fix가 multi면 multi여부를 리턴, both면 둘다 리턴)
		$fix: 잘린 문자열 뒤에 붙을 문자(기본 ...)
 * @return String, Number
 */
function strCut($str, $cut=0, $fix='...') {	
	global $mini;

	iss($result);
	iss($length);
	iss($str);
	iss($is_multi);

	if (!$str) return 0;

	// 자르는 경우 태그를 때어냄
	if ($cut) {
		$str = strip_tags($str);
	}

	//// UTF-8 일때
	if ($mini['set']['lang'] == 'UTF-8') {		
		for($a=0, $strlen=strlen($str); $a<$strlen; $a++) {
			$ord = ord($str[$a]);
			
			if ($ord >= 0 && $ord <= 127) {
				$result .= $str[$a];
				$length++;
			}
			else if ($ord >= 194 && $ord <= 223) {
				$result .= $str[$a].$str[$a+1];
				$length++;
				$a++;
			}
			else if ($ord <= 239) {
				$result .= $str[$a].$str[$a+1].$str[$a+2];
				$length+=2;
				$a+=2;
				$is_multi = 1;
			}
			else if ($ord <= 244) {
				$result .= $str[$a].$str[$a+1].$str[$a+2].$str[$a+3];
				$length+=2;
				$a+=3;
				$is_multi = 1;
			}

			if ($cut && $length >= $cut-1) {
				$result .= $fix;
				break;
			}
		}
	}

	//// EUC-KR일 때
	else {
		iss($string);
		$length = strlen($str);

		if ($cut) {
			preg_match("/^([\x00-\x7e]|.{2})*/", substr($str, 0, $cut), $string);
			$result = $string[0];
		}

		if ($fix == 'multi') {
			if (preg_match("/[\x00-\x7e]/", $str)) 
				$is_multi = 1;
		}
	}

	if (!$cut && $fix == 'multi') $length = $is_multi;
	if ($fix != 'both')
		return $cut ? $result : $length;
	else
		return array('length'=>$length, 'is_multi'=>$is_multi);
}




/** make sql query
 * @class sql
 * @param
		$data: 쿼리로 만들 데이터들
		$mode: 해당 쿼리만 반환한다
		$key_arr: 컬럼명을 지정할 경우 배열을 넣는다
 * @retur Array 
 */
function query($data, $mode = '', $key_arr = '') {	
	$keys = $values = $searches = '';
	$query = array('insert' => '', 'update' => '');

	if (!is_array($data)) 
		__error('함수의 인수가 배열이 아닙니다'.' ('.__FILE__.' line '.__LINE__.') in '.__FUNCTION__);

	if (!empty($key_arr)) {
		foreach ($key_arr as $val):
			if (!empty($val)) {
				$val2 = isset($data[$val]) ? $data[$val] : "";
				str($val2, 'encode');
				$tmp_val = (!preg_match("/[^0-9\-\.]/", $val2) && ($val2 === (string)(int)$val2)) ? "{$val2}" : "'{$val2}'";

				$keys .= ",{$val}";
				$values .= ",{$tmp_val}";
				$searches .= " and {$val}={$tmp_val}";
				$query['update'] .= ",{$val}={$tmp_val}";
				unset($val2);
			}
		endforeach;
	}
	else {
		foreach ($data as $key=>$val):
			str($val, 'encode');
			$tmp_val = (!preg_match("/[^0-9\-\.]/", $val) && ($val === (string)(int)$val)) ? "{$val}" : "'{$val}'";

			$keys .= ",{$key}";
			$values .= ",{$tmp_val}";
			$searches .= " and {$key}={$tmp_val}";
			$query['update'] .= ",{$key}={$tmp_val}";
		endforeach;
	}

	$keys = substr($keys, 1);
	$values = substr($values, 1);
	$searches = substr($searches, 4);
	$query['update'] = substr($query['update'], 1);
	$query['insert'] = "({$keys}) VALUES ({$values})";	
	$query['search'] = "({$searches})";

	switch ($mode):
		case 'update':
			return $query['update'];
			break;
		case 'insert':
			return $query['insert'];
			break;
		case 'search':
			return $query['search'];
			break;
		case 'insert_array':
			return array(
				'keys' => "({$keys})",
				'values' => "({$values})"
			);
			break;
		case '':
			return $query;
			break;
		default:
			__error('올바르지 않는 모드 입니다'.' ('.__FILE__.' line '.__LINE__.') in '.__FUNCTION__);
	endswitch;
}


/** 파일 파서
 * @class file
 * @param
		$name: description
 * @return 
 */
function parseFile(&$data, $mode='') {
	global $mini;

	//// 초기화
		if (!isset($data['hit'])) $data['hit'] = 0;
		if (!isset($data['width'])) $data['width'] = 0;
		if (!isset($data['height'])) $data['height'] = 0;
		if (!isset($data['error'])) $data['error'] = 0;
	
		$data['size_out'] = getByte($data['size'], 'encode');
		$data['date_out'] = date("y-m-d", strtotime($data['date']));
		str($data['title'], 'decode');

	// 타입에 따른 이름
	switch ($data['type']):
		case 'image':
			$data['type_out'] = '이미지';
			break;
		case 'music':
			$data['type_out'] = '음악';
			break;
		case 'movie':
			$data['type_out'] = '동영상';
			break;
		case 'swf':
		case 'flv':
			$data['type_out'] = '플래쉬';
			break;
		default:
			$data['type_out'] = '기타';
	endswitch;

	// 링크
		$data['url_thumb'] = "{$mini['dir']}download.php?mode=thumb&amp;no={$data['no']}";
		$data['url_view'] = "{$mini['dir']}download.php?mode=view&amp;no={$data['no']}";
		$data['url_download'] = "{$mini['dir']}download.php?no={$data['no']}";
	
	// link 변수 생성
		urlToLink($data);

	if ($mode)
		return $data;
}


/** 스킨 변수 준비
 * @class skin
 * @param
		$name: 스킨이름
 */
function setSkin($name = '') {
	global $mini, $data;

	if (!isset($mini['skin']) || !is_array($mini['skin'])) {
		$mini['skin'] = array();
	}

	if (empty($mini['skin']['config'])) setSkinConfig();

	// 초기화
		$mini['skin'] = array_merge($mini['skin'], array(
			'dir' => (!empty($mini['sdir']) ? $mini['sdir'] : ''),
			'rdir' => (!empty($mini['dir']) ? $mini['dir'] : ''),
			'pdir' => (!empty($mini['pdir']) ? $mini['pdir'] : ''),
			'url_write' => "{$mini['dir']}write.php?id={$_REQUEST['id']}".getURI('id, mode'),
			'url_list' => "{$mini['dir']}mini.php?id={$_REQUEST['id']}".getURI("id, no, mode, pass_encode".(empty($_REQUEST['no']) ? ", sort, s, start, div, page, quick, and, is_cmt, category" : "")),
			'url_list_all' => "{$mini['dir']}mini.php?id={$_REQUEST['id']}".getURI("id, no, mode, pass_encode, sort, s, start, div, page, quick, and, is_cmt, category"),
			'url_login' => "{$mini['dir']}login.php?id={$_REQUEST['id']}&amp;group={$_REQUEST['group']}&amp;url=".url(),
			'url_logout' => "{$mini['dir']}login.php?mode=logout&amp;url=".url(),
			'url_join' => "{$mini['dir']}member.php?id={$_REQUEST['id']}&amp;group={$_REQUEST['group']}&amp;url=".url(),
			'url_rss' => "{$mini['dir']}feed.php?id={$_REQUEST['id']}",
			'url_file' => "{$mini['dir']}file.php?id={$_REQUEST['id']}&amp;group={$_REQUEST['group']}&amp;url=".url(),
			'url_post_manage' => "{$mini['dir']}manage.php?id={$_REQUEST['id']}&amp;group={$_REQUEST['group']}&amp;mode=post&amp;url=".url(),
			'url_cmt_manage' => "{$mini['dir']}manage.php?id={$_REQUEST['id']}&amp;group={$_REQUEST['group']}&amp;mode=comment&amp;url=".url(),
			'js_back' => "onclick='history.back(); return false;'",
			'js_back2' => "onclick='history.go(-2); return false;'",
			'js_del' => "onclick='actions(\"del\"); return false;'",
			'js_post_del' => "onclick='actions(frm_list, \"post_del\"); return false;'",
			'js_post_manage' => "onclick='actions(frm_list, \"post_manage\"); return false;'",
			'js_cmt_del' => "onclick='actions(frm_cmt, \"cmt_del\"); return false;'",
			'js_cmt_manage' => "onclick='actions(frm_cmt, \"cmt_manage\"); return false;'",
			'js_member_close' => "onclick='$(\"tool_member\").toggle(\"hide\"); return false;'",
			'mode' => $_REQUEST['mode'],
			'log' => !empty($mini['log']) ? $mini['log'] : '',
			'date' => $mini['date'],
			'ip' => $mini['ip'],
			'request' => &$_REQUEST,
			'is_search' => !empty($_REQUEST['s']) || !empty($_REQUEST['quick']),
			'is_search_or_category' => !empty($_REQUEST['s']) || !empty($_REQUEST['quick']) || !empty($_REQUEST['category']),
			'js_tag_post' => "onclick='getTags(frm_write); return false;'",
			'js_prev' => "onclick='if (\$chk(key_func[\"prev\"])) key_func[\"prev\"](); return false;'",
			'js_next' => "onclick='if (\$chk(key_func[\"next\"])) key_func[\"next\"]();return false;'",
			'js_tag_cmt' => "onclick='getTags(frm_cmt); return false;'",
			'js_trackback' => "onclick='\$(\"trackback\").toggle(); return false;'",
			'js_trackback_ment' => "onclick='\$(\"trackback_ment\").toggle(); return false'",
			'js_trackback_ok' => "onclick='\$(\"form_trackback\").submit(); return false'",
			'js_cal' => "onclick='myCal.initialize($(this).getPrevious()); return false;'",
			'js_today' => "onclick='$(this).getPrevious().getPrevious().value=\"".date("Y-n-j")."\"; return false;'"
		));

	// 징계상태일 때 글쓰기 링크 뺌
		if (!empty($mini['log']) && !empty($mini['member']['no']) && empty($mini['member']['level']))
			$mini['skin']['js_write'] = "onclick='error(\"징계상태에서는 글을 쓸 수 없습니다\");'";

	// 카테고리
		if (!empty($_REQUEST['category'])) {
			$mini['skin']['category'] = $_REQUEST['category'];
			if (!empty($mini['board']['category_name']) && preg_match("/^(mini\.php|write\.php)$/i", $mini['filename'])) $mini['skin']['category_name'] = $mini['board']['category_name'][$_REQUEST['category']];
		}

	// 위치 설정
		$mini['skin']['is_join'] = !empty($_REQUEST['url']) && preg_match("/member\.php/i", $_REQUEST['url']);

	// find 연결
		if (!empty($mini['site']['no']))
			$mini['skin']['url_find'] = "{$mini['dir']}login.find.php?group={$mini['site']['no']}&amp;url=".url();

	// data 연결
		switch ($mini['filename']):
			case 'member.php':
				$mini['skin']['data'] = &$data;
				break;
		endswitch;

	// 환경설정
		$mini['skin']['set'] = &$mini['set'];
		$mini['skin']['setting'] = &$mini['setting'];
		$mini['skin']['cache'] = &$mini['cache'];
		$mini['skin']['cookie'] = &$_COOKIE;
		$mini['skin']['s'] = '';
		if (!empty($_REQUEST['s'])) $mini['skin']['s'] = &$_REQUEST['s'];

	// 회원
		if (!empty($mini['log'])) {
			$mini['skin']['member'] = &$mini['member'];
			$mini['skin']['url_myinfo'] = "{$mini['dir']}member.php?mode=modify&amp;no={$mini['member']['no']}&amp;id={$_REQUEST['id']}&amp;group={$_REQUEST['group']}&amp;url=".url();
			$mini['skin']['url_mymenu'] = "{$mini['dir']}mymenu.php?mode=memo&amp;no={$mini['member']['no']}";
		}

	// 사이트
		if (!empty($mini['site'])) {
			$mini['skin']['site'] = &$mini['site'];
		}

	// 게시판
		if (!empty($mini['board'])) {
			$mini['skin']['board'] = &$mini['board'];
		}

	// 마이메뉴
		if ($mini['filename'] == 'mymenu.php' || $mini['filename'] == 'memo.write.php') {
			$mini['skin'] = array_merge($mini['skin'], array(
				'js_friend' => "onclick='actions(\"friend\");'",
				'js_memo_block' => "onclick='actions(\"memo_block\");'",
				'js_memo_save_action' => "onclick='actions(\"memo_save\");'",
				'url_mymenu' => "{$mini['dir']}mymenu.php?mode=mymenu".getURI("mode, s, sort, quick", "&amp;"),
				'url_memo_myinfo' => "{$mini['dir']}mymenu.php?mode=myinfo".getURI("mode, s, sort, quick", "&amp;"),
				'url_memo' => "{$mini['dir']}mymenu.php?mode=memo".getURI("mode, s, sort, quick", "&amp;"),
				'url_memo_list' => "{$mini['dir']}mymenu.php?mode=memo_list".getURI("mode, s, sort, quick", "&amp;"),
				'url_memo_save' => "{$mini['dir']}mymenu.php?mode=memo_save".getURI("mode,  s, sort, quick", "&amp;"),
				'url_memo_ini' => "{$mini['dir']}mymenu.php?mode=memo_ini".getURI("mode, s, sort, quick", "&amp;"),
				'url_scrap' => "{$mini['dir']}mymenu.php?mode=scrap".getURI("mode, s, sort, quick", "&amp;"),
				'url_log' => "{$mini['dir']}mymenu.php?mode=log".getURI("mode, s, sort, quick", "&amp;")
			));
		}

	// 관리자
		//+ 관리권한 검색
		$mini['skin']['link_admin'] = !empty($mini['member']['is_board']) ? "href='{$mini['dir']}admin' target='_blank'" : "href='#' onclick='return false;'";
		$mini['skin']['js_admin'] = !empty($mini['member']['is_board']) ? "onclick='document.location.href=\"{$mini['dir']}admin\"'" : "";
			if (!empty($mini['board']))
				$mini['skin']['link_board_admin'] = !empty($mini['member']['is_board']) && !empty($mini['board']['no']) ? "href='{$mini['dir']}admin/index.php?mode=board&amp;target={$mini['board']['no']}' target='_blank'" : "href='#' onclick='return false;'";
				$mini['skin']['js_board_admin'] = !empty($mini['member']['is_board']) && !empty($mini['board']['no']) ? "onclick='window.open(\"{$mini['dir']}admin/index.php?mode=board&amp;target={$mini['board']['no']}\"); return false;'" : "";
			

	// 리스팅 관련
		if ($mini['filename'] == 'mini.php') {
			if (!empty($mini['list']['default']) && empty($_REQUEST['no'])) {
				$mini['skin'] = array_merge($mini['skin'], array(
					'total' => $mini['list']['default']['total'],
					'page' => $mini['list']['default']['page'],
					'page_total' => $mini['list']['default']['tp']
				));
			}
		}

	// 댓글 창
		if ($mini['filename'] == 'cmt.php') {
			if (!empty($_REQUEST['new'])) $mini['skin']['new'] = $_REQUEST['new'];
		}

	// 글쓰기 파일업로드 링크
		if ($mini['filename'] == 'write.php') {
			$mini['skin'] = array_merge($mini['skin'], array(
				'url_file' => "{$mini['dir']}file.php?id={$_REQUEST['id']}&amp;no={$_REQUEST['no']}".(!empty($_REQUEST['pass_encode']) ? "&amp;pass_encode={$_REQUEST['pass_encode']}" : "")."&amp;url=".url(),
				'pop_file' => "iiPopup.init({ url: \"{$mini['skin']['url_file']}\", width:iiSize[\"file\"][0], height:iiSize[\"file\"][1] });"
			));
		}

	// 가입
		if ($mini['filename'] == 'member.php') {
			$mini['skin'] = array_merge($mini['skin'], array(
				'js_chat' => "onclick='addChat({ target: this });'",
				'js_admit' => "onclick='sendAdmitMail();'",
				'js_admit_sms' => "onclick='sendAdmitSMS();'",
				'js_withdraw_button' => "onclick='\$(\"withdraw\").toggle();'",
				'js_withdraw' => "onclick='setWithdraw();'"
			));
		}

	// iiPopup
		$mini['skin'] = array_merge($mini['skin'], array(
			'pop_login' => "iiPopup.init({ url: \"{$mini['skin']['url_login']}\", width:iiSize[\"login\"][0], height:iiSize[\"login\"][1] });",
			'pop_join' => "iiPopup.init({ url: \"{$mini['skin']['url_join']}\", width:iiSize[\"join\"][0], height:iiSize[\"join\"][1], close: true });",
			'pop_file' => "iiPopup.init({ url: \"{$mini['skin']['url_file']}\", width:iiSize[\"file\"][0], height:iiSize[\"file\"][1] });",
			'pop_post_manage' => "iiPopup.init({ url: \"{$mini['skin']['url_post_manage']}\", width:iiSize[\"manage\"][0], height:iiSize[\"manage\"][1] }); actions(frm_list, \"post_manage\");",
			'pop_cmt_manage' => "iiPopup.init({ url: \"{$mini['skin']['url_cmt_manage']}\", width:iiSize[\"manage\"][0], height:iiSize[\"manage\"][1] }); actions(frm_cmt, \"cmt_manage\");"
		));

		if (!empty($mini['skin']['url_find'])) $mini['skin']['pop_find'] = "iiPopup.init({ url: \"{$mini['skin']['url_find']}\", width:iiSize[\"find\"][0], height:iiSize[\"find\"][1] });";
		if (!empty($mini['skin']['url_myinfo'])) $mini['skin']['pop_myinfo'] = "iiPopup.init({ url: \"{$mini['skin']['url_myinfo']}\", width:iiSize[\"myinfo\"][0], height:iiSize[\"myinfo\"][1], close: true });";
		if (!empty($mini['skin']['url_mymenu'])) $mini['skin']['pop_mymenu'] = "iiPopup.init({ url: \"{$mini['skin']['url_mymenu']}\", width:iiSize[\"mymenu\"][0], height:iiSize[\"mymenu\"][1] });";

	// 권한적용
		if (!empty($mini['skin']['board'])) {
			if (!getPermit("name:notice")) $mini['skin']['board']['use_notice'] = 0;
			if (!getPermit("name:permit")) $mini['skin']['board']['use_permit'] = 0;
			if (!getPermit("name:search")) $mini['skin']['board']['use_search'] = 0;
			if (!getPermit("name:trackback")) $mini['skin']['board']['use_trackback'] = 0;
			if (!getPermit("name:report")) $mini['skin']['board']['use_report'] = 0;
		}

	// 카운터
		if (!empty($mini['counter'])) {
			$mini['skin']['counter'] = $mini['counter'];
		}

	// link
		urlToLink($mini['skin']);

	if (!empty($name) && !empty($mini['sdir'])) {
		// 스킨 컨버팅
			iss($_REQUEST['skinmake']);
			if ($_REQUEST['skinmake'] || preg_match("/skinmake=1/i", $_REQUEST['url']) || preg_match("/skinmake%3D1/i", $_REQUEST['url'])) {
				$_REQUEST['skinmake'] = 1;
				include "_inc.skinmake.php";

				if (file_exists("{$mini['sdir']}head.mini")) skinConv("{$mini['sdir']}head.mini");
				if (file_exists("{$mini['sdir']}foot.mini")) skinConv("{$mini['sdir']}foot.mini");
				if (file_exists("{$mini['sdir']}widget.mini")) skinConv("{$mini['sdir']}widget.mini");
				if ($name == 'view') {
					skinConv("{$mini['sdir']}cmt.mini");
					skinConv("{$mini['sdir']}list.mini");
				}

				skinConv("{$mini['sdir']}{$name}.mini");
			}

		// head 스킨 로드
			if (preg_match("/^(list|view|write)$/", $name) && file_exists("{$mini['sdir']}head.php")) include "{$mini['sdir']}head.php";

		// 스킨 로드
			include "{$mini['sdir']}{$name}.php";

		// foot 스킨 로드
			if (preg_match("/^(list|view|write)$/", $name) && file_exists("{$mini['sdir']}foot.php")) include "{$mini['sdir']}foot.php";

		// 스킨 컨버팅 안내 문구
			if ($_REQUEST['skinmake']) {
//				echo "
//					<script type='text/javascript'>
//					//<![CDATA[
//						error('".(!empty($mini['error_msg']) ? $mini['error_msg'] : "스킨 변환 되었습니다")."');
//					//]]>
//					</script>
//				";
			}
	}
}


/** 스킨옵션 로드
 * @class skin
 */
function setSkinConfig() {
	global $mini;

	if (!empty($mini['sdir'])) $name = !empty($mini['board']['no']) ? "{$mini['sdir']}config_{$mini['board']['no']}.ini.php" : "{$mini['sdir']}config.ini.php";

	// 스킨 설정
	if (!empty($mini['sdir']) && file_exists($name)) {
		if (empty($mini['skin'])) $mini['skin'] = array();

		$mini['skin']['config'] = parse_ini_file($name, true);

		if (!empty($mini['skin']['config']['user'])) {
			$mini['skin']['user'] = $mini['skin']['config']['user'];
			unset($mini['skin']['config']['user']);
		}

		// 게시판 설정
		if (!empty($mini['skin']['config']['board'])) {
			foreach ($mini['skin']['config']['board'] as $key=>$val):
				// 스킵
				if (preg_match("/^(total|table|table_cmt|category_child|category_option|category_name|admin_category|category|site_link_arr)$/i", $key)) unset($mini['skin']['config']['board'][$key]);

				// 권한처리
				if (preg_match("/^permit_/i", $key) && !preg_match("/_and$/i", $key) && $val) {
					preg_match_all("/\[(.+)([\^\!\>\<]?\=)(.+)\]/iU", $mini['skin']['config']['board'][$key], $mat);
					$mini['skin']['config']['board'][$key] = array();

					foreach ($mat[1] as $key2=>$val2):
						$mini['skin']['config']['board'][$key][] = array(
							'field' => $mat[1][$key2],
							'mode' => $mat[2][$key2],
							'value' => $mat[3][$key2]
						);
					endforeach;
				}
			endforeach;

			$mini['board'] = array_merge($mini['board'], $mini['skin']['config']['board']);
		}

		// 그룹 설정
		if (!empty($mini['skin']['config']['site'])) {
			$mini['site'] = array_merge($mini['site'], $mini['skin']['config']['site']);
		}

		// 환경 설정
		if (!empty($mini['skin']['config']['set'])) $mini['set'] = array_merge($mini['set'], $mini['skin']['config']['set']);
	}
}


/** POST 파싱
 * @class view
 * @param
		$data: DB 데이터
		$mode: parse Mode [list|view|mhot 등]
		$ret: return 모드
 * @return 
 */
function parsePost(&$data, $mode = 'list', $ret = 0) {
	global $mini;

	iss($data['prev']);
	iss($data['next']);

	// 외부 게시판 설정 적용
		$board_data = !empty($mini['board_data']) ? $mini['board_data'] : $mini['board'];

	// decode
		foreach ($data as $key=>$val):
			str($data[$key], 'decode');
		endforeach;

	// 주소설정
	//+ .htaccess 설정에 따라 다르게 해야함
		if (true) {
			$data['url_pdir'] = "{$mini['pdir']}mini.php?id={$board_data['id']}&amp;no={$data['no']}";
			$data['url_trackback'] = "{$mini['pdir']}trackback.php?id={$board_data['id']}&amp;no={$data['no']}";
		}
		else {
		}

		if ($mode == 'pdir') {
			return str_replace("&amp;", "&", $data['url_pdir']);
		}

	// 추가필드
		iss($data['field']);
		if ($data['field']) {
			$data['field'] = unserialize($data['field']);
			if (is_array($data['field'])) {
				ksort($data['field']);
			}
		}

	// 링크
		iss($data['link']);
		if ($data['link']) {
			$data['link'] = unserialize($data['link']);
		}

	// 권한 뽑음
		$data['permit_handle'] = (
			!empty($mini['member']['level_admin']) ||
			(!empty($data['target_member']) && !empty($mini['log']) && $data['target_member'] == $mini['member']['no']) ||
			(empty($data['target_member']) && empty($mini['log']))
		);

	// 링크 설정
		$data['url_view'] = getPermit("name:view") ? "{$mini['dir']}mini.php?id={$board_data['id']}&amp;no={$data['no']}".getURI("no, id") : "";
		$data['url_del'] = $data['permit_handle'] && !empty($_SESSION['pageKey']) ? "{$mini['dir']}write.x.php?mode=del&amp;no={$data['no']}&amp;pageKey={$_SESSION['pageKey']}".getURI("no") : "";
		$data['url_modify'] = $data['permit_handle'] || getPermit("name:edit") ? "{$mini['dir']}write.php?mode=modify&amp;no={$data['no']}".getURI("no, mode") : "";
		$data['url_cmt'] = "{$mini['dir']}mini.php?id={$board_data['id']}&amp;no={$data['no']}&amp;new=1".getURI("id, no, start, div, sort, s, quick, and, is_cmt");
		$data['url_report'] = "{$mini['dir']}report.php?id={$board_data['id']}&amp;mode=post&amp;no={$data['no']}";
		$data['pop_report'] = "iiPopup.init({ url: \"{$data['url_report']}\", width:iiSize[\"report\"][0], height:iiSize[\"report\"][1] });";
		$data['pop_cmt'] = "iiPopup.init({ url: \"{$data['url_cmt']}\", width:iiSize[\"cmt\"][0], height:iiSize[\"cmt\"][1] }); return false;";
		$data['js_vote'] = "onclick='votes({ mode: \"vote\", id: \"{$board_data['id']}\", no: \"{$data['no']}\" });'";
		$data['js_hate'] = "onclick='votes({ mode: \"hate\", id: \"{$board_data['id']}\", no: \"{$data['no']}\" });'";
		$data['url_manage'] = "{$mini['dir']}manage.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;mode=post&amp;no[]={$data['no']}&amp;url=".url();
		$data['pop_manage'] = "iiPopup.init({ url: \"{$data['url_manage']}\", width:iiSize[\"manage\"][0], height:iiSize[\"manage\"][1] }); return false;";
		$data['url_manage_report'] = "{$mini['dir']}manage.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;mode=post&amp;report=1&amp;no[]={$data['no']}&amp;url=".url();
		$data['pop_manage_report'] = "iiPopup.init({ url: \"{$data['url_manage_report']}\", width:iiSize[\"manage\"][0], height:iiSize[\"manage\"][1] }); return false;";
		$data['url_report_view'] = "{$mini['dir']}manage.php?id={$board_data['id']}&amp;mode=post&amp;no={$data['no']}&amp;url=".url();
		$data['pop_report_view'] = "iiPopup.init({ url: \"{$data['url_report_view']}\", width:iiSize[\"manage\"][0], height:iiSize[\"manage\"][1] }); return false;";
		$data['js_name'] = "onclick='view_member.open(event, { target_member: \"{$data['target_member']}\", id: \"{$board_data['id']}\", post_no: \"{$data['no']}\" })'";
		$data['js_trackback_view'] = "onclick='getTrackback(\"{$data['no']}\"); return false;'";

	// 조회기록 기능
		if (!empty($board_data['use_unique_view']) && !empty($mini['member']) && inStr($mini['member']['no'], $data['history_hit'])) $data['is_read'] = 1;

	// 댓글수
		if (!empty($data['count_trackback']) && $mode == 'list')
			$data['count_comment'] += $data['count_trackback'];

	// 번호
		$data['view_no'] = $board_data['use_view_no'] ? 4294967296 - $data['num'] : $data['no'];

	// 이름
		parseName($data);

	// 관리자 체크박스
		$data['checkbox'] = !empty($mini['member']['level_admin']) ? "<input type='checkbox' name='no[]' value='{$data['no']}' class='middle' />" : "";

	// 현재글
		$data['is_now'] = (!empty($_REQUEST['no']) && $_REQUEST['no'] == $data['no']) ? 1 : 0;

	// 반대
		$data['is_hate'] = (!empty($mini['member']['level_admin']) || (!empty($mini['member']['no'] ) && $mini['member']['no'] == $data['target_member']));

	// 글 상태 설정
		if (empty($data['status'])) {
			if (!empty($board_data['status_hit']) && $data['hit'] >= $board_data['status_hit']) $data['status'] = 'hit';
			if (!empty($board_data['status_hate']) && $data['hate'] >= $board_data['status_hate']) $data['status'] = 'hate';
			if (!empty($board_data['status_vote']) && $data['vote'] >= $board_data['status_vote']) $data['status'] = 'vote';
		}

	// 날짜
		if ($data['date_notice'] == '0000-00-00 00:00:00') $data['date_notice'] = '';
		if ($data['date_popup'] == '0000-00-00 00:00:00') $data['date_popup'] = '';
		if ($data['date_issue'] == '0000-00-00 00:00:00') $data['date_issue'] = '';

		$data['time'] = strtotime($data['date']);
		$data['date_out'] = $mode == 'view' ? date($board_data['date_view'], $data['time']) : date($board_data['date_list'], $data['time']);
		$data['date_str'] = ($mini['time'] - $data['time'] < $mini['set']['date_str'] * 86400) ? dateSec($mini['time'] - $data['time'])."전" : "";
		$data['date_simple'] = date("H:i", $data['time']);
		$data['date_notice_str'] = !empty($data['date_notice']) ? dateSec(strtotime($data['date_notice']) - $mini['time']) : "";
		$data['date_popup_str'] = !empty($data['date_popup']) ? dateSec(strtotime($data['date_popup']) - $mini['time']) : "";
		$data['date_issue_str'] = !empty($data['date_issue']) ? dateSec(strtotime($data['date_issue']) - $mini['time']) : "";

	// 마지막 댓글
		iss($data['date_comment_str']);
		iss($data['date_comment_time']);
		if (!empty($data['date_comment']) && $data['date_comment'] != '0000-00-00 00:00:00') {
			$data['date_comment_time'] = strtotime($data['date_comment']);
			$data['date_comment_str'] = ($mini['time'] - $data['date_comment_time'] < $mini['set']['date_str'] * 86400) ? dateSec($mini['time'] - $data['date_comment_time'])."전" : date("m/d H:i", $data['date_comment_time']);
		}

	// 이슈글 가중치
		$data['issue_point'] = $data['issue'] * 999999 + $data['hit'] + $data['vote'] * 10;

	// 카테고리
		if (!empty($data['category'])) {
			$data['category'] = getStr($data['category']);
			$data['category_name'] = array();
			if (!empty($data['category']))
			foreach ($data['category'] as $key=>$val):
				$data['category_name'][$key] = $board_data['category_name'][$val];
			endforeach;
		}

	// 태그
		if ($data['tag']) $data['tag'] = getStr($data['tag']);

	// 내용
		if (!empty($data['ment']) && !empty($data['autobr'])) $data['ment'] = nl2br($data['ment']);
		$data['ment'] = str_replace("<br /><!--n-->", "\n", $data['ment']);
		if (strpos($data['ment'], "<pre title='code'") !== false) $mini['is_syntax'] = 1;

	// 제목
		$data['title_text'] = $data['title'];
		if (!empty($mini['setting']['title_cut'])) $data['title'] = strCut($data['title'], $mini['setting']['title_cut']);
		if (!empty($board_data['cut_title']) && $mode == 'list') $data['title'] = strCut($data['title'], $board_data['cut_title']);
		
		
		// 제목 앞에 태그가 있을 경우 태그를 이어줌
		if (strpos($data['title_text'], '<') === 0) {
			$mat = array();
			preg_match("/^\<([^\>]+)\>/i", $data['title_text'], $mat);
			if (!empty($mat)) {
				$tmp_mat = explode(" ", $mat[1]);
				if (preg_match("/^(b|i|u|strike|strong|span|font|h1|h2|h3|h4|h5|h6)$/i", $tmp_mat[0]))
					$data['title'] = "{$mat[0]}{$data['title']}</{$tmp_mat[0]}>";
				else if (preg_match("/^img$/i", $tmp_mat[0]))
					$data['title'] = "{$mat[0]}{$data['title']}";
			}
		}

	// 파일
		$data['is_file'] = 0;
		if (!empty($data['file'])) {
			unset($file);
			$file = sql("
				q: SELECT * FROM {$mini['name']['file']} WHERE ".sqlSel($data['file'])." ".($mode != 'view' ? "LIMIT 1" : "")."
				mode: array
			");

			if (!empty($file)) {
				$data['is_file'] = 1;
				$data['file_data'] = array();

				// 파일 링크 만들기
				foreach ($file as $key=>$val):
					$tmp_no = $key + 1;
					$data["url_file{$tmp_no}"] = "{$mini['dir']}download.php?mode=view&amp;no={$val['no']}";
					$data["link_file{$tmp_no}"] = "href='{$mini['dir']}download.php?no={$val['no']}'";
					$data['file_data'][$tmp_no] = parseFile($val, 1);
				endforeach;
				unset($file);
			}
		}
	
	// XHTML 설정
		$data['ment'] = str_replace(array("&amp;lt;script", "&amp;lt;/script"), array("&lt;script", "&lt;/script"), $data['ment']);
		$data['title'] = str_replace(array("&amp;lt;script", "&amp;lt;/script"), array("&lt;script", "&lt;/script"), $data['title']);

	// 경고
		if (!empty($data['alert']) && !empty($board_data['use_alert'])) {
			$data['ment'] = "<div class='alertDiv'>경고(!) 클릭하시면 내용이 펼쳐집니다. 위험한 내용이나 스포일러성 내용이 포함되어 있을 수 있습니다.</div><span style='display:none;'>{$data['ment']}</span>";
		}

	// 댓글
		$data['cmt'] = '';
		if ($data['count_comment']) {
			$data['cmt'] = str_replace(array("[:data:]", "[:link:]"), array($data['count_comment'], ''), ($mini['time'] - $data['date_comment_time'] <= $board_data['status_new_cmt'] * 3600 ? $board_data['cmt_skin_new'] : $board_data['cmt_skin']));
			str($data['cmt'], 'decode');
		}

	// 댓글 점수 없앰
		if (empty($board_data['use_cmt_point'])) $data['point'] = 0;

	// 핑백보낸것
		$data['pingback_arr'] = getStr($data['pingback']);
		$data['pingback_count'] = count($data['pingback_arr']);

	// 수정 시간제한
		if (!empty($board_data['limit_edit_post']) && empty($mini['member']['level_admin'])) {
			if (strtotime($data['date']) + ($board_data['limit_edit_post'] * 60) >= $mini['time']) {
				$data['edit_left'] = dateSec(($board_data['limit_edit_post'] * 60) - ($mini['time'] - strtotime($data['date'])));
			}
		}

	// 라이센스 처리
		if (!empty($data['license'])) {
			$data['license_out'] = getLicense($data['license']);
		}

	// 아이피
		if (!empty($data['ip'])) $data['ip_hide'] = preg_replace("/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/", "\\1.*.\\3.*", $data['ip']);

	// link 변수 생성
		urlToLink($data);

	// 비밀글 처리
		if ($data['secret'] && !getPermit("name:secret") && (empty($mini['log']) || $data['target_member'] != $mini['member']['no'])) {
			if ($mode != 'view') {
				$data['ment'] = "비밀글 입니다.";
			}

			if ($data['pass']) {
				$data['link_view'] = "href='#' onclick='iiPopup.init({ url: \"{$mini['dir']}pass.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;target=_parent&amp;url=".urlencode($data['url_view'])."\", width:iiSize[\"pass\"][0], height:iiSize[\"pass\"][1] }); return false;'";

				if ($mode != 'view') {
					$data['ment'] .= " <a {$data['link_view']}>여기를 눌러 비밀번호를 입력하세요.</a>";
				}
			}
			else {
				$data['link_view'] = "href='#' onclick='alert(\"비밀글을 볼 수 있는 권한이 없습니다\"); return false;'";
			}
		}
	
	// 비밀번호 입력 처리
		if (!empty($data['pass']) && empty($mini['member']['level_admin'])) {
			$data['link_modify'] = "href='#' onclick='iiPopup.init({ url: \"{$mini['dir']}pass.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;target=_parent&amp;url=".urlencode($data['url_modify'])."\", width:iiSize[\"pass\"][0], height:iiSize[\"pass\"][1] }); return false;'";
			$data['js_modify'] = "onclick='iiPopup.init({ url: \"{$mini['dir']}pass.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;target=_parent&amp;url=".urlencode($data['url_modify'])."\", width:iiSize[\"pass\"][0], height:iiSize[\"pass\"][1] }); return false;'";
			$data['link_del'] = "href='#' onclick='iiPopup.init({ url: \"{$mini['dir']}pass.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;target=_parent&amp;url=".urlencode($data['url_del'])."\", width:iiSize[\"pass\"][0], height:iiSize[\"pass\"][1] }); return false;'";
			$data['js_del'] = "onclick='iiPopup.init({ url: \"{$mini['dir']}pass.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;target=_parent&amp;url=".urlencode($data['url_del'])."\", width:iiSize[\"pass\"][0], height:iiSize[\"pass\"][1] }); return false;'";
		}

	// 통합제목
		$data['title_out'] = "<a {$data['link_view']} class='postView'".(!empty($mini['setting']['title_cut']) && $data['title_text'] != $data['title'] ? " title='".nl2br2(addSlashes($data['title_text']))."'" : "").">{$data['title']}</a> <a href='#' onclick='{$data['pop_cmt']}'>{$data['cmt']}</a>";

	// 태그 없는 변수
		$data['ment_notag'] = strip_tags($data['ment']);

	if ($ret)
		return $data;
}

/** POST 글쓰기 폼 파싱
 * @class view
 * @param
		$data: DB 데이터
		$mode: return 모드
 * @return 
 */
function parsePostWrite(&$data, $mode = '', $ret = 0) {
	global $mini;

	iss($data['tag']);

	//// 원하지 않는 출력정보 지우기
		unset($data['prev']);
		unset($data['next']);

	//// decode
		foreach ($data as $key=>$val):
			str($data[$key], 'decode');
			if ($mode != 'insert')
				$data[$key] = str_replace(array("\r\n", "\n"), array("\n", "<!--nl2br-->"), $data[$key]);
		endforeach;


	// 필터링 해제
		$data['ment'] = str_replace("&lt;br /&gt;&lt;!--n--&gt;", "\n", $data['ment']);

		filter($data['title'], 'decode');
		filter($data['ment'], 'decode');

	// 카테고리
		if ($mode == 'insert' && !empty($data['category'])) {
			$data['category'] = getStr($data['category']);
		}

	// tag
		if (!empty($data['tag'])) {
			$data['tag'] = implode(", ", getStr($data['tag']));
		}

		$data['ment'] = str_replace(array("&amp;lt;script", "&amp;lt;/script"), array("&lt;script", "&lt;/script"), $data['ment']);

	// 핑백보낸것
		$data['pingback_arr'] = getStr($data['pingback']);
		$data['pingback_count'] = count($data['pingback_arr']);

	// 번호
		$data['view_no'] = $mini['board']['use_view_no'] ? 4294967296 - $data['num'] : $data['no'];

	// 링크
		if (!empty($data['link'])) {
			$data['link'] = unserialize($data['link']);
			str($data['link'], 'decode');
		}

	// 날짜 초기화
		if ($data['date_notice'] == '0000-00-00 00:00:00') $data['date_notice'] = '';
		if ($data['date_popup'] == '0000-00-00 00:00:00') $data['date_popup'] = '';
		if ($data['date_issue'] == '0000-00-00 00:00:00') $data['date_issue'] = '';

	// 추가필드
		if (!empty($data['field'])) {
			$data['field'] = unserialize($data['field']);
			str($data['field'], 'decode');

			foreach ($data['field'] as $key=>$val):
				if (is_array($val)) {
					$data['field'][$key] = "[".implode("][", $val)."]";
				}
			endforeach;
		}

	// 저작권
		if (!empty($data['license'])) {
			$data['license_sample'] = preg_replace("/^CCL.*/i", "CCL", $data['license']);
		}
	
	if ($ret)
		return $data;
}


/** COMMENT 파싱
 * @class view
 * @param
		$data: DB 데이터
		$ret: return 모드
 * @return 
 */
function parseComment(&$data, $ret = 0) {
	global $mini;

	// 외부 게시판 설정 적용
		$board_data = !empty($mini['board_data']) ? $mini['board_data'] : $mini['board'];

	// decode
		if (!empty($data))
		foreach ($data as $key=>$val):
			str($data[$key], 'decode');
		endforeach;

	// 추가필드
		iss($data['field']);
		if ($data['field']) {
			$data['field'] = unserialize($data['field']);
			if (is_array($data['field'])) {
				ksort($data['field']);
			}
		}

	// 링크
		iss($data['link']);
		if ($data['link']) {
			$data['link'] = unserialize($data['link']);
		}

	// 권한 뽑음
		$data['permit_comment'] = getPermit("name:comment");
		$data['permit_handle'] = (
			!empty($mini['member']['level_admin']) ||
			(!empty($data['target_member']) && !empty($mini['log']) && $data['target_member'] == $mini['member']['no']) ||
			(empty($data['target_member']) && empty($mini['log']))
		);

	// depth
		iss($data['parent']);
		$data['depth'] = count(getStr($data['parent']));

	// 필수 스킨요소
		$data['skin'] = array(
			'id' => "tableRowsCmt_{$data['no']}",
			'tool' => "cmtTools_{$data['no']}",
			'write' => "write_comment{$data['no']}"
		);

	// 이름
		parseName($data);

	// 번호
		$data['view_no'] = $board_data['use_view_no'] ? (!empty($data['parent']) ? "{$data['num']}-" : $data['num']) : $data['no'];

	// 관리자 체크박스
		$data['checkbox'] = !empty($mini['member']['level_admin']) ? "<input type='checkbox' name='no[]' value='{$data['no']}' class='middle' />" : "";

	// 날짜
		$data['time'] = strtotime($data['date']);
		$data['date_out'] = date($board_data['date_comment'], $data['time']);
		$data['date_str'] = ($mini['time'] - $data['time'] < $mini['set']['date_str'] * 86400) ? dateSec($mini['time'] - $data['time'])."전" : "";
		$data['date_simple'] = date("H:i", $data['time']);

	// 태그
		if ($data['tag']) $data['tag'] = getStr($data['tag']);

	// 내용
		if (!empty($data['ment']) && !empty($data['autobr'])) $data['ment'] = nl2br($data['ment']);
		$data['ment'] = str_replace("<br /><!--n-->", "\n", $data['ment']);
		if (strpos($data['ment'], "<pre title='code'") !== false) $mini['is_syntax'] = 1;

	// 트랙백
		if (!empty($data['trackback'])) $data['trackback'] = amp($data['trackback'], 'encode');

	// 파일
		$data['is_file'] = 0;
		if (!empty($data['file'])) {
			unset($file);
			$file = sql("
				q: SELECT * FROM {$mini['name']['file']} WHERE ".sqlSel($data['file'])."
				mode: array
			");

			if (!empty($file)) {
				$data['is_file'] = 1;
				$data['file_data'] = array();

				// 파일 링크 만들기
				foreach ($file as $key=>$val):
					$tmp_no = $key + 1;
					$data["url_file{$tmp_no}"] = "{$mini['dir']}download.php?mode=view&amp;no={$val['no']}";
					$data["link_file{$tmp_no}"] = "href='{$mini['dir']}download.php?no={$val['no']}'";
					$data['file_data'][$tmp_no] = parseFile($val, 1);
				endforeach;
				unset($file);
			}
		}	

	// 경고
		if (!empty($data['alert']) && !empty($board_data['use_alert'])) {
			$data['ment'] = "<div class='alertDiv'>경고(!) 클릭하시면 내용이 펼쳐집니다. 위험한 내용이나 스포일러성 내용이 포함되어 있을 수 있습니다.</div><span style='display:none;'>{$data['ment']}</span>";
		}

	// 라이센스 처리
		if (!empty($data['license'])) {
			$data['license_out'] = getLicense($data['license']);
		}

	// 수정 시간제한
		if (!empty($board_data['limit_edit_comment']) && empty($mini['member']['level_admin'])) {
			if (strtotime($data['date']) + ($board_data['limit_edit_comment'] * 60) >= $mini['time']) {
				$data['edit_left'] = dateSec(($board_data['limit_edit_comment'] * 60) - ($mini['time'] - strtotime($data['date'])));
			}
		}

	// 글쓴이가 쓴 댓글 처리
		$data['is_writer'] = (!empty($data['target_member']) && !empty($mini['skin']['view']['target_member']) && $data['target_member'] == $mini['skin']['view']['target_member']);

	// 링크
		$data['url_view'] = "{$mini['dir']}mini.php?id={$board_data['id']}&amp;no={$data['target_post']}&amp;cNo={$data['no']}";
		$data['url_del'] = "{$mini['dir']}cmt.x.php?id={$board_data['id']}&amp;no={$data['no']}&amp;mode=del&amp;script=move&amp;formMode=move&amp;url=".url().(!empty($_SESSION['pageCmtKey']) ? "&amp;pageCmtKey={$_SESSION['pageCmtKey']}" : "");
		$data['js_name'] = "onclick='view_member.open(event, { target_member: \"{$data['target_member']}\", id: \"{$board_data['id']}\", post_no: \"{$data['target_post']}\", cmt_no: \"{$data['no']}\" })'";
		$data['url_award'] = "{$mini['dir']}ajax.php?mode=award&amp;id={$board_data['id']}&amp;no={$data['no']}&amp;url=".url();
		$data['js_reply'] = $data['permit_comment'] ? "onclick='replys({$data['no']}); return false;'" : "";
		$data['js_modify'] = $data['permit_handle'] ? "onclick='edits({$data['no']}); return false;'" : "";
		$data['js_del'] = $data['permit_handle'] ? "onclick='dels({$data['no']}); return false;'" : "";
		$data['js_vote'] = "onclick='votes({ mode: \"vote\", id: \"{$board_data['id']}\", cmt_no: \"{$data['no']}\" }); return false;'";
		$data['js_hate'] = "onclick='votes({ mode: \"hate\", id: \"{$board_data['id']}\", cmt_no: \"{$data['no']}\" }); return false;'";
		$data['js_award'] = "onclick=''";
		$data['url_report'] = "{$mini['dir']}report.php?id={$board_data['id']}&amp;mode=comment&amp;no={$data['no']}";
		$data['pop_report'] = "iiPopup.init({ url: \"{$data['url_report']}\", width:iiSize[\"report\"][0], height:iiSize[\"report\"][1] });";
		$data['url_manage'] = "{$mini['dir']}manage.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;mode=comment&amp;no[]={$data['no']}&amp;url=".url();
		$data['pop_manage'] = "iiPopup.init({ url: \"{$data['url_manage']}\", width:iiSize[\"manage\"][0], height:iiSize[\"manage\"][1] }); return false;";
		$data['url_manage_report'] = "{$mini['dir']}manage.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;mode=comment&amp;report=1&amp;no[]={$data['no']}&amp;url=".url();
		$data['pop_manage_report'] = "iiPopup.init({ url: \"{$data['url_manage_report']}\", width:iiSize[\"manage\"][0], height:iiSize[\"manage\"][1] }); return false;";
		$data['url_report_view'] = "{$mini['dir']}manage.php?id={$board_data['id']}&amp;mode=comment&amp;no={$data['no']}&amp;url=".url();
		$data['pop_report_view'] = "iiPopup.init({ url: \"{$data['url_report_view']}\", width:iiSize[\"manage\"][0], height:iiSize[\"manage\"][1] }); return false;";

	// 삭제 처리
		if (!empty($data['is_del']))
			$data['ment'] = "<span class='is_del'>{$data['ment']}</span>";

	// 아이피
		if (!empty($data['ip'])) $data['ip_hide'] = preg_replace("/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/", "\\1.*.\\3.*", $data['ip']);

	// link 변수 생성
		urlToLink($data);

	// 비회원 댓글 삭제시 패스워드 입력폼 링크
		if (!empty($data['pass']) && empty($mini['member']['level_admin'])) {
			$data['link_del'] = "href='#' onclick='iiPopup.init({ url: \"{$mini['dir']}pass.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;target=_parent&amp;url=".urlencode($data['url_del'])."\", width:iiSize[\"pass\"][0], height:iiSize[\"pass\"][1] }); return false;'";
			$data['js_del'] = "onclick='iiPopup.init({ url: \"{$mini['dir']}pass.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;target=_parent&amp;url=".urlencode($data['url_del'])."\", width:iiSize[\"pass\"][0], height:iiSize[\"pass\"][1] }); return false;'";
		}

	// 비밀 댓글 처리
		$is_secret = 0;
		if ($data['secret'] && !getPermit("name:secret") && (empty($mini['log']) || $data['target_member'] != $mini['member']['no'])) {
			if (empty($data['pass']) || empty($_REQUEST['pass_encode']) || (!empty($_REQUEST['pass_encode']) && $_REQUEST['pass_encode'] != md5("{$data['pass']}|{$mini['ip']}|".session_id()))) {
				$is_secret = 1;

				$data['ment'] = "비밀 댓글 입니다.";

				if ($data['pass']) {
					$data['ment'] .= " <a href='#' onclick='iiPopup.init({ url: \"{$mini['dir']}pass.php?id={$board_data['id']}&amp;group={$_REQUEST['group']}&amp;target=_parent&amp;url=".url()."\", width:iiSize[\"pass\"][0], height:iiSize[\"pass\"][1] }); return false;'>여기를 눌러 비밀번호를 입력하세요.</a>";
				}
				else {
					$data['name'] = '비밀';
				}
			}
		}
		
		if ($data['secret'] && !$is_secret) {
			$data['ment'] = '[비밀댓글 입니다]<br />' . $data['ment'];
		}

	// 태그 없는 변수
		$data['ment_notag'] = strip_tags($data['ment']);

	if ($ret)
		return $data;
}


/** CMT 글쓰기 폼 파싱
 * @class view
 * @param
		$data: DB 데이터
		$mode: return 모드
 * @return 
 */
function parseCmtWrite(&$data, $mode = '', $ret = 0) {
	global $mini;

	iss($data['tag']);


	// 원하지 않는 출력정보 지우기
		unset($data['history_vote']);
		unset($data['history_hit']);
		unset($data['title']);

		$target_post = !empty($data['target_post']) ? $data['target_post'] : 0;

		if ($mode != 'insert') {
			unset($data['target_post']);
			unset($data['pass']);
			unset($data['reply']);
			unset($data['parent']);
		}

	// 대상 댓글 입력
/*
		if ($mode == 'insert') {
			$data['reply'] = end(getStr($data['parent']));
			unset($data['parent']);
		}
*/

	// decode
		foreach ($data as $key=>$val):
			str($data[$key], 'decode');
			if ($mode != 'insert')
				$data[$key] = str_replace(array("\r\n", "\n"), array("\n", "<!--nl2br-->"), $data[$key]);
		endforeach;

	// 필터링 해제
		filter($data['ment'], 'decode');
		$data['ment'] = str_replace("<br /><!--n-->", "\n", $data['ment']);

	// tag
		if ($data['tag']) {
			$data['tag'] = implode(", ", getStr($data['tag']));
		}

	// 파일(댓글 수정일때)
		$data['is_file'] = 0;

		unset($file);
		$file = sql("
			q: SELECT * FROM {$mini['name']['file']} WHERE mode='comment' and target_post={$target_post} and ((target=0 and (ip='{$mini['ip']}' or target_member=".(!empty($mini['member']['no']) ? $mini['member']['no'] : "0").")) or target={$data['no']})
			mode: array
		");

		if (!empty($file)) {
			$data['is_file'] = 1;
			$data['file_data'] = array();

			// 파일 링크 만들기
			foreach ($file as $key=>$val):
				$tmp_no = $key + 1;
				$data["url_file{$tmp_no}"] = "{$mini['dir']}download.php?mode=view&amp;no={$val['no']}";
				$data["link_file{$tmp_no}"] = "href='{$mini['dir']}download.php?no={$val['no']}'";
				$data['file_data'][$tmp_no] = parseFile($val, 1);
			endforeach;
			unset($file);
		}

	// 링크
		if (!empty($data['link'])) {
			$data['link'] = unserialize($data['link']);
			str($data['link'], 'decode');
		}

	// 추가필드
		if (!empty($data['field'])) {
			$data['field'] = unserialize($data['field']);
			str($data['field'], 'decode');
		}

	if ($ret)
		return $data;
}

/** 회원 쓰기 파싱
 * @class member
 * @param
		$data: DB 데이터
		$mode: return 모드
 * @return 
 */
function parseMemberWrite(&$data, $mode = '', $ret = 0) {
	global $mini;

	// 필터
		filter($data['ment'], 'decode');
		filter($data['sign'], 'decode');

	// 폼에 맞게 처리
		foreach ($data as $key=>$val):
			if (!is_array($val))
				$data[$key] = addslashes($data[$key]);
		endforeach;

	// 폼에 맞게 이전 값 변형
		unset($data['pass']);
		unset($data['jumin']);
		unset($join_check);

	// 기타
		$data['tmp_co'] = $data['co_num'] ? 1 : 0;


	if ($ret)
		return $data;
}

/** memo
 * @class io
 * @param
		$data: 자료
		$ret: return 모드
 * @return 
 */
function parseMemo(&$data, $ret = 0) {
	global $mini;

	// decode
		foreach ($data as $key=>$val):
			str($data[$key], 'decode');
		endforeach;

	// 이름
		foreach (array('name_from', 'name_target') as $val):
			parseName($data, $val);
		endforeach;

	// 관리자 체크박스
		$data['checkbox'] = "<input type='checkbox' name='no[]' value='{$data['no']}' class='middle' />";

	// 시간
		if ($data['date_read'] == '0000-00-00 00:00:00') $data['date_read'] = '';
		$data['time'] = strtotime($data['date']);
		$data['date_out'] = date("m/d H:i", $data['time']);
		$data['date_str'] = ($mini['time'] - $data['time'] < $mini['set']['date_str'] * 86400) ? dateSec($mini['time'] - $data['time'])."전" : "";
		if (!$data['date_str']) $data['date_str'] = $data['date_out'];

	// 내용
		$data['ment_text'] = $data['ment'];
		$data['ment_title'] = nl2br2(str($data['ment_text'], 'encode', 1));
		$data['ment'] = nl2br($data['ment']);
		$data['ment'] = str_replace("<br /><!--n-->", "\n", $data['ment']);

	// XHTML 설정
		$data['ment'] = str_replace(array("&amp;lt;script", "&amp;lt;/script"), array("&lt;script", "&lt;/script"), $data['ment']);

	// 태그 없는 변수
		$data['ment_notag'] = strip_tags($data['ment']);

	// 링크
		$data['js_send_from'] = "onclick='sendMemo({$data['from_member']});'";
		$data['js_send_target'] = "onclick='sendMemo({$data['target_member']});'";
		$data['js_memo_next'] = "onclick='memoAction(\"next\", \"{$data['no']}\");'";
		$data['js_memo_read'] = "onclick='memoAction(\"read\", \"{$data['no']}\");'";
		$data['js_memo_read_all'] = "onclick='memoAction(\"read_all\");'";
		$data['js_memo_close'] = "onclick='memoAction(\"close\");'";	

	if ($ret)
		return $data;
}


/** 내용 필터
 * @class io
 * @param
		$str: 내용 자료
		$mode: [encode!|decode]
		$autolink: 자동링크 사용여부 [0|1!]
 * @return String
 */
function filter(&$str, $mode = 'encode', $autolink = 1) {
	global $mini;

	//// 태그필터
		if (!getPermit("name:html") && $mode == 'encode') {
			$str = str_replace("<", "&lt;", $str);
		}
		else if ($mode == 'encode') {
			$str = str_replace("<", "&lt;", $str);

			// 태그, 주석형식만 디코딩 (php코드나 기타등등은 디코딩되면 안됨)
			$str = preg_replace(array("/\&lt\;(\/)?([a-zA-Z0-9]+)([^\>]*)\>/", "/\&lt\;\!\-\-(.+)\-\-\>/sm"), array("<\\1\\2\\3>", "<!--\\1-->"), $str);
		}
		else if ($mode == 'decode') {
			$str = str_replace("&lt;", "<", $str);
		}		

	//// 문자표현
	//+ magic_quote 에 따라 잘못될 수도 있음. 차후에 테스트 해봐야 함
		if ($mode == 'encode') {
			$str = str_replace('\\', '\\\\', $str);
		}

	//// 치명태그, 자바스크립트 막기
		if (empty($mini['member']['level_admin']) && $mode == 'encode') {
			$str = preg_replace("/j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*\:/is", "javascript&#58;", $str);
			$str = preg_replace("/on([a-z]+)\=/is", "on\\1&#61;", $str);
			$str = preg_replace("/\<(\/)?(style|plaintext|pre|xmp|base|meta|iframe|script|textarea|input|form)/is", "&amp;lt;\\1\\2", $str);
		}

		elseif ($mode == 'decode') {
			$str = str_replace(array("&#61;", "javascript&#58;"), array("=", "javascript:"), $str);
		}

	//// 허용태그 풀기
		if (!empty($mini['board']['filter_tag']) && empty($mini['member']['level_admin']) && $mode == 'encode') {
			$str = preg_replace("/\&(amp\;)?lt\;(\/)?(".str_replace(array(',', ' '), array('|', ''), $mini['board']['filter_tag']).")/is", "<\\2\\3", $str);
		}

	//// 자동링크
		if ($autolink == 1) {
			if (!function_exists("strtohex")) {
				function strtohex ($str) { 
					$retval="";
					for ($i=0; $i<strlen($str[1]); $i++) { 
					$retval .= "&#x" . bin2hex(substr($str[1], $i, 1)) . ";"; 
					} return $retval; 
				}
			}

			if ($mode == 'encode') {
				if (!empty($mini['board']['use_mail_encode']))
					$str = preg_replace_callback("/([[a-z0-9\.\-_\+]+\@[a-z0-9\.\-_\+]+)/is", "strtohex", $str); // 메일은 HEX로

				$str = preg_replace("/([^'\"a-z0-9\=\:\]\,])([a-z0-9]+)\:\/\/([^'\"\s]+)/is", "\\1<a href='\\2://\\3' target='_blank' title='autolink'>\\2://\\3</a>", $str);
				$str = preg_replace("/^([a-z0-9]+)\:\/\/([^'\"\s]+)/is", "<a href='\\1://\\2' target='_blank' title='autolink'>\\1://\\2</a>", $str);
			}
			else {
				$str = preg_replace("/\<a href\='([^']+)' target\='\_blank' title\='autolink'\>([^\<]+)\<\/a\>/isU", "\\1", $str);
			}
		}

	//// 매크로 처리
		if (!function_exists("macro_tool")) {
			// 목록
			function macro_list($v) {
				global $mini;

				if (empty($mini['macro_list']))
					$mini['macro_list'] = 1;
				else
					$mini['macro_list']++;

				$v[1] = trim($v[1]);
				$v[2] = trim($v[2]);
				$output = "<span id='macroListTitle{$mini['macro_list']}' style='display:none;'>{$v[1]}<!--macro--></span><div id='macroListMent{$mini['macro_list']}' class='macroListMent' style='display:none;'>{$v[2]}</div><!--macro-->";
				return $output;
			}

			// 폴딩
			function macro_fold($v) { 
				def($v[1], "클릭하시면 내용이 펼쳐집니다");
				$v[1] = trim($v[1]);
				$v[2] = trim($v[2]);
				$output = "<div class='fold'>{$v[1]}</div><div class='fold_ment' style='display:none;'>{$v[2]}</div><!--f-->";
				return $output;
			}

			// 그밖의
			function macro_tool($v) { 
				global $mini, $tmp_check;

				if (!isset($tmp_check)) $tmp_check = 0;

				def($width, '');
				def($height, '');
				def($align, 'center');
				def($title, '1');
				$mode = trim($v[1]);
				$v[2] = trim($v[2]);
				$src = $output = '';
				$src_arr = array();

				foreach (explode(",", $v[2]) as $key=>$val):
					// 주소
					if ($key == 0) {
						$val = preg_replace("/^:/i", "", $val);
						
						if (preg_match("/\|/", $val))
							$tmp = explode("|", $val);
						else
							$tmp[] = $val;
						
						foreach ($tmp as $key2=>$val2):
							$val2 = trim($val2);
							
							if ($val2) {
								switch ($mode):
									case 'img':
										if (!preg_match("/[^0-9]/", $val2))
											$val2 = "{$mini['dir']}download.php?mode=view&amp;no={$val2}";
										else
											$val2 = amp($val2, 'encode');

										$src .= ",{$val2}";
										$src_arr[] = $val2;
										break;

									case 'slide':
									case 'flv':
									case 'music':
									case 'movie':
										//if (!preg_match("/[^0-9]/", $val2)) $val2 = "{$mini['dir']}download.php?mode=view&no={$val2}";
										$src .= ",".urlencode($val2);
										$src_arr[] = $val2;
										break;

									case 'file':
										if (!preg_match("/[^0-9]/", $val2))
											$val2 = "{$mini['dir']}download.php?mode=view&amp;no={$val2}";
										else
											$val2 = amp($val2, 'encode');

										$src .= ",".urlencode($val2);
										$src_arr[] = $val2;
										break;

									default:
										$src = $val2;										
								endswitch;
							}
						endforeach;

						$src = preg_replace("/^\,/", "", $src);
						continue;
					}

					// 설정 입력
					$tmp = array();
					$tmp = explode(":", $val);

					if (!empty($tmp[0]) && !empty($tmp[1])) {
						$tmp[0] = trim($tmp[0]);
						
						// 허용 속성
						if (!preg_match("/^(width|height|title|align|startLine|style|size)$/i", $tmp[0])) {
							$output = $v[0];
						}

						${$tmp[0]} = trim($tmp[1]);
					}

				endforeach;


				$output2 = '';

				// 앞에 해당되는 것이 없을 경우
				if (empty($output)) {
					switch ($mode):
						// 이미지
						case 'img':
							// 크기
							$size = '';
							if ($width) $size .= " width='{$width}'";
							if ($height) $size .= " height='{$height}'";

							// 설명
							if ($title) {
								$title = "<div class='macroImageTitle' style='display:none;'></div>";
							}
							else {
								$title = '';
							}

							$output2 = "<img alt='macroImage' src='{$src}'{$size} class='macroImage".($size ? " hand' onclick='viewImage(this);'" : "'")." />{$title}";
							break;

						// 슬라이드
						case 'slide':		
							$time = $tmp_check;
							$output2 = 
								"<div id='iiSlideTarget_{$time}'></div>".
								"<script type='text/javascript' src='{$mini['dir']}js/iiSlideImage.js.php?no={$src}&amp;id=iiSlideTarget_{$time}&amp;title={$title}&amp;width={$width}&amp;height={$height}'></script>";
							break;

						// 동영상
						case 'flv':
						case 'music':
							$time = $tmp_check;
							if (!empty($src_arr)) {
								if (empty($width)) $width = '400';
								if (empty($height)) $height = '220';

								// 주소처리
//								$src = amp($src, 'encode');

								if (empty($mini['is_swfobject'])) {
									$mini['is_swfobject'] = 1;
									$output2 .= "\n<script type='text/javascript' src='{$mini['dir']}addon/mediaplayer/swfobject.js'></script>";
								}

								$output2 .= 
									"<div id='iiMediaTarget_{$time}'></div>".
									"<script type='text/javascript'>".
//									"//<![CDATA[".
									"var so{$time} = new SWFObject('{$mini['dir']}addon/mediaplayer/mediaplayer.swf','mp{$time}','{$width}','{$height}','8');".
									"so{$time}.addParam('allowscriptaccess','always');".
									"so{$time}.addParam('allowfullscreen','true');".
									"so{$time}.addVariable('width','{$width}');".
									"so{$time}.addVariable('height','{$height}');".
									"so{$time}.addVariable('file','{$mini['dir']}playlist.php%3Fsel%3D".(preg_match("/[^0-9]/", $src) ? urlencode(base64_encode($src)) : $src).($mode == 'music' ? "%26type%3Dmusic" : "")."');".									
									($mode == 'music' ? "so{$time}.addVariable('displaywidth','150');" : "").
									"so{$time}.addVariable('javascriptid','mp{$time}');".
									"so{$time}.addVariable('enablejs','true');".
									"so{$time}.write('iiMediaTarget_{$time}');";

								$output2 .=
//									"//]]>".
									"</script>";
							}
								break;
	
						case 'code':
							def($startLine, 1);

							switch ($src):
								case 'cpp':
								case 'csharp':
								case 'css':
								case 'delphi':
								case 'java':
								case 'javascript':
								case 'php':
								case 'python':
								case 'ruby':
								case 'sql':
								case 'vb':
									str($v[3], 'encode');
									$v[3] = str_replace("&amp;#91;", "&#91;", $v[3]);
									$v[3] = str_replace("&amp;#93;", "&#93;", $v[3]);
									$v[3] = str_replace(array("&amp;lt;"), array("&lt;"), $v[3]);
									$v[3] = str_replace(array("\\n", "\r\n", "\n"), array("&#92;n", "\n", "<br /><!--n-->"), $v[3]);
									$output2 = "<pre title='code' class='{$src}:firstline[{$startLine}]'>{$v[3]}</pre>";
									break;

								//+ bbcode 지워야 함
								default:
									str($v[3], 'encode');
									$v[3] = str_replace("&amp;#91;", "&#91;", $v[3]);
									$v[3] = str_replace("&amp;#93;", "&#93;", $v[3]);
									$v[3] = str_replace(array("\\n", "\r\n", "\n"), array("&#92;n", "\n", "<br /><!--n-->"), $v[3]);
									$output2 = "<div class='bbcode'><pre>{$v[3]}</pre></div>";
							endswitch;
							break;

						// 글상자
						case 'box':
							$v[3] = str_replace("	", "&nbsp;&nbsp;&nbsp;&nbsp;", $v[3]);
							$v[3] = str_replace(array("<"), array("&lt;"), $v[3]);
							$output2 = "<div class='iiBox_{$src}'>{$v[3]}</div>";
							break;

						// 글꼴
						case 'font':
							$tmp = "font-family:\"{$src}\";";
							if (!empty($size)) $tmp .= "font-size:{$size};";
							$output2 = "<span style='{$tmp}'>{$v[3]}</span>";
							break;

						// 파일추가
						case 'file':
							$src = urldecode($src);
							$output2 = "<a href='{$src}' target='_blank'>{$v[3]}</a>";
							break;
					endswitch;

					// 정렬
					switch ($align):
						case 'inleft':
						case 'inright':
							$align = str_replace("in", "", $align);
							$align2 = ($align == 'right') ? 'left' : 'right';
							$output = "<table class='macroImage' align='{$align}' style='margin-{$align2}:10px;'><tr><td>{$output2}</td></tr></table>";
							break;
						
						default:
							if ($mode == 'img' || $mode == 'slide' || $mode == 'flv' || $mode == 'music')
								$output = "<div style='text-align:{$align};'><table class='macroImage' align='{$align}'><tr><td>{$output2}</td></tr></table></div>";
							else
								$output = $output2;
					endswitch;

					$v[0] = str_replace(array("<!--", "-->"), array("&lt;!--", "--&gt;"), $v[0]);
					$output = "<!--macroToolStart-->{$output}<!--macroToolEnd:{$v[0]}:macroToolEnd-->";
				}

				$tmp_check++;
				return $output;
			}

			// 내부 URL금지 및 target 설정
			function macro_url_security ($v) {
				global $mini;

				if (!preg_match("/\shref\=(['\"]?)[a-z0-9]+\:\/\/[^'\"]+(['\"]?)/is", $v[1])) {
					$v[1] = preg_replace("/(\s)href\=('|\")?([^'\"]+)('|\")?/is", "\\1href=\\2{$mini['pdir']}error.php?msg=".urlencode("내부 주소로 링크되어 차단되었습니다")."\\4", $v[1]);
				}				

				if (!preg_match("/\starget\=(['\"]?)([^'\"]+)(['\"]?)/i", $v[1])) {
					return "<a{$v[1]} target='_blank'>";
				}
				else
					return "<a{$v[1]}>";
			}
		}

		// 링크 보안처리
		if (empty($mini['member']['level_admin']) && $mode == 'encode') {
			$str = preg_replace_callback("/\<a([^\>]+)\>/isU", "macro_url_security", $str);
		}

		// encode
		if ($mode == 'encode') {
			$str_left = $str_right = $preg_left = $preg_right = array();

			$str_left[] = "\\\\]";
			$str_right[] = "&#93;";

			$str_left[] = "\\\\[";
			$str_right[] = "&#91;";

			$str = str_replace($str_left, $str_right, $str);

			$preg_left[] = "/\[a\:([^\]]+)\]([^\[]+)\[\/a\]/isU";
			$preg_right[] = "<a href='\\1' target='_blank'>\\2</a><!--macro-->";
			
			$preg_left[] = "/\[printList\]/i";
			$preg_right[] = "<div id='macroListPrint'></div>";
			
			foreach (array('i', 'b', 'u', 'strike', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'sup', 'sub') as $val2):
				$preg_left[] = "/\[{$val2}\](.+)\[\/{$val2}\]/isU";
				$preg_right[] = "<{$val2}>\\1</{$val2}><!--macro-->";
			endforeach;

			$preg_left[] = "/\[h](.+)\[\/h\]/sU";
			$preg_right[] = "<span class='highlight'>\\1</span><!--macro-->"; // 제일 마지막

			$str = preg_replace($preg_left, $preg_right, $str);
			$str = preg_replace_callback("/\[(code|font|box|file)\:([^\]]+)\](.+)\[\/(code|font|box|file)\]/isU", "macro_tool", $str);
			$str = preg_replace_callback("/\[list\:([^\]]+)\](.+)\[\/list\]/isU", "macro_list", $str);
			$str = preg_replace_callback("/\[fold\:?([^\]\:]*)](.+)\[\/fold\]/isU", "macro_fold", $str);
			$str = preg_replace_callback("/\[(img|slide|flv|music)\:(.+)\]/isU", "macro_tool", $str);
		}

		// decode
		else {
			$str_left = $str_right = $preg_left = $preg_right = array();

			$str_left[] = "&#93;";
			$str_right[] = "\\]";

			$str_left[] = "&#91;";
			$str_right[] = "\\[";

			$str = str_replace($str_left, $str_right, $str);

			$preg_left[] = "/\<span id\='macroListTitle[0-9]+' style\='display\:none\;'\>(.+)\<\!\-\-macro\-\-\>\<\/span\>\<div id\='macroListMent[0-9]+' class\='macroListMent' style\='display\:none\;'\>(.+)\<\/div\>(&lt;|\<)\!\-\-macro\-\-(\>|&gt;)/isU";
			$preg_right[] = "[list:\\1]\\2[/list]";
			
			$preg_left[] = "/\<div id\='macroListPrint'\>\<\/div\>/i";
			$preg_right[] = "[printList]";

			$preg_left[] = "/\<span class\='highlight'\>(.+)\<\/span\>(&lt;|\<)\!\-\-macro\-\-(\>|&gt;)/isU";
			$preg_right[] = "[h]\\1[/h]";
			
			$preg_left[] = "/\<\!\-\-macroToolStart\-\-\>.+\<\!\-\-macroToolEnd\:(.+)\:macroToolEnd\-\-\>/isU";
			$preg_right[] = "\\1";
		
			$preg_left[] = "/\<div class\='fold'\>(.+)\<\/div\>\<div class\='fold_ment' style\='display\:none\;'\>(.+)\<\/div\>(&lt;|\<)\!\-\-f\-\-(\>|&gt;)/isU";
			$preg_right[] = "[fold:\\1]\\2[/fold]";

			$preg_left[] = "/\<a href\='([^']+)' target\='\_blank'\>([^\<]+)\<\/a\>(&lt;|\<)\!\-\-macro\-\-(\>|&gt;)/isU";
			$preg_right[] = "[a:\\1]\\2[/a]";

			foreach (array('i', 'b', 'u', 'strike', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'sup', 'sub') as $val2):
				$preg_left[] = "/\<{$val2}\>(.+)\<\/{$val2}\>(&lt;|\<)\!\-\-macro\-\-(\>|&gt;)/isU";
				$preg_right[] = "[{$val2}]\\1[/{$val2}]";
			endforeach;

			$str = preg_replace($preg_left, $preg_right, $str);
		}

	//// 내용필터
		if (empty($mini['member']['level_admin']) && !empty($mini['board']['use_filter']) && !empty($mini['board']['filter_ment']) && $mode == 'encode') {
			// 단어 나눔
				$tmp = explode(",", $mini['board']['filter_ment']);
				$tmp_ment = $str;

			// 복수공백 제거, 공백기호 적용
				$tmp_ment = preg_replace("/&#34;|&#039;|&#124;|[\\x2A-\\x2F\\x5B\\x5D\\x7B\\x7D\\x7E]/s", "", $tmp_ment);

			// 태그, 특수기호 제거
				$tmp_ment = preg_replace(array(
					"/\<[^\>]+\>/s", 
					"/&lt;|&gt;|&amp;/s",
					"/[\\x21-\\x2F\\x3A-\\x40\\x5B-\\x60\\x7B-\\x7E]/s"
				), "", $tmp_ment);

			// 함수 정의
				if (!function_exists("asc_hex")) {
					function asc_hex($char) {
					$t_char = '';
					$j = 0; $word_length=strlen($char); 
					for($i = 0;$i<$word_length;$i++) { 
					if($j == 0) { if(ord(substr($char,$i,1)) > 0xa1 && ord(substr($char,$i,1)) <= 0xfe) { 
					$j = 1; $t_char = $t_char.bin2hex(substr($char,$i,1)); } 
					else { $t_char = $t_char."00".bin2hex(substr($char,$i,1))." "; } 
					} else { $t_char = $t_char.bin2hex(substr($char,$i,1))." "; $j = 0; } } 
					return $t_char; }
				}

			// 루프
				if (!empty($tmp_ment) && !empty($tmp))
				foreach ($tmp as $val):
					$val = trim($val);
					$pos = strpos($tmp_ment, $val);
					if ($pos !== false) {
						// 진짜 같은지 확인
							if (asc_hex(substr($tmp_ment, $pos, strlen($val))) == asc_hex($val)) {
								if ($mini['board']['filter_mode'] == 'denied')
									__error("[{$val}] 금지단어가 포함되어 있습니다");
								else
									$str = preg_replace("/".$val."/is", "<span class='filter' title='{$val}'>금지단어</span><span style='display:none;'>{$val}</span>", $str);
							}
					}
				endforeach;
		}

		else if ($mode == 'decode') {
			$str = preg_replace("/\<span class\='filter' title\='([^']+)'\>금지단어\<\/span\>\<span style\=\'display\:none\;\'\>[^\<]+<\/span\>/isU", "\\1", $str);
		}

	//// 맨 뒤 escape 방지
		if(substr($str, -1) == "\\" && $mode == 'encode') $str = substr_replace($str, "&#92;", -1);
}


/** 입력된 다수 정보를 갖고 옵션을 만든다
 * @class io
 * @param
		-q: 쿼리를 기준으로 자료를 가져온다. as 를 써서 skey 와 svalue 로 뽑아야 한다
		-str: 텍스트를 기준으로 자료를 가져온다 [value:key]...  둘중 하나를 생략하면 같은값이 된다
		-first: 앞에 넣을 자료를 입력한다. str형식으로 입력한다
		-dir: 경로를 기준으로 자료를 가져온다
		-is_dir: 경로명만 가져온다
		-skin: 출력스킨, [:key:], [:value:], [:dir:]
		-is_array: array로 반환한다
 * @return String
 */
function getOption($param) {
	global $mini;
	$param = param($param);

	iss($param['q']);
	iss($param['str']);
	iss($param['dir']);
	iss($param['first']);
	iss($param['is_dir']);
	def($param['is_array'], 0);
	iss($data);
	iss($dp);

	def($param['skin'], "<option value='[:value:]'>[:key:]</option>");
	
	$output = ($param['is_array'] ? array() : "\n");

	// first 처리
	if ($param['first']) {
		$arr = '';

		if (!preg_match("/^\[/", $param['first'])) {
			$arr[] = $param['first'];
		}

		else {
			foreach (getStr($param['first']) as $key=>$val):
				$tmp = array();
				$tmp = explode(":", $val);

				if (count($tmp) > 1) {
					$name = $tmp[0];
					$value = $tmp[1];
				}
				else {
					$name = $tmp[0];
					$value = $tmp[0];
				}

				$data[] = array(
					'skey' => $name,
					'svalue' => $value
				);
			endforeach;
		}
	}

	// 자료 로드
	if ($param['q']) {
		$data = sql("
			q: {$param['q']}
			mode: array
		");
	}

	else if ($param['str']) {
		foreach (getStr($param['str']) as $key=>$val):
			$tmp = array();
			$tmp = explode(":", $val);
			if (count($tmp) > 1) {
				$name = $tmp[0];
				$value = $tmp[1];
			}
			else {
				$name = $tmp[0];
				$value = $tmp[0];
			}

			$data[] = array(
				'skey' => $name,
				'svalue' => $value
			);
		endforeach;
	}

	else if ($param['dir']) {
		if (!preg_match("/\/$/", $param['dir'])) {
			$param['dir'] .= "/";
		}

		$dp = opendir($param['dir']);

		if ($dp) {
			while ($file = readdir($dp)):
				if ($file != '.' && $file != '..' && (($param['is_dir'] && is_dir($param['dir'].$file)) || !$param['is_dir'])) {
					$data[] = array(
						'skey' => $file,
						'svalue' => $file,
						'dir' => $param['dir'].$file
					);
				}
			endwhile;

			closedir($dp);
		}
	}

	// skin에 적용하기
	if (is_array($data))
	foreach ($data as $key=>$val):
		iss($val['dir']);
		
		if ($param['is_array']) {
			$output[$val['skey']] = $val['svalue'];
		}
		else {
			$tmp = $param['skin'];
			$tmp = str_replace(array('[:key:]', '[:value:]', '[:dir:]', '[:rand:]'), array($val['skey'], $val['svalue'], $val['dir'], rand(1000,9999)), $tmp);
			$output .= "{$tmp}\n";
		}
	endforeach;

	return $output;
}


/** 디렉토리를 가져옴
 * @class admin
 * @param
		$dir: 경로명
		$is_file: 파일
		$is_dir: 디렉토리
		$full: 모든 경로면 포함
 * @return Array
 */
function getDir($param) {
	$param = param($param);

	def($param['is_file'], 0);
	def($param['is_dir'], 1);
	def($param['full'], 0);

	if (!preg_match("/\/$/", $param['dir'])) {
		$param['dir'] .= "/";
	}

	$output = array();
	$dp = opendir($param['dir']);

	if ($dp) {
		while ($file = readdir($dp)):
			if ($file != '.' && $file != '..' && (($param['is_dir'] && is_dir($param['dir'].$file)) || $param['is_file'])) {
				$output[] = $param['full'] ? $param['dir'].$file : $file;
			}
		endwhile;
		closedir($dp);
	}

	return $output;
}


/** 단축키
 * @class io 
 * @param
		$key: 정의할 키
		$func: 정의할 자바스크립트
 * @return String
 */
function setKey($key, $func) {
	global $mini;
	$output = '';

	if (empty($mini['keyMapName']))
		$mini['keyMapName'] = 0;
	else
		$mini['keyMapName']++;
	
	if (!empty($mini['board']['key_map'])) {
		$mini['board']['key_map'][$mini['keyMapName']] = $key;
		$output = "<script type='text/javascript'>
//<![CDATA[
	if (!\$defined(key_func)) var key_func = {};
	key_func['{$mini['keyMapName']}'] = function () { {$func} };
//]]>
</script>";
	}

	return $output;
}


/** 자료 비밀번호 일치여부 확인
 * @class io
 * @param
		$data: 자료 데이터
		$move: 비밀번호 입력 이동 여부
		$error: 모드 지정
 * @return Boolean
 */
function checkPass($data, $move = 0, $error = '') {
	global $mini;

	iss($_REQUEST['id']);
	iss($_REQUEST['group']);
	iss($_REQUEST['no']);
	iss($_REQUEST['pass_encode']);

	if (!empty($mini['member']['level_admin']))
		return true;

	if (!empty($data['target_member']) && empty($mini['log']))
		__error('로그인이 필요합니다');
	if (!empty($data['target_member']) && !empty($mini['log']) && $data['target_member'] != $mini['member']['no'])
		__error('권한이 없습니다');

	if (empty($data['target_member'])) {
		if (empty($_REQUEST['pass_encode'])) {
			if ($move) {
				$url = preg_match("/^upload\./i", $mini['filename']) && !empty($_REQUEST['pageURL']) ? $_REQUEST['pageURL'] : url();
				if (!empty($_REQUEST['iframe'])) $url = '';

				__error(array(
					'mode' => !empty($error) ? $error : 'goto'.(!empty($_REQUEST['iframe']) ? '.parent' : ''),
					'url' => "pass.php?id={$_REQUEST['id']}&group={$_REQUEST['group']}&url={$url}"
				));
			}
			else 
				__error('권한이 없습니다');
		}
		else if ($_REQUEST['pass_encode'] != md5("{$data['pass']}|{$mini['ip']}|".session_id())) {
			__error("비밀번호가 일치하지 않습니다");
		}
	}

	return false;
}


/** 관리자용 - 미니보드 배열 차이점 기록
 * @class 
 * @param
		$data_ex: 기존자료
		$data: 현재자료
		$strip: slashes 빼기
 * @return 
 */
function arr_diff($data_ex, $data, $strip = 0) {
	global $mini;

	$output = array();

	if (is_array($data_ex)) {
		foreach ($data_ex as $key => $val):
			$val = trim($val);
			if (!$val) $val = 0;
			if (!empty($data[$key])) {
				$data[$key] = trim($data[$key]);
				if ($strip) $data[$key] = stripslashes($data[$key]);
			}
			if (isset($data[$key]) && empty($data[$key])) $data[$key] = 0;

			// 수정본에 없을 떄
			if (!isset($data[$key])) {
				$output['data_ex'][$key] = $val;
				continue;
			}

			else if ($val != $data[$key]) {
				$output['data'][$key] = $data[$key];
				$output['data_ex'][$key] = $val;
				unset($data[$key]);
				continue;
			}

			else {
				unset($data[$key]);
				continue;
			}
		endforeach;

		if (is_array($data)) {
			foreach ($data as $key => $val):
				$output['data'][$key] = $val;
			endforeach;
		}
	}

	if (empty($output['data'])) unset($output['data']);
	if (empty($output['data_ex'])) unset($output['data_ex']);

	return $output;
} 

/** 스킨에서 지정되는 url_key 값들을 이용해 link_key 값들을 만들어 내는 함수
 * @class io.skin
 * @param
		$arr: 배열 자료
  */
function urlToLink(&$arr) {
	global $mini;
	foreach ($arr as $key=>$val):
		if (preg_match("/^url_/i", $key)) {
			if (preg_match("/_del$/i", $key)) {
				$msg = '삭제하시겠습니까?';
				if (preg_match("/cmt\.x\.php/i", $val)) {
					$msg = '선택한 댓글을 지우시겠습니까?' . (!empty($mini['member']['level_admin']) ? "\\\\n(답변 댓글이 달려있으면 같이 지워집니다)" : '');
				}
				if (preg_match("/write\.x\.php/i", $val)) $msg = '선택한 글을 지우시겠습니까?';
				str($msg, 'encode');

				if (!isset($arr["link_".str_replace("url_", "", $key)])) $arr["link_".str_replace("url_", "", $key)] = $val ? "href='{$val}' onclick='return confirm(\"{$msg}\");'" : "href='#' onclick='return false;'";
				if (!isset($arr["js_".str_replace("url_", "", $key)])) $arr["js_".str_replace("url_", "", $key)] = $val ? "onclick='if (confirm(\"{$msg}\")) document.location.href=\"{$val}\";'" : "disabled='disabled'";
			}
			else {
				if (!isset($arr["link_".str_replace("url_", "", $key)])) $arr["link_".str_replace("url_", "", $key)] = $val ? "href='{$val}'" : "href='#' onclick='return false;'";
				if (!isset($arr["js_".str_replace("url_", "", $key)])) $arr["js_".str_replace("url_", "", $key)] = $val ? "onclick='document.location.href=\"{$val}\";'" : "disabled='disabled'";
			}

			$arr["url2_".str_replace("url_", "", $key)] = amp($val, 'encode');
		}

		if (preg_match("/^pop_/i", $key)) {
			$arr["js_{$key}"] = $val ? "onclick='{$val}'" : "";
		}
	endforeach;
}

/** &amp; 를 변형한다
 * @class str
 * @param
		$str: 문자열
		$mode: [decode|encode]
 * @return String
 */
function amp($str, $mode = 'decode') {
	return $mode != 'decode' ? str_replace('&', '&amp;', $str) : str_replace('&amp;', '&', $str);
}


/** CUBE 기록
 * @class io
 */
function setCube() {
	global $mini;

	if (!empty($mini['set']['cube_max']) && !empty($mini['set']['cube_name']) && !empty($mini['set']['use_cube']) && empty($mini['log'])
		&& (
			($mini['filename'] == 'member.php' && !empty($mini['site']['use_cube'])) || 
			($mini['filename'] != 'member.php' && !empty($mini['board']['use_cube']))
		)
	) {
		$_SESSION['cube'] = md5(microtime().rand(1000,9999));
		$_SESSION['cube_mode'] = rand(1, $mini['set']['cube_max']);
	}
}


/** CUBE 출력
 * @class io
 */
function cube() {
	global $mini;

	if (empty($_SESSION['cube']) || empty($_SESSION['cube_mode'])) {
		setCube();
	}

	if (!empty($_SESSION['cube']) && !empty($_SESSION['cube_mode']) && !empty($mini['set']['cube_max']) && !empty($mini['set']['cube_name']) && !empty($mini['set']['use_cube']) && empty($mini['log'])
		&& (
			($mini['filename'] == 'member.php' && !empty($mini['site']['use_cube'])) || 
			($mini['filename'] != 'member.php' && !empty($mini['board']['use_cube']))
		)
	) {
		$cube_arr = array();
		$cube_arr[0] = $_SESSION['cube'];
		
		for ($i=1; $i<=8; $i++):
			while (empty($tmp) || $tmp == $_SESSION['cube']):
				$tmp = md5(microtime().rand(1000,9999));
			endwhile;
			$cube_arr[$i] = $tmp;
		endfor;

		shuffle($cube_arr);

		$cube_name_arr = getStr($mini['set']['cube_name']);
		$cube_name_arr2 = explode(",", $cube_name_arr[$_SESSION['cube_mode']-1]);
		shuffle($cube_name_arr2);
		$cube_sessid = "&amp;PHPSESSID=".session_id()."&amp;time=".urlencode(md5(microtime()));

		echo "<div id='cubeDiv' class='cube' style='display:none;'><div class='cube_title'><span class='bold'>CUBE</span> - antiSpam System<br /><span style='color:#ccc;'>문장에 맞는 이미지를 선택하세요</span><br />KEYWORD: ".trim($cube_name_arr2[0])."</div>";
		foreach ($cube_arr as $val):
			echo "<img src='{$mini['dir']}cube.php?cube={$val}{$cube_sessid}' class='cube_image' alt='한개를 선택하세요' onclick='cubeSel(\"{$val}\");' />";
		endforeach;
		echo "</div>";
	}
}

/** 코드강조 출력
 * @class io
  */
function setCodeSyntax() {
	global $mini;

	if (!empty($mini['set']['syntax_rule'])) {
		echo "\n<script type='text/javascript'>\n//<![CDATA[\n";
		echo "new Element('link', {'rel': 'stylesheet', 'media': 'screen', 'type': 'text/css', 'href': '{$mini['dir']}addon/dp.SyntaxHighlighter/Styles/SyntaxHighlighter.css'}).inject(document.head);\n";
		echo "//]]>\n</script>\n";
		echo "<script type='text/javascript' src='{$mini['dir']}addon/dp.SyntaxHighlighter/Scripts/shCore.js'></script>\n";
		foreach (explode(",", $mini['set']['syntax_rule']) as $val):
			$val = trim($val);
			$val = strtoupper(substr($val, 0, 1)).strtolower(substr($val, 1));
			echo "<script type='text/javascript' src='{$mini['dir']}addon/dp.SyntaxHighlighter/Scripts/shBrush{$val}.js'></script>\n";
		endforeach;
		echo "<script type='text/javascript'>\n".
			"//<![CDATA[\n".
			"dp.SyntaxHighlighter.ClipboardSwf = '{$mini['dir']}addon/dp.SyntaxHighlighter/Scripts/clipboard.swf';\n".
			"dp.SyntaxHighlighter.HighlightAll('code');\n".
			"//]]>\n".
			"</script>";
	}
}

/** 주소 만들기
 * @class url
 * @param
		-url: 기본이 되는 URL을 설정. 없으면 referer 를 기본으로 한다. referer 도 없으면 default_url 값으로 한다
		-default_url: referer가 없을 때 기본이 되는 url 설정
		-param: 추가되는 param을 설정한다
 * @return 
 */
function makeURI($param = '') {
	
}

?>