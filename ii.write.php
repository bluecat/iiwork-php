<?php

# 글쓰기 함수

global $mini;

//+ 권한체크
//+ 변수가공
//+ 추가필드, etc필드 입력 방법

/** 글 등록
 * @class write
 * @param
		$data: 글쓰기 data, 이미 check와 가공이 끝난 데이터여야 함
		$board_data: 게시판 설정 data
		-id: 게시판 아이디. 게시판 설정 data가 없을 때 아이디를 토대로 설정을 로드한다.
		-num: 글번호 지정. (글번호를 지정할 수 있다. 없다면 새로 구함)
		-is_check: 입력변수 체크 여부
		-target_member: 대상회원을 지정할 수 있음
		-is_conv: 컨버팅여부
		-is_manage: 게시물관리
 * @return Array data
 */
function addPost($data, $board_data = '', $param = '') {
	global $mini;
	$param = param($param);
	iss($param['id']);
	iss($param['num']);
	iss($param['is_conv']);
	iss($data_before);
	iss($data_notice);
	iss($data_next);
	iss($data_prev);
	$output_conv = array();
	def($param['is_check'], 1);
	
	$trackback = $trackback_excerpt = $trackback_charset = '';	
	if (!empty($data['trackback'])) $trackback = $data['trackback'];
	if (!empty($data['trackback_excerpt'])) $trackback_excerpt = $data['trackback_excerpt'];
	if (!empty($data['trackback_charset'])) $trackback_charset = $data['trackback_charset'];
	$is_saveImage = !empty($data['saveImage']) ? 1 : 0;

	if (empty($param['is_conv'])) {
		unset($data['no']);
		unset($data['num']);
		unset($data['prev']);
		unset($data['next']);
		unset($data['target_member']);
		unset($data['trackback']);
		unset($data['pingback']);
		unset($data['ip']);
		unset($data['date_comment']);
		unset($data['name_comment']);
		unset($data['count_comment']);
		unset($data['hit']);
		unset($data['vote']);
		unset($data['hate']);
		unset($data['download']);
		unset($data['date']);
		unset($data['date_last']);
		unset($data['date_expire']);
		unset($data['history_vote']);
		unset($data['history_hit']);
		
		if (empty($mini['member']['level_admin'])) {
			unset($data['ment_advice']);
			unset($data['is_lock']);
			unset($data['admit_file']);
			unset($data['admit_post']);
			unset($param['target_member']);
			unset($data['relate']);
		}
	}
	iss($param['target_member']);
	iss($data['next']);
	iss($data['prev']);
	iss($data['num']);
	iss($data['notice']);
	iss($data['secret']);
	iss($data['pass_encode']);

	if (!is_array($data)) __error('입력된 데이터가 없습니다');
		
		

	//// 게시판 설정
		if (!is_array($board_data)) {
			if ($param['id']) {
				$board_data = getBoard($param['id'], 1);
			}
			else __error('게시판 설정이 없습니다');
		}

	//// 기본 규칙 체크
	if (empty($param['is_conv'])) {
		// 공지사항 체크
			if ($data['notice'] && $data['secret']) {
				__error('공지사항은 비밀글일 수 없습니다');
			}

		// 징계
			if (!empty($mini['log']) && !empty($mini['member']['no']) && empty($mini['member']['level'])) {
				__error('징계상태에서 글을 쓸 수 없습니다');
			}

		// 추가필드 권한 체크
			if (!empty($board_data['field'])) {
				foreach ($board_data['field'] as $key=>$val):
					if (!empty($data['field'][$key]) && !empty($val['is_admin']) && empty($mini['member']['level_admin'])) __error("[{$val['name']}]은 관리자만 입력하실 수 있습니다");
					if (empty($data['field'][$key]) && !empty($val['is_req'])) __error("[{$val['name']}]을 입력해 주세요");
				endforeach;
			}

		// 연속글 체크
			if (!empty($board_data['limit_post']) && empty($mini['member']['level_admin'])) {
				if ($board_data['limit_post'] < 2) $board_data['limit_post'] = 2;

				//+이 쿼리 속도를 확인해 봐야 함
				$tmp_limit_array = array();
				$tmp_limit_array = sql("q:SELECT ip FROM {$board_data['table']} ORDER BY date DESC LIMIT ".($board_data['limit_post'] - 1).", mode:firstFieldArray");
				if (!is_array($tmp_limit_array)) $tmp_limit_array = array();
				if (!count(array_diff($tmp_limit_array, array($mini['ip']))) && !empty($mini['board']['total']['default']) && $mini['board']['total']['default'] >= $board_data['limit_post'] - 1) {
					__error("연속으로 {$board_data['limit_post']}번 이상 글을 작성하실 수 없습니다");
				}
			}
		
		// 도배 방지 체크
			if (empty($mini['memeber']['level_admin']) && !empty($board_data['dobe']) && check($board_data['dobe'], 'type:num') && sql("SELECT COUNT(*) FROM {$board_data['table']} WHERE date >= DATE_ADD('{$mini['date']}', INTERVAL -{$board_data['dobe']} SECOND) and (ip='{$mini['ip']}'".(!empty($mini['log']) ? " or target_member={$mini['member']['no']}" : "").")")) {
				__error("{$board_data['dobe']} 초 이내에 글을 작성하실 수 없습니다");
			}
	}

	//// 입력변수 체크
		if ($param['is_check']) checkField($data, $board_data['table'], $param);

	//// 기본정보 입력
		def($data['date'], $mini['date']);
		def($data['date_comment'], $mini['date']);
		def($data['ip'], $mini['ip']);
	
	//// 회원정보 입력
		$data['target_member'] = $param['target_member'];
		iss($data['name']);
		iss($data['mail']);

		if (empty($param['is_conv'])) {
			if (empty($param['is_conv'])) def($data['target_member'] , (!empty($mini['member']['no']) ? $mini['member']['no'] : ""));
			
			if ($data['target_member']) {
				check($data['target_member'], "type:num, name:회원번호");
				$mdata = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$data['target_member']}");

				// 회원 정보 넣기
					if (is_array($mdata)) {
						parseMember($mdata);
						$data['name'] = $mdata['name'];
					}
					else __error('존재하지 않는 회원 번호 입니다.');
			}

			// 비회원일 때 회원정보 입력 확인
			else {
				check($data['name'], "min:1, max:16, name:이름");
				if (empty($data['pass'])) __error('비밀번호를 입력해 주세요. 글 수정시 필요합니다');
			}
		}

	//// 마이너스 포인트일 때 포인트 부족 확인하기
		if (empty($param['is_conv'])) {
			if (empty($mini['member']['level_admin']) && isset($board_data['point_post']) && $board_data['point_post'] < 0 && !empty($mdata) && $mdata['point'] < abs($board_data['point_post'])) {
				__error("포인트가 부족합니다 [".(abs($board_data['point_post']) - $mdata['point'])." 포인트 부족]");
			}
		}

	//// 글 가공
		checkPost($data, $board_data);

	//// 최근글 구하기
		if (empty($param['is_conv']) || empty($data['num'])) {
			$data_before = sql("SELECT * FROM {$board_data['table']} USE INDEX (num) ORDER BY num LIMIT 1");

			if (is_array($data_before)) {
				if ($data_before['num'] == 1) __error('더이상 글을 쓰실 수 없습니다. (4,294,967,295개 제한)');
				$data['num'] = $data_before['num'];
			}

			// 글번호 구하기
			if (!$param['num']) {		
				if (!empty($data['num']))
					$data['num']--;
				else
					$data['num'] = 4294967295;
			}
			else {
				$data['num'] = $param['num'];
			}
		}

		if (!$data['num']) __error('게시물 num이 올바르지 않습니다.');

	//// 다음글 구하기
		if (empty($param['is_conv']) || !empty($param['is_manage'])) {
			if (is_array($data_before)) {
				// 아랫글의 아랫글에서 마지막 자료를 빼고 아랫글을 넣는다
				unset($tmp);
				$tmp = getStr($data_before['next']);
				if (is_array($tmp) && count($tmp)>=$board_data['list_count_relate']) array_pop($tmp);
				if (is_array($tmp) && count($tmp)>=1) $data['next'] = "[".implode("][", $tmp)."]";
				if (is_array($data_before)) $data['next'] = "[{$data_before['no']}]{$data['next']}";
			}

			// 게시물이 없으면 auto_increment 값을 초기화 한다.
				if (!sql("SELECT COUNT(*) FROM {$board_data['table']} LIMIT 1")) {
					sql("TRUNCATE TABLE {$board_data['table']}");
				}
		}

	//// 쿼리 실행
		if (empty($param['is_conv']) || !empty($param['is_manage'])) {
			sql("INSERT INTO {$board_data['table']} ".query($data, 'insert'));
		}
		else {
			$output_conv = query($data, 'insert_array', getStr(getColumns($board_data['table'])));
		}

		if (empty($param['is_conv']) || !empty($param['is_manage'])) $data['no'] = getLastId($board_data['table'], (!empty($param['is_manage']) ? "(ip='{$data['ip']}' and date='{$data['date']}')" : ""));


	if (empty($param['is_conv']) || !empty($param['is_manage'])) {
		// 다음글들의 이전글을 수정
		$tmp_q = '';
		if ($data['next']) {
			$data_next = sql("q:SELECT * FROM {$board_data['table']} USE INDEX (num) WHERE ".sqlSel($data['next'])." ORDER BY num, mode:array");
			
			if (is_array($data_next))
			foreach ($data_next as $key=>$val):
				unset($tmp);
				$out = array();
				$tmp = getStr($val['prev']);

				// 없을 경우 그냥 추가
				if (!is_array($tmp) || count($tmp) < 1)
					$out[0] = $data['no'];
				
				// 있을 경우 해당 위치에 넣고 하나씩 밀어냄
				else {
					if (count($tmp) - 1 < $key) {
						$out = $tmp;
						$out[count($tmp)] = $data['no'];
					}
					else {
						foreach ($tmp as $key2=>$val2):
							if ($key2 == $key)
								$out[] = $data['no'];
							
							$out[] = $val2;
						endforeach;
					}
				}

				// 많을 경우 마지막을 잘라냄
				if (is_array($out) && count($out)>$board_data['list_count_relate']) array_pop($out);

				// 입력
				sql("UPDATE {$board_data['table']} SET prev='[".implode("][", $out)."]' WHERE no='{$val['no']}'");
			endforeach;
		}

		// 총 게시물 수
		updateTotal($data, $board_data, "add");
		if (!empty($mini['board']) && $board_data['no'] == $mini['board']['no']) $mini['board']['total'] = $board_data['total'];
		sql("UPDATE {$mini['name']['admin']} SET total = '".serialize($board_data['total'])."' WHERE id='{$board_data['id']}'");
	
		// 포인트
		if ($board_data['point_post'] !== 0 && !empty($mini['log']) && (!empty($param['target_member']) || !empty($data['target_member']))) {
			setPoint("
				target: ".(!empty($param['target_member']) ? $param['target_member'] : $data['target_member'])."
				point: {$board_data['point_post']}
				msg: 글 작성
				parent_no: {$board_data['no']}
				data_no: {$data['no']}
			");
		}

		if (empty($param['is_conv'])) {
			//+ 글 알림

			// 트랙백 보내기
			if (!empty($trackback)) {
				sendTrackback($trackback, $trackback_excerpt, $trackback_charset, $data, $board_data, 'post');
			}

			// 이미지 치환
			if (!empty($is_saveImage) && !empty($data['ment'])) {
				$data['ment'] = saveImage($data['no'], $data['ment'], $board_data);
				sql("UPDATE {$board_data['table']} SET ment='{$data['ment']}' WHERE no={$data['no']}");
			}
		}
	}

	// 검색어 등록
	$output_conv_index = addIndex($data, "
		id: {$board_data['no']}
		num: {$data['num']}
		date: {$data['date']}
		target: {$data['target_member']}
		ip: {$data['ip']}
		is_conv: ".(!empty($param['is_conv']) && empty($param['is_manage']) ? "1" : "0")."
	");

	if (!empty($param['is_conv']) && empty($param['is_manage'])) {
		return array(
			'data' => $output_conv,
			'index' => $output_conv_index
		);
	}
	else if (!empty($param['is_manage'])) {
		return array(
			'no' => $data['no'],
			'num' => $data['num']
		);
	}
	else
		return $data['no'];
} // END function


/** 글 수정
 * @class write
 * @param
		$data: 글쓰기 data, 이미 check와 가공이 끝난 데이터여야 함
		$board_data: 게시판 설정 data
		-id: 게시판 아이디. 게시판 설정 data가 없을 때 아이디를 토대로 설정을 로드한다.
		-no: 대상 글번호 지정. (글번호를 지정할 수 있다. 없다면 data에 저장되어 있는 자료를 수정)
		-is_update: 회원정보 업데이트 여부
		-is_ex: 이전정보 로드 여부
		-is_check: 입력변수 체크 여부
  */
function editPost(&$data, $board_data = '', $param = '') {
	global $mini;
	$param = param($param);
	iss($param['id']);
	iss($param['no']);
	iss($data['no']);
	def($param['is_update'], 1);
	def($param['is_ex'], 1);
	def($param['is_check'], 1);

	$trackback = $trackback_excerpt = $trackback_charset = '';	
	if (!empty($data['trackback'])) $trackback = $data['trackback'];
	if (!empty($data['trackback_excerpt'])) $trackback_excerpt = $data['trackback_excerpt'];
	if (!empty($data['trackback_charset'])) $trackback_charset = $data['trackback_charset'];
	$is_saveImage = !empty($data['saveImage']) ? 1 : 0;

	unset($data['pass']); // 패스워드는 먼저 제거
	unset($data['num']);
	unset($data['prev']);
	unset($data['next']);
	unset($data['target_member']);
	unset($data['trackback']);
	unset($data['pingback']);
	unset($data['ip']);
	unset($data['date_comment']);
	unset($data['name_comment']);
	unset($data['count_comment']);
	unset($data['hit']);
	unset($data['vote']);
	unset($data['hate']);
	unset($data['download']);
	unset($data['date']);
	unset($data['date_last']);
	unset($data['date_expire']);
	unset($data['history_vote']);
	unset($data['history_hit']);
	
	if (empty($mini['member']['level_admin'])) {
		unset($data['ment_advice']);
		unset($data['is_lock']);
		unset($data['admit_file']);
		unset($data['admit_post']);
		unset($data['relate']);
	}
	

	//// 게시판 설정
		if (!is_array($board_data)) {
			if ($param['id']) {
				$board_data = getBoard($param['id'], 1);
			}
			else __error('게시판 설정이 없습니다');
		}

	//// 게시물 번호
		$no = $param['no'] ? $param['no'] : $data['no'];
		unset($data['no']);
		check($no, "type:num, name:게시물번호");


	//// 데이터 로드
		if ($param['is_ex']) {
			$data_ex = sql("SELECT * FROM {$board_data['table']} WHERE no={$no}");
			if (!is_array($data_ex)) __error('해당 게시물이 존재하지 않습니다');
		}

	//// 잠긴글
		if (empty($mini['member']['level_admin']) && !empty($data_ex['is_lock'])) {
			__error('게시물이 잠겨 있어 수정, 삭제를 할 수 없습니다');
		}

	//// 회원정보 로드
		if (!empty($data_ex['target_member'])) {
			$mdata = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$data_ex['target_member']}");

			// 회원정보 업데이트 여부
				if ($param['is_update'] && $param['is_ex'] && $data_ex['target_member']) {			
					if (is_array($mdata)) {
						parseMember($mdata);
						$data['name'] = $mdata['name'];
					}

					// 회원탈퇴된 아이디인 경우 회원정보를 지움. 비밀번호는 랜덤 채워넣기
					else {
						$data_ex['target_member'] = $data['target_member'] = 0;
						$data['pass'] = md5();
					}
				}
		}

	//// 권한 체크
		if (!empty($data_ex['target_member'])) {
			if (empty($mini['log']))
				__error('권한이 없습니다. [로그인이 필요합니다]');
			if (empty($mini['member']['level_admin']) && !getPermit("name:edit") && $mdata['no'] != $mini['member']['no'])
				__error('권한이 없습니다. [자신이 쓴 글만 수정할 수 있습니다]');
			if (!empty($mini['member']['level_admin']) && !getPermit("name:edit") && $mdata['no'] != $mini['member']['no'] && $mdata['level_admin'] >= $mini['member']['level_admin'])
				__error('권한이 없습니다. [자신보다 높거나 같은 권한의 관리자가 쓴 글 입니다]');
		}
		else if (empty($mini['member']['level_admin']) && !getPermit("name:edit")) {
			if (empty($_REQUEST['pass_encode']))
				__error('비밀번호가 없습니다');
			if (!empty($mini['log']) && empty($mini['member']['level_admin']))
				__error('권한이 없습니다. [비회원이 쓴글 입니다]');
			if (empty($mini['member']['level_admin']) && $_REQUEST['pass_encode'] != md5("{$data_ex['pass']}|{$mini['ip']}|".session_id()))
				__error('권한이 없습니다. [비밀번호가 일치하지 않습니다]');
		}

	//// 추가필드 권한 체크
		if (!empty($board_data['field'])) {
			foreach ($board_data['field'] as $key=>$val):
				if (!empty($data['field'][$key]) && !empty($val['is_admin']) && empty($mini['member']['level_admin'])) __error("[{$val['name']}]은 관리자만 입력하실 수 있습니다");
				if (empty($data['field'][$key]) && !empty($val['is_req'])) __error("[{$val['name']}]을 입력해 주세요");
			endforeach;
		}

	//// 수정 시간제한
		if (!empty($board_data['limit_edit_post']) && empty($mini['member']['level_admin'])) {
			if (!empty($data_ex) && strtotime($data_ex['date']) + ($board_data['limit_edit_post'] * 60)  < $mini['time'])
				__error("작성 후 {$board_data['limit_edit_post']}분이 지난 글을 수정할 수 없습니다");
		}

	//// 입력정보 체크
		if (empty($data['target_member']) && empty($mini['member']))
			check($data['name'], "min:1, max:16, name:이름");
		
		if (!empty($data['mail']))
			check($data['mail'], "type:mail, name:메일");


	//// 입력변수 체크
		if ($param['is_check']) {
			// 입력변수 체크
				checkField($data, $board_data['table'], $param);

			// 글 가공
				checkPost($data, $board_data);
		}

	//+ 수정 정보 넣는다

	//// 직접 수정이 안되는 정보는 제외시킨다
		unset($data['no']);
		unset($data['num']);
		unset($data['date']);
		unset($data['ip']);
		unset($data['hit']);
		unset($data['vote']);
		unset($data['hate']);
		unset($data['admit_file']);
		unset($data['admit_post']);
		unset($data['target_member']);

	//+ 권한별로 제외하는 필드를 지정한다. (이를테면 target_member 같은거 바꿀 수 없으니깐)

	//// 휴지통
		if (!empty($board_data['use_trash_edit'])) {
			$trash = $trash_tmp = array();
			foreach ($data_ex as $key2=>$val2):
				switch ($key2):
					case 'num':
					case 'category':
					case 'tag':
					case 'target_member':
					case 'name':
					case 'mail':
					case 'title':
					case 'ment':
					case 'ip':
					case 'date':
					case 'trackback':
						$trash[$key2] = $val2;
						break;

					case 'no':
					case 'prev':
					case 'next':
						break;
					
					default:
						$trash_tmp[$key2] = $val2;
				endswitch;
			endforeach;

			// 변경점
				$tmp_data_ex = $data_ex;
				str($tmp_data_ex, 'decode');
				$tmp_diff = arr_diff($tmp_data_ex, $data, 1);
				
			// 정의되지 않은 것들은 제외
				if (!empty($tmp_diff['data_ex']))
				foreach ($tmp_diff['data_ex'] as $key2 => $val2):
					if (!isset($data[$key2])) unset($tmp_diff['data_ex'][$key2]);
				endforeach;

				if (!empty($tmp_diff['data']))
				foreach ($tmp_diff['data'] as $key2 => $val2):
					if (!isset($data_ex[$key2])) unset($tmp_diff['data'][$key2]);
				endforeach;

				if (empty($tmp_diff['data'])) unset($tmp_diff['data']);
				if (empty($tmp_diff['data_ex'])) unset($tmp_diff['data_ex']);

			// 가공
				if (!empty($tmp_diff)) {
					$tmp_diff = serialize($tmp_diff);

					$trash = array_merge($trash, array(
						'target_member_in' => (!empty($mini['log']) ? $mini['member']['no'] : 0),
						'ip_in' => $mini['ip'],
						'date_in' => $mini['date'],
						'num' => $data_ex['no'],
						'id' => $board_data['no'],
						'is_edit' => 1,
						'field' => serialize($trash_tmp),
						'diff' => $tmp_diff
					));

					sql("INSERT INTO {$mini['name']['trash']} ".query($trash, 'insert'));
				}
		}

	//// 쿼리
		sql("UPDATE {$board_data['table']} SET ".query($data, 'update')." WHERE no={$no}");

	//// 정보 재입력
		$data['no'] = $data_ex['no'];

	//// 총 게시물 수 수정
		if ($param['is_ex']) {
			iss($data_ex['category']);
			iss($data_ex['tag']);
			$check = 0;

			if (isset($data['category'])) {
				if ($data['category'] != $data_ex['category'])
					$check = 1;
			}

			if (isset($data['tag'])) {
				if ($data['tag'] != $data_ex['tag'])
					$check = 1;
			}

			if ($check) {
				updateTotal($data_ex, $board_data, 'del');
				updateTotal($data, $board_data, 'add');
				if (!empty($mini['board']) && $board_data['no'] == $mini['board']['no']) $mini['board']['total'] = $board_data['total'];
				sql("UPDATE {$mini['name']['admin']} SET total = '".serialize($board_data['total'])."' WHERE no={$board_data['no']}");
			}
		}

	//// 검색어 수정
		if ($param['is_ex']) {
			if (!empty($data['title']) && $data_ex['title'] != $data['title']) $data_ex['title'] = $data['title'];
			if (!empty($data['ment']) && $data_ex['ment'] != $data['ment']) $data_ex['ment'] = $data['ment'];
			if (!empty($data['name']) && $data_ex['name'] != $data['name']) $data_ex['name'] = $data['name'];
			if (!empty($data['category']) && $data_ex['category'] != $data['category']) $data_ex['category'] = $data['category'];
			if (!empty($data['tag']) && $data_ex['tag'] != $data['tag']) $data_ex['tag'] = $data['tag'];

			delIndex($data_ex['num']);
			addIndex($data_ex, "
				id: {$board_data['no']}
				num: {$data_ex['num']}
				date: {$data_ex['date']}
				ip: {$data_ex['ip']}
				target: {$data_ex['target_member']}
			");
		}

	//// 트랙백 보내기
		if (!empty($trackback)) {
			$result = '';
			$result = sendTrackback($trackback, $trackback_excerpt, $trackback_charset, $data, $board_data, 'post');
			if ($result)
				__error("글수정에 성공했지만 트랙백을 보내지 못했습니다. ({$result})");
		}

	//// 이미지 치환
		if (!empty($is_saveImage) && !empty($data['ment'])) {
			$data['ment'] = saveImage($data_ex['no'], $data['ment'], $board_data);
			sql("UPDATE {$board_data['table']} SET ment='{$data['ment']}' WHERE no={$data_ex['no']}");
		}
} // END function


/** 글 삭제
 * @class write
 * @param
		$no: 게시물 번호, 배열로 복수개 가능
		$board_data: 게시판 설정 data
		-id: 게시판 아이디. 게시판 설정 data가 없을 때 아이디를 토대로 설정을 로드한다.
  */
function delPost($no, $board_data = '', $param = '') {
	global $mini;
	$param = param($param);
	iss($param['id']);

	//// 게시판 설정
		if (!is_array($board_data)) {
			if ($param['id']) {
				$board_data = getBoard($param['id'], 1);
			}
			else __error('게시판 설정이 없습니다');
		}

	//// 게시물 번호
		if (!is_array($no)) {
			$tmp = $no;
			$no = Array();
			$no[0] = $tmp;
		}

	//// 삭제
		foreach ($no as $key=>$val):
			check($val, "type:num, name:게시물번호");

			// 데이터 로드
				$data_ex = sql("SELECT * FROM {$board_data['table']} WHERE no={$val}");
				if (!is_array($data_ex)) __error('해당 게시물이 존재하지 않습니다');

			// 잠긴글
				if (empty($mini['member']['level_admin']) && !empty($data_ex['is_lock'])) {
					__error("[{$data_ex['no']}] 게시물이 잠겨 있어 수정, 삭제를 할 수 없습니다");
				}

			// 회원정보 로드
				if (!empty($data_ex['target_member'])) {
					$mdata = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$data_ex['target_member']}");

					if (is_array($mdata))
						parseMember($mdata);
					else {
						$data_ex['target_member'] = 0;
						$data_ex['pass'] = md5();
					}
				}

			// 권한 체크
				if (!empty($data_ex['target_member'])) {
					if (empty($mini['log']))
						__error("[{$data_ex['no']}] 권한이 없습니다. [로그인이 필요합니다]");
					if (empty($mini['member']['level_admin']) && $mdata['no'] != $mini['member']['no'])
						__error("[{$data_ex['no']}] 권한이 없습니다. [자신이 쓴 글만 수정할 수 있습니다]");
					if (!empty($mdata['level_admin']) && $mdata['no'] != $mini['member']['no'] && $mdata['level_admin'] >= $mini['member']['level_admin'])
						__error("[{$data_ex['no']}] 권한이 없습니다. [자신보다 높거나 같은 권한의 관리자가 쓴 글 입니다]");
				}
				else if (empty($mini['member']['level_admin'])) {
					if (count($no) > 1) __error('비회원의 글은 한번에 한개씩 삭제하실 수 있습니다.');
					else if (empty($mini['member']['level_admin'])) {
						if (empty($_REQUEST['pass_encode'])) {
							__error(array(
								'mode' => 'goto',
								'url' => "pass.php?id={$_REQUEST['id']}&group={$_REQUEST['group']}&url=".url(),
							));
						}
						if (!empty($mini['log']))
							__error("[{$data_ex['no']}] 권한이 없습니다. [비회원이 쓴글 입니다]");
						if ($_REQUEST['pass_encode'] != md5("{$data_ex['pass']}|{$mini['ip']}|".session_id()))
							__error("[{$data_ex['no']}] 권한이 없습니다. [비밀번호가 일치하지 않습니다]");
					}
				}

			// 휴지통
				if (!empty($board_data['use_trash'])) {
					$trash = $trash_tmp = array();
					foreach ($data_ex as $key2=>$val2):
						switch ($key2):
							case 'category':
							case 'tag':
							case 'target_member':
							case 'name':
							case 'mail':
							case 'title':
							case 'ment':
							case 'ip':
							case 'date':
							case 'trackback':
								$trash[$key2] = $val2;
								break;

							case 'no':
							case 'prev':
							case 'next':
								break;
						
							default:
								$trash_tmp[$key2] = $val2;
						endswitch;
					endforeach;

					$trash = array_merge($trash, array(
						'target_member_in' => (!empty($mini['log']) ? $mini['member']['no'] : 0),
						'ip_in' => $mini['ip'],
						'date_in' => $mini['date'],
						'num' => $data_ex['no'],
						'id' => $board_data['no'],
						'field' => serialize($trash_tmp)
					));					

					sql("INSERT INTO {$mini['name']['trash']} ".query($trash, 'insert'));
				}

			// 댓글 제거
				$tmp = sql("q:SELECT no FROM {$board_data['table_cmt']} WHERE target_post={$data_ex['no']}, mode:firstFieldArray");
				if (!empty($tmp) && count($tmp) >= 1) {
					delCmt($tmp, $board_data, "del_post:1", $data_ex);
				}

			// 쿼리
				sql("DELETE FROM {$board_data['table']} WHERE no={$val}");

			// 현재글의 prev에서 현재글을 뺀다
				if ($data_ex['prev']) {
					$data_prev = sql("q:SELECT * FROM {$board_data['table']} USE INDEX (num) WHERE ".sqlSel($data_ex['prev'])." ORDER BY num DESC, mode:array");
					
					if (is_array($data_prev))
					foreach ($data_prev as $key2=>$val2):
						// 빼기
						$val2['next'] = str_replace("[{$data_ex['no']}]", "", $val2['next']);

						// next 연장해주기
						if ($data_ex['next']) {
							$tmp = array();
							$tmp = getStr($data_ex['next']);
							$tmp_key = $board_data['list_count_relate'] - (1 * ($key2+1));
							iss($tmp[$tmp_key]);

							$tmp2 = array();
							$tmp2 = getStr($val2['next']);
							if ($tmp_key >= 0 && $tmp2[count($tmp2)-1] != $tmp[$tmp_key] && $tmp[$tmp_key]) $val2['next'] .= "[".$tmp[$tmp_key]."]";
						}

						// 입력
						sql("UPDATE {$board_data['table']} SET next='{$val2['next']}' WHERE no={$val2['no']}");
					endforeach;
				}

			// 현재글의 next에서 현재글을 뺀다
				if ($data_ex['next']) {
					$data_next = sql("q:SELECT * FROM {$board_data['table']} USE INDEX (num) WHERE ".sqlSel($data_ex['next'])." ORDER BY num, mode:array");
					
					if (is_array($data_next))
					foreach ($data_next as $key2=>$val2):
						// 빼기
						$val2['prev'] = str_replace("[{$data_ex['no']}]", "", $val2['prev']);

						// prev 연장해주기
						if ($data_ex['prev']) {
							$tmp = array();
							$tmp = getStr($data_ex['prev']);
							$tmp_key = $board_data['list_count_relate'] - (1 * ($key2+1));
							iss($tmp[$tmp_key]);

							$tmp2 = array();
							$tmp2 = getStr($val2['prev']);
							if ($tmp_key >= 0 && $tmp2[count($tmp2)-1] != $tmp[$tmp_key] && $tmp[$tmp_key]) $val2['prev'] .= "[".$tmp[$tmp_key]."]";
						}

						// 입력
						sql("UPDATE {$board_data['table']} SET prev='{$val2['prev']}' WHERE no={$val2['no']}");
					endforeach;
				}

			// 총 게시물 수
				updateTotal($data_ex, $board_data, "del");
				if (!empty($mini['board']) && $board_data['no'] == $mini['board']['no']) $mini['board']['total'] = $board_data['total'];
				sql("UPDATE {$mini['name']['admin']} SET total = '".serialize($board_data['total'])."' WHERE no={$board_data['no']}");

			// 포인트
				if ($board_data['point_post'] !== 0 && !empty($mini['log']) && !empty($data_ex['target_member'])) {
					setPoint("
						target: {$data_ex['target_member']}
						point: {$board_data['point_post']}
						msg: 글 삭제
						parent_no: {$board_data['no']}
						data_no: {$data_ex['no']}
						is_del: 1
					");
				}

			//+ 파일 제거

			// 검색어 제거
				delIndex($data_ex['num']);
		endforeach;
} // END function


/** 댓글 등록
 * @class write
 * @param
		$data: 글쓰기 data, 이미 check와 가공이 끝난 데이터여야 함
		$board_data: 게시판 설정 data
		-id: 게시판 아이디. 게시판 설정 data가 없을 때 아이디를 토대로 설정을 로드한다.
		-target_post: 타겟 게시물 지정
		-is_check: 입력변수 체크 여부
		-target_member: 대상회원을 지정할 수 있음
		-target_post: 대상게시물번호
		-target_num: 대상게시물num
		-trackback
		-is_conv: 컨버팅
		-is_manage: 게시물관리
 * @return Array data
 */
function addCmt($data, $board_data = '', $param = '') {
	global $mini;
	$param = param($param);
	
	$output_conv_update_reply = '';
	iss($data_post);
	iss($data_reply);
	iss($param['id']);
	iss($param['target_post']);
	iss($param['target_num']);
	def($param['is_check'], 1);
	iss($data_before);
	iss($param['is_conv']);

	$trackback = $trackback_excerpt = $trackback_charset = '';	
	if (!empty($data['trackback'])) $trackback = $data['trackback'];
	if (!empty($data['trackback_excerpt'])) $trackback_excerpt = $data['trackback_excerpt'];
	if (!empty($data['trackback_charset'])) $trackback_charset = $data['trackback_charset'];
	$is_saveImage = !empty($data['saveImage']) ? 1 : 0;

	if (empty($param['is_conv'])) {
		unset($data['no']);
		unset($data['target_member']);
		unset($data['trackback']);
		unset($data['report']);
		unset($data['ip']);
		unset($data['vote']);
		unset($data['hate']);
		unset($data['download']);
		unset($data['date_last']);
		unset($data['history_vote']);
		unset($data['is_del']);

		if (empty($mini['member']['level_admin'])) {
			unset($data['num']);
			unset($data['parent']);
			unset($data['ment_advice']);
			unset($data['is_lock']);
			unset($data['admit_file']);
			unset($data['admit_post']);
			unset($param['target_member']);
			unset($data['date']);
		}
	}
	iss($data['notice']);
	iss($data['secret']);
	iss($data['pass_encode']);
	iss($data['target_post']);
	iss($data['num']);
	iss($data['reply']);
	iss($data['parent']);
	iss($param['target_member']);

	if (!is_array($data)) __error('입력된 데이터가 없습니다');

	//// 게시판 설정
		if (!is_array($board_data)) {
			if ($param['id']) {
				$board_data = getBoard($param['id'], 1);
			}
			else __error('게시판 설정이 없습니다');
		}

	//// 기본 규칙 체크
		if (empty($param['is_conv'])) {
			// 공지사항 체크
				if ($data['notice'] && $data['secret']) {
					__error('공지사항은 비밀댓글일 수 없습니다');
				}

			// punish
				if (!empty($mini['log']) && !empty($mini['member']['no']) && empty($mini['member']['level'])) {
					__error('징계상태에서 댓글을 쓸 수 없습니다');
				}

			// 추가필드 권한 체크
				if (!empty($board_data['field'])) {
					foreach ($board_data['field'] as $key=>$val):
						if (!empty($data['field'][$key]) && !empty($val['is_admin']) && empty($mini['member']['level_admin'])) __error("[{$val['name']}]은 관리자만 입력하실 수 있습니다");
						if (empty($data['field'][$key]) && !empty($val['is_req'])) __error("[{$val['name']}]을 입력해 주세요");
					endforeach;
				}

			// 코멘트 점수주기 변수 담아놓기
				if (!empty($data['point'])) {
					$point = $data['point'];
					unset($data['point']);

					if (preg_match("/[^0-9]/", $point)) __error('댓글 점수주기는 정수만 입력할 수 있습니다');
				}

			// 연속 댓글 체크
				if (!empty($board_data['limit_comment']) && empty($mini['member']['level_admin'])) {
					if ($board_data['limit_comment'] < 2) $board_data['limit_comment'] = 2;

					//+이 쿼리 속도를 확인해 봐야 함
					$tmp_limit_array = array();
					$tmp_limit_array = sql("q:SELECT ip FROM {$board_data['table_cmt']} ORDER BY date DESC LIMIT ".($board_data['limit_comment'] - 1).", mode:firstFieldArray");
					if (!is_array($tmp_limit_array)) $tmp_limit_array = array();
					if (!count(array_diff($tmp_limit_array, array($mini['ip']))) && sql("SELECT COUNT(*) FROM {$board_data['table_cmt']} LIMIT ".($board_data['limit_comment'] - 1)) > $board_data['limit_comment'] - 1) {
						__error("연속으로 {$board_data['limit_comment']}번 이상 댓글을 작성하실 수 없습니다");
					}
				}
		}

	//// 입력변수 체크
		def($data['target_post'], $param['target_post']);
		check($data['target_post'], "type:num, name:대상게시물번호");

		if ($param['is_check']) {
			checkField($data, $board_data['table_cmt'], $param);
			if (empty($param['is_conv']) && !getPermit("name: comment")) __error('권한이 없습니다');
		}

	//// 대상 게시물 정보 로드
		if ($param['target_post'] && $param['target_num']) {
			$data_post['num'] = $param['target_num'];
		}
		else {
			$data_post = sql("SELECT * FROM {$board_data['table']} WHERE no={$data['target_post']}");
		}

		if (!is_array($data_post))
			__error('대상 게시물이 존재하지 않습니다');

	//// 잠긴글
		if (empty($param['is_conv']) && empty($mini['member']['level_admin']) && !empty($data_post['is_lock'])) {
			__error('게시물이 잠겨 있어 댓글을 작성할 수 없습니다');
		}

	//// 지난글 댓글제한
		if (empty($param['is_conv']) && !empty($board_data['reject_comment']) && empty($mini['member']['level_admin']) && empty($data_post['notice'])) {
			if ($mini['time'] - strtotime($data_post['date']) > $board_data['reject_comment'] * 86400) {
				__error("{$board_data['reject_comment']}일이 지난 글에는 댓글을 작성할 수 없습니다");
			}
		}

	//// 번호
		if (empty($param['is_conv']) || empty($data['num'])) {
			$data['num'] = sql("SELECT MAX(num) FROM {$board_data['table_cmt']} WHERE target_post={$data['target_post']}");
			if (empty($data['num'])) $data['num'] = 0;
			$data['num']++;
		}

	
	//// 답변 댓글 설정
		if (empty($param['is_conv']) && $data['reply'] && empty($data['parent'])) {
			check($data['reply'], "type:num, name:대상댓글번호");
			$data_reply = sql("SELECT * FROM {$board_data['table_cmt']} WHERE no={$data['reply']}");
			if (!is_array($data_reply)) __error('대상댓글이 존재하지 않습니다');
			if (!empty($data_reply['is_del'])) __error('삭제된 댓글에는 댓글을 달 수 없습니다');
			if (!empty($data_reply['notice'])) __error('공지댓글에는 댓글을 달 수 없습니다');

			$data['num'] = $data_reply['num'];
			$data['parent'] = $data_reply['parent']."[{$data_reply['no']}]";
			$data['reply'] = sql("SELECT MAX(reply) FROM {$board_data['table_cmt']} WHERE target_post={$data['target_post']} and parent LIKE '%[{$data_reply['no']}]%'");
			def($data['reply'], $data_reply['reply']);
			$data['reply']++;

			// 비밀댓글의 답변은 모두 비밀
				if (!empty($data_reply['secret'])) $data['secret'] = 1;
		}

	
	//// 기본정보 입력
		def($data['date'], $mini['date']);
		def($data['ip'], $mini['ip']);
	
	//// 회원정보 입력
		if (!empty($param['target_member'])) $data['target_member'] = $param['target_member'];

		if (empty($param['is_conv'])) {
			def($data['target_member'] ,(!empty($mini['member']['no']) ? $mini['member']['no'] : ""));
		
			if ($data['target_member']) {
				check($data['target_member'], "type:num, name:회원번호");
				$mdata = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$data['target_member']}");

				// 회원 정보 넣기
					if (is_array($mdata)) {
						parseMember($mdata);
						$data['name'] = $mdata['name'];
					}
					else __error('존재하지 않는 회원 번호 입니다.');
			}

			// 비회원일 때 회원정보 입력 확인
			else {
				iss($data['name']);
				iss($data['mail']);
				check($data['name'], "min:1, max:16, name:이름");
				if (empty($data['pass'])) __error('비밀번호를 입력해 주세요. 글 수정시 필요합니다');
			}

			// 마이너스 포인트일 때 포인트 부족 확인하기
				if (empty($mini['member']['level_admin']) && isset($board_data['point_comment']) && $board_data['point_comment'] < 0 && !empty($mdata) && $mdata['point'] < abs($board_data['point_comment'])) {
					__error("포인트가 부족합니다 [".(abs($board_data['point_comment']) - $mdata['point'])." 포인트 부족]");
				}

			// 댓글이 없으면 auto_increment 값을 초기화 한다.
				if (!sql("SELECT COUNT(*) FROM {$board_data['table_cmt']} LIMIT 1")) {
					sql("TRUNCATE TABLE {$board_data['table_cmt']}");
				}
		}

	//// trackback
		if (!empty($param['trackback'])) {
			$data['trackback'] = $param['trackback'];
		}

	//// 댓글 가공
		checkPost($data, $board_data, 'cmt');

	//// 쿼리 실행
		if (empty($param['is_conv']) || !empty($param['is_manage'])) {
			sql("INSERT INTO {$board_data['table_cmt']} ".query($data, 'insert'));
			$data['no'] = getLastId($board_data['table_cmt'], "ip='{$data['ip']}' and date='{$data['date']}'");
		}
		else {
			$output_conv = query($data, 'insert_array', getStr(getColumns($board_data['table_cmt'])));
		}		
	
	//// 답변 댓글 밀기(반업데이트)
		if (empty($param['is_conv']) && $data['reply']) {
			sql("UPDATE {$board_data['table_cmt']} SET reply=reply+1 WHERE target_post={$data['target_post']} and reply >= {$data['reply']} and no != {$data['no']}");
		}

	if (empty($param['is_conv']) || !empty($param['is_manage'])) {
		// 포인트
			if (
				$board_data['point_comment'] !== 0 &&
				!empty($mini['log']) &&
				(!empty($param['target_member']) || !empty($data['target_member'])) &&
				(empty($board_data['use_cmt_point_one']) || sql("SELECT COUNT(*) FROM {$board_data['table_cmt']} WHERE target_post={$data_post['no']} and target_member=".(!empty($param['target_member']) ? $param['target_member'] : $data['target_member'])) == 1)
			) {
				setPoint("
					target: ".(!empty($param['target_member']) ? $param['target_member'] : $data['target_member'])."
					point: {$board_data['point_comment']}
					msg: 댓글 작성
					parent_no: {$board_data['no']}
					data_no: {$data['no']}
				");
			}

		if (empty($param['is_conv'])) {
			// 게시물 댓글 개수 증가
				$tmp_q = !empty($data['trackback']) ? "count_trackback=count_trackback+1" : "count_comment=count_comment+1";
				$tmp_q .= ",date_comment = '{$mini['date']}'";
				$tmp_q .= ",name_comment = '{$data['name']}'";
				if (!empty($point)) $tmp_q .= ",point=".(($data_post['point'] * $data_post['point_count'] + $point) / ($data_post['point_count']+1)).",point_count=point_count+1";

				sql("UPDATE {$board_data['table']} SET {$tmp_q} WHERE no={$data['target_post']}");

			// 댓글 알림
				if (empty($param['is_conv'])) {
					if ((empty($data_reply) && !empty($data_post['memo']) && !empty($data_post['target_member'])) || (!empty($data_reply['memo']) && !empty($data_reply['target_member']))) {
						include "{$mini['dir']}skin/template/memo.cmt_notice.tpl.php";
						if (!function_exists('skinConv')) include "{$mini['dir']}_inc.skinmake.php";

						if (!empty($tpl)) {
							$tmp = !empty($tpl[$mini['site']['template']['cmt_notice']]) ? $tpl[$mini['site']['template']['cmt_notice']] : current($tpl);

							unset($mini['skin']);
							$mini['skin'] = '';
							if (!empty($mini['site'])) $mini['skin']['site'] = &$mini['site'];
							if (!empty($mini['board'])) $mini['skin']['board'] = &$mini['board'];
							if (!empty($data)) {
								$mini['skin']['data'] = &$data;
								$mini['skin']['data']['url_view'] = "{$mini['dir']}mini.php?id={$board_data['id']}&amp;no={$data['target_post']}&amp;cNo={$data['no']}";
							}
							if (!empty($data_post)) $mini['skin']['data_post'] = parsePost($data_post, 'list', 1);
							if (!empty($data_reply)) $mini['skin']['data_reply'] = &$data_reply;
							$mini['skin']['date'] = $mini['date'];
							$mini['skin']['pdir'] = $mini['pdir'];
							$mini['skin']['target'] = (!empty($data_reply) ? $data_reply['target_member'] : $data_post['target_member']);
							$mini['skin']['name'] = (!empty($data_reply) ? $data_reply['name'] : $data_post['name']);

							if (empty($mdata) || $mini['skin']['target'] != $mdata['no']) {
								$result = sendMemo(array(
									'skip_filter' => 1,
									'target_member' => (!empty($data_reply) ? $data_reply['target_member'] : $data_post['target_member']),
									'ment' => skinConv((!empty($data_reply) ? $tmp['cmt'] : $tmp['post']), 'str')
								), $mini['skin']['target'], '', '');
							}
						}
						else {
							__error('인증메일을 발송할 수 없습니다. 관리자에게 문의해 주세요');
						}
					}
				}

			// 트랙백 댓글 주소 설정
				if (!empty($data_reply) && !empty($data_reply['trackback'])) {
					$tmp_data = getSocket("
						url: {$data_reply['trackback']}
						skip_header: 1
					");

					if (!empty($tmp_data) && strpos($tmp_data, "<rdf:RDF") !== false) {
						preg_match("/\<rdf\:Description.+trackback\:ping\=\"([^\"]+)\" \/\>/is", $tmp_data, $mat);

						if (!empty($mat[1])) {
							$trackback = $mat[1];
						}
					}
				}

			// 트랙백 보내기
				if (!empty($trackback)) {
					sendTrackback($trackback, $trackback_excerpt, $trackback_charset, $data, $board_data, 'cmt');
				}

			// 이미지 치환
				if (!empty($is_saveImage) && !empty($data['ment'])) {
					$data['ment'] = saveImage($data['no'], $data['ment'], $board_data, 'cmt');
					sql("UPDATE {$board_data['table_cmt']} SET ment='{$data['ment']}' WHERE no={$data['no']}");
				}
		}
	}

	//// 검색어 등록
		$output_conv_index = addIndex($data, "
			id: {$board_data['no']}
			num: {$data_post['num']}
			cmt_no: {$data['no']}
			date: {$data['date']}
			target: {$data['target_member']}
			ip: {$data['ip']}
			is_conv: ".(!empty($param['is_conv']) && empty($param['is_manage']) ? "1" : "0")."
		");

	if (!empty($param['is_conv']) && empty($param['is_manage'])) {
		return array(
			'data' => $output_conv,
			'index' => $output_conv_index
		);
	}
	else {
		return $data;
	}
} // END function


/** 댓글 수정
 * @class write
 * @param
		$data: 댓글 data, 이미 check와 가공이 끝난 데이터여야 함
		$board_data: 게시판 설정 data
		-id: 게시판 아이디. 게시판 설정 data가 없을 때 아이디를 토대로 설정을 로드한다.
		-no: 대상 댓글번호 지정. (댓글 번호를 지정할 수 있다. 없다면 data에 저장되어 있는 자료를 수정)
		-is_update: 회원정보 업데이트 여부
		-is_ex: 이전정보 로드 여부
		-is_check: 입력변수 체크 여부
  */
function editCmt(&$data, $board_data = '', $param = '') {
	global $mini;
	$param = param($param);
	iss($param['id']);
	iss($param['no']);
	iss($data['no']);
	def($param['is_update'], 1);
	def($param['is_ex'], 1);
	def($param['is_check'], 1);

	$trackback = $trackback_excerpt = $trackback_charset = '';	
	if (!empty($data['trackback'])) $trackback = $data['trackback'];
	if (!empty($data['trackback_excerpt'])) $trackback_excerpt = $data['trackback_excerpt'];
	if (!empty($data['trackback_charset'])) $trackback_charset = $data['trackback_charset'];
	$is_saveImage = !empty($data['saveImage']) ? 1 : 0;

	unset($data['pass']);
	unset($data['target_member']);
	unset($data['report']);
	unset($data['ip']);
	unset($data['vote']);
	unset($data['hate']);
	unset($data['download']);
	unset($data['date']);
	unset($data['date_last']);
	unset($data['parent']);
	unset($data['history_vote']);
	unset($data['point_count']);
	unset($data['point_sum']);

	if (empty($mini['member']['level_admin'])) {
		unset($data['is_del']);
		unset($data['trackback']);
		unset($data['ment_advice']);
		unset($data['is_lock']);
		unset($data['admit_file']);
		unset($data['admit_post']);
	}


	//// 게시판 설정
		if (!is_array($board_data)) {
			if ($param['id']) {
				$board_data = getBoard($param['id'], 1);
			}
			else __error('게시판 설정이 없습니다');
		}

	//// 게시물 번호
		$no = $param['no'] ? $param['no'] : $data['no'];
		unset($data['no']);
		check($no, "type:num, name:댓글번호");

	//// 데이터 로드
		if ($param['is_ex']) {
			$data_ex = sql("SELECT * FROM {$board_data['table_cmt']} WHERE no={$no}");
			if (!is_array($data_ex)) __error('해당 댓글이 존재하지 않습니다');
		}

	//// 잠긴댓글
		if (empty($mini['member']['level_admin']) && !empty($data_ex['is_lock'])) {
			__error('댓글이 잠겨 있어 수정, 삭제를 할 수 없습니다');
		}

	//// 회원정보 로드
		if (!empty($data_ex['target_member'])) {
			$mdata = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$data_ex['target_member']}");

			// 회원정보 업데이트 여부
				if ($param['is_update'] && $param['is_ex'] && $data_ex['target_member']) {
					if (is_array($mdata)) {
						parseMember($mdata);
						$data['name'] = $mdata['name'];
					}

					// 회원탈퇴된 아이디인 경우 회원정보를 지움. 비밀번호는 랜덤 채워넣기
					else {
						$data_ex['target_member'] = $data['target_member'] = 0;
						$data['pass'] = md5();
					}
				}
		}

	//// 권한 체크
		if (empty($data_ex['trackback'])) {
		if (!empty($data_ex['target_member'])) {
			if (empty($mini['log']))
				__error('권한이 없습니다. [로그인이 필요합니다]');
			if (empty($mini['member']['level_admin']) && $mdata['no'] != $mini['member']['no'])
				__error('권한이 없습니다. [자신이 쓴 댓글만 수정할 수 있습니다]');
			if (!empty($mini['member']['level_admin']) && $mdata['no'] != $mini['member']['no'] && $mdata['level_admin'] >= $mini['member']['level_admin'])
				__error('권한이 없습니다. [자신보다 높거나 같은 권한의 관리자가 쓴 댓글 입니다]');
		}
		else if (empty($mini['member']['level_admin'])) {
			if (empty($data['pass_encode']))
				__error('비밀번호가 없습니다');
			if (!empty($mini['log']) && empty($mini['member']['level_admin']))
				__error('권한이 없습니다. [비회원이 쓴댓글 입니다]');
			if (empty($mini['member']['level_admin']) && $data['pass_encode'] != md5("{$data_ex['pass']}|{$mini['ip']}|".session_id()))
				__error("권한이 없습니다. [비밀번호가 일치하지 않습니다]");
		}
		}

	//// 추가필드 권한 체크
		if (!empty($board_data['field'])) {
			foreach ($board_data['field'] as $key=>$val):
				if (!empty($data['field'][$key]) && !empty($val['is_admin']) && empty($mini['member']['level_admin'])) __error("[{$val['name']}]은 관리자만 입력하실 수 있습니다");
				if (empty($data['field'][$key]) && !empty($val['is_req'])) __error("[{$val['name']}]을 입력해 주세요");
			endforeach;
		}

	//// 수정 시간제한
		if (!empty($board_data['limit_edit_comment']) && empty($mini['member']['level_admin'])) {
			if (!empty($data_ex) && strtotime($data_ex['date']) + ($board_data['limit_edit_comment'] * 60)  < $mini['time'])
				__error("작성 후 {$board_data['limit_edit_comment']}분이 지난 댓글을 수정할 수 없습니다");
		}

	//// 대상 게시물 정보 로드
		check($data['target_post'], "type:num, name:대상게시물번호");
		$data_post = sql("SELECT * FROM {$board_data['table']} WHERE no={$data['target_post']}");

	//// 답변 댓글일 때 원본 댓글 정보 로드
		if (!empty($data_ex['parent'])) {
			$data_reply = sql("SELECT * FROM {$board_data['table_cmt']} WHERE no=".(end(getStr($data_ex['parent']))));
			if (is_array($data_reply)) {
				// 원본 댓글이 비밀일 때 답변들도 비밀 유지
					if (!empty($data_reply['secret'])) $data['secret'] = 1;
				// 공지댓글에 댓글 금지
					if (!empty($data_reply['notice'])) __error('공지댓글에는 댓글을 달 수 없습니다');
			}
		}
		
	//// 입력정보 체크
		if (isset($data['name']))
			check($data['name'], "min:1, max:16, name:이름");

		if (isset($data['mail']))
			check($data['mail'], "type:mail, name:메일, is_not:1");

	//// 입력변수 체크
		if ($param['is_check']) {
			// 입력변수 체크
				checkField($data, $board_data['table_cmt'], $param);

			// 글 가공
				checkPost($data, $board_data, 'cmt');
		}


	//// 직접 수정이 안되는 정보는 제외시킨다
		unset($data['no']);
		unset($data['reply']);
		unset($data['pass']);
		unset($data['vote']);
		unset($data['hate']);
		unset($data['ip']);
		unset($data['date']);
		unset($data['target_member']);

	//// trackback
		if (!empty($param['trackback'])) {
			$data['trackback'] = $param['trackback'];
		}
	

	//+ 권한별로 제외하는 필드를 지정한다. (이를테면 target_member 같은거 바꿀 수 없으니깐)


	//// 쿼리
		sql("UPDATE {$board_data['table_cmt']} SET ".query($data, 'update')." WHERE no={$no}");
		$data['no'] = $data_ex['no'];


	//// 검색어 수정
		if ($param['is_ex']) {
			if (!empty($data['ment']) && $data_ex['ment'] != $data['ment']) $data_ex['ment'] = $data['ment'];
			if (!empty($data['name']) && $data_ex['name'] != $data['name']) $data_ex['name'] = $data['name'];
			if (!empty($data['tag']) && $data_ex['tag'] != $data['tag']) $data_ex['tag'] = $data['tag'];

			delIndex($data_post['num'], $data_ex['no']);
			addIndex($data_ex, "
				id: {$board_data['no']}
				num: {$data_post['num']}
				cmt_no: {$data_ex['no']}
				date: {$data_ex['date']}
				ip: {$data_ex['ip']}
			");
		}

	//// 트랙백 댓글 주소 설정
		if (!empty($data_reply) && !empty($data_reply['trackback'])) {
			$tmp_data = getSocket("
				url: {$data_reply['trackback']}
				skip_header: 1
			");

			if (!empty($tmp_data) && strpos($tmp_data, "<rdf:RDF") !== false) {
				preg_match("/\<rdf\:Description.+trackback\:ping\=\"([^\"]+)\" \/\>/is", $tmp_data, $mat);

				if (!empty($mat[1])) {
					$trackback = $mat[1];
				}
			}
		}

	//// 트랙백 보내기
		if (!empty($trackback)) {
			$result = '';
			$result = sendTrackback($trackback, $trackback_excerpt, $trackback_charset, $data, $board_data, 'cmt');
			if ($result)
				__error("댓글 수정에 성공했지만 트랙백을 보내지 못했습니다. ({$result})");
		}

	//// 이미지 치환
		if (!empty($is_saveImage) && !empty($data['ment'])) {
			$data['ment'] = saveImage($data_ex['no'], $data['ment'], $board_data, 'cmt');
			sql("UPDATE {$board_data['table_cmt']} SET ment='{$data['ment']}' WHERE no={$data_ex['no']}");
		}
} // END function


/** 댓글 삭제
 * @class write
 * @param
		$no: 댓글 번호, 배열로 복수개 가능
		$board_data: 게시판 설정 data
		-id: 게시판 아이디. 게시판 설정 data가 없을 때 아이디를 토대로 설정을 로드한다.
		-del_post: 권한에 상관없이 무조건 지워진다
  */
function delCmt($no, $board_data = '', $param = '', $data_post = '') {
	global $mini;
	$param = param($param);
	iss($param['id']);
	$output = array();
	$output['success'] = $output['fail'] = 0;
	$output['msg'] = '';


	//// 게시판 설정
		if (!is_array($board_data)) {
			if ($param['id']) {
				$board_data = getBoard($param['id'], 1);
			}
			else __error('게시판 설정이 없습니다');
		}

	//// 댓글 번호
		if (!is_array($no)) {
			$tmp = $no;
			$no = Array();
			$no[0] = $tmp;
		}

	//// 삭제
		foreach ($no as $key=>$val):
			$result = 1;
			check($val, "type:num, name:댓글번호");

			// 데이터 로드
				$data_ex = sql("SELECT * FROM {$board_data['table_cmt']} WHERE no={$val}");
				if (!is_array($data_ex))
					continue;
				if ($data_ex['is_del'] && empty($mini['member']['level_admin']))
					continue;

			// 잠긴댓글
				if (empty($mini['member']['level_admin']) && !empty($data_ex['is_lock'])) {
					__error('댓글이 잠겨 있어 수정, 삭제를 할 수 없습니다');
				}

			// 회원정보 로드
				if (!empty($data_ex['target_member'])) {
					$mdata = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$data_ex['target_member']}");

					if (is_array($mdata))
						parseMember($mdata);
					else {
						$data_ex['target_member'] = 0;
						$data_ex['pass'] = md5();
					}
				}

			// 권한 체크
				if (!empty($data_ex['target_member']) && empty($param['del_post'])) {
					if (empty($mini['log']))
						__error("[{$data_ex['no']}] 권한이 없습니다. [로그인이 필요합니다]");
					if (empty($mini['member']['level_admin']) && empty($mdata['level_admin']) && $mdata['no'] != $mini['member']['no'])
						__error("[{$data_ex['no']}] 권한이 없습니다. [자신이 쓴 댓글만 수정할 수 있습니다]");
					if (!empty($mdata['level_admin']) && $mdata['no'] != $mini['member']['no'] && $mdata['level_admin'] >= $mini['member']['level_admin'])
						__error("[{$data_ex['no']}] 권한이 없습니다. [자신보다 높거나 같은 권한의 관리자가 쓴 댓글 입니다]");
				}
				else if (empty($param['del_post'])) {
					if (count($no) > 1 && empty($mini['member']['level_admin'])) __error('비회원의 댓글는 한번에 한개씩 삭제하실 수 있습니다.');
					if (empty($mini['member']['level_admin'])) {
						if (empty($_REQUEST['pass_encode'])) {
							__error(array(
								'mode' => 'goto',
								'url' => "pass.php?id={$_REQUEST['id']}&group={$_REQUEST['group']}&url=".url()
							));
						}
						if (!empty($mini['log']))
							__error("[{$data_ex['no']}] 권한이 없습니다. [비회원이 쓴댓글 입니다]");
						if ($_REQUEST['pass_encode'] != md5("{$data_ex['pass']}|{$mini['ip']}|".session_id()))
							__error("[{$data_ex['no']}] 권한이 없습니다. [비밀번호가 일치하지 않습니다]");
					}
				}

			// 대상 게시물 로드
				if (empty($data_post))
					$data_post = sql("SELECT * FROM {$board_data['table']} WHERE no={$data_ex['target_post']}");

			// 휴지통
				if (!empty($board_data['use_trash'])) {
					$trash = $trash_tmp = array();
					foreach ($data_ex as $key2=>$val2):
						switch ($key2):
							case 'category':
							case 'tag':
							case 'target_member':
							case 'target_post':
							case 'name':
							case 'mail':
							case 'title':
							case 'ment':
							case 'ip':
							case 'date':
							case 'trackback':
								$trash[$key2] = $val2;
								break;
							
							case 'no':
							case 'prev':
							case 'next':
								break;

							default:
								$trash_tmp[$key2] = $val2;
						endswitch;
					endforeach;

					// 댓글 자식 구하기
					$tmp = sql("q:SELECT no FROM {$mini['board']['table_cmt']} WHERE target_post={$data_ex['target_post']} and num={$data_ex['num']} and reply > {$data_ex['reply']} ORDER BY reply, mode:firstFieldArray");
					$tmp_child = is_array($tmp) ? "[".implode("][", $tmp)."]" : "";

					$trash = array_merge($trash, array(
						'target_member_in' => (!empty($mini['log']) ? $mini['member']['no'] : 0),
						'ip_in' => $mini['ip'],
						'date_in' => $mini['date'],
						'num' => $data_ex['no'],
						'id' => $board_data['no'],
						'field' => serialize($trash_tmp),
						'child' => $tmp_child
					));					

					sql("INSERT INTO {$mini['name']['trash']} ".query($trash, 'insert'));
				}

			// 자식 댓글이 있을 경우 삭제 댓글 처리함
				$count_reply = sql("q:SELECT no FROM {$board_data['table_cmt']} WHERE target_post={$data_ex['target_post']} and parent LIKE '%[{$data_ex['no']}]%', mode:firstFieldArray");

				if (!empty($count_reply) && empty($mini['member']['level_admin']) && empty($param['del_post'])) {
					$name = !empty($mini['log']) ? $mini['member']['name'] : $data_ex['name'];
					sql("UPDATE {$board_data['table_cmt']} SET ment='{$name}에 의해 삭제 되었습니다. [".date("Y/m/d H:i:s")."]', field='', is_del=1 WHERE no={$data_ex['no']}");
				}

			// 삭제 처리
				else {
					// 쿼리
						sql("DELETE FROM {$board_data['table_cmt']} WHERE no={$val}");

					// 답변 댓글 당기기
						sql("UPDATE {$board_data['table_cmt']} SET reply=reply-1 WHERE target_post={$data_ex['target_post']} and reply > {$data_ex['reply']} and reply > 0");

					// 답변 댓글 모두 제거
						if (!empty($count_reply)) {
							delCmt($count_reply, $board_data);
						}
				}

			// 검색어 제거
				delIndex($data_post['num'], $data_ex['no']);
		
			// 게시물의 댓글 수 빼기
				if (empty($data_ex['is_del']))
					sql("UPDATE {$board_data['table']} SET ".(!empty($data_ex['trackback']) ? "count_trackback=count_trackback-1" : 
					"count_comment=count_comment-1")." WHERE no={$data_ex['target_post']}");

			// 포인트
				if (
					$board_data['point_comment'] !== 0 &&
					!empty($mini['log']) &&
					!empty($data_ex['target_member']) &&
					(empty($board_data['use_cmt_point_one']) || !sql("SELECT COUNT(*) FROM {$board_data['table_cmt']} WHERE target_post={$data_post['no']} and target_member={$data_ex['target_member']}"))
				) {
					setPoint("
						target: {$data_ex['target_member']}
						point: {$board_data['point_comment']}
						msg: 댓글 삭제
						parent_no: {$board_data['no']}
						data_no: {$data_ex['no']}
						is_del: 1
					");
				}
		endforeach;
} // END function


/** 글 가공
 * @class write
 * @param
		$data: 글 데이타
		$board_data: 게시판 설정 배열
		$mode: [|cmt]
 * @return 
 */
function checkPost(&$data, $board_data, $mode = '') {
	global $mini;

	iss($data['tag']);

	//// 카테고리 가공
	if (!$mode) {
		iss($data['category']);
		if (is_array($data['category']) && count($data['tag']) > 0) {
			$data['category'] = array_unique($data['category']);
			$data['category'] = "[".implode("][", $data['category'])."]";
		}
		else if (!empty($mini['board']['use_category'])) {
			__error('한개 이상의 카테고리를 선택하셔야 합니다');
		} 		
		else {
			if ($_REQUEST['mode'] != 'modify') $data['category'] = "[1]";
		}

		// 관리자 카테고리 확인
		if (empty($mini['member']['level_admin']) && !empty($data['category']) && !empty($board_data['admin_category']) && count(array_intersect(getStr($data['category']), getStr($board_data['admin_category'])))) {
			__error('해당 카테고리는 관리자만 선택할 수 있습니다');
		}
	}

	//// 태그 가공
		if (is_array($data['tag']) && count($data['tag']) > 0) {
			$data['tag'] = array_unique($data['tag']);
			$data['tag'] = "[".implode("][", $data['tag'])."]";
		}
		else {
			$tmp_output = array();
			$tmp = explode(",", $data['tag']);
			if (is_array($tmp) && count($tmp) > 0) {
				foreach ($tmp as $key=>$val):
					$val = trim($val);
					$tmp_output[] = $val;
				endforeach;

				$data['tag'] = (!empty($tmp_output) && count($tmp_output) > 0) ? "[".implode("][", $tmp_output)."]" : "";
			}
		}
	
	//+데이터 가공
} // END function


/** 입력변수 체크
 * @class write
 * @param
		$data: 게시물 데이타
		$table: 테이블 명
 * @return 
 */
function checkField(&$data, $table, $param = '') {
	global $mini;
	$param = param($param);

	// DB 컬럼 로드
		iss($col);
		$col = getColumns($table);

	// 체크박스로 받아오는 것들 초기화
		iss($data['notice']);
		iss($data['secret']);
		iss($data['alert']);
		iss($data['robot']);
		iss($data['memo']);
		iss($data['autobr']);
		iss($data['popup']);
		iss($data['issue']);

	// 입력변수 체크
		foreach ($data as $key=>$val):
			switch ($key):
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
					else if ($key != 'pass') {
						unset($data[$key]);
					}
					break;


				// 추가필드
				case 'field':
					if (is_array($val)) {
						str($data[$key], 'encode');
						$data[$key] = serialize($data[$key]);
					}
					else if (!empty($val)) {
						__error('추가필드 형식이 올바르지 않습니다. field[필드명] 형태로 지정하셔야 합니다');
					}
					break;

				// 링크
				case 'link':
					if (is_array($val)) {
						if (empty($param['is_conv'])) {
							foreach ($val as $key2=>$val2):
								$data[$key][$key2] = trim($data[$key][$key2]);
								check($data[$key][$key2], 'type:homepage, name:링크, is_not:1');
							endforeach;
						}

						str($data[$key], 'encode');
						$data[$key] = serialize($data[$key]);
					}
					else if (!empty($val)) {
						__error('링크 형식이 올바르지 않습니다. link[숫자] 형태로 지정하셔야 합니다');
					}
					break;

				// 공지사항
				case 'notice':
					if (!empty($val) && !getPermit("name:notice"))
						__error('공지사항을 쓸 권한이 없습니다');
					break;

				// 이름
				case 'name':
					if (empty($param['trackback']) && empty($param['is_conv']))
						check($val, "min:1, max:16, name:이름");
					break;

				// 메일
				case 'mail':
					if (empty($param['is_conv']))
					check($val, "type:mail, name:메일, is_not:1");
					break;

				// 제목 필터
				case 'title':
					if (empty($val) || (strCut($val) < 3 && empty($mini['member']['level_admin']))) __error('제목을 3bytes 이상 입력해주세요');
					filter($data[$key], 'encode', 0);
					break;

				// 내용 필터
				case 'ment':
					if (empty($val) || (strCut($val) < 4 && empty($mini['member']['level_admin']))) __error('내용을 4bytes 이상 입력해주세요');
					filter($data[$key], 'encode');
					break;

				// 공지사항 날짜
				case 'date_notice':
				case 'date_popup':
				case 'date_issue':
					if (!empty($val)) {
						$tmp_name = str_replace('date_', '', $key);
						if ($mini['time'] >= strtotime($val)) __error('유효시간이 과거입니다');
						if (empty($data[$tmp_name])) $data[$key] = '';
					}
					break;

				// 관련글
				case 'relate':
					if (!empty($val)) {
						$data[$key] = preg_replace("/[^0-9\,]+/is", "", $val);
					}
					break;
			
				default:							
					// 존재하지 않는 필드일 때 빼기
					if (!inStr($key, $col)) {
						unset($data[$key]);
					}

					else {
						// 값 처리하기
						if (is_array($val)) {
							foreach ($val as $key2=>$val2):
								str($val2, 'encode');
							endforeach;
						}
						else {
							str($data[$key], 'encode');
						}
					}
			endswitch;
		endforeach;

	// 스킵한 것 지우기
		if (isset($data['pass_encode'])) unset($data['pass_encode']);
} // END function


/** 검색어 추가
 * @class write
 * @param
		$data: 대상 String 데이터
		$mode: 모드
		-num: 대상 게시물
		-cmt_no: 댓글 번호
		-id: 대상 아이디
		-date: 날짜
		-target: 대상 회원번호
		-ip: 대상 IP
 * @return 
 */
function addIndex($data, $param = '') {
	global $mini;
	$param = param($param);

	iss($param['num']);
	iss($param['cmt_no']);
	iss($param['id']);
	iss($param['date']);
	iss($param['target']);
	iss($param['ip']);
	iss($query);

	$arr = $param['cmt_no'] ? array('ment', 'tag', 'name') : array('ment', 'title', 'tag', 'category', 'name');

	foreach ($arr as $val):
		$tmp = array();
		if ($val == 'name') {
			$tmp = $data[$val];
		}
		else {
			$tmp = ($val == 'category' || $val == 'tag') ? getStr($data[$val]) : getIndex($data[$val]);
		}

		if (is_array($tmp) && count($tmp) > 0) {
			foreach ($tmp as $val2):
				$query .= ",('{$val}', '{$param['num']}', '{$param['cmt_no']}', '{$param['id']}', '{$param['ip']}', '{$param['date']}', '{$val2}', '{$param['target']}')";
			endforeach;
		}
		else if (!empty($tmp)) {
			$query .= ",('{$val}', '{$param['num']}', '{$param['cmt_no']}', '{$param['id']}', '{$param['ip']}', '{$param['date']}', '{$tmp}', '{$param['target']}')";
		}
	endforeach;

	if (!empty($param['is_conv'])) {
		return array(
			'keys' => '(mode, num, cmt_no, id, ip, date, ment, target_member)',
			'values' => substr($query, 1)
		);
	}
	else {
		sql("INSERT INTO {$mini['name']['search']} (mode, num, cmt_no, id, ip, date, ment, target_member) VALUES ".substr($query, 1));
	}
} // END function


/** 검색어 제거
 * @class write
 * @param
		$num: 대상 넘버
		$cmt_no: 댓글 넘버
 * @return 
 */
function delIndex($num, $cmt_no = '') {
	global $mini;
	sql("DELETE FROM {$mini['name']['search']} WHERE num={$num}".($cmt_no ? " and cmt_no={$cmt_no}": ""));
} // END function


/** 총 게시물 수 증감
 * @class write
 * @param
		$data: category, tag가 있는 게시물 배열
		$board_data: 게시판 설정 배열
		$mode: 모드 [add|del]
  */
function updateTotal($data, &$board_data, $mode = 'add') {
	global $mini;

	iss($data['category']);
	iss($data['tag']);

	// 기본
	if ($mode == 'add')
		$board_data['total']['default']++;
	else
		$board_data['total']['default']--;
	
	// 카테고리
	if ($data['category']) {
		foreach (getStr($data['category']) as $key=>$val):
			def($board_data['total']['category'][$val], 0);
			if ($mode == 'add')
				$board_data['total']['category'][$val]++;
			else
				$board_data['total']['category'][$val]--;
		endforeach;
	}		
} // END function


/** 게시물 등록 LOCK
 * @class write
 */
function lock() {
	global $mini;
	iss($mini['lock']);

	while(!$mini['lock']){ $mini['lock'] = fopen("{$mini['dir']}lock.txt","r"); }
	flock($mini['lock'], LOCK_SH);
} // END function


/** 게시물 등록 UNLOCK
 * @class write
 */
function unlock() {
	global $mini;

	if ($mini['lock']) {
		flock($mini['lock'], LOCK_UN);
		fclose($mini['lock']);
		unset($mini['lock']);
	}
} // END function


/** 트랙백을 보낸다
 * @class io
 * @param
		$tb: 트랙백 주소(콤마구분)
		$tb_ment: 트랙백 내용(옵션)
		$tb_charset: 트랙백 문자인코딩(옵션)
		$data: 게시물정보
		$board_data: 게시판정보
   @return String Error Message
 */
function sendTrackback($tb, $tb_ment, $tb_charset = '', $data, $board_data, $where = 'post') {
	global $mini;
	
	$result = $msg = '';
	$pingback = array();

	if (!empty($tb)) {
		// 데이터 가공
		$ins = array();
		if (!empty($tb_ment))
			$ins['excerpt'] = strip_tags($tb_ment);
		else if (!empty($data['ment']))
			$ins['excerpt'] = strip_tags($data['ment']);
		else
			$ins['excerpt'] = "linked this entry.";

		if (!empty($data['title']))
			$ins['title'] = $data['title'];
		else
			$ins['title'] = "{$data['name']}님께서 댓글에 댓글을 남기셨습니다.";

		// 글 가공
		$ins['url'] = $where == 'post' ? "{$mini['pdir']}mini.php?id={$board_data['id']}&no={$data['no']}" : "{$mini['pdir']}mini.php?id={$board_data['id']}&no={$data['target_post']}&cNo={$data['no']}";
		if (!empty($board_data['name']))
			$ins['blog_name'] = $board_data['name'];

		// 비밀번호 삽입(미니보드)
		if (!empty($mini['set']['trackback_pass'])) {
			$ins['pass'] = md5(md5("{$mini['set']['trackback_pass']}|{$ins['url']}"));
		}

		// EUC-KR자동 설정
		if (preg_match("/(naver\.com|empas\.com)/i", $tb)) {
			$tb_charset = 'EUC-KR';
		}

		// 자료 가공
		$input = '';
		foreach ($ins as $key=>$val):
			if ($tb_charset) {
				$val = iconv($mini['set']['lang'], "{$tb_charset}//IGNORE", $val);
			}
			
			$input .= "&{$key}=".rawurlencode($val);
		endforeach;

		if ($input) {
			$input = substr($input, 1);
			$input_size = strlen($input);
		}

		def($tb_charset, $mini['set']['lang']);

		// 루프
		if ($input && $input_size)
		foreach (explode(",", $tb) as $key=>$val):
			$val = trim($val);
			if (check($val, 'type:homepage')) {

				$result = getSocket(array(
					'url' => $val,
					'header' => 
						"Content-type: application/x-www-form-urlencoded; charset={$tb_charset}\r\n".
						"Content-length: {$input_size}\r\n",
					'data' => $input
				));

				if (strpos($result, '<error>0</error>') === false) {
					$mat = array();
					preg_match("/\<message\>(.+)\<\/message\>/isU", $result, $mat);
					if (!empty($mat[1])) {
						$mat[1] = str_replace(array("<![CDATA[", "]]>"), "", $mat[1]);
						$msg .= $mat[1]."\n";
					}
					else {
						$msg .= "{$result}\n";
//						$msg .= "에러메세지 없음\n";
					}
				}
				else {
					$pingback[] = $val;
				}
			}
		endforeach;
	}
	else
		$msg = '트랙백 주소가 없습니다.';

	// DB에 pingback 주소 기록
	if (empty($msg) && count($pingback) > 0 && $where == 'post') {
		sql("UPDATE {$board_data['table']} SET pingback=CONCAT(IF(ISNULL(pingback), '', pingback), '[".implode("][", $pingback)."]') WHERE no={$data['no']}");
	}

	return $msg;
} // END function


/** 외부이미지 저장
 * @class write
 * @param
		$target: 대상게시물번호
		$data: String 자료
 * @return String 치환된 자료 
 */
function saveImage($target, $data, $board_data = '', $where = 'post') {
	global $mini;

	$mat = $mat2 = array();
	str($data, 'decode');
	if (empty($board_data)) $board_data = &$mini['board'];

	preg_match_all("/\<img.+src\=('|\")(.+)('|\")/isU", $data, $mat);
	preg_match_all("/\<img.+src\=([^'\"]+)/isU", $data, $mat2);

	if (!empty($mat2[1])) {
		if (!empty($mat[2]))
			foreach ($mat2[1] as $val):
				$mat[2][] = $val;
			endforeach;
		else
			$mat[2] = $mat2[1];
	}


	$saved = $ins_file = array();

	if (empty($board_data['dir']) || !is_dir("{$mini['dir']}file/{$board_data['dir']}")) {
		// 디렉토리 생성
		$check = 0;
		while (!$check):
			$tmp_name = $board_data['id']."_".substr(md5(microtime()), 0, 10);
			if (!is_dir("{$mini['dir']}file/{$tmp_name}"))
				$check = 1;
		endwhile;

		if ($tmp_name && mkdir("{$mini['dir']}file/{$tmp_name}", 0707)) {
			chmod("{$mini['dir']}file/{$tmp_name}", 0707);
			sql("UPDATE {$mini['name']['admin']} SET dir='{$tmp_name}' WHERE no={$board_data['no']}");
			$board_data['dir'] = $tmp_name;
		}
		else {
			__error("[{$mini['dir']}file/{$tmp_name}] 디렉토리를 생성할 수 없습니다");
		}

		if (!is_writeable("{$mini['dir']}file/{$board_data['dir']}/")) {
			__error("디렉토리에 쓰기 권한이 없습니다. 퍼미션을 변경해 주세요");
		}
	}

	if (!empty($mat) && !empty($mat[2])) {
		foreach ($mat[2] as $key=>$val):
			if (strpos($val, "://") !== false && !in_array($val, $saved)) {
				if (!preg_match("/\:\/\/".$_SERVER['HTTP_HOST']."/i", $val)) {
					$input = getSocket("
						url: {$val}
						method: get
						skip_header: 1
					");

					if (!empty($input)) {
						$auth = md5(microtime());
						$tmp_name = "sfile/saveImage.{$auth}.gif";
						$fp = fopen($tmp_name, "w+");
						fwrite($fp, $input);
						fclose($fp);

						if (empty($_FILES['saveImage'])) $_FILES['saveImage'] = array();
						$_FILES['saveImage']['size'] = filesize($tmp_name);
						$_FILES['saveImage']['tmp_name'] = $tmp_name;
						$_FILES['saveImage']['name'] = basename($val);

						chkFile("
							dir: {$mini['dir']}file/{$board_data['dir']}/
							is_download: 1
							target: saveImage
						");

						// 이미지일때만 업로드
						if (!empty($_FILES['saveImage']['width'])) {
							$data2 = a(uploadFile("
								target: saveImage
								is_copy: 1
							"), 0);

							// 기본정보
							$ins = array();
							$ins['id'] = $board_data['no'];
							$ins['target_member'] = (!empty($mini['member']['no'])) ? $mini['member']['no'] : 0;
							$ins['name'] = $data2['name'];
							$ins['url'] = $data2['path'];
							$ins['size'] = $data2['size'];
							$ins['is_admit'] = (!empty($board_data['use_file_admit']) && empty($mini['member']['level_admin'])) ? 0 : 1;
							$ins['ip'] = $mini['ip'];
							$ins['date'] = $mini['date'];
							$ins['width'] = (!empty($data2['width'])) ? $data2['width'] : 0;
							$ins['height'] = (!empty($data2['height'])) ? $data2['height'] : 0;
							$ins['ext'] = $data2['ext'];
							$ins['target'] = $target;
							$ins['type'] = $data2['type'];

							// 파일해시
							$ins['hash'] = getHash($data2);

							sql("INSERT INTO {$mini['name']['file']} ".query($ins, 'insert'));
							$ins['no'] = getLastId($mini['name']['file'], "(ip='{$mini['ip']}' and date='{$mini['date']}' and name='{$ins['name']}')");

							$ins_file[] = $ins['no'];

							// 치환
							$data = str_replace($val, "download.php?no={$ins['no']}&amp;mode=view", $data);
							$saved[] = $val;
						}

						@unlink($_FILES['saveImage']['tmp_name']);
					}
				}
			}
		endforeach;

		if (!empty($ins_file)) {
			$tmp = "[".implode("][", $ins_file)."]";
			sql("UPDATE {$board_data['table']} SET file=CONCAT(file, '{$tmp}') WHERE no={$target}");
		}
	}

	str($data, 'encode');
	return $data;
} // END function


/** 쪽지 쓰기
 * @class memo
 * @param
		$data: 자료
		$from_no: 보내는사람번호
		$data_target: 받는회원자료
		$data_from: 보내는회원자료
		$ret: return 모드
  */
function sendMemo($data, $from_no = '', $data_target = '', $data_from = '', $ret = 0) {
	global $mini;
	unset($data['from_member']);

	if (!is_array($data)) {
		$data = param($data);
	}

	if (empty($data['target_member'])) __error('받는사람이 없습니다');
	if (empty($data['ment'])) __error('내용이 없습니다');

	// 회원정보
		if (empty($data_target)) {
			check($data['target_member'], 'type:num, name:받는사람번호');
			$data_target = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$data['target_member']}");
			if (!is_array($data_target)) __error('존재하지 않는 회원 입니다');
			$data_target = parseMember($data_target, 1);
		}

		if (empty($data_from)) {
			if (!empty($from_no)) {
				check($from_no, 'type:num, name:보내는사람번호');
				$data_from = sql("SELECT * FROM {$mini['name']['member']} WHERE no={$from_no}");
				if (!is_array($data_from)) __error('존재하지 않는 회원 입니다');
				$data_from = parseMember($data_from, 1);
			}

			else if (!empty($mini['log'])) {
				$data_from = $mini['member'];
			}

			else __error('보내는사람 정보가 없습니다');
		}

		$data['target_member'] = $data_target['no'];
		$data['from_member'] = $data_from['no'];
		$data['name_target'] = $data_target['name'];
		$data['name_from'] = $data_from['name'];

	// 친구메세지만 허용 확인
		if (!empty($data_target['ini']['memo']) && !empty($data_target['ini']['memo']['use_friend']) && empty($mini['member']['level_admin'])) {
			if (empty($data_target['ini']['friend']) || (!empty($data_target['ini']['friend']) && !in_array($from_no, $data_target['ini']['friend']))) {
				__error('메세지를 보낼 수 없습니다. 상대방이 친구에게만 메세지를 받도록 설정했습니다.');
			}
		}

	// 차단 확인
		if (!empty($data_target['ini']['memo']['block']) && inStr($from_no, $data_target['ini']['memo']['block'])) {
			$data['is_block'] = 1;
		}

	// 내용 필터
		if (empty($data['skip_filter'])) filter($data['ment'], 'encode');

	// 기본정보 입력
		$data['date'] = $mini['date'];
		$data['ip'] = $mini['ip'];

	// 없는 것 빼기
		$col = getColumns($mini['name']['memo']);
		foreach ($data as $key=>$val):
			if (!inStr($key, $col)) {
				unset($data[$key]);
			}
		endforeach;

	// 쿼리
		sql("INSERT INTO {$mini['name']['memo']} ".query($data, 'insert'));
	
	// 리턴
		if ($ret) return $data;
} // END function


/** 파일 Type 구하기
 * @class file
 * @param
		$ext: 확장자
 * @return String
 */
function getFileType($ext) {
	switch ($ext):
		case 'jpg': case 'jpeg': case 'jpe': case 'gif': case 'png': case 'tif': case 'tiff': case 'bmp': case 'wbmp':
			return 'image';
			break;

		case 'mp3': case 'wav': case 'wma': case 'ogg': case 'rm':
			return  'music';
			break;

		case 'swf':
			return 'swf';
			break;

		case 'flv':
			return 'flv';
			break;

		case 'avi': case 'mpg': case 'mpv': case 'rm': case 'ram': case 'qt': case 'mov': case 'asf': case 'asx': case 'wmv':
			return 'movie';
			break;

		default:
			return '';
	endswitch;
} // END function


/** 게시판 file 폴더 생성하기
 * @class file
 * @param
		$data: board data
 * @return String Full Dir
 */
function makeDir(&$data) {
	global $mini;

	if (empty($data['dir']) || !is_dir("{$mini['dir']}file/{$data['dir']}")) {
		// 디렉토리 생성
		$check = 0;
		while (!$check):
			$tmp_name = $data['id']."_".substr(md5(microtime()), 0, 10);
			if (!is_dir("{$mini['dir']}file/{$tmp_name}"))
				$check = 1;
		endwhile;

		if ($tmp_name && mkdir("{$mini['dir']}file/{$tmp_name}", 0707)) {
			sql("UPDATE {$mini['name']['admin']} SET dir='{$tmp_name}' WHERE no={$data['no']}");
			$data['dir'] = $tmp_name;
		}
		else {
			__error("[{$mini['dir']}file/{$tmp_name}] 디렉토리를 생성할 수 없습니다");
		}
	}

	$dir = "{$mini['dir']}file/{$data['dir']}/";
	if (!is_writeable($dir)) {
		__error("디렉토리에 쓰기 권한이 없습니다. 퍼미션을 변경해 주세요");
	}

	return $dir;
} // END function


/** 이미지 크기 변환
 * @class file
 * @param
		$data: addFile data
		-dir: 복사본으로 생성할 경우 생성될 경로
		-width: 이미지 너비
		-height: 이미지 높이
		-type: 썸네일 타입 (1일 경우 크기에 맞게 사진을 늘림)
		-skip_update: 쿼리 업데이트를 생략
 */
function resizeImage(&$data, $param = '') {
	global $mini;
	$param = param($param);
	def($param['type'], 0);
	iss($param['width']);
	iss($param['height']);
	iss($param['dir']);

	// 따로 저장할 경우(i.e 썸네일) 디렉토리 생성
	if (!empty($param['dir'])) {
		$is_thumb = 1;
		if (!is_dir($param['dir'])) {
			if (!mkdir($param['dir'], 0707)) __error("[resizeImage: {$param['dir']}] 디렉토리를 생성할 수 없습니다");
		}
	}

	// 경로가 없으면 uploadFile 경로로 지정
	else {
		$param['dir'] = dirname("{$mini['dir']}{$data['url']}");
		if ($param['dir']) $param['dir'] .= "/";
	}

	//+debug
//	__error(preg_replace("/(\s|\n|\t)/", "", htmlspecialchars($image)));
//	__error("{$mini['pdir']}addon/phpthumb/phpThumb.php?no={$data['no']}&PHPSESSID=".session_id()."&w={$param['width']}&h={$param['height']}&iar={$param['type']}");

	// 이미지 저장하기
	$file_url = "{$param['dir']}".basename($data['url']);
	$fp = fopen($file_url, "w+");

	if ($fp) {
		$image = getSocket("
			method: get
			url: {$mini['pdir']}addon/phpthumb/phpThumb.php?no={$data['no']}&PHPSESSID=".session_id()."&w={$param['width']}&h={$param['height']}&iar={$param['type']}
			skip_header: 1
		");

		fwrite($fp, $image, strlen($image));
		fclose($fp);
		@chmod($file_url, 0777);

		$ins = array();

		// 이미지 정보 다시 입력
		if (empty($is_thumb)) {
			$img = getimagesize($file_url);
			$ins['size'] = strlen($image);
			$ins['width'] = $img[0];
			$ins['height'] = $img[1];

			$tmp_data = $ins;
			$tmp_data['path'] = $file_url;
			$ins['hash'] = getHash($tmp_data);
		}
		else {
			$ins['is_thumb'] = !empty($is_thumb);
		}

		if (!empty($param['skip_update'])) {
			return $ins;
		}
		else {
			sql("UPDATE {$mini['name']['file']} SET ".query($ins, 'update')." WHERE no={$data['no']}");
			return true;
		}
	}

	else return false;
} // END function


/** 파일 체크
 * @class file
 * @param
		-dir: 업로드할 디렉토리
		-target: $_FILES 중에서 하나만 지정한 key값
		-size: 최대용량 제한
		-width: 이미지 너비 제한
		-height: 이미지 높이 제한
		-filename: 파일이름 강제로 지정하기
		-is_overwrite: 같은 파일명이 있으면 덮어 씌움
		-is_download: 다운로드만 받을 수 있도록 파일명을 조절
		-is_exists: 같은 파일명이 있으면 중지
		-is_image: 이미지 파일만 허용
		-is_none: 파일 없음을 허용
 */
function chkFile($param = '') {
	global $mini, $_FILES;
	iss($param);
	$param = param($param);
	iss($param['dir']);
	iss($param['target']);
	iss($param['size']);
	iss($param['width']);
	iss($param['height']);
	iss($param['filename']);
	iss($param['is_overwrite']);
	iss($param['is_download']);
	iss($param['is_exists']);
	iss($param['is_image']);
	iss($param['is_none']);
	
	if (!is_array($_FILES) && !$param['is_none']) __error("업로드된 파일이 없습니다");
	if (count($_FILES) < 1) __error("업로드된 파일이 없거나, 용량을 초과 했습니다.");
	if (!$param['dir']) __error("업로드 디렉토리가 지정되지 않았습니다");
	if (!empty($param['dir']) && !preg_match("/(\/|\\\)$/", $param['dir'])) {
		$param['dir'] .= "/";
	}

	// 업로드 디렉토리 생성
	if (!is_dir($param['dir'])) {
		if (!@mkdir($param['dir'], 0707)) __error("[{$param['dir']}] 업로드 디렉토리를 생성할 수 없습니다.");
	}

	$limitsize = getByte(get_cfg_var("upload_max_filesize"), 'decode');
	$param['size'] = ($param['size'] && $limitsize > $param['size']) ? $param['size'] : $limitsize;

	foreach ($_FILES as $key=>$val):
		iss($val['is_image']);

		//// 타겟 체크
		if ($param['target'] && $param['target'] != $key) continue;

		//// 파일 없음 체크
		if (!$val['name']) {
			if ($param['is_none'])
				continue;
			else
				__error("[{$key}] 파일명이 없습니다");
		}
		else {
			$filename=$filename_org=$val['name'];
			$tmp_filename = str($val['name'], 'encode', 1);
		}

		//// 기본 error 체크
		if (!empty($val['error'])) {
			switch ($val['error']):
				// 스킵
				case 'UPLOAD_ERR_OK':
				case 0:
				case 'UPLOAD_ERR_NO_FILE':
				case 4:
					break;

				case 'UPLOAD_ERR_INI_SIZE':
				case 1:
					__error("[{$tmp_filename}] 파일 용량이 ".ini_get("upload_max_filesize")."를 초과할 수 없습니다.\\n(php.ini, upload_max_filesize 설정에서 변경가능)");

				case 'UPLOAD_ERR_FORM_SIZE':
				case 2:
					__error("[{$tmp_filename}] 파일 용량이 초과 되었습니다.\\n(해당 Form 의 MAX_FILE_SIZE 설정에서 변경 가능합니다)");

				case 'UPLOAD_ERR_PARTIAL':
				case 3:
					__error("[{$tmp_filename}] 업로드된 파일이 올바르지 않습니다");

				case 'UPLOAD_ERR_NO_TMP_DIR':
				case 6:
					__error("임시 폴더가 없어 파일을 업로드 할 수 없습니다. 서버 관리자에게 문의해 주세요.\\n(설정된 임시 폴더는 ".ini_get("upload_tmp_dir")." 입니다)");

				case 'UPLOAD_ERR_CANT_WRITE':
				case 7:
					__error("디스크에 쓰기를 실패 했습니다. 서버 관리자에게 문의해 주세요");
			endswitch;
		}

		//// 파일명 체크
		if ($param['filename']) $filename = $param['filename'];
		$filename = str_replace(array("|", "'", "\"", "\\"), "", $filename);
		$filename = trim($filename);

		//// 확장자 체크
		$val['ext'] = strtolower(substr(strrchr($filename, "."), 1));

		//// 이미지 관련 체크
		$is_image = getimagesize($val['tmp_name']);
		
		if (!empty($is_image) && is_array($is_image)) {
			$val['is_image'] = 1;
			$val['width'] = $is_image[0];
			$val['height'] = $is_image[1];
		}
		else
			$val['is_image'] = 0;

		//// 파일타입
		$val['type'] = getFileType($val['ext']);
		if ($val['type'] == 'image' && empty($val['is_image'])) __error("[{$tmp_filename}] 이미지 파일이 아닙니다");
		if (!$val['is_image'] && $param['is_image']) __error("[{$tmp_filename}] 이미지 파일만 허용합니다");
		if ($val['is_image']) {
			if($param['width'] && $param['width'] < $val['width']) __error("[{$tmp_filename}] 이미지 너비가 {$param['width']}px 를 초과 했습니다. (현재 {$val['width']}px)");
			if($param['height'] && $param['height'] < $val['height']) __error("[{$tmp_filename}] 이미지 높이가 {$param['height']}px 를 초과 했습니다. (현재 {$val['height']}px)");
		}

		//// 한글제거
		if (strCut($filename, 0, 'multi')) {
			$filename = urlencode($filename);
		}

		//// 기호제거
		$filename = preg_replace("/[^0-9a-z_\-\.]/i", "", $filename);
		
		//// 파일명 변경
		if ($param['is_download']){
			$filename = str_replace(".","_",$filename);
			$filename .= ".mb";
		}

		//// 중복 체크
		if(file_exists($param['dir'].$filename) && !$param['is_overwrite']){
			if($param['is_exists']) __error("[{$tmp_filename}] 중복된 파일명입니다.");
			$a=0; $tmp2_filename = $filename;
			
			while(file_exists($param['dir'].$tmp2_filename)):
				$a++;
				if(strpos($filename, ".") !== false) 
					$tmp2_filename = preg_replace("/(\.?[^\.]+)$/is", $a."\\1", $filename);
				else
					$tmp2_filename = $filename.$a;
			endwhile;

			$filename = $tmp2_filename;
		}

		//// 내용 중복
		if (!empty($val['size']) && !empty($mini['board']['use_file_unique']) && !empty($mini['board']['no'])) {
			if (sql("SELECT COUNT(*) FROM {$mini['name']['file']} WHERE id={$mini['board']['no']} and hash='".str(getHash($val), 'encode', 1)."'")) {
				__error("[{$tmp_filename}] 이미 같은 파일이 있습니다");
			}
		}
		
		//// 용량 체크
		if ($param['size'] && $param['size'] < $val['size']) __error("[{$tmp_filename}] 파일 용량이 " .number_format($param['size']). " bytes를 초과할 수 없습니다\\n(현재 {$val['size']}bytes)");
		if($val['size'] <= 0) __error("[{$tmp_filename}] 파일 용량은 0 byte 이상이어야 합니다");

		//// 변수 입력
		$val['path'] = $param['dir'].$filename;
		$val['path_only'] = $param['dir'];
		$val['name_insert'] = $filename;
		$_FILES[$key] = $val;
	endforeach;	
} // END function


/** 파일 업로드
 * @class file
 * @param
		-target: $_FILES 중에서 하나만 지정한 key값
		-filename: 파일명 강제지정
		-is_copy: copy를 사용한다
 * @return Array 성공한 _FILES 배열
 */
function uploadFile($param = '') {
	global $mini, $_FILES;
	iss($param);
	$param = param($param);
	iss($param['target']);
	iss($success);
	def($param['is_copy'], 0);

	foreach ($_FILES as $key=>$val):
		//// 타겟 체크
		if ($param['target'] && $param['target'] != $key) continue;

		//// 파일명 강제지정
		if (!empty($param['filename']) && !empty($val['path'])) {
			$val['path'] = dirname($val['path']).'/'.$param['filename'];
			$val['name_insert'] = $param['filename'];
		}

		//// 파일 업로드
		if (!empty($val['path'])) {
			if ($param['is_copy'])
				$result = copy($val['tmp_name'], $val['path']);
			else
				$result = move_uploaded_file($val['tmp_name'], $val['path']);
			
			if($result) $success[] = $val;
			else {
				foreach($success as $val2) @unlink($val2['path']);
				__error("[{$val['tmp_name']}] 파일 업로드에 실패했습니다");
			}
		}
	endforeach;

	return $success;
} // END function


/** 파일정보를 DB에 추가한다
 * @class write 
 * @param
		$data: chkFile 후에 넘어온 데이터
		-id: 게시판번호. 없으면 mini[board]의 정보를 활용
		-target_member: 회원번호. 없으면 mini[member]의 정보를 활용
		-target: 대상자료번호
		-target_pos: 대상게시물번호(댓글일때만)
		-mode: post|comment|memo|box
 * @return Array
 */
function addFile($data, $param = '') {
	global $mini;
	$param = param($param);
	$ins = array();
	
	if (!empty($param['id'])) def($ins['id'], $param['id']);
	if (!empty($data['id'])) def($ins['id'], $data['id']);
	if (!empty($mini['board']['no'])) def($ins['id'], $mini['board']['no']);

	if (!empty($param['target_member'])) def($ins['target_member'], $param['target_member']);
	if (!empty($data['target_member']) && !empty($mini['member']['level_admin'])) def($ins['target_member'], $data['target_member']);
	if (!empty($mini['member']['no'])) def($ins['target_member'], $mini['member']['no']);

	if (!empty($data['ip']) && !empty($mini['member']['level_admin'])) def($ins['ip'], $data['ip']);
	def($ins['ip'], $mini['ip']);

	if (!empty($data['date']) && !empty($mini['member']['level_admin'])) def($ins['date'], $data['date']);
	def($ins['date'], $mini['date']);

	if (!empty($param['mode'])) def($ins['mode'], $param['mode']);
	if (!empty($data['mode'])) def($ins['mode'], $data['mode']);
	def($ins['mode'], '');

	if (!empty($param['target'])) def($ins['target'], $param['target']);
	if (!empty($data['target'])) def($ins['target'], $data['target']);
	def($ins['target'], 0);

	if (!empty($param['target_post']) && $ins['mode'] == 'comment') $ins['target_post'] = $param['target_post'];

	$ins['name'] = $data['name'];
	$ins['url'] = $data['path'];
	$ins['size'] = $data['size'];
	$ins['is_admit'] = (!empty($mini['board']['use_file_admit']) && empty($mini['member']['level_admin'])) ? 0 : 1;
	$ins['width'] = (!empty($data['width'])) ? $data['width'] : 0;
	$ins['height'] = (!empty($data['height'])) ? $data['height'] : 0;
	$ins['ext'] = $data['ext'];
	$ins['type'] = $data['type'];

	// 파일해시
	$ins['hash'] = getHash($data);

	sql("INSERT INTO {$mini['name']['file']} ".query($ins, 'insert'));

	// 후처리
	$ins['no'] = getLastId($mini['name']['file'], "(ip='{$ins['ip']}' and date='{$ins['date']}' and name='{$ins['name']}')");
	$ins['error'] = 0;

	return $ins;
} // END function

?>