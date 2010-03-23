<?php

# 로그인

if (empty($mini['dir'])) $mini['dir'] = '';

//// 기본 include
	if (!function_exists("sqlConnect")) {
		$mini['auto'] = 1;
		include "{$mini['dir']}_inc.php";
	}

/**
 * 로그인
 * @class login
 * @param
		$data: POST array
		-site: 그룹번호
		-board: 게시판번호
		-is_passed: 무조건로그인[0!|1]
  */
function setLogin(&$data, $param) {
	global $mini;
	$param = param($param);
	
	iss($data['uid']);
	iss($data['pass']);
	iss($data['pass_encode']);
	iss($data['autologin']);
	iss($mini['set']['use_login_session']);
	iss($pass_after);
	iss($key_login);
	iss($autologin_after);
	def($mini['this']['script'], $_REQUEST['script']);
	def($mini['this']['script'], 'back');
	def($mini['set']['lock_login'], 5);
	def($param['is_passed'], 0);
	$site_data = $board_data = array();
	$site = '';

	if (empty($param['site'])) __error('선택된 그룹이 없습니다.'.' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');

	//// 그룹 로드
		if (!empty($mini['site']) && $mini['site']['no'] == $param['site']) {
			$site_data = $mini['site'];
		}
		else {
			$site_data = getSite($param['site'], 1);
		}

	//// 게시판 로드
		if (!empty($param['board'])) {
			if (!empty($mini['board']) && $mini['board']['no'] == $param['board']) {
				$board_data = $mini['board'];
			}
			else {
				$board_data = getBoard($param['board'], 1);
			}
		}
		else if (!empty($mini['board']['site']) && $mini['board']['site'] == $site_data['no']) {
			$board_data = $mini['board'];
		}

	//// 변수 검사
		check($data['uid'], "name: 아이디");
		if (!$param['is_passed']) check($data['pass_encode'], "type:id, name:암호화된 비밀번호, min:16, max:40");
		if (!isset($site_data)) __error('선택된 그룹이 없습니다.');
		$data['uid'] = mysql_escape_string($data['uid']);

	//// 미니아이 로그인
		if (preg_match("/^\@/", $data['uid'])) {
			__error('준비중 입니다.');
		}

	//// 그룹 선택
		else {
			$site = "[{$site_data['no']}]";

			// 그룹의 그룹연결
			if (!empty($site_data['site_link']))
				$site .= $site_data['site_link'];

			// 게시판의 그룹연결
			if (!empty($board_data['site_link']))
				$site .= $board_data['site_link'];
		}

	//// 데이터 로드
		$tmp_data = sql("q:SELECT * FROM {$mini['name']['member']} WHERE uid='{$data['uid']}', mode:array");

	//// 아이디 확인
		if (!is_array($tmp_data)) __error('일치하는 회원이 없습니다');

	//// 그룹 확인
		$check = 0;
		foreach ($tmp_data as $key=>$val):
			if (inStr($val['site'], $site) || count(array_intersect( getStr($site), getStr($val['site_link']))) || inStr('god', $val['admin']) || inStr('admin', $val['admin'])) {
				$check = 1;
				$data_ex = $val;
			}
		endforeach;

		if (!$check)
			__error('일치하는 회원이 없습니다');

	//// 컨버팅 회원 확인
		if (preg_match("/^\!/", $data_ex['pass'])) {
			if (!empty($mini['complete']['ajax'])) {
				__complete(array(
					'mode' => 'ajax,reload.parent',
					'script' => "window.open(\"{$mini['dir']}login.conv.php?no={$data_ex['no']}\", \"conv\", \"width=400, height=400, scrollbars=2\");"
				));
			}

			else {
				__complete(array(
					'mode' => 'move',
					'url' => "{$mini['dir']}login.conv.php?no={$data_ex['no']}"
				));
			}
		}

	//// 실패 회수 확인
		if ($data_ex['lock_login'] >= $mini['set']['lock_login'] && $data_ex['no'] != 1) {
			__error("로그인을 {$mini['set']['lock_login']}회 이상 실패하여 아이디가 잠겼습니다. 관리자에게 문의하세요");
		}

	//// 비밀번호 확인
		if (!$param['is_passed'] && $data['pass_encode'] != md5("{$data_ex['pass']}|{$mini['ip']}|".session_id())) {
			if ($data_ex['pass'] == 'reset!') {
				__error('비밀번호가 초기화 되었습니다. 아이디/비밀번호 찾기를 통해 새 비밀번호로 설정해 주세요');
			}

			sql("UPDATE {$mini['name']['member']} SET lock_login = lock_login + 1 WHERE no={$data_ex['no']}");
			addLog("
				mode: login_lock_login
				target_member: {$data_ex['no']}
				field1: {$data_ex['lock_login']}
			");
			__error("비밀번호가 일치하지 않습니다 (".($data_ex['lock_login']+1)."회 오류)");
		}

	//// 암호화
		// 자동 로그인
			if ($data['autologin']) {
				$pass_after = '';
				$key_login = md5($mini['date']);
				$autologin_after = md5("{$data_ex['pass']}|{$mini['ip']}|{$key_login}");
				$interval = time() + 2592000; // 30 days after
			}

		// 일반 로그인
			else {
				$pass_after = md5("{$data_ex['pass']}|{$mini['ip']}");
				$key_login = $autologin_after = '';
				$interval = 0;
			}

	//// 굽기
		// 세션
			if ($mini['set']['use_login_session']) {
				$_SESSION['m_no'] = $data_ex['no'];
				$_SESSION['m_pass'] = $pass_after;
			}

		// 쿠키
			else {
				setcookie("m_no", $data_ex['no'], $interval, '/');
				setcookie("m_pass", $pass_after, $interval, '/');
			}

		// 자동로그인
			if ($data['autologin']) {
				setcookie("m_no", $data_ex['no'], $interval, '/');
				setcookie("m_autologin", $autologin_after, $interval, '/');
			}
			else {
				setcookie("m_autologin", '', 0, '/');
			}

	//// 로그인 기록 추가
		def($mini['set']['login_history_count'], 10);
		$data_ex['history_login'] .= "{$mini['ip']}|{$mini['date']}\n";
		$tmp = explode("\n", $data_ex['history_login']);
		if (count($tmp) > $mini['set']['login_history_count']) {
			unset($tmp[0]);
		}

		$data_ex['history_login'] = is_array($tmp) ? implode("\n", $tmp) : "";

	//// 로그인 포인트 설정
		if (!empty($site_data['point_login'])) {
			if (!sql("SELECT COUNT(*) FROM {$mini['name']['log']} WHERE mode='point' and target_member={$data_ex['no']} and field3='로그인' and date >= '".date("Y/m/d 00:00:00", $mini['time'])."'")) {
				setPoint("
					target: {$data_ex['no']}
					msg: 로그인
					point: {$site_data['point_login']}
				");
			}
		}

	//// 다중 자동로그인 설정
		//+ 정식버젼에서 지울 구문임
		if (!empty($data_ex['ip']) && strpos($data_ex['ip'], '[') === false) $data_ex['ip'] = "[{$data_ex['ip']}]";
		if (!empty($data_ex['key_login']) && strpos($data_ex['key_login'], '[') === false) $data_ex['key_login'] = "[{$data_ex['key_login']}]";

		if (empty($key_login)) $key_login = '0';
		
		// 입력
			$data_ex['ip'] .= "[{$mini['ip']}]";
			$data_ex['key_login'] .= "[{$key_login}]";
		
		// 3개 한정
			$arr_ip = getStr($data_ex['ip']);
			if (count($arr_ip) > 5) {
				unset($arr_ip[0]);
				$data_ex['ip'] = "[".implode("][", $arr_ip)."]";
			}
			$arr_key_login = getStr($data_ex['key_login']);
			if (count($arr_key_login) > 5) {
				unset($arr_key_login[0]);
				$data_ex['key_login'] = "[".implode("][", $arr_key_login)."]";
			}

	//// DB수정
		sql("UPDATE {$mini['name']['member']} SET ip='{$data_ex['ip']}', date_login='{$mini['date']}', key_login='{$data_ex['key_login']}', lock_login=0, count_login=count_login+1, history_login='{$data_ex['history_login']}' WHERE no={$data_ex['no']}");
} // END function


/**
 * 로그아웃
 * @class login
 */
function setLogout() {
	global $mini;

	unset($_SESSION['m_no']);
	unset($_SESSION['m_pass']);
	setcookie("m_no", '', 0, '/');
	setcookie("m_pass", '', 0, '/');
	setcookie("m_autologin", '', 0, '/');

	if ($mini['log']) {
		sql("UPDATE {$mini['name']['member']} SET ip='', key_login='', date_login='', ip='' WHERE no={$mini['member']['no']}");
		unset($mini['member']);
		unset($mini['log']);
	}
} // END function

?>