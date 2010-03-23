<?php
# 회원 실행 라이브러리

/**
 * 회원 등록
 * @class admin.member
 * @param
		$data: 자료
		-is_check: 입력변수 체크 여부
 * @return Array 등록된 회원 자료
 */
function addMember($data, $param = '') {
	global $mini;
	$param = param($param);

	def($param['is_check'], 1);

	unset($data['formMode']);
	unset($data['formMsg']);
	unset($data['formFunc']);
	unset($data['formURL']);
	unset($data['formHTML']);
	unset($data['no']);
	unset($data['ip']);
	unset($data['ip_join']);
	unset($data['key_find']);
	unset($data['key_sms']);
	unset($data['key_login']);
	unset($data['date']);
	unset($data['date_login']);
	unset($data['count_login']);
	unset($data['count_vote']);
	unset($data['count_post']);
	unset($data['count_comment']);
	unset($data['count_recent_comment']);
	unset($data['history_login']);
	unset($data['mode']);
	unset($data['script']);
	unset($data['admin']);
	unset($data['id_mode']);

	if (empty($mini['member']['level_admin'])) {
		unset($data['site']);
		unset($data['site_link']);
		unset($data['level']);
		unset($data['admit']);
		unset($data['confirm_co']);
		unset($data['point']);
		unset($data['point_sum']);
		unset($data['money']);
		unset($data['count_alert']);
		unset($data['lock_login']);
		unset($data['history_admin']);
		unset($data['date_punish']);
	}

	//// 추가필드 권한 체크
		if (!empty($mini['site']['field'])) {
			foreach ($mini['site']['field'] as $key=>$val):
				if (empty($data['field'][$key]) && !empty($val['is_req'])) __error("[{$val['name']}]을 입력해 주세요");
			endforeach;
		}
		
	//// 변수 체크
		if ($param['is_check']) {
			checkFieldMember($data);

			// 권한체크
				if (!empty($mini['is_admin']))
					checkAdmin("
						site: {$_POST['site']}
						type: ajax
					");
		}

	//// 아이디 중복 체크
		check($data['uid'], 'type:id, name:회원아이디');
		if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE uid='{$data['uid']}'"))
			__error('중복된 회원 아이디 입니다.');

	//// 닉네임 중복 체크
		if (!isset($data['name'])) __error('회원 닉네임을 입력해 주세요');
		if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE name='{$data['name']}'"))
			__error("중복된 닉네임 입니다.");

	//// 주민등록번호 중복 체크 및 암호화
		if (isset($data['jumin']) && $data['jumin']) {
			$data['jumin'] = md5($data['jumin']);
			if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE jumin='{$data['jumin']}'"))
				__error("중복된 주민등록번호 입니다.");
		}

	//// 사업자번호 중복 체크
		if (isset($data['co_num']) && $data['co_num']) {
			if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE co_num='{$data['co_num']}'"))
				__error("중복된 사업자등록번호 입니다.");
		}

	//// 메일 중복 체크
		if (!empty($data['mail'])) {
			if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE mail='{$data['mail']}'"))
				__error("중복된 메일 입니다.");
		}

	//// 필수입력 정보 사이트 정보대로 처리
		if (!empty($mini['site']['join_check'])) {
			$tmp = '';

			foreach ($mini['site']['join_check'] as $key=>$val):
				if (empty($data[$key]) && !empty($val['name'])) {
					$tmp = (!empty($tmp)) ? $tmp.",{$val['name']}" : $val['name'];
				}
			endforeach;

			if (!empty($tmp))
				__error("필수입력정보를 입력해 주세요. [{$tmp}]");
		}

	//// 기본변수 여부 체크
		if (isset($mini['site'])) def($data['site'], $mini['site']['no']);
		check($data['site'], 'type:num, name:그룹');
		if (!isset($data['pass'])) __error('비밀번호를 입력해 주세요');

	//// 가입 기본 포인트 적용
		if (!empty($mini['site']['point_join']) && empty($data['point']) && empty($data['point_sum'])) {
			$data['point'] = $data['point_sum'] = $mini['site']['point_join'];
		}

	//// 가입 승인 기능 설정
		if (!empty($mini['site']['admit']) && (empty($mini['member']['level_admin']) || $mini['member']['level_admin'] < 2)) { 
			$data['admit'] = 0;
		}

	//// 재가입 방지 확인
		if (!empty($mini['site']['withdraw'])) {
			iss($data['mail']);
			if (sql("SELECT COUNT(*) FROM {$mini['name']['log']} WHERE mode='member_withdraw' and (field1='{$data['uid']}' or ip='{$mini['ip']}' or field2='{$data['mail']}') and date >= '".date("Y-m-d H:i:s", $mini['time'] - (86400 * $mini['site']['withdraw']))."'")) {
				__error('해당 아이디, IP 혹은 메일주소로 재가입 하실 수 없습니다');
			}
		}

	//// 기본변수 입력
		$data['date'] = $mini['date'];
		$data['ip_join'] = $mini['ip'];
		unset($data['pass_encode']);
		unset($data['pass_confirm']);
		unset($data['jumin_encode']);

	//// 쿼리
		sql("INSERT INTO {$mini['name']['member']} ".query($data, 'insert'));
		$data['no'] = getLastId($mini['name']['member'], "uid='{$data['uid']}' and date='{$mini['date']}' and ip_join='{$mini['ip']}'");

	//// 로그 기록
		addLog("
			mode: member_add
			field1: {$data['no']}
		");

	//// 인증메일 발송
		if (empty($mini['member']['level_admin']) && !empty($mini['site']['admit']) && $mini['site']['admit'] == 'mail' && !empty($mini['set']['use_smtp']) && !empty($mini['site']['template']['admit'])) {
			include "{$mini['dir']}skin/template/mail.admit.tpl.php";
			if (!function_exists('skinConv')) include "{$mini['dir']}_inc.skinmake.php";

			if (!empty($tpl) && (!empty($mini['site']['mail']) || !empty($mini['set']['mail']))) {
				$tmp = !empty($tpl[$mini['site']['template']['admit']]) ? $tpl[$mini['site']['template']['admit']] : current($tpl);

				// 키 생성
				$admit_key = rand(100000, 999999);

				unset($mini['skin']);
				$mini['skin'] = '';
				$mini['skin']['site'] = &$mini['site'];
				$mini['skin']['data'] = &$data;
				$mini['skin']['date'] = $mini['date'];
				$mini['skin']['key'] = $admit_key;
				$mini['skin']['url_key'] = "{$mini['pdir']}ajax.php?mode=admit_mail&no={$data['no']}&key={$admit_key}";
				$mini['skin']['link_key'] = "href='{$mini['skin']['url_key']}' target='_blank'";

				sql("UPDATE {$mini['name']['member']} SET key_find = '{$admit_key}|{$mini['date']}' WHERE no={$data['no']}");
				
				$result = send_mail(array(
					'from_name' => $mini['site']['name'],
					'from_mail' => (!empty($mini['site']['mail']) ? $mini['site']['mail'] : $mini['set']['mail']),
					'to_name' => $data['name'],
					'to_mail' => $data['mail'],
					'title' => skinConv($tmp['title'], 'str'),
					'ment' => skinConv($tmp['ment'], 'str')
				));
			}
			else {
				__error('인증메일을 발송할 수 없습니다. 관리자에게 문의해 주세요');
			}
		}

	//// 가입메일 발송
		if (empty($mini['member']['level_admin']) && !empty($mini['set']['use_smtp']) && !empty($mini['site']['template']['join'])) {
			include "{$mini['dir']}skin/template/mail.join.tpl.php";
			if (!function_exists('skinConv')) include "{$mini['dir']}_inc.skinmake.php";

			if (!empty($tpl) && (!empty($mini['site']['mail']) || !empty($mini['set']['mail']))) {
				unset($mini['skin']);
				$mini['skin'] = '';
				$mini['skin']['date'] = $mini['date'];
				$mini['skin']['site'] = &$mini['site'];
				$mini['skin']['data'] = &$data;
			
				$result = send_mail(array(
					'from_name' => $mini['site']['name'],
					'from_mail' => (!empty($mini['site']['mail']) ? $mini['site']['mail'] : $mini['set']['mail']),
					'to_name' => $data['name'],
					'to_mail' => $data['mail'],
					'title' => skinConv($tpl[$mini['site']['template']['join']]['title'], 'str'),
					'ment' => skinConv($tpl[$mini['site']['template']['join']]['ment'], 'str')
				));
			}
		}

	//// 가입SMS 발송
		if (empty($mini['member']['level_admin']) && !empty($mini['set']['use_sms']) && !empty($mini['site']['template']['join_sms'])) {
			include "{$mini['dir']}skin/template/sms.join.tpl.php";
			if (!function_exists('skinConv')) include "{$mini['dir']}_inc.skinmake.php";
			if (!function_exists('iiSMSSend')) include "{$mini['dir']}_inc.sms.php";

			if (!empty($tpl) && !empty($mini['site']['cp'])) {
				unset($mini['skin']);
				$mini['skin'] = '';
				$mini['skin']['date'] = $mini['date'];
				$mini['skin']['site'] = &$mini['site'];
				$mini['skin']['data'] = &$data;
			
				$result = iiSMSSend($data['cp'], $mini['site']['cp'], skinConv($tpl[$mini['site']['template']['join_sms']], 'str'), $mini['set']['lang']);
			}
		}

	return $data;
} // END function


/**
 * 회원 수정
 * @class admin.member
 * @param
		$data: 자료
		$no: 대상번호
		-is_check: 입력변수 체크 여부
  */
function editMember($data, $no, $param = '') {
	global $mini;
	$param = param($param);

	def($param['is_check'], 1);
	$is_admin = (!empty($mini['member']['level_admin']) && $mini['member']['level_admin'] >= 2);

	//// 수정할 수 없는 정보 제거
		unset($data['no']);
		unset($data['ip']);
		unset($data['ip_join']);
		unset($data['key_find']);
		unset($data['key_sms']);
		unset($data['key_login']);
		unset($data['date']);
		unset($data['date_login']);
		unset($data['count_login']);
		unset($data['count_vote']);
		unset($data['count_post']);
		unset($data['count_comment']);
		unset($data['count_recent_comment']);
		unset($data['history_login']);
		unset($data['mode']);
		unset($data['script']);
		unset($data['admin']);
		unset($data['id_mode']);

		if (!$is_admin) {
			unset($data['site']);
			unset($data['site_link']);
			unset($data['level']);
			unset($data['admit']);
			unset($data['confirm_co']);
			unset($data['confirm_mail']);
			unset($data['point']);
			unset($data['point_sum']);
			unset($data['money']);
			unset($data['count_alert']);
			unset($data['lock_login']);
			unset($data['history_admin']);
			unset($data['date_punish']);
		}
		else {
			$data['admit'] = $data['confirm_cp'] = $data['confirm_mail'] = 1;
		}

		if (isset($data['admit_sms'])) $admit_sms = $data['admit_sms'];

	//// 추가필드 권한 체크
		if (!empty($mini['site']['field'])) {
			foreach ($mini['site']['field'] as $key=>$val):
				if (empty($data['field'][$key]) && !empty($val['is_req'])) __error("[{$val['name']}]을 입력해 주세요");
			endforeach;
		}

	//// 변수 체크
		if ($param['is_check'])
			checkFieldMember($data);

	//// 넘어오지 않은 변수 처리
		if (!empty($mini['member']['level_admin']) && empty($data['site_link'])) $data['site_link'] = '';
		if (empty($data['open'])) $data['open'] = '';

	//// 번호 넣기
		if (is_array($no) && isset($data['name'])) 
			__error('회원 다중 수정에 닉네임 수정이 포함되어 있습니다');
		
		if (!is_array($no)) {
			$tmp = $no;
			$no = Array();
			$no[0] = $tmp;
		}

	foreach ($no as $key=>$val):
		//// 번호 체크
			check($val, 'type:num, name:회원번호');		

		//// 데이터 로드
			$data_ex = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$val}");
			if (!is_array($data_ex)) __error('해당 회원이 존재하지 않습니다');

		//// 권한체크
			if (!empty($mini['is_admin']))
				checkAdmin("
					site: {$data_ex['site']}
					type: ajax
				");

		//// 아이디 중복 체크
			if (isset($data['uid'])) {
				check($data['uid'], 'type:id, name:회원아이디');
				if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE no!={$val} and uid='{$data['uid']}'"))
					__error('중복된 회원 아이디 입니다.');
			}

		//// 닉네임 중복 체크
			if (isset($data['name'])) {
				if (empty($data['name'])) __error('회원 닉네임을 입력해 주세요');
				if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE no!={$val} and name='{$data['name']}'"))
					__error("중복된 닉네임 입니다.");
			}

		//// 메일 중복 체크
			if (isset($data['mail'])) {
				if (empty($data['mail'])) __error('메일을 입력해 주세요');
				if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE no!={$val} and mail='{$data['mail']}'"))
					__error("중복된 메일 입니다.");
			}

		//// 주민등록번호 중복 체크 및 암호화
			if (isset($data['jumin']) && $data['jumin']) {
				$data['jumin'] = md5($data['jumin']);
				if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE no!={$val} and jumin='{$data['jumin']}'"))
					__error("중복된 주민등록번호 입니다.");
			}

		//// 주민등록번호 삭제 방지
			if (isset($data['jumin']) && empty($data['jumin']) && $data['jumin'] !== 0) {
				unset($data['jumin']);
			}
			if (isset($data['jumin']) && empty($data['jumin']) && $data['jumin'] === 0) {
				$data['jumin'] = '';
			}

		//// 사업자번호 중복 체크
			if (isset($data['co_num']) && $data['co_num']) {
				if (sql("SELECT COUNT(*) FROM {$mini['name']['member']} WHERE no!={$val} and co_num='{$data['co_num']}'"))
					__error("중복된 사업자등록번호 입니다.");
			}

		//// 인증받은 자료 변경시 인증 풀림
			if (!$is_admin) {
				if (!empty($data_ex['confirm_mail']) && $data_ex['mail'] != $data['mail'])
					$data['confirm_mail'] = 0;
				if (!empty($data_ex['confirm_cp']) && $data_ex['cp'] != $data['cp'])
					$data['confirm_cp'] = 0;
			}

		//// SMS인증 확인
			if (!empty($admit_sms) && !empty($mini['site']['admit']) && $mini['site']['admit'] == 'sms' && !$is_admin) {
				if (empty($data_ex['key_sms'])) __error('인증번호를 받지 않았습니다');
				$tmp = explode("|", $data_ex['key_sms']);
				if ($admit_sms != $tmp[0]) __error('인증번호가 일치하지 않습니다');
				if ($data['cp'] != $tmp[1]) __error('인증받은 휴대전화번호가 아닙니다');
				if ($mini['time'] - 300 > strtotime($tmp[2])) __error('5분이 경과되었습니다. 다시 인증번호를 신청하시기 바랍니다');

				$data['admit'] = 1;
				$data['key_sms'] = $data['key_find'] = '';
				$data['confirm_cp'] = 1;
			}

		//// 필수입력 정보 사이트 정보대로 처리해야 함
			if (!empty($mini['site']['join_check'])) {
				$tmp = '';

				foreach ($mini['site']['join_check'] as $key2=>$val2):
					if (empty($data[$key2]) && !empty($val2['name'])) {
						$tmp = (!empty($tmp)) ? $tmp.",{$val2['name']}" : $val2['name'];
					}
				endforeach;

				if (!empty($tmp))
					__error("필수입력정보를 입력해 주세요. [{$tmp}]");
			}

		//// 기본변수 여부 체크
			if (isset($data['site']) && !check($data['site'], 'type:num')) __error("그룹을 선택해 주세요");

		//// 다른 회원의 정보를 수정할 때 권한 체크
			// 그룹 정보를 저장
			if (!empty($mini['site']))
				$tmp_site = $mini['site'];
			
			// 파싱
			if ($mini['member']['level_admin'] < 3) $mini['member']['level_admin'] = 0;
			getSite($data_ex['site']);
			$tmp = parseMember($data_ex, 1);
			if (empty($mini['member']['level_admin']) && count(array_intersect($mini['member']['site_admin'], $tmp['site_link_arr'])))
				$mini['member']['level_admin'] = 2;

			// 비교
			if (!empty($mini['member']['level_admin']) && $mini['member']['no'] != $data_ex['no'] && $mini['member']['level_admin'] < 4 && $mini['member']['level_admin'] <= $tmp['level_admin'])
				__error('자신보다 높은 권한의 관리자 정보를 수정/열람할 수 없습니다');
		
			if (!empty($tmp_site)) {
				$mini['site'] = $tmp_site;
				unset($tmp_site);
			}

		//// 쿼리
			sql("UPDATE {$mini['name']['member']} SET ".query($data, 'update')." WHERE no={$val}");

		//// 번호 재입력
			$data['no'] = $val;

		//// 로그 기록
			addLog(array(
				'mode' => 'member_edit',
				'field1' => $data['no']
			));
	endforeach;
} // END function


/**
 * 회원 삭제
 * @class admin.member
 * @param
		$no: 대상번호(array가능)
 * @return 
 */
function delMember($no) {
	global $mini;

	//// 회원 번호
		if (!is_array($no)) {
			$tmp = $no;
			$no = Array();
			$no[0] = $tmp;
		}

	foreach ($no as $key=>$val):
		//// 번호 체크
			check($val, "type:num, name:회원번호");

		//// 데이터 로드
			$data_ex = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$val}");
			if (!is_array($data_ex)) __error('해당 회원이 존재하지 않습니다');

		//// 권한체크
			if (!empty($mini['is_admin'])) {
				checkAdmin("
					site: {$data_ex['site']}
					type:
				");
			}

			if ($val == 1) {
				__error('설치할 때 생성된 최고관리자는 삭제할 수 없습니다');
			}

		//// 쿼리
			sql("DELETE FROM {$mini['name']['member']} WHERE no={$val}");

		//// 파일 삭제
			if (file_exists("{$mini['dir']}sfile/icon/{$data_ex['no']}.gif")) @unlink("{$mini['dir']}sfile/icon/{$data_ex['no']}.gif");
			if (file_exists("{$mini['dir']}sfile/icon_name/{$data_ex['no']}.gif")) @unlink("{$mini['dir']}sfile/icon_name/{$data_ex['no']}.gif");
			if (file_exists("{$mini['dir']}sfile/photo/{$data_ex['no']}.gif")) @unlink("{$mini['dir']}sfile/photo/{$data_ex['no']}.gif");

		//// 탈퇴 기록
			if (!empty($mini['site']['withdraw']) && empty($mini['member']['level_admin'])) {
				iss($data_ex['mail']);
				
				addLog("
					mode: member_withdraw
					field1: {$data_ex['uid']}
					field2: {$data_ex['mail']}
				");
			}

		//// 로그 기록
			addLog(array(
				'mode' => 'member_del',
				'field1' => $data_ex['no'],
				'ment' => $data_ex
			));
	endforeach;
} // END function


/**
 * 입력 변수 체크 - 회원
 * @class admin.member 
 * @param
		$data: 자료
  */
function checkFieldMember(&$data) {
	global $mini;

	if (!is_array($data))
		__error("입력된 데이터가 없습니다");

	// DB 컬럼 로드
		iss($col);
		$col = getColumns($mini['name']['member']);

	foreach ($data as $key=>$val):
		switch ($key):
			// 숫자 체크
			case 'site':
			case 'level':
			case 'confirm_jumin':
			case 'permit_mail':
			case 'confirm_mail':
			case 'permit_cp':
			case 'confirm_cp':
			case 'age':
			case 'icon':
			case 'icon_name':
			case 'photo':
			case 'point':
			case 'point_sum':
			case 'money':
			case 'lock_login':
			case 'admit':
				check($val, "type:num, name:{$key}, is_not:1");
				break;

			// 삭제 설정
			case 'date':
			case 'no':
				unset($data[$key]);
				break;

			// 그룹연결
			case 'site_link':
				if (is_array($val)) {
					$data[$key] = "[".implode("][", $val)."]";
				}
				break;

			// 비밀번호
			case 'pass_encode':
			case 'pass':
				if (isset($data['pass_encode']) && $data['pass_encode']) {
					switch ($mini['site']['secure_pass']):
						case 'md5':
						case 'sha1':
						case 'mixed':
							check($data['pass_encode'], "type:id, name:암호화된 비밀번호, min:16, max:40");
							break;
						case 'mysql':
							$data['pass_encode'] = mysql_escape_string($data['pass_encode']);
							$tmp = array();
							$tmp = sql("SELECT password('{$data['pass_encode']}') as pass");
							$data['pass'] = $tmp['pass'];
							break;
						case 'mysql_old':
							$data['pass_encode'] = mysql_escape_string($data['pass_encode']);
							$tmp = array();
							$tmp = sql("SELECT old_password('{$data['pass_encode']}') as pass");
							$data['pass'] = $tmp['pass'];
							break;
					endswitch;

					$data['pass'] = $data['pass_encode'];
					unset($data['pass_encode']);
				}
				else {
					unset($data[$key]);
				}
				break;

			// 홈페이지
			case 'homepage':
				check($data[$key], "type:homepage, name:홈페이지, is_not:1");
				break;

			// 주민등록번호
			case 'jumin':
				check($val, "type:jumin, name:주민등록번호, is_not:1");
				break;

			// 사업자번호
			case 'co_num':
				check($val, "type:co_num, name:사업자등록번호, is_not:1");

			// 메일
			case 'mail':
				check($val, "type:mail, name:메일, is_not:1");
				
				// 가입제한 메일 체크
					if (!empty($mini['site']['filter_mail'])) {
						if (inStr(a(explode('@', $val), '1'), $mini['site']['filter_mail'])) {
							__error("가입이 제한된 메일 도메인 입니다. 다른 도메인을 사용한 메일로 가입해주세요");
						}							
					}
				break;

			// 휴대전화
			case 'cp':
				check($val, "type:cp, name:휴대전화, is_not:1");
				break;

			// 메신져
			case 'chat':
				if (!empty($val)) {
					$data[$key] = "[".implode("][", array_unique(getStr($val)))."]";
				}
				break;

			// 추가필드
			case 'field':
				if (is_array($val)) {
					str($data[$key], 'encode');
					$data[$key] = serialize($data[$key]);
				}
				else
					__error('추가필드 형식이 올바르지 않습니다');
				break;

			// 내용 필터
			case 'sign':
			case 'ment':
				if (!empty($val)) {
					filter($data[$key], 'encode');
				}
				break;

			// str형식
			case 'open':
				$data[$key] = "[".implode("][", $val)."]";
				break;
			
			// 기본(단일필드)
			default:
				// tmp 값 제외
					if (preg_match("/^tmp_/i", $key))
						unset($data[$key]);

				// 존재하지 않는 필드일 때 빼기
					if (!inStr($key, $col)) {
						unset($data[$key]);
					}

				// 배열 값 제외
					if (is_array($val))
						__error("[{$key}] 값은 허용되지 않습니다");
		endswitch;
	endforeach;
} // END function
?>