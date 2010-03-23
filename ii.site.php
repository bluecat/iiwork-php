<?php
# 그룹 실행 라이브러리

/**
 * 그룹 등록
 * @class admin.site
 * @param
		$data: 자료
		-is_check: 입력변수 체크 여부
 * @return Array 등록된 그룹 자료
 */
function addSite($data, $param = '') {
	global $mini;
	$param = param($param);

	def($param['is_check'], 1);
	
	//// 변수 체크
		if ($param['is_check']) {
			checkFieldSite($data);

			// 권한체크
				checkAdmin("
					mode: admin
					type: ajax
				");
		}

	//// 기본변수 여부 체크
		if (!isset($data['name'])) __error('그룹 이름을 입력해 주세요');

	//// 기본변수 입력
		$data['date'] = $mini['date'];
		unset($data['mode']);
		unset($data['script']);

	//// 이름 중복 체크
		if (sql("SELECT COUNT(*) FROM {$mini['name']['site']} WHERE name='{$data['name']}'"))
			__error('중복된 그룹이름 입니다.');

	//// 쿼리
		sql("INSERT INTO {$mini['name']['site']} ".query($data, 'insert'));
		$data['no'] = getLastId($mini['name']['site'], "name='{$data['name']}' and date='{$data['date']}'");

	//// 로그 기록
		addLog("
			mode: site_add
			field1: {$data['no']}
		");

	return $data;
} // END function


/**
 * 그룹 수정
 * @class admin.site
 * @param
		$data: 자료
		$no: 대상번호
		-is_check: 입력변수 체크 여부
 * @return 
 */
function editSite($data, $no, $param = '') {
	global $mini;
	$param = param($param);

	def($param['is_check'], 1);
	
	//// 변수 체크
		if ($param['is_check'])
			checkFieldSite($data);

	//// 번호 넣기
		if (is_array($no) && isset($data['id'])) 
			__error('그룹 다중 수정에 아이디 수정이 포함되어 있습니다');
		
		if (!is_array($no)) {
			$tmp = $no;
			$no = Array();
			$no[0] = $tmp;
		}

	//// 수정할 수 없는 정보 제거
		unset($data['no']);
		unset($data['date']);
		unset($data['mode']);
		unset($data['script']);

	//// 넘어오지 않은 변수 처리
		if (empty($data['site_link'])) $data['site_link'] = '';


	foreach ($no as $key=>$val):
		//// 번호 체크
			check($val, 'type:num, name:그룹번호');

		//// 데이터 로드
			$data_ex = sql("SELECT * FROM {$mini['name']['site']} WHERE no={$val}");
			if (!is_array($data_ex)) __error('해당 그룹이 존재하지 않습니다');

		//// 권한체크
			checkAdmin("
				site: {$data_ex['no']}
				type: ajax
			");

		//// 기본변수 여부 체크
			if (isset($data['name']) && !$data['name']) __error('그룹 이름을 입력해 주세요');

		//// 이름 중복 체크
			if (isset($data['name'])) {
				if (sql("SELECT COUNT(*) FROM {$mini['name']['site']} WHERE no!={$val} and name='{$data['name']}'"))
					__error('중복된 그룹이름 입니다.');
			}

		//// 쿼리
			sql("UPDATE {$mini['name']['site']} SET ".query($data, 'update')." WHERE no={$val}");

		//// 번호 재입력
			$data['no'] = $val;

		//// 로그 기록
			addLog(array(
				'mode' => 'site_edit',
				'field1' => $data['no'],
				'ment' => arr_diff($data_ex, $data)
			));
			
	endforeach;
} // END function


/**
 * 그룹 삭제
 * @class admin.site
 * @param
		$no: 대상번호
 * @return 
 */
function delSite($no) {
	global $mini;

	//// 그룹 번호
		if (!is_array($no)) {
			$tmp = $no;
			$no = Array();
			$no[0] = $tmp;
		}

	foreach ($no as $key=>$val):
		//// 번호 체크
			check($val, "type:num, name:그룹번호");

		//// 기본그룹 삭제 불가능
			if ($val == 1) __error('1번 그룹은 삭제할 수 없습니다');

		//// 데이터 로드
			$data_ex = sql("SELECT * FROM {$mini['name']['site']} WHERE no={$val}");
			if (!is_array($data_ex)) __error('해당 그룹이 존재하지 않습니다');

		//// 권한체크
			checkAdmin("
				mode: admin
				type: ajax
			");

		//// 쿼리
			sql("DELETE FROM {$mini['name']['site']} WHERE no={$val}");

		//// 그룹에 속해있던 회원 및 게시판 기본 그룹으로 이동
			sql("UPDATE {$mini['name']['admin']} SET site=1 WHERE site={$val}");
			sql("UPDATE {$mini['name']['member']} SET site=1 WHERE site={$val}");

		//// 로그 기록
			addLog(array(
				'mode' => 'site_del',
				'field1' => $data_ex['no'],
				'ment' => $data_ex
			));
	endforeach;

} // END function


/**
 * 입력 변수 체크 - 그룹
 * @class admin.site 
 * @param
		$data: 자료
  */
function checkFieldSite(&$data) {
	global $mini;

	if (!is_array($data))
		__error("입력된 데이터가 없습니다");

	// DB 컬럼 로드
		iss($col);
		$col = getColumns($mini['name']['site']);

	foreach ($data as $key=>$val):
		switch ($key):
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

			// 추가필드
			case 'field':
				if (is_array($val)) {
					str($data[$key], 'encode');
					$data[$key] = serialize($data[$key]);
				}
				else
					__error('추가필드 형식이 올바르지 않습니다');
				break;

			// 가입항목설정
			case 'join_setting':
				if (is_array($val)) {
					$data[$key] = serialize($data[$key]);
				}
				else
					__error('가입항목설정 형식이 올바르지 않습니다');
				break;

			// 템플릿
			case 'template':
				if (is_array($val)) {
					str($data[$key], 'encode');
					$data[$key] = serialize($data[$key]);
				}
				else
					__error('템플릿 형식이 올바르지 않습니다');
				break;

			// 메일
			case 'mail':
				check($val, "type:mail, name:대표 메일");
				break;

			// 휴대전화
			case 'cp':
				check($val, "type:cp, name:대표 휴대전화, is_not:1");
				break;

			// 회원상태목록
			case 'status':
				if (!empty($val)) {
					$tmp = array_unique(getStr($val));
					if (is_array($tmp)) {
						$data[$key] = "[".implode("][", $tmp)."]";
					}
				}
				break;

			// 그룹이름 태그처리
			case 'name':
				// str($data[$key], 'encode');
				break;
			
			// 기본(단일필드)
			default:
				// tmp 값 제외
				if (preg_match("/^tmp_/i", $key))
					unset($data[$key]);
				
				// 배열 값 제외
				if (is_array($val))
					__error("[{$key}] 값은 허용되지 않습니다");

				// 존재하지 않는 필드일 때 빼기
					if (!inStr($key, $col)) {
						unset($data[$key]);
					}

				// 권한
					if (preg_match("/permit_/i", $key) && $val && count(getStr($val)) > 1) {
						$data[$key] = "[".implode("][", array_unique(getStr($val)))."]";
					}
		endswitch;
	endforeach;
} // END function

?>