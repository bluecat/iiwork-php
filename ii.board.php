<?php
# 게시판 실행 라이브러리

/**
 * 게시판 등록
 * @class admin.board
 * @param
		$data: 자료
		-is_check: 입력변수 체크 여부
		-is_conv
 * @return Array 등록된 게시판 자료
 */
function addBoard($data, $param = '') {
	global $mini;
	$param = param($param);

	def($param['is_check'], 1);
	
	//// 변수 체크
		if ($param['is_check']) {
			checkFieldBoard($data);

			// 권한체크
				checkAdmin("
					site: {$data['site']}
					type: ajax
				");
		}

	//// 아이디 중복 체크
		check($data['id'], 'type:id, name:게시판아이디');
		if (!preg_match("/[^0-9]/", $data['id'])) __error('게시판 아이디에 숫자만 입력하실 수 없습니다.');
		if (sql("SELECT COUNT(*) FROM {$mini['name']['admin']} WHERE id='{$data['id']}'"))
			__error('중복된 게시판 아이디 입니다.');

	//// 기본변수 여부 체크
		if (!isset($data['name'])) __error('게시판 이름을 입력해 주세요');
		if (!isset($data['skin'])) __error('스킨을 선택해 주세요');
		check($data['site'], 'type:num, name:그룹');

	//// 기본변수 입력
		if (empty($data['date'])) $data['date'] = $mini['date'];
		unset($data['mode']);
		unset($data['script']);

	//// 스키마 로드
		if (!isset($mini['scheme'])) {
			include "{$mini['dir']}_db.php";
			$mini['scheme'] = array();
			$mini['scheme'] = $install_table;
		}

	//// 기본 언어셋 추가
		// 버젼 정보 로드
		$version = sql("SELECT VERSION()");
		$version_arr = explode(".", $version);
		$check_version = 0;
		if (!empty($version_arr)) {
			if (!empty($version_arr[0]) && $version_arr[0] >= 5)
				$check_version = 1;
			if (!empty($version_arr[0]) && $version_arr[0] == 4 && !empty($version_arr[1]) && $version_arr[1] >= 1)
				$check_version = 1;
		}

		if (!empty($check_version)) {
			$mini['scheme']['board'][1] .= " DEFAULT CHARACTER SET utf8";
			$mini['scheme']['cmt'][1] .= " DEFAULT CHARACTER SET utf8";
		}

	//// 쿼리
		sql("INSERT INTO {$mini['name']['admin']} ".query($data, 'insert'));
		$data['no'] = getLastId($mini['name']['admin']);

		$table_board = str_replace("[:table:]", $mini['name']['board'].$data['no'], $mini['scheme']['board'][1]);
		$table_cmt = str_replace("[:table:]", $mini['name']['cmt'].$data['no'], $mini['scheme']['cmt'][1]);

	//// 게시판 생성 쿼리
		sql($table_board);
		sql($table_cmt);

	//// 로그 기록
		addLog("
			mode: board_add
			field1: {$data['no']}
		");

	return $data;
} // END function


/**
 * 게시판 수정
 * @class admin.board
 * @param
		$data: 자료
		$no: 대상번호
		-is_check: 입력변수 체크 여부
 * @return 
 */
function editBoard($data, $no, $param = '') {
	global $mini;
	$param = param($param);

	def($param['is_check'], 1);
	
	//// 변수 체크
		if ($param['is_check']) {
			checkFieldBoard($data);
		}

	//// 번호 넣기
		if (is_array($no) && isset($data['id'])) 
			__error('게시판 다중 수정에 아이디 수정이 포함되어 있습니다');
		
		if (!is_array($no)) {
			$tmp = $no;
			$no = Array();
			$no[0] = $tmp;
		}

	//// 수정할 수 없는 정보 제거
		unset($data['no']);
		unset($data['dir']);
		unset($data['date']);
		unset($data['mode']);
		unset($data['script']);

	//// 넘어오지 않은 변수명 처리
		if (empty($data['field'])) $data['field'] = '';
		if (empty($data['site_link'])) $data['site_link'] = '';

	foreach ($no as $key=>$val):
		//// 번호 체크
			check($val, 'type:num, name:게시판번호');

		//// 데이터 로드
			$data_ex = sql("SELECT * FROM {$mini['name']['admin']} WHERE no={$val}");
			if (!is_array($data_ex)) __error('해당 게시판이 존재하지 않습니다');

		//// 권한체크
			checkAdmin("
				site: {$data_ex['site']}
				board: {$data_ex['no']}
				type: ajax
			");

		//// 아이디 중복 체크
			if (isset($data['id'])) {
				check($data['id'], 'type:id, name:게시판아이디');
				if (!preg_match("/[^0-9]/", $data['id'])) __error('게시판 아이디에 숫자만 입력하실 수 없습니다.');
				if (sql("SELECT COUNT(*) FROM {$mini['name']['admin']} WHERE no!={$val} and id='{$data['id']}'"))
					__error('중복된 게시판 아이디 입니다.');
			}

		//// 기본변수 여부 체크
			if (isset($data['name']) && !$data['name']) __error('게시판 이름을 입력해 주세요');
			if (isset($data['skin']) && !$data['skin']) __error('스킨을 선택해 주세요');
			if (isset($data['site']) && !check($data['site'], 'type:num')) __error("그룹을 선택해 주세요");

		//// 쿼리
			sql("UPDATE {$mini['name']['admin']} SET ".query($data, 'update')." WHERE no={$val}");

		//// 번호 재입력
			$data['no'] = $val;

		//// 로그 기록
			addLog(array(
				'mode' => 'board_edit',
				'field1' => $data['no'],
				'ment' => arr_diff($data_ex, $data)
			));
	endforeach;
} // END function


/**
 * 게시판 삭제
 * @class admin.board
 * @param
		$no: 대상번호
 * @return 
 */
function delBoard($no) {
	global $mini;

	//// 게시판 번호
		if (!is_array($no)) {
			$tmp = $no;
			$no = Array();
			$no[0] = $tmp;
		}

	foreach ($no as $key=>$val):
		//// 번호 체크
			check($val, "type:num, name:게시판번호");

		//// 데이터 로드
			$data_ex = sql("SELECT * FROM {$mini['name']['admin']} WHERE no={$val}");
			if (!is_array($data_ex)) __error('해당 게시판이 존재하지 않습니다');

		//// 권한체크
			checkAdmin("
				site: {$data_ex['site']}
				type: ajax
			");

		//// 쿼리
			checkTime("delAdmin");
			sql("DELETE FROM {$mini['name']['admin']} WHERE no={$val}");
			checkTime("delAdmin");

		//// 게시판 제거 쿼리
			checkTime("delData");
			sql("DROP TABLE {$mini['name']['board']}{$data_ex['no']}");
			sql("DROP TABLE {$mini['name']['cmt']}{$data_ex['no']}");
			checkTime("delData");

		//// 관련 자료 제거
			sql("DELETE FROM {$mini['name']['search']} WHERE id={$data_ex['no']}");
			sql("DELETE FROM {$mini['name']['file']} WHERE id={$data_ex['no']}");
			sql("DELETE FROM {$mini['name']['report']} WHERE id={$data_ex['no']}");
			sql("DELETE FROM {$mini['name']['trash']} WHERE id={$data_ex['no']}");
			sql("DELETE FROM {$mini['name']['counter']} WHERE id={$data_ex['no']}");
			sql("DELETE FROM {$mini['name']['counter_log']} WHERE id={$data_ex['no']}");

		//// 자료 폴더 제거
			if (is_dir("{$mini['dir']}file/{$data_ex['dir']}")) rename("{$mini['dir']}file/{$data_ex['dir']}", "{$mini['dir']}file/_deleted_{$data_ex['dir']}");

		//// 로그 기록
			addLog(array(
				'mode' => 'board_del',
				'field1' => $data_ex['no'],
				'ment' => $data_ex
			));
	endforeach;

} // END function


/**
 * 입력 변수 체크 - 게시판
 * @class admin.board 
 * @param
		$data: 자료
  */
function checkFieldBoard(&$data) {
	global $mini;

	if (!is_array($data))
		__error("입력된 데이터가 없습니다");

	// DB 컬럼 로드
		iss($col);
		$col = getColumns($mini['name']['admin']);

	foreach ($data as $key=>$val):
		switch ($key):
			// 삭제 설정
			case 'date':
			case 'no':
			case 'dir':
				unset($data[$key]);
				break;

			// 그룹연결
			case 'site_link':
				if (is_array($val)) {
					$data[$key] = "[".implode("][", $val)."]";
				}
				break;

			// 카테고리
			case 'category':
				if (is_array($val)) {
					foreach ($val as $key2=>$val2):
						check($val2['no'], 'type:num, name: 카테고리번호');
						check($val2['depth'], 'type:num, name: 카테고리단계, is_not:1');
						check($val2['name'], 'name: 카테고리이름');
						str($data[$key][$key2]['name'], 'encode');
					endforeach;

					$data[$key] = serialize($data[$key]);
				}
				else
					__error('카테고리 형식이 올바르지 않습니다');
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

			// 옵션
			case 'options':
				if (is_array($val)) {
					str($data[$key], 'encode');
					$data[$key] = serialize($data[$key]);
				}
				else
					__error('스킨옵션 형식이 올바르지 않습니다');
				break;

			// 단축키
			case 'key_map':
				if (is_array($val)) {
					str($data[$key], 'encode');
					$data[$key] = serialize($data[$key]);
				}
				else
					__error('단축키 형식이 올바르지 않습니다');
				break;

			// 기본(단일필드)
			default:
				// tmp 값 제외
				if (preg_match("/^tmp_/i", $key))
					unset($data[$key]);
				
				// 배열 값 제외
				if (is_array($val) && !preg_match("/^(config)$/", $key))
					__error("[{$key}] 값은 허용되지 않습니다");

				// 존재하지 않는 필드일 때 빼기
					if (!inStr($key, $col)) {
						unset($data[$key]);
					}

				// 권한
					if (preg_match("/permit_/i", $key) && $val && count(getStr($val)) > 1) {
						$data[$key] = "[".implode("][", array_unique(getStr($val)))."]";
						//__error($data[$key]);
					}
				break;
		endswitch;
	endforeach;
} // END function

?>